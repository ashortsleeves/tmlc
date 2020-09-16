/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since5.0.0
 *
 * @type {PlainObject}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Configures ET meta fields in the Global Tribe variable
 *
 * @since 5.0.0
 *
 * @type {PlainObject}
 */
tribe.tickets.meta = {};

/**
 * Initializes in a Strict env the code that manages the RSVP block.
 *
 * @since5.0.0
 *
 * @param  {PlainObject} $   jQuery
 * @param  {PlainObject} obj tribe.tickets.meta
 *
 * @return {void}
 */
( function( $, obj ) {
	'use strict';
	const $document = $( document );

	/**
	 * Selectors used for configuration and setup
	 *
	 * @since5.0.0
	 *
	 * @type {PlainObject}
	 */
	obj.selectors = {
		formField: '.tribe-tickets__form-field',
		formFieldRequired: '.tribe-tickets__form-field--required',
		formFieldInput: '.tribe-tickets__form-field-input',
		formFieldInputError: '.tribe-tickets__form-field-input--error',
		formFieldInputCheckboxRadioGroup: '.tribe-common-form-control-checkbox-radio-group',
		formFieldInputCheckbox: {
			container: '.tribe-tickets__form-field--checkbox',
			checkbox: '.tribe-tickets__form-field-input--checkbox',
		},
		formFieldInputBirthday: {
			container: '.tribe-tickets__form-field--birth',
			select: '.tribe-tickets__form-field--birth select',
			day: '.tribe-tickets__form-field--birth-day',
			month: '.tribe-tickets__form-field--birth-month',
			year: '.tribe-tickets__form-field--birth-year',
			value: '.tribe-tickets__form-field--birth-value',
		},
		hiddenElement: '.tribe-common-a11y-hidden',
	};

	/**
	 * Validate Checkbox/Radio group.
	 * We operate under the assumption that you must check _at least_ one,
	 * but not necessarily all.
	 *
	 * @since5.0.0
	 *
	 * @param {jQuery} $group The jQuery object for the checkbox group.
	 *
	 * @return {boolean} If the input group is valid.
	 */
	obj.validateCheckboxRadioGroup = function( $group ) {
		const checked     = $group.find( 'input:checked' ).length;
		const required    = $group.find( 'input:required' ).length;

		// the group is valid if there are no required.
		// or if it is required and there's at least one checked.
		const isValid = ! required || ( required && checked );

		return !! isValid;
	};

	/**
	 * Check if it's the birthday meta field.
	 *
	 * @since5.0.0
	 *
	 * @param {jQuery} $input jQuery object of the input.
	 *
	 * @return {boolean} If the field is valid.
	 */
	obj.isFieldBirthday = function( $input ) {
		return $input.hasClass( obj.selectors.formFieldInputBirthday.value.className() );
	};

	/**
	 * Validates the birthday field.
	 *
	 * @since5.0.0
	 *
	 * @param {jQuery} $input jQuery object of the input.
	 *
	 * @return {boolean} If the field is valid.
	 */
	obj.validateFieldBirthday = function( $input ) {
		const wrapper = $input.closest( obj.selectors.formFieldInputBirthday.container );
		const day = wrapper.find( obj.selectors.formFieldInputBirthday.day );
		const month = wrapper.find( obj.selectors.formFieldInputBirthday.month );
		const year = wrapper.find( obj.selectors.formFieldInputBirthday.year );
		let isValidField = true;

		if ( ! day.prop( 'required' ) && ! month.prop( 'required' ) && ! year.prop( 'required' ) ) {
			return isValidField;
		}

		[ day, month, year ].forEach( function( el ) {

			// Check if given value is a positive number, even if it's a string
			if ( isNaN( parseInt( el.val() ) ) || parseInt( el.val() ) <= 0 ) {
				el.addClass( obj.selectors.formFieldInputError.className() );

				isValidField = false;
			} else {
				el.removeClass( obj.selectors.formFieldInputError.className() );
			}
		} );

		return isValidField;
	};

	/**
	 * Validates a single field.
	 *
	 * @since5.0.0
	 *
	 * @param {HTMLElement} input DOM Object that is the field we are validating.
	 *
	 * @return {boolean} If the field is valid.
	 */
	obj.validateField = function( input ) {
		const $input     = $( input );
		let isValidField = input.checkValidity();

		if ( ! isValidField ) {
			// Got to be careful of required checkbox/radio groups.
			if ( $input.is( ':checkbox' ) || $input.is( ':radio' ) ) {
				const $group = $input.closest( obj.selectors.formFieldInputCheckboxRadioGroup );

				if ( $group.length ) {
					isValidField = obj.validateCheckboxRadioGroup( $group );
				}
			}
		}

		if ( obj.isFieldBirthday( $input ) ) {
			isValidField = obj.validateFieldBirthday( $input );
		}

		if ( ! isValidField ) {
			$input.addClass( obj.selectors.formFieldInputError.className() );
		} else {
			$input.removeClass( obj.selectors.formFieldInputError.className() );
		}

		return isValidField;
	};

	/**
	 * Populate the different birthday field <select>
	 * depending on the value from the hidden input.
	 *
	 * @since5.0.0
	 */
	obj.populateFieldBirthday = function() {
		$( obj.selectors.formFieldInputBirthday.container ).each( function( index, value ) {
			const wrapper = $( value );

			const day = wrapper.find( obj.selectors.formFieldInputBirthday.day );
			const month = wrapper.find( obj.selectors.formFieldInputBirthday.month );
			const year = wrapper.find( obj.selectors.formFieldInputBirthday.year );
			const realValue = wrapper.find( obj.selectors.formFieldInputBirthday.value );

			const savedValues = realValue.val().split( '-' );

			if ( 3 === savedValues.length ) {
				year.val( savedValues[ 0 ] );
				month.val( savedValues[ 1 ] );
				day.val( savedValues[ 2 ] );
			}
		} );
	};

	/**
	 * Update the birthday hidden input value depending
	 * on the changes the different <select> had.
	 *
	 * @since5.0.0
	 *
	 * @param {event} e input event.
	 *
	 * @return {void}
	 */
	obj.updateFieldBirthdayValue = function( e ) {
		const wrapper = $( e.target ).closest( obj.selectors.formFieldInputBirthday.container );
		const day = wrapper.find( obj.selectors.formFieldInputBirthday.day );
		const month = wrapper.find( obj.selectors.formFieldInputBirthday.month );
		const year = wrapper.find( obj.selectors.formFieldInputBirthday.year );
		const realValue = wrapper.find( obj.selectors.formFieldInputBirthday.value );

		// Data is stored in format: yyyy-mm-dd
		realValue.val( year.val() + '-' + month.val() + '-' + day.val() );
		realValue.trigger( 'change' );
	};

	/**
	 * Handle the required checkboxes. Once a checkbox changes we update
	 * the required value and set it only to the checked ones.
	 *
	 * @since5.0.0
	 *
	 * @param {event} e input change event.
	 *
	 * @return {void}
	 */
	obj.handleRequiredCheckboxes = function( e ) {
		const $input = $( e.target );
		const $group = $input.closest( obj.selectors.formFieldInputCheckbox.container );

		if ( ! $group.hasClass( obj.selectors.formFieldRequired.className() ) ) {
			return;
		}

		const $checked = $group.find( obj.selectors.formFieldInputCheckbox.checkbox + ':checked' );
		const $groupCheckboxes = $group.find( obj.selectors.formFieldInputCheckbox.checkbox );

		// If they un-check all, set them all as required.
		if ( 0 === $checked.length ) {
			$groupCheckboxes.attr( 'required', true );
			$groupCheckboxes.attr( 'aria-required', true );
			return;
		}

		// Only set the checked ones as required.
		$groupCheckboxes.removeAttr( 'required' );
		$groupCheckboxes.removeAttr( 'aria-required' );

		$checked.attr( 'required', true );
		$checked.attr( 'aria-required', true );
	};

	/**
	 * Init tickets attendee fields.
	 *
	 * @since5.0.0
	 *
	 * @return {void}
	 */
	obj.ready = function() {
		// init tickets attendee fields.
		$document.on(
			'change',
			obj.selectors.formFieldInputBirthday.select,
			obj.updateFieldBirthdayValue
		);

		$document.on(
			'change',
			obj.selectors.formFieldInputCheckbox.checkbox,
			obj.handleRequiredCheckboxes
		);
	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, tribe.tickets.meta );
