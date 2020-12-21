<?php

namespace WP_Defender\Component\Security_Tweaks\Servers;

use Calotes\Helper\HTTP;

class Server {
	public static function create( $server ) {
		return new Server_Factory( $server );
	}

	/**
	 * Determine the server
	 * Incase we are using a hybrid server and need to know where static files are houses, pass true as a param
	 *
	 * @param $use_static_path - use static path instead of home url. This is the path to Defender changelog
	 */
	public static function get_current_server( $use_static_path = false ) {
		$url         = $use_static_path ? defender_path( 'changelog.txt' ) : home_url();
		$server_type = get_site_transient( 'defender_current_server' );

		if ( ! is_array( $server_type ) ) {
			$server_type = [];
		}

		if ( isset( $server_type[ $url ] ) && ! empty( $server_type[ $url ] ) ) {
			return strtolower( $server_type[ $url ] );
		}

		// Url should be end with php
		global $is_apache, $is_nginx, $is_IIS, $is_iis7;

		$server     = null;
		$ssl_verify = apply_filters( 'defender_ssl_verify', true ); //most hosts dont really have valid ssl or ssl still pending

		if ( $is_nginx ) {
			$server = 'nginx';
		} elseif ( $is_apache ) {
			//case the url is detecting php file
			if ( pathinfo( $url, PATHINFO_EXTENSION ) == 'php' ) {
				$server = 'apache';
			} else {
				//so the server software is apache, let see what the header return
				$request = wp_remote_head( $url, array(
					'user-agent' => ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'Defender Self Ping',
					'sslverify'  => $ssl_verify
				) );
				$server  = wp_remote_retrieve_header( $request, 'server' );
				$server  = explode( '/', $server );
				if ( strtolower( $server[0] ) == 'nginx' ) {
					//proxy case
					$server = 'nginx';
				} else {
					$server = 'apache';
				}
			}
		} elseif ( $is_iis7 || $is_IIS ) {
			$server = 'iis-7';
		}

		if ( is_null( $server ) ) {
			//if fall in here, means there is st unknown.
			$request = wp_remote_head( $url, array(
				'user-agent' => ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'Defender Self Ping',
				'sslverify'  => $ssl_verify
			) );
			$server  = wp_remote_retrieve_header( $request, 'server' );
			$server  = explode( '/', $server );
			$server  = $server[0];
		}

		$server_type[ $url ] = $server;
		set_site_transient( 'defender_current_server', $server_type, 3600 );

		return $server;
	}

	/**
	 * Check whether ping test failed or not
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function ping_test_failed( $url ) {
		$response = wp_remote_post( $url, [ 'user-agent' => 'WP Defender Self Ping Test' ] );

		if ( is_wp_error( $response ) ) {
			return true;
		}

		return 200 !== wp_remote_retrieve_response_code( $response );
	}
}