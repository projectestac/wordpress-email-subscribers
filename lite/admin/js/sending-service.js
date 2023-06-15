if ( 'undefined' !== typeof wp.i18n ) {
    var __ = wp.i18n.__;
} else {
    // Create a dummy fallback function incase i18n library isn't available.
    var __ = ( text, textDomain ) => {
        return text;
    }
}

jQuery(document).ready(function() {

	// Variable used to delay the onboarding tasks progress animations.
	let time_increament = 0;

	// Wrapper objects for onboarding functions.
	let onboarding_functions = {
		perform_configuration_tasks: function() {

			let params = {
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'ig_es_setup_email_sending_service',
					request: 'perform_configuration_tasks',
					security: ig_es_js_data.security
				},
				dataType: 'json',
				success: function(data, status, xhr) {

					if ( data.status && 'success' === data.status ) {
						jQuery(document).trigger('ig_es_perform_configuration_tasks_success');
					} else {
						jQuery(document).trigger('ig_es_perform_configuration_tasks_error');
					}


					let tasks = data.tasks;
					if( jQuery.isPlainObject( tasks ) && ! jQuery.isEmptyObject( tasks ) ) {
						for( let task_name in tasks ) {
							if( tasks.hasOwnProperty( task_name ) ) {
								// time_increament += 100;
								setTimeout(function(){
									ig_es_change_onboard_task_status( 'ig-es-onboard-' + task_name, 'in-progress' );
								},time_increament);

								let task_data    = tasks[ task_name ];
								let task_status  = task_data.status;
								let task_message = task_data.message;
								//time_increament += 1000;
								setTimeout(function(){
									ig_es_change_onboard_task_status( 'ig-es-onboard-' + task_name, task_status, task_message );
								},time_increament);
							}
						}
					}
				},
				error: function(data, status, xhr) {
					ig_es_handle_onboard_task_error( 'perform_configuration_tasks', data, status, xhr );
				}
			};

			jQuery('.active').fadeOut('fast').removeClass('active');
			jQuery('.sp.es-delivery-check').addClass('active').fadeIn('slow');

			jQuery.ajax(params);
		},
		dispatch_emails_from_server: function() {
			let task = 'dispatch_emails_from_server';
			let task_html_elem = 'ig-es-onboard-' + task;
			setTimeout(function(){						
				ig_es_change_onboard_task_status( task_html_elem, 'in-progress' );
			}, time_increament);
			var params = {
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'ig_es_setup_email_sending_service',
					request: 'dispatch_emails_from_server',
					security: ig_es_js_data.security
				},
				dataType: 'json',
				success: function(data, status, xhr) {
					let tasks = data.tasks;
					if( tasks && tasks.hasOwnProperty( 'dispatch_emails_from_server' ) ) {
						let task_data     = tasks[ task ];
						let form_source	  = '';
						if( 'error' === task_data.status ) {
							form_source = 'es_email_send_error';
						} else {
							form_source = 'es_email_send_success';
						}
						jQuery('#ig-es-onboarding-final-steps-form #sign-up-form-source').val(form_source);
					}
					ig_es_handle_onboard_task_response( 'dispatch_emails_from_server', data );
				},
				error: function(data, status, xhr) {
					ig_es_handle_onboard_task_error( 'dispatch_emails_from_server', data, status, xhr );
				}
			};

			jQuery.ajax(params);
		},
		check_test_email_on_server: function() {
			setTimeout(function(){						
				ig_es_change_onboard_task_status( 'ig-es-onboard-check_test_email_on_server', 'in-progress' );
			}, time_increament);
			
			// Add 10s delay while checking for arrival of test email on our server since from some hosts, some delay may happen in receiveing the test email.
			setTimeout(function(){
				var params = {
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'ig_es_setup_email_sending_service',
						request: 'check_test_email_on_server',
						security: ig_es_js_data.security
					},
					dataType: 'json',
					success: function(data, status, xhr) {
						ig_es_handle_onboard_task_response( 'check_test_email_on_server', data );
					},
					error: function(data, status, xhr) {
						ig_es_handle_onboard_task_error( 'check_test_email_on_server', data, status, xhr );
					}
				};
	
				jQuery.ajax(params);
			}, 3000);
		},
		updating_email_delivery_main_task_status: function() {
			setTimeout(function() {
				let unsuccessful_tasks = jQuery('#ig-es-onboard-test-email-delivery-tasks-list li[data-status="error"]');
				// Check if there are any unsuccessfull tasks related to email delivery i.e. having any errors.
				if ( 0 === unsuccessful_tasks.length ) {
					ig_es_change_onboard_task_status( 'ig-es-onboard-test-email-delivery', 'success');
				}else{
					ig_es_change_onboard_task_status( 'ig-es-onboard-test-email-delivery', 'error');
				}
			}, time_increament);
		},
		update_onboarding_step: function( step = 1 ) {
			var params = {
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'ig_es_setup_email_sending_service',
					request: 'update_onboarding_step',
					step: step,
					security: ig_es_js_data.security
				},
				dataType: 'json',
				success: function(data, status, xhr) {},
				error: function(data, status, xhr) {
					ig_es_handle_onboard_task_error( '', data, status, xhr );
				}
			};

			jQuery.ajax(params);
		},
		handle_functions_error_event: function() {
			onboarding_functions.updating_email_delivery_main_task_status();
			setTimeout(function(){
				let email_delivery_error_text = jQuery('#es_delivery_check_processed').data('error-text');
				jQuery('#es_delivery_check_processed').text(email_delivery_error_text);
			}, time_increament)
		}
	};

	jQuery('#ig-ess-optin-cta').on( 'click', function() {
		jQuery('#sending-service-benefits').hide();
		jQuery('#sending-service-onboarding-tasks-list').show();
		ig_es_start_processing_tasks_queue( 'perform_configuration_tasks' );
	});
	jQuery('#ig-es-complete-ess-onboarding').on( 'click', function(){
		var btn_elem = jQuery(this);
		var params = {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'ig_es_setup_email_sending_service',
				request: 'complete_ess_onboarding',
				security: ig_es_js_data.security
			},
			dataType: 'json',
			beforeSend: function() {
				jQuery(btn_elem).addClass('cursor-wait').attr('disabled', true);
				jQuery(btn_elem).find('.es-btn-arrow').hide();
				jQuery(btn_elem).find('.es-btn-loader').show().addClass('animate-spin').attr('disabled', true);
			},
			success: function(response, status, xhr) {
				if ( 'success' === response.status ) {
					jQuery('#sending-service-onboarding-tasks-list').replaceWith(response.html);
				} else {
					alert( ig_es_js_data.i18n_data.ajax_error_message );
				}
			},
			error: function(response, status, xhr) {
				ig_es_handle_onboard_task_error( '', response, status, xhr );
			}
		};

		jQuery.ajax(params);	
	});

	// Variable to hold order of onboarding tasks to be performed.
	let onboarding_functions_queue = [
		'perform_configuration_tasks',
		'dispatch_emails_from_server',
		'check_test_email_on_server',
	];

	jQuery(document).on('ig_es_check_test_email_on_server_success', function(){
		jQuery('#ig-es-complete-ess-onboarding').removeClass('opacity-50 pointer-events-none').find('.button-text').text( __( 'Continue', 'email-subscribers' ) );
	});

	let ig_es_change_onboard_task_status = function(id, status, message = '') {
		
		let task_icon					= jQuery('#'+id + ' div:first');
		let task_list_message   		= jQuery('#'+id + ' p:first');

		jQuery('#' + id).attr('data-status', status);
		if ( 'in-progress' === status) {
	  		task_icon.replaceWith('<div class="relative pt-1 flex items-center justify-center flex-shrink-0 w-5 h-5"><span class="animate-ping absolute w-4 h-4 bg-indigo-200 rounded-full"></span><span class="relative block w-2 h-2 bg-indigo-700 rounded-full"></span></div>');
	  		task_list_message.removeClass().addClass('text-indigo-800 text-sm');
		}

		if( 'success' === status ) {
			task_icon.replaceWith('<div class="relative flex items-center justify-center flex-shrink-0 w-5 h-5"><svg class="mt-1 w-full h-full text-indigo-700 transition duration-150 ease-in-out group-hover:text-indigo-800 group-focus:text-indigo-800" viewBox="0 0 20 20" fill="currentColor" ><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" ></path> </svg></div>');
			task_list_message.removeClass().addClass('text-gray-800 text-sm');
		}

		if( 'error' === status ) {
			task_icon.replaceWith('<div class="relative flex items-center justify-center flex-shrink-0 w-5 h-5"><svg class="mt-1 w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>');
			if( '' === message ){
				task_list_message.removeClass().addClass('text-gray-700 font-normal text-sm');
			} else {
				alert( message );
			}
		}

		let text_css_class = '';
		if( '' !== message ){
			// if( 'error' === status ){
			// 	text_css_class = 'text-gray-700 font-normal';
			// }
			// else{
			// 	text_css_class = 'text-gray-800 ';
			// }
			// task_list_message.replaceWith('<p class="text-sm ' + text_css_class +'">' + message + '</p>');
		}
	}

	let ig_es_show_onboarding_error_popup = function( error_message = '', additional_message = '' ) {
		if ( '' === error_message ) {
			return;
		}

		ig_es_show_onboarding_popup( 'error', error_message, additional_message );
	}

	let ig_es_show_onboarding_popup = function( message_type, message, additional_message = '' ) {
		if ( message_type === '' || '' === message ) {
			return;
		}

		let message_classes = '';
		if( 'error' === message_type ) {
			message_classes = 'bg-red-50';
		}

		jQuery('.es-popup-message .message').removeClass('bg-red-50').addClass(message_classes).html(message);
		jQuery('.additional-message').text( additional_message );
		jQuery('.es-popup-message').removeClass('error info warning').addClass(message_type).fadeIn('slow');
	}
	
	let ig_es_handle_onboard_task_response = function( task, response ) {
		let task_html_elem = 'ig-es-onboard-' + task;
		let tasks = response.tasks;
		if( jQuery.isPlainObject( tasks ) && ! jQuery.isEmptyObject( tasks ) && tasks.hasOwnProperty( task ) ) {
			if( tasks.hasOwnProperty( task ) ) {
				let task_data     = tasks[ task ];
				let task_status   = task_data.status;
				let error_message = '';
				let additional_message = '';
				if( 'undefined' !== typeof task_data.additional_message ){
					additional_message = task_data.additional_message;
				}
				if( 'error' === task_data.status ) {
					// Get task specific error messages if found.
					if( 'undefined' !== typeof task_data.message ) {
						if( Array.isArray( task_data.message ) ) {
							jQuery(task_data.message).each(function(index, message) {
								error_message += message;
							})
						} else {
							error_message += task_data.message;
						}
					} else {
						// Get generic error message if there is not any specific error message.
						error_message = ig_es_ess_onboarding_data.error_message;
					}
				} 
				
				//time_increament += 1000;
				setTimeout(function(){
					ig_es_change_onboard_task_status( task_html_elem, task_status );
					jQuery(document).trigger( 'ig_es_' + task + '_' + task_status );
				},time_increament);
				
			}
		}

	}

	let ig_es_handle_onboard_task_error = function( task_name = '', data, status, xhr) {
		if( 'error' === status ) {
			setTimeout(function(){
				alert( ig_es_ess_onboarding_data.error_message );
				if( '' !== task_name ) {
					let task_html_elem_id = 'ig-es-onboard-' + task_name;
					ig_es_change_onboard_task_status(task_html_elem_id, 'error');
				}
			}, time_increament)
		}
	}

	let onboarding_tasks_done   = ig_es_ess_onboarding_data.ig_es_ess_onboarding_tasks_done;
	let onboarding_tasks_failed = ig_es_ess_onboarding_data.ig_es_ess_onboarding_tasks_failed;
	let successful_email_tasks_count   = 0;
	if( jQuery.isPlainObject( onboarding_tasks_done ) && ! jQuery.isEmptyObject( onboarding_tasks_done ) ) {
		for( let task_group in onboarding_tasks_done ) {
			jQuery(onboarding_tasks_done[task_group]).each(function(index, task_name){
				if( 'email_delivery_check_tasks' === task_group ) {
					successful_email_tasks_count++;
				}
				ig_es_change_onboard_task_status( 'ig-es-onboard-' + task_name, 'success' );
			});
		}
	}

	let unsuccessful_email_tasks_count = 0;
	if( jQuery.isPlainObject( onboarding_tasks_failed ) && ! jQuery.isEmptyObject( onboarding_tasks_failed ) ) {
		for( let task_group in onboarding_tasks_failed ) {
			jQuery(onboarding_tasks_failed[task_group]).each(function(index, task_name){
				if( 'email_delivery_check_tasks' === task_group ) {
					unsuccessful_email_tasks_count++;
				}
				ig_es_change_onboard_task_status( 'ig-es-onboard-' + task_name, 'error' );
			});
		}
		jQuery('#ig-es-complete-ess-onboarding').addClass('opacity-50 pointer-events-none');
	}
	if( successful_email_tasks_count > 0 ) {
		// If there aren't any failed tasks for email test tasks them make main tasks as successful.
		if( unsuccessful_email_tasks_count <= 0 ) {
			ig_es_change_onboard_task_status( 'ig-es-onboard-test-email-delivery', 'success');
		} else {
			ig_es_change_onboard_task_status( 'ig-es-onboard-test-email-delivery', 'in-progress');
		}
	}	

	let ig_es_start_processing_tasks_queue = function( current_task = '' ) {
		if ( '' === current_task ) {
			return;
		}

		// Return if it is not in the onboarding functions list.
		if( ! onboarding_functions.hasOwnProperty( current_task ) || 'function' !== typeof onboarding_functions[current_task] ) {
			return;
		}
		let current_task_index = onboarding_functions_queue.indexOf(current_task);
		if( current_task_index > -1 ) {
			// Remove current task from queue since we are going to call it straight away. Others tasks will be bind to success event of previous task.
			//onboarding_functions_queue.splice(current_task_index, 1);
		}
		jQuery(onboarding_functions_queue).each(function(index, onboarding_function){
			if( index === 0 ) {
				return;
			}
			if( onboarding_functions.hasOwnProperty( onboarding_function ) && 'function' === typeof onboarding_functions[onboarding_function] ) {
				// Other functions will be triggered only if previous one has been done.
				jQuery(document).on( 'ig_es_' + onboarding_functions_queue[index - 1] + '_success', onboarding_functions[onboarding_function]);

				// Bind a function which will handle error on the queue processing.
				jQuery(document).on( 'ig_es_' + onboarding_functions_queue[index - 1] + '_error', onboarding_functions.handle_functions_error_event);
			}
		});
		if( onboarding_functions.hasOwnProperty( current_task ) && 'function' === typeof onboarding_functions[current_task] ) {
			onboarding_functions[current_task]();
		}
	}

	let next_task = ig_es_ess_onboarding_data.next_task;
	if( '' !== next_task ) {
		ig_es_start_processing_tasks_queue( next_task );
	}
});
