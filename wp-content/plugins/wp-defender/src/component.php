<?php

namespace WP_Defender;

use WP_Defender\Extra\IP_Helper;

class Component extends \Calotes\Base\Component {
	use \WP_Defender\Traits\IO;
	use \WP_Defender\Traits\User;
	use \WP_Defender\Traits\Formats;

	/**
	 * Get user IP
	 * todo need to test with cloudflare & aws
	 *
	 * @return string
	 */
	public function get_user_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
		if ( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) ) {
			//this looks like it come from cloudflare, so this should contain the actual IP, and REMOTE_ADDR is contain
			//cloudflare IP
			list( $cloudflare_ipv4_range, $cloudflare_ipv6_range ) = $this->cloudflare_ip_ranges();
			$ip_helper = new IP_Helper();
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				foreach ( $cloudflare_ipv4_range as $cf_ip ) {
					if ( $ip_helper->ipv4_in_range( $ip, $cf_ip ) ) {
						$ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
						break;
					}
				}
			} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				foreach ( $cloudflare_ipv6_range as $cf_ip ) {
					if ( $ip_helper->ipv6_in_range( $ip, $cf_ip ) ) {
						$ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
						break;
					}
				}
			}
		}
		if ( is_null( $ip ) ) {
			//this case can be behind a reserve proxy, however it should not happen,
			//this must be a custom proxy, this can be sprof
			$headers = [
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_X_CLUSTER_CLIENT_IP',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED'
			];

			foreach ( $headers as $key ) {
				if ( array_key_exists( $key, $_SERVER ) === true ) {
					foreach ( explode( ',', $_SERVER[ $key ] ) as $tmp_ip ) {
						$tmp_ip = trim( $tmp_ip );
						if ( filter_var( $tmp_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
							$ip = $tmp_ip;
						}
					}
				}
			}
		}

		return apply_filters( 'defender_user_ip', $ip );
	}

	/**
	 * We fetching the ip range here
	 * https://www.cloudflare.com/ips/
	 *
	 * @return array
	 */
	private function cloudflare_ip_ranges() {
		return [
			[
				'173.245.48.0/20',
				'103.21.244.0/22',
				'103.22.200.0/22',
				'103.31.4.0/22',
				'141.101.64.0/18',
				'108.162.192.0/18',
				'190.93.240.0/20',
				'188.114.96.0/20',
				'197.234.240.0/22',
				'198.41.128.0/17',
				'162.158.0.0/15',
				'104.16.0.0/12',
				'172.64.0.0/13',
				'131.0.72.0/22',
			],
			[
				'2400:cb00::/32',
				'2606:4700::/32',
				'2803:f800::/32',
				'2405:b500::/32',
				'2405:8100::/32',
				'2a06:98c0::/29',
				'2c0f:f248::/32'
			]
		];
	}

	/**
	 * @param $freq
	 *
	 * @return string
	 */
	public function frequency_to_text( $freq ) {
		$text = '';
		switch ( $freq ) {
			case 1:
				$text = __( 'daily', 'wpdef' );
				break;
			case 7:
				$text = __( 'weekly', 'wpdef' );
				break;
			case 30:
				$text = __( 'monthly', 'wpdef' );
				break;
			default:
				//param not from the button on frontend, log it
				$this->log( sprintf( __( 'Unexpected value %s from IP %s', 'wpdef' ), $freq, $this->get_user_ip() ) );
				break;
		}

		return $text;
	}

	/**
	 * @param string $endpoint
	 * @param array $body_args
	 * @param array $request_args
	 * @param bool $return_raw
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	public function dev_call( $endpoint, $body_args = array(), $request_args = array(), $return_raw = false ) {
		$wpmu_dev = new WPMUDEV();
		$api_key  = $wpmu_dev->get_apikey();
		if ( false !== $api_key ) {
			$domain            = network_site_url();
			$post_vars['body'] = $body_args;
			if ( ! isset( $post_vars['body']['domain'] ) ) {
				$post_vars['body']['domain'] = $domain;
			}
			$post_vars['timeout']     = 30;
			$post_vars['httpversion'] = '1.1';

			$post_vars            = array_merge( $post_vars, $request_args );
			$headers              = isset( $post_vars['headers'] ) ? $post_vars['headers'] : array();
			$post_vars['headers'] = array_merge( $headers, array(
				'Authorization' => 'Basic ' . $api_key
			) );

			$response = wp_remote_request(
				$endpoint,
				apply_filters( 'wd_wpmudev_call_request_args', $post_vars )
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( true === $return_raw ) {
				return $response;
			}

			if (
				'OK' !== wp_remote_retrieve_response_message( $response )
				or 200 !== wp_remote_retrieve_response_code( $response )
			) {
				return new \WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
			} else {
				$data = wp_remote_retrieve_body( $response );

				return json_decode( $data, true );
			}
		} else {
			return new \WP_Error( 'dashboard_required',
				sprintf( esc_html__( 'WPMU DEV Dashboard will be required for this action. Please visit <a target="_blank" href="%s">here</a> and install the WPMU DEV Dashboard', 'wpdef' )
					, 'https://premium.wpmudev.org/project/wpmu-dev-dashboard/' ) );
		}
	}
}