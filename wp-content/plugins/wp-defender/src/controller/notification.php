<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\HTTP;
use WP_Defender\Controller2;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\User;
use WP_Defender\Traits\WPMU;

class Notification extends Controller2 {
	use User, Formats, WPMU;

	public $slug = 'wdf-notification';

	/**
	 * @var \WP_Defender\Component\Notification
	 */
	protected $service;

	/**
	 * Advanced_Tools constructor.
	 */
	public function __construct() {
		$this->register_page(
			esc_html__( 'Notifications', 'wpdef' ),
			$this->slug,
			array(
				&$this,
				'main_view',
			),
			$this->parent_slug
		);
		$this->register_routes();
		$this->service = wd_di()->get( \WP_Defender\Component\Notification::class );
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		//we use custom ajax endpoint here as the nonce would fail with other user
		add_action( 'wp_ajax_defender_listen_user_subscribe', [ &$this, 'verify_subscriber' ] );
		add_action( 'wp_ajax_nopriv_defender_listen_user_subscribe', [ &$this, 'verify_subscriber' ] );
		add_action( 'defender_notify', [ &$this, 'send_notify' ], 10, 2 );
		if ( ! wp_next_scheduled( 'wdf_maybe_send_report' ) ) {
			$this->service->add_hooks();
			$timestamp = gmmktime( gmdate( 'H' ), 0, 0 );
			wp_schedule_event( $timestamp, 'thirty_minutes', 'wdf_maybe_send_report' );
		}
		add_action( 'wdf_maybe_send_report', array( &$this, 'report_sender' ) );
		add_action( 'admin_notices', [ &$this, 'show_subscribed_confirmation' ] );
	}

	/**
	 * @return null
	 */
	public function show_subscribed_confirmation() {
		if ( ! defined( 'IS_PROFILE_PAGE' ) || constant( 'IS_PROFILE_PAGE' ) === false ) {
			return null;
		}
		$slug = isset( $_GET['slug'] ) ? $_GET['slug'] : false;
		$slug = sanitize_text_field( $slug );
		if ( $slug === false ) {
			return null;
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			return null;
		}
		$context = isset( $_GET['context'] ) ? $_GET['context'] : false;
		$strings = '';
		if ( $context === 'subscribed' ) {
			$unsubscribe_link = $this->service->create_unsubscribe_url( $m, $this->get_current_user_email() );
			$strings          = sprintf( __( 'You are now subscribed to receive <strong>%s</strong>. Made a mistake? <a href="%s">Unsubscribe</a>', 'wpdef' ), $m->title, $unsubscribe_link );
		} elseif ( $context === 'unsubscribe' ) {
			$strings = sprintf( __( 'You are now unsubscribed from <strong>%s</strong>.', 'wpdef' ), $m->title );
		}
		?>
        <div class="notice notice-success" style="position:relative;">
            <p><?php echo $strings ?></p>
            <a href="<?php echo get_edit_profile_url() ?>" class="notice-dismiss" style="text-decoration: none">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </a>
        </div>
		<?php
	}

	/**
	 * Trigger report check signals
	 */
	public function report_sender() {
		$this->service->maybe_dispatch_report();
	}

	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Dispatch notification
	 *
	 * @param string $slug
	 * @param object $args
	 */
	public function send_notify( $slug, $args ) {
		$this->service->dispatch_notification( $slug, $args );
	}

