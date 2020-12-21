<?php

namespace WP_Defender\Component;

use Calotes\Base\Component;
use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Setting\Security_Tweaks;

class Security_Tweak extends Component {


	/**
	 * Use for cache
	 *
	 * @var Security_Tweaks
	 */
	public $model;

	/**
	 * Safe way to get cached model
	 *
	 * @return Security_Tweaks
	 */
	protected function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return $this->model = new Security_Tweaks();
	}

	public function get_issues() {
		$issues       = array();
		$tweaks       = Array_Cache::get( 'tweaks', 'tweaks' );
		$issue_tweaks = $this->get_model()->issues;
		foreach ( $issue_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$issues[] = array(
					'label' => ($tweaks[ $slug ]->to_array())['title'],
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener' ) . '#' . $slug
				);
			}
		}
		return $issues;
	}

	public function get_ignored() {
		$ignored        = array();
		$tweaks         = Array_Cache::get( 'tweaks', 'tweaks' );
		$ignored_tweaks = $this->get_model()->ignore;
		foreach ( $ignored_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$ignored[] = array(
					'label' => ($tweaks[ $slug ]->to_array())['title'],
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener&view=ignored' ) . '#' . $slug
				);
			}
		}
		return $ignored;
	}

	public function get_fixed() {
		$fixed        = array();
		$tweaks         = Array_Cache::get( 'tweaks', 'tweaks' );
		$fixed_tweaks = $this->get_model()->fixed;
		foreach ( $fixed_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$fixed[] = array(
					'label' => ($tweaks[ $slug ]->to_array())['title'],
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener&view=resolved' ) . '#' . $slug
				);
			}
		}
		return $fixed;
	}
}