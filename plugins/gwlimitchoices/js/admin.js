
( function( $ ) {

	window.GPLCAdmin = {

		addRuleFieldAfter: function( ruleField, targetLabel, ruleFields ) {

			var targetIndex;

			$.each( ruleFields, function( i, _ruleField ) {
				if ( targetLabel == _ruleField.label ) {
					targetIndex = i;
					return false;
				}
			} );

			ruleFields.splice( targetIndex + 1, 0, ruleField );

			return ruleFields;
		},

		addLimitChoiceInputs: function () {

			// skip if field doesn't have any choices	
			if ( ! field.choices ) {
				return;
			}
			$( 'ul#field_choices li' ).each(function (i) {

				var limitValue = typeof field.choices[i]['limit'] != 'undefined' ? field.choices[i]['limit'] : '';

				// skip this row if already has a limit input
				if ($( this ).find( 'input.field-choice-limit' ).length > 0) {
					return;
				}

				// add limit input
				$( this ).find( 'input.field-choice-input:last' ).after( '<input type="text" class="field-choice-input field-choice-limit gform-input" value="' + limitValue + '" onkeyup="GPLCAdmin.setChoiceLimit(' + i + ', this.value)" />' );

				// replace onclick options
				$( this ).find( 'img.add_field_choice' ).attr( 'onclick', $( this ).find( 'img.add_field_choice' ).attr( 'onclick' ) + ' GPLCAdmin.addLimitChoiceInputs();' );
				$( this ).find( 'img.delete_field_choice' ).attr( 'onclick', $( this ).find( 'img.delete_field_choice' ).attr( 'onclick' ) + ' GPLCAdmin.addLimitChoiceInputs();' );

			});

		},

		setChoiceLimit: function (index, value) {
			field.choices[index]['limit'] = value;
		},

		addEnableLimitsCheckbox: function () {

			/**
			 * For GF 2.4 and below, we need to add the checkbox with JavaScript.
			 *
			 * For GF 2.5 and above, the checkbox is added using a callback on gform_field_standard_settings
			 * along with some data attributes.
			 */
			if ( $('body').hasClass('gf-legacy-ui') ) {
				// add checkbox if it has not been added
				if ($( '#field_choice_limits_enabled' ).length < 1) {
					$( 'li.gw-limit-choice.field_setting' )
						.children( 'div:first-child' )
						.after( GPLCAdminData.enableCheckboxLegacyMarkup );
				}
			}

			// check or uncheck
			$( '#field_choice_limits_enabled' ).prop( 'checked', field['gwlimitchoices_enableLimits'] == true );

			GPLCAdmin.toggleEnableLimits();

		},

		removeEnableLimitsCheckbox: function () {
			if ( ! $('body').hasClass('gf-legacy-ui') ) {
				return;
			}

			$( '#field_choice_limits_enabled' ).parent( 'div' ).remove();
		},

		toggleEnableLimits: function () {
			var isChecked = $( '#field_choice_limits_enabled' ).prop( 'checked' );
			var $el = ! $('body').hasClass('gf-legacy-ui') ? $('.choices-ui__content') : $('li.gw-limit-choice.field_setting');

			if (isChecked) {
				$el.addClass( 'limits-enabled' );
			} else {
				$el.removeClass( 'limits-enabled' );
			}
		}

	}

	gform.addFilter( 'gform_conditional_logic_fields', function( ruleFields, form, selectedFieldId ) {

		jQuery.each( form.fields, function( i, field ) {

			var isCondLogicSupportedFieldType = $.inArray( GetInputType( field ), [ 'checkbox', 'multiselect' ] ) == -1;

			if ( field['gwlimitchoices_enableLimits'] && isCondLogicSupportedFieldType ) {
				GPLCAdmin.addRuleFieldAfter( {
					label: '(Remaining) ' + field.label,
					value: 'gplc_count_remaining_' + field.id
				}, field.label, ruleFields );
			}

		} );

		return ruleFields;
	} );

	/**
	 * Handle field settings load
	 */
	$( document ).bind('gform_load_field_settings', function(event, field) {

		if ($.inArray( field.type, GPLCAdminData.allowedFieldTypes ) == -1 && $.inArray( field.inputType, GPLCAdminData.allowedFieldTypes ) == -1) {

			if ( ! $('body').hasClass('gf-legacy-ui') ) {
				$( '.choices-ui__content' ).removeClass( 'gw-limit-choice' );
			} else {
				$( '.choices_setting' ).removeClass( 'gw-limit-choice' );
			}

			GPLCAdmin.removeEnableLimitsCheckbox();
			return;

		} else {

			// add limit class to choice setting
			if ( ! $('body').hasClass('gf-legacy-ui') ) {
				$( '.choices-ui__content' ).addClass( 'gw-limit-choice' );
			} else {
				$( '.choices_setting' ).addClass( 'gw-limit-choice' );
			}

			// add limit header if does not exists
			if ( ! $( '.gfield_choice_header_limit' ).length) {
				$( '.gfield_choice_header_price' ).after( '<label class="gfield_choice_header_limit">Limit</label>' );
			}

			// init enable limits checkbox
			GPLCAdmin.addEnableLimitsCheckbox( field );

			// init choice inputs
			GPLCAdmin.addLimitChoiceInputs();

			// only bind once for sorting action
			if ( ! GPLCAdmin.limitChoicesSetup) {

				$( '#field_choices' ).bind('sortupdate', function () {
					// was firing before GF's update function
					setTimeout( 'GPLCAdmin.addLimitChoiceInputs()', 1 );
				});

				$( document ).on('gform_load_field_choices', function () {

					GPLCAdmin.addLimitChoiceInputs();

				});

			}

			GPLCAdmin.limitChoicesSetup = true;

		}

		GPLCAdmin.limitChoicesSetup = false;

	} );

} )( jQuery );
