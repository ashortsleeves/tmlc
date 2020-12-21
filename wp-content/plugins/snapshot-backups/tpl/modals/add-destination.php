<?php // phpcs:ignore
/**
 * Welcome modal.
 *
 * @package snapshot
 */

use WPMUDEV\Snapshot4\Helper;

$assets = new Helper\Assets();

wp_nonce_field( 'snapshot_s3_connection', '_wpnonce-snapshot_s3_connection' );

?>

<div class="sui-modal sui-modal-md" id="snapshot-add-destination-modal">
	<div
		role="dialog"
		id="snapshot-add-destination-dialog"
		class="sui-modal-content"
		aria-modal="true"
	>

		<?php
		$this->render(
			'modals/modal_parts/add-destination-screen-1',
			array(
				'assets' => $assets,
			)
		);
		$this->render(
			'modals/modal_parts/add-destination-screen-2-s3',
			array(
				'assets' => $assets,
			)
		);
		$this->render(
			'modals/modal_parts/add-destination-screen-3-s3',
			array(
				'assets' => $assets,
			)
		);
		$this->render(
			'modals/modal_parts/add-destination-screen-4-s3',
			array(
				'assets' => $assets,
			)
		);
		$this->render(
			'modals/modal_parts/add-destination-screen-2-gd',
			array(
				'assets'   => $assets,
				'auth_url' => $auth_url,
			)
		);
		$this->render(
			'modals/modal_parts/add-destination-screen-3-gd',
			array(
				'assets' => $assets,
			)
		);
		$this->render(
			'modals/modal_parts/add-destination-screen-4-gd',
			array(
				'assets' => $assets,
			)
		);
		?>

	</div>
</div>