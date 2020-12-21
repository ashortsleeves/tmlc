<?php

namespace WP_Defender\Model;

use WP_Defender\Behavior\Scan_Item\Core_Integrity;
use WP_Defender\Behavior\Scan_Item\Malware_Result;
use WP_Defender\Behavior\Scan_Item\Malware_Scan;
use WP_Defender\Behavior\Scan_Item\Vuln_Result;
use WP_Defender\Component\Error_Code;
use WP_Defender\DB;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\IO;

class Scan extends DB {
	use IO, Formats;

	const STATUS_INIT = 'init', STATUS_ERROR = 'error', STATUS_FINISH = 'finish';
	const IGNORE_INDEXER = 'defender_scan_ignore_index';
	protected $table = 'defender_scan';

	/**
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * Scan status, the native status is init, error, and finish, we can have other status base on the
	 * task the scan is running, like gather_fact, core_integrity etc
	 *
	 * @var string
	 * @defender_property
	 */
	public $status;
	/**
	 * Mysql time
	 * @var string
	 * @defender_property
	 */
	public $date_start;

	/**
	 * Store the current percent
	 * @var int
	 * @defender_property
	 */
	public $percent = 0;

	/**
	 * Store how many tasks we process
	 * @var int
	 * @defender_property
	 */
	public $total_tasks = 0;

	/**
	 * We will use this so internal task can store the current checkpoint
	 *
	 * @var string
	 * @defender_property
	 */
	public $task_checkpoint = '';

	/**
	 * mysql time
	 * @var string
	 * @defender_property
	 */
	public $date_end;

	/**
	 * This only true when a scan trigger by report schedule
	 * @var bool
	 * @defender_property
	 */
	public $is_automation = false;

	/**
	 * Return an array with various params, mostly this will be use
	 *
	 * @return array
	 */
	public function prepare_issues() {
		$orm           = self::get_orm();
		$models        = $this->get_issues();
		$arr           = [];
		$ignored       = [];
		$count_core    = 0;
		$count_malware = 0;
		$count_vuln    = 0;
		foreach ( $models as $model ) {
			if ( $model->status === Scan_Item::STATUS_IGNORE ) {
				$ignored[] = $model->to_array();
			} elseif ( $model->status === Scan_Item::STATUS_ACTIVE ) {
				$arr[] = $model->to_array();
				switch ( $model->type ) {
					case Scan_Item::TYPE_INTEGRITY:
						$count_core ++;
						break;
					case Scan_Item::TYPE_SUSPICIOUS:
						$count_malware ++;
						break;
					case Scan_Item::TYPE_VULNERABILITY:
						$count_vuln ++;
						break;
				}
			}
		}

		return [
			'ignored'       => $ignored,
			'issues'        => $arr,
			'count_core'    => $count_core,
			'count_malware' => $count_malware,
			'count_vuln'    => $count_vuln
		];
	}

