<?php
class Tribe__Tickets_Plus__Assets {
	/**
	 * Enqueue scripts for front end
	 *
	 * @since 4.6
	 * @since 4.11.1 Only load if in a tickets-enabled post context.
	 *
	 * @see   \tribe_tickets_is_enabled_post_context()
	 */
	public function enqueue_scripts() {
		// Set up our base list of enqueues.
		$enqueue_array = [
			[ 'event-tickets-plus-tickets-css', 'tickets.css', [ 'dashicons' ] ],
			[ 'jquery-deparam', 'vendor/jquery.deparam/jquery.deparam.js', [ 'jquery' ] ],
			[ 'jquery-cookie', 'vendor/jquery.cookie/jquery.cookie.js', [ 'jquery' ] ],
			[ 'event-tickets-plus-attendees-list-js', 'attendees-list.js', [ 'event-tickets-attendees-list-js' ] ],
			[ 'event-tickets-plus-meta-js', 'meta.js', [ 'jquery-cookie', 'jquery-deparam' ] ],
			[ 'tribe-tickets-plus-attendee-meta', 'v2/tickets-meta.js', [ 'jquery', 'tribe-common' ] ],
		];

		// and the engine...
		tribe_assets(
			tribe( 'tickets-plus.main' ),
			$enqueue_array,
			'wp_enqueue_scripts',
			[
				'localize'     => [
					'name' => 'TribeTicketsPlus',
					'data' => [
						'ajaxurl'                  => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
						'save_attendee_info_nonce' => wp_create_nonce( 'save_attendee_info' ),
					],
				],
				'conditionals' => tribe_callback( 'tickets.assets', 'should_enqueue_frontend' ),
			]
		);

	}

	/**
	 * Enqueue scripts for admin views
	 *
	 * @since 4.6
	 */
	public function admin_enqueue_scripts() {
		// Set up our base list of enqueues.
		$enqueue_array = [
			[ 'event-tickets-plus-meta-admin-css', 'meta.css', [] ],
			[ 'event-tickets-plus-meta-report-js', 'meta-report.js', [] ],
			[ 'event-tickets-plus-attendees-list-js', 'attendees-list.js', [ 'event-tickets-attendees-list-js' ] ],
			[ 'event-tickets-plus-meta-admin-js', 'meta-admin.js', [ 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable' ] ],
			[ 'event-tickets-plus-admin-css', 'admin.css', [ 'event-tickets-admin-css' ] ],
			[ 'event-tickets-plus-admin-tables-js', 'tickets-tables.js', [ 'underscore', 'jquery', 'tribe-common' ] ],
			[ 'event-tickets-plus-admin-qr', 'qr.js', [ 'jquery' ] ],
		];

		/**
		 * Filter the array of module names.
		 *
		 * @since 4.6
		 *
		 * @param array the array of modules
		 */
		$modules = Tribe__Tickets__Tickets::modules();
		$modules = array_values( $modules );

		if ( in_array( 'WooCommerce', $modules )  ) {
			$enqueue_array[] = [
				'event-tickets-plus-wootickets-css',
				'wootickets.css',
				[ 'event-tickets-plus-meta-admin-css' ],
			];
		}

		// and the engine...
		tribe_assets(
			tribe( 'tickets-plus.main' ),
			$enqueue_array,
			'admin_enqueue_scripts',
			[
				'priority' => 0,
				'groups'       => 'event-tickets-plus-admin',
				'localize' => (object) [
					'name' => 'tribe_qr',
					'data' => [
						'generate_qr_nonce'   => wp_create_nonce( 'generate_qr_nonce' ),
					],
				],
			]
		);
	}
}
