(function($){
	$(document).ready(function(){
		IG_ES_Workflows = {

			$triggers_box: $('#ig_es_workflow_trigger'),
			$actions_box : $('#ig_es_workflow_actions'),
			$trigger_select: $('.js-trigger-select').first(),
			$actions_container: $('.ig-es-actions-container'),

			init: function() {
				IG_ES_Workflows.init_triggers_box();
				IG_ES_Workflows.init_actions_box();
				IG_ES_Workflows.init_show_hide();
				IG_ES_Workflows.init_date_pickers();
				IG_ES_Workflows.init_workflow_status_switch();
			},

			init_date_pickers: function() {
				$( '.ig-es-date-picker' ).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 1,
					showButtonPanel: true
				});
				
				let d = new Date();
				let n = d.getHours() + 1;
				$('.ig-es-time-picker').timepicker({
					timeFormat: 'H:mm',
					interval: 15,
					startTime: new Date(0,0,0,n,0,0), 
					dynamic: true,
					dropdown: true,
					scrollbar: false,
					minTime: new Date(0,0,0,n,0,0)
				});
			},

			init_workflow_status_switch: function() {
				$('.ig-es-switch.js-toggle-workflow-status').click(function(){

					let $switch, state, new_state;

					$switch = $(this);

					if ( $switch.is('.ig-es-loading') ) {
						return;
					}

					state     = $switch.attr( 'data-ig-es-switch' );
					new_state = state === 'active' ? 'inactive' : 'active';

					$switch.addClass('ig-es-loading');
					
					$.post( ajaxurl, {
						action: 'ig_es_toggle_workflow_status',
						workflow_id: $switch.attr( 'data-workflow-id' ),
						new_state: new_state,
						security: ig_es_workflows_data.security,
						dataType: 'json'
					}, function( response ) {
						$switch.attr( 'data-ig-es-switch', new_state );
						$switch.removeClass('ig-es-loading');
					});

				});
			},

			/**
			 * Show / hide logic with data attributes
			 */
			init_show_hide: function() {

				let update = function( $el ) {
					let id          = $el.data( 'ig-es-bind' );
					let value       = $el.val();
					let is_checkbox = $el.is('input[type="checkbox"]');

					$('[data-ig-es-show]').each(function() {
						if ( is_checkbox && $(this).data('ig-es-show') === id ) {
							if ( $el.is(':checked') ) {
								$(this).show();
							} else {
								$(this).hide();
							}
						} else {
							let logic = $(this).data('ig-es-show').split('=');

							if ( logic[0] !== id ) {
								return;
							}

							let possible_values = logic[1].split('|');

							if ( possible_values.indexOf( value ) !== -1 ) {
								$(this).show();
							} else {
								$(this).hide();
							}
						}
					});


					$('[data-ig-es-hide]').each(function() {
						if ( is_checkbox && $(this).data('ig-es-hide') === id ) {
							if ( $el.is(':checked') ) {
								$(this).hide();
							} else {
								$(this).show();
							}
						} else {
							let logic = $(this).data('ig-es-hide').split('=');

							if ( logic[0] !== id ) {
								return;
							}

							let possible_values = logic[1].split('|');

							if ( possible_values.indexOf( value ) !== -1 ) {
								$(this).hide();
							} else {
								$(this).show();
							}
						}
					});
				};


				$(document).on( 'change', '[data-ig-es-bind]', function() {
					update( $(this) );
				});

				$('[data-ig-es-bind]').each(function() {
					update( $(this) );
				});

			},

			/**
			 *
			 */
			init_triggers_box: function() {
				IG_ES_Workflows.$trigger_select.change(function(){
					IG_ES_Workflows.fill_trigger_fields( $(this).val() );
					IG_ES_Workflows.remove_actions();
				});
			},

			/**
			 * @param trigger_name
			 */
			fill_trigger_fields: function( trigger_name ) {

				// Remove existing fields
				IG_ES_Workflows.$triggers_box.find('tr.ig-es-trigger-option').remove();

				if ( trigger_name ) {

					IG_ES_Workflows.$triggers_box.addClass('ig-es-loading');

					let workflow_id = $('#workflow_id').val();
					$.ajax({
						url: ajaxurl,
						data: {
							action: 'ig_es_fill_trigger_fields',
							trigger_name: trigger_name,
							security: ig_es_workflows_data.security,
							workflow_id: workflow_id
						}
						})
						.done(function(response){

							if ( ! response.success ) {
								return;
							}

							IG_ES_Workflows.$triggers_box.find('tbody').append( response.data.fields );
							IG_ES_Workflows.$triggers_box.removeClass('ig-es-loading');
							IG_ES_Workflows.$triggers_box.find('.js-trigger-description').html( '<p class="ig-es-field-description">' + response.data.trigger.description + '</p>' );
						});
				}
			},

			/**
			 *
			 */
			init_actions_box: function() {
				// Action select change
				$(document).on('change', '.js-action-select', function () {
					let $action = $(this).parents('.ig-es-action').first();
					IG_ES_Workflows.fill_action_fields( $action, $(this).val() );
				});

				// Add new action
				$('.js-ig-es-add-action').click(function (e) {
					e.preventDefault();
					IG_ES_Workflows.add_new_action();
				});

				$(document).on('click', '.js-edit-action, .ig-es-action__header', function (e) {
					e.preventDefault();
					e.stopImmediatePropagation();

					let $action = $(this).parents('.ig-es-action').first();

					if ($action.is('.js-open')) {
						IG_ES_Workflows.action_edit_close($action);
					} else {
						IG_ES_Workflows.action_edit_open($action);
					}
				});

				// Delete action
				$(document).on('click', '.js-delete-action', function (e) {
					e.preventDefault();
					let $action = $(this).parents('.ig-es-action').first();
					IG_ES_Workflows.action_delete($action);
				});

				$('#ig_es_workflow_save #publish').on('click', function(e){
					let trigger_name = $('.js-trigger-select').val();

					if ( '' === trigger_name) {
						e.preventDefault();
						alert( ig_es_workflows_data.no_trigger_message );
						return;
					}

					let actions = $('.ig-es-action:not([data-action-number=""]) .js-action-select');
					if ( 0 === $( actions ).length ) {
						e.preventDefault();
						alert( ig_es_workflows_data.no_actions_message );
						return;
					} else {
						$(actions).each(function() {
							let action_name = $(this).val();
							// Check if user have selected an action or not.
							if( '' === action_name ) {
								e.preventDefault();
								// Open the action accordion if is not already open.
								$(this).closest('.ig-es-action:not(.js-open)').find('.ig-es-action__header').trigger('click');
								alert( ig_es_workflows_data.no_action_selected_message );
								return false;
							}
						});
					}
				});
			},

			add_new_action: function() {

				let $new_action,
					action_number = IG_ES_Workflows.get_number_of_actions() + 1;

				$('.js-ig-es-no-actions-message').hide();

				$new_action = $('.ig-es-action-template .ig-es-action').clone();

				IG_ES_Workflows.$actions_container.append($new_action);

				$new_action.attr( 'data-action-number', action_number );

				IG_ES_Workflows.action_edit_open($new_action);
			},

			/**
			 * @param $action
			 */
			action_delete: function( $action ) {
				$action.remove();
			},

			action_edit_close: function( $action ) {

				let action_number = $action.data('action-number');

				$action.removeClass('js-open');
				$action.find('.ig-es-action__fields').slideUp(150);
			},

			action_edit_open: function( $action ) {

				let action_number = $action.data('action-number');

				$action.addClass('js-open');
				$action.find('.ig-es-action__fields').slideDown(150);
			},

			/**
			 *
			 */
			fill_action_fields: function( $action, selected_action ) {

				let action_number = $action.data('action-number');
				action_number     = ( typeof action_number !== 'undefined' && action_number !== '' ) ? action_number : IG_ES_Workflows.get_number_of_actions() + 1;
				let $select       = $action.find('.js-action-select');

				let selected_trigger = $('.js-trigger-select').val();

				IG_ES_Workflows.$actions_box.addClass('ig-es-loading');

				// Remove existing fields
				$action.find('tr.ig-es-table__row:not([data-name="action_name"])').remove();

				$.ajax({
					url: ajaxurl,
					data: {
						action: 'ig_es_fill_action_fields',
						action_name: selected_action,
						action_number: action_number,
						trigger_name: selected_trigger,
						security: ig_es_workflows_data.security
					}
				}).done(function(response){

					$action.find('.ig-es-table tbody').append( response.data.fields );
					IG_ES_Workflows.$actions_box.removeClass('ig-es-loading');

					// Fill select box name
					$select.attr('name', 'ig_es_workflow_data[actions]['+action_number+'][action_name]' );

					// Pre fill title
					$action.find('.action-title').text( response.data.title );

					$action.find('.js-action-description').html( response.data.description );

				});

			},

			get_number_of_actions: function () {
				return $('.ig-es-action:not([data-action-number=""])').length;
			},

			remove_actions: function() {
				let number_of_actions = IG_ES_Workflows.get_number_of_actions();
				if ( number_of_actions > 0 ) {
					let confirm_trigger_change = window.confirm( ig_es_workflows_data.trigger_change_message );
					if ( confirm_trigger_change ) {
						$('.ig-es-action:not([data-action-number=""])').remove();
					}
				}
			}
		}

		IG_ES_Workflows.init();
	});
})(jQuery);