	/**
	 * @param null $type
	 * @param null $status
	 *
	 * @return Scan_Item[]
	 */
	public function get_issues( $type = null, $status = null ) {
		$orm     = self::get_orm();
		$builder = $orm->get_repository( Scan_Item::class )
		               ->where( 'parent_id', $this->id );

		if ( $type !== null && in_array( $type, [
				Scan_Item::TYPE_VULNERABILITY,
				Scan_Item::TYPE_INTEGRITY,
				Scan_Item::TYPE_SUSPICIOUS
			] ) ) {
			$builder->where( 'type', $type );
		}
		if ( $status !== null && in_array( $status, [ Scan_Item::STATUS_IGNORE, Scan_Item::STATUS_ACTIVE ] ) ) {
			$builder->where( 'status', $status );
		}
		$models = $builder->get();
		foreach ( $models as $key => $model ) {
			switch ( $model->type ) {
				case Scan_Item::TYPE_INTEGRITY:
					$model->attach_behavior( Core_Integrity::class, Core_Integrity::class );
					break;
				case Scan_Item::TYPE_SUSPICIOUS:
					$model->attach_behavior( Malware_Result::class, Malware_Result::class );
					break;
				case Scan_Item::TYPE_VULNERABILITY:
					$model->attach_behavior( Vuln_Result::class, Vuln_Result::class );
					break;
			}
			$models[ $key ] = $model;
		}

		return $models;
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function unignore_issue( $id ) {
		$issue = $this->get_issue( $id );
		if ( ! is_object( $issue ) ) {
			return false;
		}
		$issue->status = Scan_Item::STATUS_ACTIVE;
		$issue->save();

		$ignore_lists = get_site_option( self::IGNORE_INDEXER, [] );
		$data         = $issue->raw_data;
		if ( isset( $data['file'] ) ) {
			unset( $ignore_lists[ array_search( $data['file'], $ignore_lists ) ] );
		} elseif ( isset( $data['slug'] ) ) {
			unset( $ignore_lists[ array_search( $data['slug'], $ignore_lists ) ] );
		}

		$ignore_lists = array_unique( $ignore_lists );
		$ignore_lists = array_filter( $ignore_lists );
		update_site_option( self::IGNORE_INDEXER, $ignore_lists );
	}

	/**
	 * Check if a slug is ignored, we use a global indexer, so we can check while
	 * the active scan is running
	 *
	 * @param $slug
	 *
	 * @return bool
	 */
	public function is_issue_ignored( $slug ) {
		$ignore_lists = get_site_option( self::IGNORE_INDEXER, [] );

		return in_array( $slug, $ignore_lists, true );
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function ignore_issue( $id ) {
		$issue = $this->get_issue( $id );
		if ( ! is_object( $issue ) ) {
			return false;
		}

		$issue->status = Scan_Item::STATUS_IGNORE;
		$issue->save();

		//add this into global ingnore index
		$ignore_lists = get_site_option( self::IGNORE_INDEXER, [] );
		$data         = $issue->raw_data;
		if ( isset( $data['file'] ) ) {
			$ignore_lists[] = $data['file'];
		} elseif ( isset( $data['slug'] ) ) {
			$ignore_lists[] = $data['slug'];
		}
		$ignore_lists = array_unique( $ignore_lists );
		$ignore_lists = array_filter( $ignore_lists );
		update_site_option( self::IGNORE_INDEXER, $ignore_lists );
	}

	/**
	 * @param $id
	 *
	 * @return Scan_Item|null
	 */
	public function get_issue( $id ) {
		$orm   = self::get_orm();
		$model = $orm->get_repository( Scan_Item::class )
		             ->where( 'id', $id )->first();
		if ( is_object( $model ) ) {
			switch ( $model->type ) {
				case Scan_Item::TYPE_INTEGRITY:
					$model->attach_behavior( Core_Integrity::class, Core_Integrity::class );
					break;
				case Scan_Item::TYPE_SUSPICIOUS:
					$model->attach_behavior( Malware_Result::class, Malware_Result::class );
					break;
				case Scan_Item::TYPE_VULNERABILITY:
					$model->attach_behavior( Vuln_Result::class, Vuln_Result::class );
					break;
			}
		}

		return $model;
	}

	/**
	 * Remove an issue, this will happen when that issue is resolve, or the file link
	 * to this issue get deleted
	 *
	 * @param $id
	 */
	public function remove_issue( $id ) {
		$orm = self::get_orm();
		$orm->get_repository( Scan_Item::class )->delete( [ 'id' => $id ] );
	}

	/**
	 * This will build the data we use to output to frontend, base on the current scenario
	 * @return array
	 */
	public function to_array() {
		if ( ! in_array( $this->status, [ self::STATUS_ERROR, self::STATUS_FINISH ] ) ) {
			//case process
			return [
				'status'      => $this->status,
				'status_text' => $this->get_status_text(),
				'percent'     => $this->percent,
				//this only for hub, when a scan running
				'count'       => [
					'total' => 0
				]
			];
		} elseif ( self::STATUS_FINISH === $this->status ) {
			$data = $this->prepare_issues();

			return [
				'status'        => $this->status,
				'issues_items'  => $data['issues'],
				'ignored_items' => $data['ignored'],
				'last_scan'     => $this->format_date_time( $this->date_start ),
				'count'         => [
					'total'   => count( $data['issues'] ),
					'core'    => $data['count_core'],
					'content' => $data['count_malware'],
					'vuln'    => $data['count_vuln']
				]
			];
		}
	}


	/**
	 * @param false $from_report
	 *
	 * @return Scan|\WP_Error
	 */
	public static function create( $from_report = false ) {
		$orm    = self::get_orm();
		$active = self::get_active();
		if ( is_object( $active ) ) {
			return new \WP_Error( Error_Code::INVALID, __( "A scan is already in progress", 'wpdef' ) );
		}
		$model                = new Scan();
		$model->status        = self::STATUS_INIT;
		$model->date_start    = gmdate( 'Y-m-d H:i:s' );
		$model->date_end      = gmdate( 'Y-m-d H:i:s' );
		$model->is_automation = $from_report;

		$orm->save( $model );

		return $model;
	}

	/**
	 * Delete current scan
	 */
	public function delete() {
		//delete all the relate result items
		$orm = self::get_orm();
		$orm->get_repository( Scan_Item::class )->delete( [
			'parent_id' => $this->id
		] );
		$orm->get_repository( self::class )->delete( [
			'id' => $this->id
		] );
	}

	/**
	 * Get the current active scan if any
	 *
	 * @return self|null
	 */
	public static function get_active() {
		$orm   = self::get_orm();
		$model = $orm->get_repository( self::class )->where( 'status', 'NOT IN', [
			self::STATUS_FINISH,
			self::STATUS_ERROR
		] )->first();

		return $model;
	}

	/**
	 * Get last result
	 *
	 * @return self|null
	 */
	public static function get_last() {
		$orm   = self::get_orm();
		$model = $orm->get_repository( self::class )->where( 'status', self::STATUS_FINISH )
		             ->order_by( 'id', 'desc' )->first();

		return $model;
	}

	/**
	 * @return array
	 */
	public static function get_last_all() {
		$orm    = self::get_orm();
		$models = $orm->get_repository( self::class )->where( 'status', self::STATUS_FINISH )
		              ->order_by( 'id', 'desc' )->get();

		return $models;
	}

	/**
	 * If the scan find any, we will use this to add the issue
	 *
	 * @param $type
	 * @param $data
	 * @param $status
	 */
	public function add_item( $type, $data, $status = Scan_Item::STATUS_ACTIVE ) {
		$model            = new Scan_Item();
		$model->type      = $type;
		$model->parent_id = $this->id;
		$model->raw_data  = $data;
		$model->status    = $status;
		$ret              = $model->save();

		return $ret;
	}

	/**
	 * Return current status as readable string
	 *
	 * @return string
	 */
	public function get_status_text() {
		switch ( $this->status ) {
			case self::STATUS_INIT:
				return __( 'Initializing...', 'wpdef' );
			case 'gather_fact':
				return __( 'Gathering information...', 'wpdef' );
			case 'core_integrity_check':
				return __( 'Analyzing WordPress Core...', 'wpdef' );
			case 'vuln_check':
				return __( 'Checking for any published vulnerabilities in your plugins & themes...', 'wpdef' );
			case 'suspicious_check':
				return __( 'Analyzing WordPress Content...', 'wpdef' );
			default:
				return __( 'The scan is running', 'wpdef' );
		}
	}

	/**
	 * Calculation scan percentage base on the tasks percent.
	 *
	 * @param $task_percent
	 * @param $pos
	 *
	 * @return float
	 */
	public function calculate_percent( $task_percent, $pos = 1 ) {
		$task_max      = 100 / $this->total_tasks;
		$task_base     = $task_max * ( $pos - 1 );
		$micro         = $task_percent * $task_max / 100;
		$this->percent = round( $task_base + $micro, 2 );
		if ( $this->percent > 100 ) {
			$this->percent = 100;
		}

		return $this->percent;
	}

	/**
	 * Get list of whitelisted files
	 *
	 * @return array
	 */
	private function whitelisted_files() {

		return array(
			// configuration files
			'user.ini',
			'php.ini',
			'robots.txt',
			'.htaccess',
			'nginx.conf',
			// hidden system files and directories
			'.well_known',
			'.idea',
			'.DS_Store',
			'.svn',
			'.git',
			'.quarantine',
			'.tmb',
		);
	}

	/**
	 * Check if a slug is whitelisted
	 *
	 * @param string $slug path to file
	 *
	 * @return bool
	 */
	public function is_issue_whitelisted( $slug ) {
		$whitelisted_files = $this->whitelisted_files();
		foreach ( $whitelisted_files as $file ) {
			if ( stristr( $slug, $file ) !== false ) {
				return true;
			}
		}

		return false;
	}
}