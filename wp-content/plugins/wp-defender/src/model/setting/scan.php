<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Scan extends Setting {
	public $table = 'wd_scan_settings';

	/**
	 * Enable core integrity check while perform a scan
	 * @var bool
	 */
	public $integrity_check = true;
	/**
	 * Check the files inside wp-content by our malware signatures
	 * @var bool
	 */
	public $scan_malware = true;
	/**
	 * Check if any plugins or themes have a known vulnerability
	 * @var bool
	 */
	public $check_known_vuln = true;

	/**
	 * If a file is smaller than this, we wil include it to the test
	 * @var int
	 */
	public $filesize = 10;

	/**
	 * Define labels for settings key
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'integrity_check'  => __( 'Scan WordPress Core', 'wpdef' ),
			'check_known_vuln' => __( 'Scan Plugins & Themes', 'wpdef' ),
			'scan_malware'     => __( 'Scan Suspicious Code', 'wpdef' ),
			'filesize'         => __( 'Maximum included filesize', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}