	/**
	 * @param Request $request
	 *
	 * @defender_route
	 */
	public function validate_email( Request $request ) {
		$data  = $request->get_data( [
			'email' => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field'
			]
		] );
		$email = isset( $data['email'] ) ? $data['email'] : false;
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return new Response( true, [
				'error'  => false,
				'avatar' => get_avatar_url( $data['email'] )
			] );
		} else {
			return new Response( false, [
				'error' => __( 'Invalid email address', 'wpdef' )
			] );
		}
	}


	/**
	 * @param Request $request
	 *
	 * @defender_route
	 * @is_public
	 * @return Response
	 */
	public function unsubscribe_and_send_email( Request $request ) {
		$slug = HTTP::get( 'slug', '' );
		$hash = HTTP::get( 'hash', '' );
		$slug = sanitize_text_field( $slug );
		if ( empty( $slug ) || empty( $hash ) ) {
			wp_die( __( 'Invalid request', 'wpdef' ) );
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			wp_die( __( 'Invalid request', 'wpdef' ) );
		}
		$inhouse = false;
		foreach ( $m->in_house_recipients as &$recipient ) {
			$email = $recipient['email'];
			if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) ) ) {
				if ( ! is_user_logged_in() ) {
					auth_redirect();
				}
				if ( $email !== $this->get_current_user_email() ) {
					wp_die( __( 'Invalid request', 'wpdef' ) );
				}
				$recipient['status'] = \WP_Defender\Model\Notification::USER_SUBSCRIBE_CANCELED;
				$m->save();
				$inhouse = true;
				//send email
				$this->service->send_unsubscribe_email( $m, $email, $inhouse );
				break;
			}
		}

		if ( $inhouse === false ) {
			//no match on inhouse, check the outhouse list
			foreach ( $m->out_house_recipients as &$recipient ) {
				$email = $recipient['email'];
				if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) ) ) {
					$recipient['status'] = \WP_Defender\Model\Notification::USER_SUBSCRIBE_CANCELED;
					$m->save();
					$this->service->send_unsubscribe_email( $m, $email, $inhouse );
				}
			}
		}
		if ( $inhouse ) {
			wp_redirect( add_query_arg( [
				'slug'    => $slug,
				'context' => 'unsubscribe'
			], get_edit_profile_url() ) );
		} else {
			wp_redirect( get_home_url() );
		}
		exit;
	}

	/**
	 * An endpoint for saving single config from frontend
	 *
	 * @param Request $request
	 *
	 * @defender_route
	 *
	 * @return Response
	 */
	public function save( Request $request ) {
		$raw_data = $request->get_data();
		$slug     = sanitize_textarea_field( $raw_data['slug'] );
		$model    = $this->service->find_module_by_slug( $slug );

		if ( ! is_object( $model ) ) {
			// should never here
			die;
		}
		$data = $request->get_data_by_model( $model );
		$model->import( $data );
		$model->status = \WP_Defender\Model\Notification::STATUS_ACTIVE;
		if ( $model->validate() ) {
			if ( $model->last_sent === 0 ) {
				//this mean that the notification or report never sent, we will use the moment that it get activate
				$model->last_sent = time();
			}
			$model->save();
			$this->service->send_subscription_confirm_email(
				$model,
				$this->dump_routes_and_nonces()
			);

			return new Response(
				true,
				array_merge(
					array(
						'message' => __(
							'You have activated the notification successfully. Note, recipients will need to confirm their subscriptions to begin receiving notifications.',
							'wpdef'
						),
					),
					$this->data_frontend()
				)
			);
		}

		return new Response( false, [
			'message' => $model->get_formatted_errors()
		] );
	}

	/**
	 *
	 * @defender_route
	 */
	public function save_bulk( Request $request ) {
		$data = $request->get_data(
			array(
				'reports'       => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_textarea_field',
				),
				'notifications' => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_textarea_field',
				),
			)
		);
		$this->save_reports( $data['reports'] );
		$this->save_notifications( $data['notifications'] );

		return new Response(
			true,
			array_merge(
				$this->data_frontend(),
				array(
					'message' => __(
						'Your settings have been updated successfully. Any new recipients will receive an email to confirm their subscription.',
						'wpdef'
					),
				)
			)
		);
	}

	/**
	 * Process bulk reports saving
	 *
	 * @param $data
	 */
	private function save_reports( $data ) {
		foreach ( $data['configs'] as $datum ) {
			$slug  = $datum['slug'];
			$model = $this->service->find_module_by_slug( $slug );
			if ( ! is_object( $model ) ) {
				continue;
			}

			$import = array(
				//bulk saving must always enabled
				'status'               => \WP_Defender\Model\Notification::STATUS_ACTIVE,
				'configs'              => $datum,
				'in_house_recipients'  => $data['in_house_recipients'],
				'out_house_recipients' => $data['out_house_recipients'],
				'day'                  => $data['day'],
				'time'                 => $data['time'],
				'frequency'            => $data['frequency'],
				'day_n'                => $data['day_n'],
			);
			foreach ( $import['out_house_recipients'] as $key => $val ) {
				if ( ! filter_var( $val['email'], FILTER_VALIDATE_EMAIL ) ) {
					unset( $import['out_house_recipients'][ $key ] );
				}
			}
			$model->import( $import );
			if ( $model->validate() ) {
				$model->save();
				$this->service->send_subscription_confirm_email(
					$model,
					$this->dump_routes_and_nonces()
				);
			}
		}
	}

	/**
	 * @param $data
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 * @throws \ReflectionException
	 */
	private function save_notifications( $data ) {
		foreach ( $data['configs'] as $datum ) {
			$slug  = $datum['slug'];
			$model = $this->service->find_module_by_slug( $slug );
			if ( ! is_object( $model ) ) {
				continue;
			}
			$import = array(
				'status'               => \WP_Defender\Model\Notification::STATUS_ACTIVE,
				'configs'              => $datum,
				'in_house_recipients'  => $data['in_house_recipients'],
				'out_house_recipients' => $data['out_house_recipients'],
			);
			foreach ( $import['out_house_recipients'] as $key => $val ) {
				if ( ! filter_var( $val['email'], FILTER_VALIDATE_EMAIL ) ) {
					unset( $import['out_house_recipients'][ $key ] );
				}
			}
			$model->import( $import );
			if ( $model->validate() ) {
				$model->save();
				$this->service->send_subscription_confirm_email(
					$model,
					$this->dump_routes_and_nonces()
				);
			}
		}
	}

	/**
	 * Bulk activate
	 *
	 * @param Request $request
	 *
	 * @return Response
	 * @throws \Exception
	 * @defender_route
	 */
	public function bulk_activate( Request $request ) {
		$data  = $request->get_data(
			array(
				'slugs' => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slugs = $data['slugs'];
		if ( empty( $slugs ) ) {
			return new Response( false, array() );
		}

		foreach ( $slugs as $slug ) {
			$model = $this->service->find_module_by_slug( $slug );
			if ( is_object( $model ) ) {
				$model->status = \WP_Defender\Model\Notification::STATUS_ACTIVE;
				if ( $model->last_sent === 0 ) {
					//this mean that the notification or report never sent, we will use the moment that it get activate
					$model->last_sent = time();
				}
				$model->save();
			}
		}

		return new Response(
			true,
			array_merge(
				array(
					'message' => 'You have activated the notification successfully. Note, recipients will need to confirm their subscriptions to begin receiving notifications.',
				),
				$this->data_frontend()
			)
		);
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function bulk_deactivate( Request $request ) {
		$data  = $request->get_data(
			array(
				'slugs' => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slugs = $data['slugs'];
		if ( empty( $slugs ) ) {
			return new Response( false, array() );
		}

		foreach ( $slugs as $slug ) {
			$model = $this->service->find_module_by_slug( $slug );
			if ( is_object( $model ) ) {
				$model->status = \WP_Defender\Model\Notification::STATUS_DISABLED;
				$model->save();
			}
		}

		return new Response(
			true,
			array_merge(
				array(
					'message' => __( 'You have deactivated the notifications successfully.', 'wpdef' ),
				),
				$this->data_frontend()
			)
		);
	}

	/**
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function disable( Request $request ) {
		$data = $request->get_data(
			array(
				'slug' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$slug  = $data['slug'];
		$model = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $model ) ) {
			// NEVER HERE
			die;
		}
		$model->status = \WP_Defender\Model\Notification::STATUS_DISABLED;
		$model->save();

		return new Response(
			true,
			array_merge(
				$this->data_frontend(),
				array(
					'message' => __( 'You have deactivated the notification successfully.', 'wpdef' ),
				)
			)
		);
	}

	/**
	 * This is a receiver, to process subscribe confirmation from email
	 *
	 */
	public function verify_subscriber() {
		$hash    = HTTP::get( 'hash', false );
		$slug    = HTTP::get( 'uid', false );
		$inhouse = HTTP::get( 'inhouse', 0 );
		if ( $inhouse && ! is_user_logged_in() ) {
			//this is inhouse so we need rdirect
			auth_redirect();
		}
		if ( false === $hash || false === $slug ) {
			wp_die( __( 'You shall not pass', 'wpdef' ) );
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			wp_die( __( 'You shall not pass', 'wpdef' ) );
		}
		if ( $inhouse ) {
			$processed = false;
			foreach ( $m->in_house_recipients as &$recipient ) {
				if ( $recipient['status'] === \WP_Defender\Model\Notification::USER_SUBSCRIBED ) {
					continue;
				}
				$email = $recipient['email'];
				if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) )
				     && $email === $this->get_current_user_email() ) {
					$recipient['status'] = \WP_Defender\Model\Notification::USER_SUBSCRIBED;
					$this->service->send_subscribed_email( $email, $m );
					$processed = true;
				}
			}
		} else {
			foreach ( $m->out_house_recipients as &$recipient ) {
				if ( $recipient['status'] === \WP_Defender\Model\Notification::USER_SUBSCRIBED ) {
					continue;
				}
				$email = $recipient['email'];
				if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) ) ) {
					$recipient['status'] = \WP_Defender\Model\Notification::USER_SUBSCRIBED;
					$this->service->send_subscribed_email( $email, $m );
				}
			}
		}
		$m->save();
		if ( $inhouse ) {
			if ( $processed ) {
				wp_redirect( add_query_arg( [
					'slug'    => $m->slug,
					'context' => 'subscribed'
				], get_edit_profile_url() ) );
			} else {
				wp_redirect( home_url() );
			}
		} else {
			wp_redirect( home_url() );
		}
		exit;
	}

	/**
	 * Enqueue assets & output data
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script(
			'def-notification',
			'notification',
			array_merge( $this->data_frontend(), $this->dump_routes_and_nonces() )
		);
		wp_enqueue_script( 'def-momentjs', defender_asset_url( '/assets/js/vendor/moment/moment.min.js' ) );
		wp_enqueue_script( 'def-notification' );
		$this->enqueue_main_assets();
	}

	/**
	 * An endpoint for fetching users pool
	 *
	 * @defender_route
	 */
	public function get_users( Request $request ) {
		$data     = $request->get_data(
			array(
				'paged'   => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
				'search'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'exclude' => array(
					'type' => 'array',
				),
				'module'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field'
				)
			)
		);
		$paged    = isset( $data['paged'] ) ? $data['paged'] : 1;
		$exclude  = isset( $data['exclude'] ) ? $data['exclude'] : [];
		$username = isset( $data['search'] ) ? $data['search'] : '';
		$slug     = isset( $data['module'] ) ? $data['module'] : null;
		if ( strlen( $username ) ) {
			$username = "*$username*";
		}

		$users = $this->service->get_users_pool( $exclude, '', $username, 'ID', 'DESC', 10, $paged );

		if ( $slug !== null ) {
			$notification = $this->service->find_module_by_slug( $slug );
			if ( is_object( $notification ) ) {
				foreach ( $notification->in_house_recipients as $recipient ) {
					foreach ( $users as &$user ) {
						if ( $user['email'] === $recipient['email'] ) {
							$user['status'] = $recipient['status'];
						}
					}
				}
			}
		}

		wp_send_json_success( $users );
	}

	function remove_settings() {
		foreach ( $this->service->get_modules_as_objects() as $module ) {
			$module->delete();
		}

	}

	function remove_data() {
	}

	function to_array() {
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget
	 *
	 * @return array
	 */
	public function data_frontend() {
		return array(
			'notifications'          => $this->service->get_modules(),
			'inactive_notifications' => $this->service->get_inactive_modules(),
			'active_count'           => $this->service->count_active(),
			'next_run'               => $this->service->get_next_run(),
			'misc'                   => array(
				'days_of_week'      => $this->get_days_of_week(),
				'times_of_day'      => $this->get_times(),
				'timezone_text'     => sprintf(
					__(
						'Your timezone is set to <strong>%1$s</strong>, so your current time is <strong>%2$s</strong>.',
						'wpdef'
					),
					wp_timezone_string(),
					date( 'H:i', current_time( 'timestamp' ) )
				),
				'default_recipient' => $this->get_default_recipient(),
			),
		);
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset
	 *
	 * @param $data array
	 *
	 * @return boolean
	 */
	public function import_data( $data ) {
		// TODO: Implement import_data() method.
	}

	/**
	 * @return array
	 */
	public function export_strings() {
		$modules = wd_di()->get( Notification::class )->service->get_modules_as_objects();
		$strings = [];
		foreach ( $modules as $module ) {
			$string = __( '%s: %s', 'wpdef' );
			if ( $module->type === 'notification' ) {
				$string = sprintf( $string, $module->title, $module->status === \WP_Defender\Model\Notification::STATUS_ACTIVE ?
					__( 'Enabled', 'wpdef' ) : __( 'Disabled', 'wpdef' ) );
			} else {
				$string = sprintf( $string, $module->title, $module->status === \WP_Defender\Model\Notification::STATUS_ACTIVE ?
					$module->to_string() : __( 'Disabled', 'wpdef' ) );
			}
			$strings[] = $string;
		}

		return $strings;
	}
}