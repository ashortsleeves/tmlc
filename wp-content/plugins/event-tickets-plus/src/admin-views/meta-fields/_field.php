<div id="field-<?php echo esc_attr( $field_id ); ?>" class="tribe-tickets-attendee-info-active-field meta-postbox closed">
	<?php if ( version_compare( $GLOBALS['wp_version'], '5.5', '>=' ) ) : ?>
		<div class="postbox-header">
			<h2 class="hndle ui-sortable-handle">
				<span><span class="tribe-tickets-attendee-info-field-type"><?php echo esc_html( $type_name ); ?>:</span> <?php echo esc_html( $label ); ?></span>
			</h2>
			<div class="handle-actions hide-if-no-js">
				<?php
				/* 
				 * We need to build support for this later.
				<button type="button" class="handle-order-higher" aria-disabled="false" aria-describedby="tribe-tickets-attendee-info-<?php echo esc_attr( $field_id ); ?>-handle-order-higher-description">
					<span class="screen-reader-text"><?php esc_html_e( 'Move up', 'event-tickets-plus' ); ?></span>
					<span class="order-higher-indicator" aria-hidden="true"></span>
				</button>
				<span class="hidden" id="tribe-tickets-attendee-info-<?php echo esc_attr( $field_id ); ?>-handle-order-higher-description"><?php esc_html_e( 'Move box up', 'event-tickets-plus' ); ?></span>
				<button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="tribe-tickets-attendee-info-<?php echo esc_attr( $field_id ); ?>-handle-order-lower-description">
					<span class="screen-reader-text"><?php esc_html_e( 'Move down', 'event-tickets-plus' ); ?></span>
					<span class="order-lower-indicator" aria-hidden="true"></span>
				</button>
				<span class="hidden" id="tribe-tickets-attendee-info-<?php echo esc_attr( $field_id ); ?>-handle-order-lower-description"><?php esc_html_e( 'Move box down', 'event-tickets-plus' ); ?></span>
				*/
				?>
				<button type="button" class="handlediv" aria-expanded="true">
					<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', 'event-tickets-plus' ); ?></span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>
			</div>
		</div>
	<?php else : ?>
		<div class="handlediv tribe-tickets-attendee-info-compat-toggle" title="Click to toggle"><br></div>
		<h3 class="hndle ui-sortable-handle">
			<span><?php echo esc_html( $type_name ); ?>:</span> <?php echo esc_html( $label ); ?>
		</h3>
	<?php endif; ?>

	<div class="inside">
		<input type="hidden" class="ticket_field" name="tribe-tickets-input[<?php echo esc_attr( $field_id ); ?>][type]" value="<?php echo esc_attr( $type ); ?>">

		<div class="tribe-tickets-input tribe-tickets-input-text">
			<label for="tickets_attendee_info_field"><?php echo esc_html_x( 'Label:', 'Attendee information fields', 'event-tickets-plus' ); ?></label>
			<input type="text" class="ticket_field" name="tribe-tickets-input[<?php echo esc_attr( $field_id ); ?>][label]" value="<?php echo esc_attr( $label ); ?>">
		</div>

		##FIELD_EXTRA_DATA##

		<div class="tribe-tickets-input tribe-tickets-input-checkbox tribe-tickets-required">
			<label class="prompt"><input type="checkbox" <?php checked( $required, 'on' ); ?> class="ticket_field" name="tribe-tickets-input[<?php echo esc_attr( $field_id );?>][required]" value="on">
				<?php echo esc_html_x( 'Required?', 'Attendee information fields', 'event-tickets-plus' ); ?>
			</label>
		</div>
		<div class="tribe-tickets-delete-field">
			<a href="#" class="delete-attendee-field" ><?php echo esc_html_x( 'Delete this field', 'Attendee information fields', 'event-tickets-plus' ); ?></a>
		</div>
	</div>
</div>
