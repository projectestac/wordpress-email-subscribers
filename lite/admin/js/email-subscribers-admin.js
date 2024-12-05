(function ($) {

	$(document).ready(
		function () {

			// When we click outside, close the dropdown
			$(document).on("click", function (event) {
				var $trigger = $("#ig-es-add-tags-button");
				if ($trigger !== event.target && !$trigger.has(event.target).length) {
					$("#ig-es-tags-dropdown").hide();
				}
			});

			// Toggle Dropdown
			$('body')
			.on('click', '#ig-es-add-tags-button', function (e) {
				e.preventDefault();
			$('#ig-es-tags-dropdown').toggle();
			});

			// When we click outside, close the dropdown
			$(document).on("click", function (event) {
				var $trigger = $("#ig-es-add-tag-icon");
				if ($trigger !== event.target && !$trigger.has(event.target).length) {
					$("#ig-es-tag-icon-dropdown").hide();
				}
			});

			var clipboard = new ClipboardJS('.ig-es-merge-tag', {
				text: function(trigger) {
						let tag_text = $(trigger).data('tag-text');
						if ( '' === tag_text ) {
							tag_text = $(trigger).text();
						}
						return tag_text.trim();
				}
			});

			clipboard.on('success', function(e) {
				let sourceElem    = e.trigger;
				let clipBoardText = e.text;
				let targetID      = $(sourceElem).closest('.merge-tags-wrapper').data('target-elem-id');
				var target        = document.getElementById(targetID);

				if (target.setRangeText) {
					target.focus();
					//if setRangeText function is supported by current browser
					target.setRangeText(clipBoardText);
				} else {
					target.focus()
					document.execCommand('insertText', false /*no UI*/, clipBoardText);
				}
				if ( 'undefined' !== typeof tinymce.activeEditor ) {
					tinymce.activeEditor.execCommand('mceInsertContent', false, clipBoardText);
				}
			});

			var $newDiv = $("<div/>").addClass("pt-2 pb-2").html(`<div class="ig_es_process_message">Page <span id="ig_es_page_number">1</span> is processing <svg class="es-btn-loader animate-spin h-4 w-4 text-indigo inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
			<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
			<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
			</svg> </div>`);

			
			var getUrlParameter = function getUrlParameter(sParam) {
				var sPageURL = window.location.search.substring(1),
					sURLVariables = sPageURL.split('&'),
					sParameterName,
					i;
			
				for (i = 0; i < sURLVariables.length; i++) {
					sParameterName = sURLVariables[i].split('=');
			
					if (sParameterName[0] === sParam) {
						return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
					}
				}
				return false;
			};

			
			$('.es-audience-view table.contacts #cb-select-all-1').click(function (e) {

				if($('.es-audience-view table.contacts #cb-select-all-1').prop('checked') == true){
					flag = confirm( ig_es_js_data.i18n_data.confirm_select_all );					
				}

				if( flag ) {
					$('.es-audience-view .tablenav.top #doaction').click(function (e) {
						e.preventDefault();
						let actionData = $(this).closest('form').serializeArray();
						let unchecked_subscriber_checkboxes = $('.es-audience-view form input[type="checkbox"][name="subscribers[]"]:not(:checked)');
						let exclude_subscribers = [];
						if ( unchecked_subscriber_checkboxes.length > 0 ) {
							$(unchecked_subscriber_checkboxes).each((index,unchecked_subscriber_checkbox) => {
								let unchecked_subscriber_id = $(unchecked_subscriber_checkbox).val();
								exclude_subscribers.push(unchecked_subscriber_id);
							});
						}
						actionData.push({ name: "exclude_subscribers", value: exclude_subscribers });
						actionData.push({ name: "is_ajax", value: true });
						let pageNumber = getUrlParameter('paged');
						pageNumber = pageNumber ? pageNumber : 1;
						ig_es_apply_contacts_bulk_action( actionData, pageNumber );
						$(".es-audience-view table.contacts").addClass("ig_es_contacts_table");
						
					});
				}
							

			});


			function ig_es_apply_contacts_bulk_action( actionData, pageNumber ) {
				jQuery.ajax({
					method: 'POST',
					url: location.href,
					data: actionData,
					dataType: 'json',
					beforeSend: function () {
						$($newDiv).find("#ig_es_page_number").text(pageNumber);
						$('.es-audience-view .tablenav.top').append($newDiv);
					},
					success: function (response) {
						if ( 'undefined' !== typeof response.success  ) {
							if (response.success) {
								if ( ! response.data.completed ) {									
									actionData.push({name: 'paged', value: response.data.paged });
									actionData.push({name: 'total_pages', value: response.data.total_pages });
									actionData.push({name: 'start_page', value: response.data.start_page });
									ig_es_apply_contacts_bulk_action( actionData, response.data.paged );									
								} else {									
									$('.ig_es_process_message').text('Process completed , reloading the page!');
									let current_url = new URL(window.location.href);
									let bulk_action = response.data.bulk_action;
									
									setTimeout(()=>{
										current_url.searchParams.append('bulk_action', bulk_action);
										window.location.href = current_url;								
									},1000);
								}
							} else {

								if( true !== response.data.errortype ) {
									alert(response.data.message);
								}

								if( true == response.data.errortype ) {
									if ( ! response.data.completed ) {
										actionData.push({name: 'paged', value: response.data.paged });
										actionData.push({name: 'total_pages', value: response.data.total_pages });
										actionData.push({name: 'start_page', value: response.data.start_page });
										ig_es_apply_contacts_bulk_action( actionData, response.data.paged );
									} else 
									{
										$('.ig_es_process_message').text('Process completed , reloading the page!');
										let current_url = new URL(window.location.href);
										let bulk_action = response.data.bulk_action;
										
										setTimeout(()=>{
											current_url.searchParams.append('bulk_action', bulk_action);
											window.location.href = current_url;								
										},1000);
									}							

								}

							}
						} else {
							alert( response.i18n_data.ajax_error_message );
						}
					},
					error: function (err) {
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					}
				});
			}







			// Toggle Dropdown
			$('#ig-es-add-tag-icon').click(function () {
				$('#ig-es-tag-icon-dropdown').toggle();
			});

			$('.es-preview-report').click(function(){

				let campaign_id 	= $(this).data('campaign-id');
				let campaign_type 	= $(this).data('campaign-type');
				let elem = $(this);

				let preview_data = {
					action       : 'ig_es_preview_email_report',
					security     : ig_es_js_data.security,
					campaign_id  : campaign_id,
					campaign_type: campaign_type,
				};


				jQuery.ajax({
					method: 'POST',
					url: ajaxurl,
					data: preview_data,
					dataType: 'json',
					beforeSend: function() {
						$(elem).next('.es-preview-loader').show();
					},
					success: function (response) {
						if (response.success) {
							if ( 'undefined' !== typeof response.data ) {
								let response_data = response.data;
								let template_html = response_data.template_html;
								$('#report_preview_template').load().show();
								ig_es_load_iframe_preview('.report_preview_container', template_html);
							}
						} else {
							alert( ig_es_js_data.i18n_data.ajax_error_message );
						}
					},
					error: function (err) {
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					}
				}).always(function(){
					$(elem).next('.es-preview-loader').hide();
				});
			});

			$('.es_template_preview').click(function(){
				let template_id 	= $(this).data('post-id');
				let elem = $(this);

				let preview_data = {
					action       : 'ig_es_preview_template',
					security     : ig_es_js_data.security,
					template_id  : template_id,
				};

				jQuery.ajax({
					method: 'POST',
					url: ajaxurl,
					data: preview_data,
					dataType: 'json',
					beforeSend: function() {
						$(elem).next('.es-template-preview-loader').show();
					},
					success: function (response) {
						if (response.success) {
							if ( 'undefined' !== typeof response.data ) {
								let response_data = response.data;
								let template_html = response_data.template_html;
								$('.template_preview_container').html(template_html);
								$('#es_preview_template').load().show();
							}
						} else {
							alert( ig_es_js_data.i18n_data.ajax_error_message );
						}
					},
					error: function (err) {
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					}
				}).always(function(){
					$(elem).next('.es-template-preview-loader').hide();
				});
			});

			$(document).on('change', '.es_visible', function () {
				if ($('.es_visible:checked').length >= 1) {
					$('.es_required').prop('disabled', false);
					$('.es_name_label').removeAttr('disabled');
				} else {
					$('.es_required').prop('disabled', true);
					$('.es_name_label').attr('disabled', 'disabled');
				}
			});

			$('.es_visible').change();

			$('.setting-content').hide();
			var settings_tab = window.location.hash;
			var settings_tab_link = $('a[href="' + settings_tab +'"]');
		    if( settings_tab && settings_tab_link.length > 0 ){
		        settings_tab_link.addClass('active').parent('li').addClass('active');
		        $(settings_tab).show();
		    } else {
		        $('#menu-nav li:first-child').addClass('active').find('a').addClass('active');
				$('.setting-content:first').show();
		    }

			$('#menu-nav li').click(function(){
				$('#menu-nav li,#menu-nav li a').removeClass('active');
				$(this).addClass('active').find('a').addClass('active');
				$('.setting-content').hide();
				var activeTab = $(this).find('a').attr('href');
				$(activeTab).show();
				// Trigger resize event to fix scroll issue in the API tab.
				$(document).trigger('resize');
				return false;
			});

			if(jQuery('#es_allow_contact').is(":checked")){
				jQuery('#es_list_label').show();
			}
			jQuery(document).on('change', '#es_allow_contact' , function(e) {
				if(jQuery(this).is(":checked")){
					jQuery('#es_list_label').show();
				}else{
					jQuery('#es_list_label').hide();
				}
			});

			jQuery(document).on('change', '.es-email-status-container #es_email_status' , function(e) {
				let send_optin_emails_toggle_container = jQuery('.step2-send-optin-emails');
				if ('0' === jQuery(this).val() || 'unsubscribed' === jQuery(this).val()) {
					send_optin_emails_toggle_container.hide();
					jQuery("#send_optin_emails").prop("checked", false);
				} else {
					send_optin_emails_toggle_container.show();
				}
			});

			if(jQuery('#show_in_popup').is(":checked")){
				jQuery('#popup_input_block').show();
			}
			jQuery(document).on('change', '#show_in_popup' , function(e) {
				if(jQuery(this).is(":checked")){
					jQuery('#popup_input_block').show();
				}else{
					jQuery('#popup_input_block').hide();
				}
			});
	

			let action_after_submit = $(".ig_es_action_after_submit:checked").val()
			change_block_as_per_action(action_after_submit);

			$("input:radio[name='form_data[settings][action_after_submit]']").click(function() {
				let action_after_submit = $(this).val();
				change_block_as_per_action(action_after_submit);
			});

			function change_block_as_per_action( action_after_submit = '' ) {
				if ( 'show_success_message' === action_after_submit ) {
					$('#show_message_block').removeClass('hidden');
					$('#show_redirect_to_url_block').addClass('hidden');
				} else {
					$('#show_redirect_to_url_block').removeClass('hidden');
					$('#show_message_block').addClass('hidden');
				}				
			}


			$("#broadcast_form .pre_btn, #broadcast_form #content_menu").click(function() {
				var fieldset = $(this).closest('.es_fieldset');
				fieldset.find('.es_broadcast_first').fadeIn('normal');
				fieldset.next().find('.es_broadcast_second').hide();

				fieldset.find('#broadcast_button').show();
				fieldset.find('#broadcast_button1, #broadcast_button2').hide();

				$('#summary_menu').removeClass("active");
				$('#content_menu').addClass("active");
				//$('.active').removeClass('active').prev().addClass('active');

			});

			$("#campaign_form #view_campaign_content_button,#campaign_form #campaign_content_menu,.es-first-step-tab, #view_form_content_button").click(function() {
				var fieldset = $(this).closest('.es_fieldset');
				fieldset.find('.es_campaign_first,.es-first-step').fadeIn('normal');
				fieldset.next().find('.es_campaign_second,.es-second-step').hide();

				fieldset.find('#view_campaign_summary_button, #view_campaign_preview_button,.es-first-step-buttons-wrapper').show();
				fieldset.find('#view_campaign_content_button, #campaign_summary_actions_buttons_wrapper,.es-second-step-buttons-wrapper').hide();

				$('#campaign_summary_menu,.es-second-step-tab').removeClass("active");
				$('#campaign_content_menu,.es-first-step-tab').addClass("active");
			});

			let schedule_option = $('input:radio[name="data[scheduling_option]"]:checked').val()
			broadcast_send_option_change_text(schedule_option);

			$("input:radio[name='data[scheduling_option]']").click(function() {
				let scheduling_option = $(this).val();
				broadcast_send_option_change_text(scheduling_option);
			});

			function broadcast_send_option_change_text( scheduling_option = 'Schedule' ) {
				if ( 'schedule_later' === scheduling_option ) {
					$('.display_schedule').removeClass('hidden');
					$('.ig_es_broadcast_send_option_text').text('Schedule');

				} else {
					$('.display_schedule').addClass('hidden');
					$('.ig_es_broadcast_send_option_text').text('Send');
				}
			}



			$('#preview_template').hide();
			$('#spam_score_modal').hide();

			$("#close_template").on('click', function (event) {
				event.preventDefault();
				$('#preview_template').hide();
			});

			$("#close-unsubscribe-feedback-popup").on('click', function (event) {
				event.preventDefault();
				$('#unsubscribe-feedback-popup').hide();
			});

			$("#close-campaign-preview-popup").on('click', function (event) {
				event.preventDefault();
				$('#campaign-preview-popup').hide();
			});

			if (jQuery('.statusesselect').length) {
				var statusselect = jQuery('.statusesselect')[0].outerHTML;
			}

			if (jQuery('.groupsselect').length) {
				var groupselect = jQuery('.groupsselect')[0].outerHTML;
			}

			// Audience filter switch for Advanced filter

			jQuery('.ig-es-switch.js-toggle-collapse').click(function(event){
				event.preventDefault();
				let $switch, state, new_state;
				$switch = jQuery(this);
				state     = $switch.attr( 'data-ig-es-switch' );
				new_state = state === 'active' ? 'inactive' : 'active';
				$switch.attr( 'data-ig-es-switch', new_state );
				if(new_state === 'active'){
					jQuery('.es-collapsible').show();
				}
				else{
					jQuery('.es-collapsible').hide();
				}
			})

			//Upsell Send confirmation email on Audience screen
			$(".email-subscribers_page_es_subscribers #bulk-action-selector-top option[value=bulk_send_confirmation_email_upsell]").attr('disabled','disabled');
			jQuery(".es-audience-view .bulkactions #bulk-action-selector-top").after(statusselect);
			jQuery(".es-audience-view .bulkactions #bulk-action-selector-top").after(groupselect);

			jQuery('.groupsselect').hide();
			jQuery('.statusesselect').hide();

			jQuery("#bulk-action-selector-top").change(function () {
				if (jQuery('option:selected', this).attr('value') == 'bulk_list_update' || jQuery('option:selected', this).attr('value') == 'bulk_list_add') {
					jQuery('.groupsselect').eq(1).show();
					jQuery('.statusesselect').eq(1).hide();
				} else if (jQuery('option:selected', this).attr('value') == 'bulk_status_update') {
					jQuery('.statusesselect').eq(1).show();
					jQuery('.groupsselect').eq(1).hide();
				} else {
					jQuery('.statusesselect').hide();
					jQuery('.groupsselect').hide();
				}
			});

			jQuery('.es-audience-view .tablenav.bottom #bulk-action-selector-bottom').hide();
			jQuery('.es-audience-view .tablenav.bottom #doaction2').hide();
			jQuery(document).on('change', "#base_template_id", function () {
				var img = jQuery('option:selected', this).data('img-url');
				jQuery('.es-templ-img').html('<img src="' + img + '"/>');
			});

			//send test emails
			$(document).on('click', '#es-send-test', function (e) {
				e.preventDefault();
				var test_email = $('#es-test-email').val();
				if (test_email) {
					var params = {
						es_test_email: test_email,
						action: 'es_send_test_email',
						security: ig_es_js_data.security
					};
					$('#es-send-test').next('#spinner-image').show();
					jQuery.ajax({
						method: 'POST',
						url: ajaxurl,
						data: params,
						dataType: 'json',
						success: function (response) {
							if (response && typeof response.status !== 'undefined' && response.status == "SUCCESS") {
								let successMessageHTML = '<span style="color:green">' + response.message + '</span>';
								//$('#es-send-test').parent().find('.helper').html(successMessageHTML);
								$('#es-send-test').closest('td').find('.helper').html(successMessageHTML);	
							} else {
								let errorMessageHTML = '<span style="color:#e66060"><strong>' + ig_es_js_data.i18n_data.sending_error_text + '</strong>: ' + ( Array.isArray( response.message ) ? response.message.join() : response.message ) + '</span>';
								//$('#es-send-test').parent().find('.helper').html(errorMessageHTML);
								$('#es-send-test').closest('td').find('.helper').html(errorMessageHTML);	
							}

							$('#es-send-test').next('#spinner-image').hide();
						},

						error: function (err) {
							$('#es-send-test').next('#spinner-image').hide();
						}
					});
				} else {
					confirm('Add test email ');
				}

			});

			//klawoo form submit
			jQuery("form[name=klawoo_subscribe]").submit(function (e) {
				e.preventDefault();
				var form = e.target;
				jQuery(form).find('#klawoo_response').html('');
				jQuery(form).find('#klawoo_response').show();

				params = jQuery(form).serializeArray();
				params.push({
					name: 'action',
					value: 'es_klawoo_subscribe',
				});

				// Add ajax security nonce.
				params.push({
					name: 'security',
					value: ig_es_js_data.security,
				});

				jQuery.ajax({
					method: 'POST',
					type: 'text',
					url: ajaxurl,
					async: false,
					data: params,
					success: function (response) {
						if (response != '') {
							jQuery('#klawoo_response').html(response);
							if (jQuery(form).hasClass('es-onboarding')) {
								setTimeout(function () {
									location.reload();
								}, 2000);
							} else {
								jQuery('.es-emm-optin #name').val('');
								jQuery('.es-emm-optin #email').val('');
								jQuery('.es-emm-optin #es-gdpr-agree').attr('checked', false);
								setTimeout(function () {
									jQuery(form).find('#klawoo_response').hide('slow');
								}, 2000);
							}


						} else {
							jQuery('#klawoo_response').html('error!');
						}
					}
				});

			});


			// Select List ID for Export
			var _href = $('#ig_es_export_link_select_list').attr("href");
			$('#ig_es_export_list_dropdown').change(function () {
				var selected_list_id = $(this).val();

				$('#ig_es_export_link_select_list').attr("href", _href + '&list_id=' + selected_list_id);

				// Update total count in lists
				var params = {
					action: 'count_contacts_by_list',
					list_id: selected_list_id,
					security: ig_es_js_data.security,
				};

				$.ajax({
					method: 'POST',
					url: ajaxurl,
					async: false,
					data: params,
					success: function (response) {
						if (response != '') {
							response = JSON.parse(response);
							$('#ig_es_export_select_list .ig_es_total_contacts').text(response.total);
						}
					}
				});

			});

			// Filtering campaign status based of type
			var campaign_type = $('#ig_es_filter_campaign_type').val();
			campaign_status(campaign_type);

			$('#ig_es_filter_campaign_type').change(function (e) {
				var campaign_type = $(this).val();
				$('#ig_es_filter_campaign_status_by_type').val('');
				campaign_status(campaign_type);
			});

			$('#ig_es_filter_reports_by_campaign_type option[value="sequence"]').hide();

			function campaign_status( campaign_type ) {
				var $status_id = $('#ig_es_filter_campaign_status_by_type');
				switch (campaign_type) {
					case 'newsletter':
						$status_id.children('option').show();
						$('#ig_es_filter_campaign_status_by_type option[value="0"]').html('Draft').show();
						$('#ig_es_filter_campaign_status_by_type option[value="1"]').hide();
						break;
					case 'post_notification':
					case 'post_digest':
					case 'sequence':
						$status_id.children('option').hide();
						$('#ig_es_filter_campaign_status_by_type option[value=""],option[value="1"]').show();
						$('#ig_es_filter_campaign_status_by_type option[value="0"]').html('In Active').show();
						break;
					default:
						$status_id.children('option').show();
						break;
				}
			}


			jQuery('#ig-es-log-files').ig_es_select2();
		

			// Broadcast Setttings
			$('#ig_es_campaign_submit_button').attr("disabled", true);

			let update_contacts_counts_xhr = {};
			let edited_campaign_data = {}; // Variable to hold campaign id and name of edited campaign which is used in showing updated options select campaign field and updated HTML for conditions.
			jQuery(document).on('bind_campaign_rules_events', function( e, data ){
				if ( 'undefined' !== typeof data && 'undefined' !== typeof data.conditions_elem ) {
					conditions_elem = data.conditions_elem;
				} else {
					conditions_elem = jQuery('.ig-es-conditions');
				}

				jQuery.each(jQuery(conditions_elem), function () {
					var _self = jQuery(this),
						conditions = _self.find('.ig-es-conditions-wrap'),
						groups = _self.find('.ig-es-condition-group'),
						cond = _self.find('.ig-es-condition');

					jQuery.each(edited_campaign_data,function(campaign_id, campaign_name){
						let option        = jQuery(conditions_elem).find('.ig-es-campaign-select-field option[value="' + campaign_id + '"]');
						let option_exists = jQuery(option).length > 0;
						let new_option    = new Option(campaign_name, campaign_id, false, false);
						// Add new option don't exists else update option's text
						if( ! option_exists ){
							jQuery(conditions_elem).find('.ig-es-campaign-select-field').append(new_option);
						} else {
							jQuery(option).text(campaign_name);
						}
					});

					groups.eq(0).appendTo(_self.find('.ig-es-condition-container'));

					_self
						.on('click', '.add-condition', function () {
							ig_es_add_and_condtion();
						})
						.on('click', '.add-or-condition', function () {
							var cont = jQuery(this).parent(),
								id = cont.find('.ig-es-condition').last().data('id'),
								clone = cond.eq(0).clone();

							clone.removeAttr('id').appendTo(cont).data('id', ++id);
							jQuery.each(clone.find('input, select'), function () {
								var _this = jQuery(this),
									name = _this.attr('name');
								// match and replace regex '][any digit][any digit]' with '][AND rule counter][OR rule counter]'
								_this.attr('name', name.replace(/\]\[\d+\]\[\d+\]/, '][' + cont.data('id') + '][' + id + ']')).prop('disabled', false);
								name = _this.attr('name');
								if( jQuery(_this).hasClass('ig-es-campaign-rule-form-multiselect') ) {
									jQuery(_this).ig_es_select2();
								}
								if( jQuery(_this).hasClass('condition-field')) {
									ig_es_handle_list_condition();
								}
							});
							clone.find('.condition-field').val('').trigger('focus');
							cond = _self.find('.ig-es-condition');
						})

						jQuery(_self).closest('.ig-es-campaign-rules').find('.close-conditions').on('click', function(){
							jQuery(document).trigger('ig_es_update_contacts_counts',[{condition_elem:_self}]);
						});

						jQuery(_self).closest('.ig-es-campaign-rules').find('.remove-conditions').on('click', function () {
							if (confirm(ig_es_js_data.i18n_data.remove_conditions_message)) {
								jQuery(conditions).empty();
								jQuery(_self).closest('.ig-es-campaign-rules').find('.ig-es-conditions-render-wrapper').empty();
								jQuery(document).trigger('ig_es_update_contacts_counts',[{condition_elem:_self}]);
							}
							return false;
						});
					conditions
						.on('click', '.remove-condition', function () {
							var c = jQuery(this).parent();
							if (c.parent().find('.ig-es-condition').length == 1) {
								c = c.parent();
							}
							c.slideUp(100, function () {
								jQuery(this).remove();
								ig_es_handle_list_condition();
								jQuery(document).trigger('ig_es_update_contacts_counts',[{condition_elem:_self}]);
							});
						})
						.on('change', '.condition-field', function (event) {

							var condition = jQuery(this).closest('.ig-es-condition'),
								field = jQuery(this);
							ig_es_show_operator_and_value_field(field);
							jQuery(document).trigger('ig_es_update_contacts_counts',[{condition_elem:_self}]);
						})
						.on('change', '.condition-operator', function () {
							jQuery(document).trigger('ig_es_update_contacts_counts',[{condition_elem:_self}]);
						})
						.on('change', '.condition-value', function () {
							jQuery(document).trigger('ig_es_update_contacts_counts',[{condition_elem:_self}]);
						})
						.on('click', '.ig-es-condition-add-multiselect', function () {
							jQuery(this).parent().clone().insertAfter(jQuery(this).parent()).find('.condition-value').select().trigger('focus');
							return false;
						})
						.on('click', '.ig-es-condition-remove-multiselect', function () {
							jQuery(this).parent().remove();
							jQuery(document).trigger('ig_es_update_contacts_counts',[{condition_elem:_self}]);
							return false;
						})
						.on('change', '.ig-es-conditions-value-field-multiselect > .condition-value', function () {
							if (0 == jQuery(this).val() && jQuery(this).parent().parent().find('.condition-value').size() > 1) jQuery(this).parent().remove();
						})
						.find('.condition-field').prop('disabled', false).trigger('change');

					jQuery(document).trigger('ig_es_update_contacts_counts',[{condition_elem:_self}]);

					// Add one list condition if there are no conditions.
					if( 0 === jQuery(_self).find('.ig-es-conditions-wrap .ig-es-condition-group').length ) {
						ig_es_add_default_list_condition();
					} else {
						jQuery(_self).find('.ig-es-conditions-wrap .ig-es-condition-group .condition-value').each(function(){
							if( jQuery(this).hasClass('ig-es-campaign-rule-form-multiselect') ) {
								jQuery(this).ig_es_select2();
							}
						});
					}

					function ig_es_add_and_condtion( condition_data ) {
						let id = groups.length,
								clone = groups.eq(0).clone();

						clone.removeAttr('id').appendTo(conditions).data('id', id).show();
						jQuery.each(clone.find('input, select'), function () {
							let _this = jQuery(this);
								name = _this.attr('name');
							// match and replace regex '][any digit]' with '][id]' i.e. AND rule counter
							_this.attr('name', name.replace(/\]\[\d+\]/, '][' + id + ']')).prop('disabled', false);

							if( jQuery(_this).hasClass('ig-es-campaign-rule-form-multiselect') ) {
								jQuery(_this).ig_es_select2();
							}
						});

						if ( 'undefined' === typeof condition_data ) {
							condition_data = {
								condition: '',
							}
						}
						let condition = condition_data.condition;

						let condition_field = clone.find('.condition-field');

						jQuery(condition_field).val(condition).trigger('focus');

						if ( '' !== condition ) {
							ig_es_show_operator_and_value_field(condition_field);
						}

						groups = _self.find('.ig-es-condition-group');
						cond = _self.find('.ig-es-condition');
						ig_es_handle_list_condition();
					}

					function ig_es_handle_list_condition( selected_elem ) {
						if ( ig_es_js_data.is_pro ) {
							return;
						}

						var condition_fields = jQuery('.ig-es-conditions-wrap .condition-field');
						var list_rule_count = 0;
						jQuery(condition_fields).each(function(){
							var selected_rule = jQuery(this).val();
							if ( '_lists__in' === selected_rule ) {
								list_rule_count++;
							}
						});
						var disable_list_rule = list_rule_count > 0;
						var campaign_rules = jQuery('.ig-es-conditions-wrap .condition-field');
						jQuery(campaign_rules).each(function(index,elem){
							var list_rule_option = jQuery(this).find('option[value = "_lists__in"]');
							var list_rule_text   = jQuery(list_rule_option).text();
							list_rule_text       = list_rule_text.replace(' [MAX]','');
							if ( 'undefined' !== typeof selected_elem ) {
								if( disable_list_rule && ! ( jQuery(selected_elem)[0] === elem ) ) {
									list_rule_text += ' [MAX]';
									jQuery(list_rule_option).prop("selected", false).attr('disabled','disabled');
								} else {
									jQuery(list_rule_option).removeAttr('disabled');
								}
							} else {
								if( index > 0 && disable_list_rule ) {
									list_rule_text += ' [MAX]';
									jQuery(list_rule_option).prop("selected", false).attr('disabled','disabled');
								} else {
									jQuery(list_rule_option).removeAttr('disabled');
								}
							}
							jQuery(list_rule_option).text(list_rule_text);
						});
					}

					function ig_es_add_default_list_condition() {
						ig_es_add_and_condtion({ condition: '_lists__in' });
					}

					function ig_es_show_operator_and_value_field( field ) {

						var condition = jQuery(field).closest('.ig-es-condition'),
								operator_field, value_field;

						condition.find('div.ig-es-conditions-value-field').removeClass('active').find('.condition-value').prop('disabled', true);
						condition.find('div.ig-es-conditions-operator-field').removeClass('active').find('.condition-operator').prop('disabled', true);

						var field_value = jQuery(field).val();
						condition.find('.ig-es-conditions-operator-fields,.ig-es-conditions-value-fields').attr('data-condition', field_value );

						value_field = condition.find('div.ig-es-conditions-value-field[data-fields*=",' + field_value + ',"]').addClass('active').find('.condition-value').prop('disabled', false);
						operator_field = condition.find('div.ig-es-conditions-operator-field[data-fields*=",' + field_value + ',"]').addClass('active').find('.condition-operator').prop('disabled', false);

						if (!value_field.length) {
							value_field = condition.find('div.ig-es-conditions-value-field-default').addClass('active').find('.condition-value').prop('disabled', false);
						}
						if (!operator_field.length) {
							operator_field = condition.find('div.ig-es-conditions-operator-field-default').addClass('active').find('.condition-operator').prop('disabled', false);
						}

						if ( jQuery(field).hasClass('condition-field') ) {
							ig_es_handle_list_condition(field);
						}
					}
				});
			});

			$(document).on('ig_es_update_contacts_counts', function(e, data){

				let condition_elem           = data.condition_elem;
				let condition_container_elem = $(condition_elem).closest('.ig-es-campaign-rules');
				let selected_list_id         = $('#ig_es_broadcast_list_ids').val();
				let campaign_id              = $(condition_container_elem).data('campaign-id');
				let campaign_type            = $(condition_container_elem).data('campaign-type');
				let conditions = [],
					groups = $(condition_elem).find('.ig-es-conditions-wrap > .ig-es-condition-group'),
					i = 0;

				$.each(groups, function () {
					let c = $(this).find('.ig-es-condition');
					$.each(c, function () {
						let _this = $(this),
							value,
							field = _this.find('.condition-field').val(),
							operator = _this.find('.ig-es-conditions-operator-field.active').find('.condition-operator').val();

						if (!operator || !field) return;

						value = _this.find('.ig-es-conditions-value-field.active').find('.condition-value').map(function () {
							return $(this).val();
						}).toArray();
						if (value.length == 1) {
							value = value[0];
						}

						if (!conditions[i]) {
							conditions[i] = [];
						}

						conditions[i].push({
							field: field,
							operator: operator,
							value: value,
						});
					});
					i++;
				});

				// Return if no list or conditions selected.
				if ( ! selected_list_id && 0 === conditions.length ) {
					$('.ig_es_list_contacts_count').text(0);
					$(condition_container_elem).find('.ig-es-conditions-render-wrapper').html('');
					return;
				}

				let get_count = 'newsletter' === campaign_type || 'post_notification' === campaign_type || 'post_digest' === campaign_type ? 'yes' : 'no'; // Get count only when on broadcast screen

				// Update total count in lists
				let params = {
					action: 'count_contacts_by_list',
					list_id: selected_list_id,
					conditions: conditions,
					status: 'subscribed',
					get_count: get_count,
					security: ig_es_js_data.security
				};

				if ( 'undefined' !== typeof update_contacts_counts_xhr && 'undefined' !== typeof update_contacts_counts_xhr[campaign_id] ) {
					update_contacts_counts_xhr[campaign_id].abort();
				}

				update_contacts_counts_xhr[campaign_id] = $.ajax({
					method: 'POST',
					url: ajaxurl,
					async: true,
					data: params,
					beforeSend: function() {
						$('#spinner-image').show();
					},
					success: function (response) {
						if (response !== '') {
							response = JSON.parse(response);
							if (get_count && response.hasOwnProperty('total')) {
								let total                 = response.total;
								let total_recipients_text = "<div class='mt-1.5 py-2'><span class='font-medium text-base text-gray-700'><span class='ig_es_list_contacts_count'>" + total + "</span> <span class='text-base font-medium text-gray-700'></span><span class='font-normal text-sm text-gray-500'> recipients </span></div>";
								$('#ig_es_total_contacts .ig_es_list_contacts_count').html(total);
								$('#ig_es_total_recipients').html(total_recipients_text);

								$(condition_container_elem).find('.ig-es-total-contacts').text(total);
								if (total == 0) {
									$('#ig_es_campaign_submit_button').attr("disabled", true);
								} else {
									$('#ig_es_campaign_submit_button').attr("disabled", false);
								}
							}
							$(condition_container_elem).find('.ig-es-conditions-render-wrapper').html('');
							if ( response.hasOwnProperty('conditions_html') ) {
								let conditions = $.parseHTML(response.conditions_html);
								$(conditions).find('.campaign-name').each(function(){
									let campaign_id = $(this).data('campaign-id');
									if (edited_campaign_data.hasOwnProperty(campaign_id)) {
										let campaign_name = edited_campaign_data[campaign_id];
										$(this).text(campaign_name);
									}
								});
								$(condition_container_elem).find('.ig-es-conditions-render-wrapper').append($(conditions));
							}

							if ( $(condition_container_elem).find('.ig-es-conditions-wrap .condition-field').length > 0 ) {
								$(condition_container_elem).find('.remove-all-conditions-wrapper').removeClass('hidden');
							} else {
								$(condition_container_elem).find('.remove-all-conditions-wrapper').addClass('hidden');
							}
						}
					}
				}).always(function(){
					$('#spinner-image').hide();
				});;
			});

			// Add/update campaign option in select campaign rule
			jQuery(document).on('ig_es_sequence_name_updated',function(e,seq_data){
				let seq_id     = seq_data.id;
				let seq_name   = seq_data.name;
				let new_option = new Option(seq_name, seq_id, false, false);
				edited_campaign_data[seq_id] = seq_name;
				jQuery('.es_seq_right_wrapper:not([data-seq-id="' + seq_id + '"])').each(function(){
					let current_seq_id = jQuery(this).data('seq-id');

					// We are restricting admin to select sequence campaigns whose id is greater then current sequence id.
					if ( current_seq_id > seq_id ) {
						jQuery(this).find('.ig-es-campaign-select-field').each(function(){
							let option        = jQuery(this).find('option[value = ' + seq_id + ']');
							let option_exists = jQuery(option).length > 0;
							// Add new option don't exists else update option's text
							if( ! option_exists ){
								jQuery(this).append(new_option);
							} else {
								jQuery(option).text(seq_name);
							}
							jQuery(this).ig_es_select2().trigger('change');
						});
					}
				});
			});

			//post notification category select
			jQuery(document).on('change', '.es-note-category-parent', function () {
				var val = jQuery('.es-note-category-parent:checked').val();
				if ( '{a}All{a}' === val || '{a}None{a}' === val ) {
					jQuery('input[name="data[es_note_cat][]"]').not('.es_custom_post_type').closest('tr').hide();
				} else {
					jQuery('input[name="data[es_note_cat][]"]').not('.es_custom_post_type').closest('tr').show();
				}
			});

			jQuery(document).trigger('bind_campaign_rules_events');

			jQuery('.es-note-category-parent').trigger('change');

			jQuery('#tabs-general input[name="ig_es_from_email"]').on('change', function () {
				let from_email        = jQuery(this).val();
				let is_valid_email    = ig_es_is_valid_email(from_email);
				if ( is_valid_email ) {
					let from_email_domain = from_email.split('@')[1].toLowerCase();
					let is_popolar_domain = ig_es_js_data.popular_domains.indexOf(from_email_domain) > -1;
					if ( is_popolar_domain ) {
						jQuery('#ig-es-from-email-notice').removeClass('hidden');
					} else {
						jQuery('#ig-es-from-email-notice').addClass('hidden');
					}
				}
			});

			//es mailer settings
			jQuery(document).on('change', '.es_mailer', function (e) {
				var val = jQuery('.es_mailer:checked').val();
				var wrapper_row = jQuery(this).closest('tr');
				jQuery(wrapper_row).find('[name*="ig_es_mailer_settings"], .es_sub_headline, .field-desciption').not('.es_mailer').hide();
				jQuery(wrapper_row).find('.' + val).show();
			});
			jQuery('.es_mailer').trigger('change');

			//preview broadcast
			// ig_es_preview_broadcast
			jQuery(document).on('click', '#ig_es_preview_broadcast', function (e) {
				ig_es_sync_wp_editor_content();
				if (jQuery('.wp-editor-boradcast').val() !== '') {
					jQuery('.es-form').find('form').attr('target', '_blank');
					jQuery('.es-form').find('form').find('#es_broadcast_preview').val('preview');
					jQuery(this).unbind('submit').submit();
				}
			});

			jQuery(document).on('click', '#ig_es_campaign_submit_button', function (e) {
				if (jQuery('.wp-editor-boradcast').val() !== '') {
					jQuery('.es-form').find('form').attr('target', '');
					jQuery('.es-form').find('form').find('#es_broadcast_preview').val('');
				}
			});


			$('.ig-es-campaign-status-toggle-label input[type="checkbox"]').change(function() {
				let checkbox_elem       = $(this);
				let campaign_id         = $(checkbox_elem).val();
				let new_campaign_status = $(checkbox_elem).prop('checked') ? 1 : 0;
				let data                = {
					action: 'ig_es_toggle_campaign_status',
					campaign_id: campaign_id,
					new_campaign_status: new_campaign_status,
					security: ig_es_js_data.security
				}
				jQuery.ajax({
					method: 'POST',
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function (response) {
						if ( !response.success ) {
							alert( ig_es_js_data.i18n_data.ajax_error_message );
							// Revert back toggle status.
							$(checkbox_elem).prop( 'checked', ! new_campaign_status );
						}
					},
					error: function (err) {
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					}
				});
			});

			$('.ig_es_save_campaign, .ig_es_draft_campaign, #view_campaign_summary_button, #campaign_summary_menu').on('click', function(e) {
				let trigger_elem = $(this);
				ig_es_draft_campaign( trigger_elem );
			});

			$('#ig_es_campaign_subject,#edit-es-campaign-body,#campaign_form #inline_css').on('change',function(e){
				let trigger_elem = $(this);
				ig_es_draft_campaign( trigger_elem );
			});

			$('#save_campaign_btn').on('click', function(e) {
				let trigger_elem = $(this);
				ig_es_save_campaign( trigger_elem );
			});

			$('#save__and_schedule_campaign_btn').on('click', function(e) {
				let trigger_elem = $(this);
				ig_es_save_and_schedule_campaign( trigger_elem );
			});

			$('#activate_campaign_btn').on('click', function(e) {
				let trigger_elem = $(this);
				ig_es_activate_campaign( trigger_elem );
			});

			$("#campaign_form #view_campaign_summary_button, #campaign_form #campaign_summary_menu, .es-second-step-tab, #view_form_summary_button").click(function() {

				let fieldset = $(this).closest('.es_fieldset');
				fieldset.next().find('div.es_campaign_second,.es-second-step').fadeIn('normal');
				fieldset.find('.es_campaign_first,.es-first-step').hide();

				fieldset.find('#view_campaign_content_button,#campaign_summary_actions_buttons_wrapper,.es-second-step-buttons-wrapper').show();
				fieldset.find('#view_campaign_summary_button,#view_campaign_preview_button,.es-first-step-buttons-wrapper').hide();

				$('#campaign_content_menu,.es-first-step-tab').removeClass("active");
				$('#campaign_summary_menu,.es-second-step-tab').addClass("active");
				//$('.active').removeClass('active').next().addClass('active');

				// Trigger template content changed event to update email preview.
				$('textarea[name="data[body]"]').trigger('change');
			});

			$('#edit-campaign-form-container').on('change','.wp-campaign-body-editor, #edit-es-campaign-body,textarea[name="data[body]"],#ig_es_campaign_subject',function(event){

				ig_es_sync_wp_editor_content();

				let form_data = $(this).closest('form').serialize();
				
				//var formData = JSON.stringify($(this).closest('form').serializeArray()); 
				var formData = $(this).closest('form').serializeArray()
				.reduce(function (json, { name, value }) {
				  json[name] = value;
				  return json;
				}, {}); 

				// Add action to form data
				form_data += form_data + '&action=ig_es_get_campaign_preview&preview_type=inline&security='  + ig_es_js_data.security;
				jQuery.ajax({
					method: 'POST',
					url: ajaxurl,
					data: form_data,
					dataType: 'json',
					beforeSend:function(){
						if(jQuery('#campaign_summary_menu').hasClass('active')){
							jQuery('.ig-es-ajax-loader').css("visibility","visible");
						}
					},
					success: function (response) {
						if (response.success) {
							if ( 'undefined' !== typeof response.data ) {
								let response_data      = response.data;
								let preview_html       = response_data.preview_html;
								let campaign_subject   = response_data.campaign_subject;
								let contact_name       = response_data.contact_name;
								let contact_email      = response_data.contact_email;
								$('.campaign_preview_subject').html(campaign_subject);
								$('.campaign_preview_contact_name').html(contact_name);
								if ( '' !== contact_email ) {
									$('.campaign_preview_contact_email').html( '&lt;' + contact_email + '&gt;');
								}

								ig_es_load_iframe_preview('.campaign_preview_content', preview_html);
							}
						} else {
							alert( ig_es_js_data.i18n_data.ajax_error_message );
						}
					},
					error: function (err) {
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					},
					complete:function(){
						jQuery('.ig-es-ajax-loader').css("visibility","hidden");
					}
				});
			});

			$('#es_test_email_btn').on('click', function(){
				let preview_option  = $('[name="preview_option"]:checked').val();
				let template_button = $('#es_test_email_btn');

				$(template_button).parent().find('.es-send-success').hide();
				$(template_button).parent().find('.es-send-error').hide();
				if ( 'preview_in_popup' === preview_option ) {
					ig_es_show_broadcast_preview_in_popup();
				} else if ( 'preview_in_email' === preview_option ) {
					ig_es_send_broadcast_preview_email();
				}
			});

			$('#view_campaign_preview_button').on('click', function(){
				let template_button = $('#view_campaign_preview_button');
				$(template_button).parent().find('.es-send-success').hide();
				$(template_button).parent().find('.es-send-error').hide();
				jQuery(template_button).addClass('loading');
				ig_es_process_campaign_action( 'save' ).then( response => {
					if (response.success) {
						let response_data = response.data;
						let campaign_id   = response_data.campaign_id;
						$('#campaign_id').val( campaign_id );
						ig_es_show_campaign_preview_in_popup();
					} else {
						alert( ig_es_js_data.i18n_data.campaign_preivew_error_message );
					}
				}, response => {
					alert( ig_es_js_data.i18n_data.campaign_preivew_error_message );
				});
			});

			$('#view_template_preview_button').on('click', function(){
				let template_button = $('#view_template_preview_button');
				$(template_button).parent().find('.es-send-success').hide();
				$(template_button).parent().find('.es-send-error').hide();
				//ig_es_show_template_preview_in_popup();
			});

			$('#save_campaign_as_template_button').on('click', function(e){
				e.preventDefault();
				ig_es_save_campaign_as_template();
			});

			$('#campaign_form [name="preview_option"]').on('click',function(){
				let preview_option = $('[name="preview_option"]:checked').val();

				if ( 'preview_in_email' === preview_option ) {
					$('#es_test_send_email').show();
				} else {
					$('#es_test_send_email').hide();
				}
			});

			$('#toggle-sender-details').on('click',function(){
				let toggle_control_element = $(this);
				let sender_details_container_element = $('#sender-details-container');
				let is_visible = $(sender_details_container_element).is(':visible');
				if ( is_visible ) {
					$(sender_details_container_element).hide();
					$(toggle_control_element).removeClass('toggled');
				} else {
					$(sender_details_container_element).show();
					$(toggle_control_element).addClass('toggled');
				}
			});

			// Check email authentication headers
			jQuery('#ig-es-verify-auth-headers').click(function(e){
				e.preventDefault();

				var params = {
					type:'POST',
					url:ajaxurl,
					data:{
						action:'es_send_auth_test_email',
						security:ig_es_js_data.security,
					},
					dataType:'json',
					beforeSend:function(){
						jQuery('#ig-es-verify-auth-headers').next('#spinner-image').show();
					},
					success:function(res){

						if( 'SUCCESS' === res['status'] ){
							let time_delay = 5000;

							setTimeout(function(){
								getEmailAuthHeaders();
							},time_delay);
						}
						else{
							jQuery('#ig-es-verify-auth-headers').next('#spinner-image').hide();
							jQuery('#ig-es-verify-auth-message').addClass('text-red-500').html(ig_es_js_data.i18n_data.error_send_test_email);
						}
					},
					error:function(err){
						jQuery('#ig-es-verify-auth-headers').next('#spinner-image').hide();
						jQuery('#ig-es-verify-auth-message').addClass('text-red-500').html(ig_es_js_data.i18n_data.error_send_test_email);
					},
				}
				jQuery.ajax(params);

			});

			function getEmailAuthHeaders(){

				var params = {
					type:'POST',
					url:ajaxurl,
					data:{
						action:'es_get_auth_headers',
						security:ig_es_js_data.security,
					},
					dataType:'json',
					success:function(res){
						let headerData = [];
						let table_elem = jQuery('#ig-es-settings-authentication-table');
						try {
							headerData = JSON.parse(res.data);

							if( 'undefined' !== table_elem && Array.isArray(headerData) && headerData.length > 0 ){
								populateTableData( table_elem, headerData, false );
								jQuery('#ig-es-verify-auth-message').addClass('text-green-500').html(ig_es_js_data.i18n_data.success_verify_email_headers);
							}
							else{
								jQuery('#ig-es-verify-auth-message').addClass('text-red-500').html(ig_es_js_data.i18n_data.error_server_busy);
							}
						}
						catch(err){
							jQuery('#ig-es-verify-auth-message').addClass('text-red-500').html(ig_es_js_data.i18n_data.error_server_busy);
						}
					},
					error:function(err){
						jQuery('#ig-es-verify-auth-message').addClass('text-red-500').html(ig_es_js_data.i18n_data.error_server_busy);
					},
					complete:function(){
						jQuery('#ig-es-verify-auth-headers').next('#spinner-image').hide();
					}
				}
				jQuery.ajax(params);

			}

			function populateTableData(table_element, data_array,mapByIndex){
				let table_row;
				let row_id,cell_value='';
				let row_data_keys={};
				let table_body = table_element.find('tbody');

				for (let index = 0; index < data_array.length; index++) {
					row_id = (mapByIndex) ? data_array[index] : data_array[index]['key'];
					table_row = table_body.find('tr[data-row-id="'+row_id+'"]');

					row_data_keys = Object.keys(data_array[index]);

					row_data_keys.forEach(key => {
						cell_value = data_array[index][key];

						if( cell_value.length > 30){
							cell_value = cell_value.slice(0,30) + '...';
						}
						table_row.find('td[data-cell-id="'+row_id+"-"+key+'"]').html(cell_value );
					});
				}
			}


			jQuery(document).on( 'click', '#es_check_auth_header', function(e){
				window.location.href = '?page=es_settings&btn=check_auth_header#tabs-email_sending';
				jQuery('html, body').animate({
					scrollTop: jQuery("#ig-es-settings-authentication-table").offset().top
				}, 2000);
			});

			if(window.location.href.indexOf('page=es_settings&section=ess#tabs-email_sending') !== -1){
				jQuery('html, body').animate({
					scrollTop: jQuery("#ig_es_test_send_email-field-row").offset().top
				}, 2000);
			}
			if(window.location.href.indexOf('page=es_settings&btn=check_auth_header#tabs-email_sending') !== -1){
				jQuery('html, body').animate({
					scrollTop: jQuery("#ig-es-settings-authentication-table").offset().top
				}, 2000);
			}
			// Check spam score
			jQuery(document).on('click', '.es_spam' , function(e) {
				e.preventDefault();
				var tmpl_id   = jQuery('.es_spam').next().next('#es_template_id').val();
				var subject   = jQuery('#ig_es_broadcast_subject,#ig_es_campaign_subject').val();
				var content   = jQuery('.wp-editor-boradcast,.wp-campaign-body-editor').val();
				jQuery('.es_spam').next('.es-loader-img').show();

				let from_name  = jQuery( '#from_name' ).val();
				let from_email = jQuery( '#from_email' ).val();

				var params = {
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'es_get_spam_score',
						tmpl_id: tmpl_id,
						subject: subject,
						content: content,
						from_name: from_name,
						from_email: from_email,
						security: ig_es_js_data.security
					},
					dataType: 'json',
					success: function(res) {
						if(res && typeof res.status !== 'undefined') {
							jQuery('.es_spam').next('.es-loader-img').hide();

							if( 'success' === res.status ) {
								if(res.res.spamScoreData.score !== 'undefined') {
									var score = res.res.spamScoreData.score;
									score = (score < 0) ? 0 : score;
									jQuery('.es-spam-score').text(score);
									if(parseInt(score) < 4) {
										jQuery('.es-spam-success').show();
										jQuery('.es-spam-error').hide();
										jQuery('.es-spam-error-log').hide();
										jQuery('.es-spam-score').addClass('es-spam-score-success text-green-600').removeClass('es-spam-score-error');
									} else {
										jQuery('.es-spam-error').show();
										jQuery('.es-spam-success').hide();
										var rules = res.res.spamScoreData.rules;
										jQuery('.es-spam-score').addClass('es-spam-score-error text-red-600').removeClass('es-spam-score-success');
										jQuery('.es-spam-error-log').show();
										jQuery('.es-spam-error-log').find('ul').empty();
										let rule_classes = '';
										if( 1 === jQuery('#spam_score_modal').length ) {
											rule_classes = 'text-base pb-1 list-none text-center font-medium text-red-400';
										}
										for (var i = rules.length - 1; i >= 0; i--) {
											if(rules[i].score > 1.2){

												jQuery('.es-spam-error-log').find('ul').append('<li class="' + rule_classes + '">'+ rules[i].description + '</li>');
											}
										}
									}
									jQuery('#spam_score_modal').show();
								}
							} else {
								alert( res.error_message );
							}
						}
					},
					error: function(res) {
						jQuery('#es_test_email_btn').next('.es-loader-img').hide();
					}
				};
				jQuery.ajax(params);
			});

			// Close spam score popup
			jQuery("#close_score").on('click', function (event) {
				event.preventDefault();
				jQuery('#spam_score_modal').hide();
			});

			// Show/hide utm campaign name field.
			jQuery('#enable_utm_tracking').on('change', function() {
				let enable_utm_tracking = jQuery(this).prop('checked') ? 'yes' : 'no';
				if( 'yes' === enable_utm_tracking ) {
					jQuery('.ig_es_utm_campaign_name_wrapper').removeClass('hidden');
				} else {
					jQuery('.ig_es_utm_campaign_name_wrapper').addClass('hidden');
				}
			});

			// Hide trial to premium offer notice when accepted. This function just hide it from frontend, actual notice gets hidden when reloading page in new tab.
			jQuery('#ig-es-optin-trial-to-premium-offer').on('click', function(){
				jQuery(this).closest('.notice').hide('slow');
			});

			jQuery('#es_close_preview').on('click', function (event) {
				event.preventDefault();
				$('#report_preview_template').hide();
			});

			jQuery('#es_close_template_preview').on('click', function (event) {
				event.preventDefault();
				$('#es_preview_template').hide();
			});

			// Prevent campaign form submission on enter key press
			jQuery('#campaign_form').on('keyup keypress', function(e) {
				let pressedKeyCode  = e.keyCode || e.which;
				let enterKeyCode    = 13;
				let enterKeyPressed = pressedKeyCode === enterKeyCode;
				if ( enterKeyPressed ) {
				  let targetType = e.target.type;
				  if ( 'textarea' !== targetType ) {
					e.preventDefault();
				  }
				}
			});

			// Create rest API key for selected user
			jQuery('#ig-es-generate-rest-api-key').click(function(e){
				e.preventDefault();
				let user_id = jQuery('#ig-es-rest-api-user-id').val();
				let message_class = '';
				if ( '' === user_id ) {
					message_class = 'text-red-600';
					jQuery('#response-messages').removeClass('hidden').find('div').attr('class', message_class).html(ig_es_js_data.i18n_data.select_user);
					return;
				}

				let btn_elem = $(this);

				jQuery.ajax({
					type:'POST',
					url:ajaxurl,
					data:{
						action:'ig_es_generate_rest_api_key',
						user_id: user_id,
						security:ig_es_js_data.security,
					},
					dataType:'json',
					beforeSend:function(){
						jQuery(btn_elem).addClass('loading');
					},
					success:function(response){
						if( response.status ){
							let status = response.status;
							let message = response.message;
							jQuery('.rest-api-response').removeClass('hidden').addClass(status).html(message);
							message_class = '';
							jQuery('#response-messages').removeClass('hidden').find('div').attr('class', message_class).html(message);
						} else{
							message_class = 'text-red-600';
							jQuery('#response-messages').removeClass('hidden').find('div').attr('class', message_class).html(ig_es_js_data.i18n_data.ajax_error_message);
						}
					},
					error:function(err){
						alert(ig_es_js_data.i18n_data.ajax_error_message);
					},
				}).always(function(){
					jQuery(btn_elem).removeClass('loading');
				});

			});
			
			// Delete rest API key for selected user
			jQuery('.ig-es-delete-rest-api-key').click(function(e){
				e.preventDefault();
				let delete_rest_api = confirm( ig_es_js_data.i18n_data.delete_rest_api_confirmation );
				if ( ! delete_rest_api ) {
					return;
				}
				let rest_api_row = jQuery(this).closest('.ig-es-rest-api-row');
				let user_id = jQuery(rest_api_row).data('user-id');
				let api_index = jQuery(rest_api_row).data('api-index');

				let btn_elem = $(this);

				jQuery.ajax({
					type:'POST',
					url:ajaxurl,
					data:{
						action:'ig_es_delete_rest_api_key',
						user_id: user_id,
						api_index: api_index,
						security:ig_es_js_data.security,
					},
					dataType:'json',
					beforeSend:function(){
						jQuery(btn_elem).addClass('loading');
					},
					success:function(response){
						if( response.status ){
							let status = response.status;
							if ( 'success' === status ) {
								jQuery(rest_api_row).remove();
							} else {
								alert( response.message );
							}
						} else{
							alert( ig_es_js_data.i18n_data.ajax_error_message );
						}
					},
					error:function(err){
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					},
				}).always(function(){
					jQuery(btn_elem).removeClass('loading');
				});

			});

			// Workflow JS
			IG_ES_Workflows = {

				$triggers_box: $('#ig_es_workflow_trigger'),
				$actions_box : $('#ig_es_workflow_actions'),
				$variables_box: $('#ig_es_workflow_variables'),
				$trigger_select: $('.js-trigger-select').first(),
				$actions_container: $('.ig-es-actions-container'),

				init: function() {
					IG_ES_Workflows.init_triggers_box();
					IG_ES_Workflows.init_actions_box();
					IG_ES_Workflows.init_rules_box();
					IG_ES_Workflows.init_variables_box();
					IG_ES_Workflows.init_show_hide();
					IG_ES_Workflows.init_workflow_status_switch();
					IG_ES_Workflows.init_workflow_gallery();
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
							security: ig_es_js_data.security,
							dataType: 'json'
						}, function( response ) {
							$switch.attr( 'data-ig-es-switch', new_state );
							$switch.removeClass('ig-es-loading');
						});

					});
				},

				init_workflow_gallery: function() {
					$('.ig-es-create-workflow-from-gallery-item').click(function(){
						let $gallery_item = $(this);
						if ( $gallery_item.is('.loading') ) {
							return;
						}
						$gallery_item.addClass('loading');
						$.post( ajaxurl, {
							action: 'ig_es_create_workflow_from_gallery_item',
							item_name: $gallery_item.attr( 'data-item-name' ),
							security: ig_es_js_data.security,
							dataType: 'json'
						}, function( response ) {
							if ( response.success ) {
								window.location.href = response.data.redirect_url;
							} else {
								alert( response.data.error_message );
							}
							$gallery_item.removeClass('loading');
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
					IG_ES_Workflows.$trigger_select.change(function(e){
						IG_ES_Workflows.fill_trigger_fields( $(this).val() );
						IG_ES_Workflows.maybe_show_run_option();
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
								security: ig_es_js_data.security,
								workflow_id: workflow_id
							}
							})
							.done(function(response){

								if ( ! response.success ) {
									return;
								}

								ig_es_workflows_data.trigger = response.data.trigger;
								IG_ES_Workflows.refine_variables();

								IG_ES_Workflows.$triggers_box.find('tbody').append( response.data.fields );
								IG_ES_Workflows.$triggers_box.removeClass('ig-es-loading');
								IG_ES_Workflows.$triggers_box.find('.js-trigger-description').html( '<p class="ig-es-field-description">' + response.data.trigger.description + '</p>' );
								$(document).trigger('ig_es_workflow_trigger_updated', [response.data]);
							});
					} else {
						$('.ig-es-variables-group').addClass('hidden');
						IG_ES_Workflows.toggle_no_variable_message();
					}
				},

				init_rules_box: function () {
					try {
						//All rules avalilable in ES workflow
						let rule_details = igEsWorkflowRules;
						let all_rules = rule_details?.all_rules;
						let grouped_rules = [];
						let valid_rules = {};

						let $meta_box_footer = $('.rules-metabox-footer');
						let $rule_template_container = $('#rule-template-container');
						let $rules_container = $('#ig-es-rules-container');
						let $main_rule_container= $('.ig-es-rule-container');

						//Add new rule group.
						$(document).on('click', '.ig-es-add-rule-group', function () {
							let $new_rule_group = add_new_rule_group();
							$rules_container.append($new_rule_group);
						});

						//Add one more rules in respective rule group
						$(document).on('click', '.ig-es-rule__add', function () {
							let $rule_group = $(this).closest('.ig-es-rule-group');

							let rule_id = get_unique_number();
							let rule_group_id = $rule_group.data('group-id');

							$new_rule_container = $rule_template_container.find('.ig-es-rule-container').clone();
							$new_rule_container.data('rule-id', rule_id);
							add_rule_container_to_rule_group($new_rule_container, rule_group_id, rule_id);

							$rule_group.append($new_rule_container);
						});

						//Remove rule from the respective rule group..If there is no rules in the group, then remove entire rule group
						$(document).on('click', '.ig-es-rule__remove', function () {
							let $rule_group = $(this).closest('.ig-es-rule-group');

							let rules_count = $rule_group.find('.ig-es-rule-container').length;
							if (rules_count > 1) {
								$(this).closest('.ig-es-rule-container').remove();
							} else {
								$rule_group.remove();
							}
							validate_and_show_option_to_add_rules(false);
						});

						//On selecting rule, reset the compare field and value field
						$(document).on('change', '.rule-select-field', function () {
							let $rule_group = $(this).closest('.ig-es-rule-group');
							let $rule_container = $(this).closest('.ig-es-rule-container');
							let $compare_field = $rule_container.find('.rule-compare-field');

							let rule_name = $(this).val();
							let rule_group_id = $rule_group.data('group-id');
							let rule_id = $rule_container.data('rule-id');

							render_compare_field($rule_container, $compare_field, rule_group_id, rule_id, {name: rule_name})
						});

						// Reset everything while changing the trigger
						$(document).on('ig_es_workflow_trigger_updated', function (e, data) {
							filter_valid_rules(data?.trigger?.supplied_data_items,data?.trigger?.name);
							validate_and_show_option_to_add_rules(true);
						});

						/**
						 * Get the timestamp for Rule group ID and Rule ID.
						 *
						 * @returns {number}
						 */
						const get_unique_number = function () {
							return Date.now();
						}

						/**
						 * Render the compare field and value field of the respective rule container
						 * @param $rule_container
						 * @param $compare_field
						 * @param rule_group_id
						 * @param rule_id
						 * @param rule_details
						 */
						const render_compare_field = function ($rule_container, $compare_field, rule_group_id = 0, rule_id = 0, rule_details = {}) {
							let rule_name = rule_details?.name;
							if (rule_name && rule_name.length > 1) {
								let rule = all_rules[rule_name];
								let compare_types = rule?.compare_types;

								if (compare_types && Object.keys(compare_types).length > 0) {
									let compare_field_options = [];
									$.each(compare_types, function( key, value ) {
										compare_field_options.push('<option value="'+ key +'">'+ value +'</option>');
									});
									$compare_field.html(compare_field_options.join(''));

									let rule_compare = rule_details?.compare;
									if (rule_compare) {
										$compare_field.val(rule_compare);
									}
									$compare_field.removeAttr('disabled')
								}
								add_rule_value_field($rule_container, rule, rule_group_id, rule_id, rule_details);
							} else {
								$compare_field.html('');
								$compare_field.attr('disabled', true);
								add_rule_value_field($rule_container, {type: ''}, rule_group_id, rule_id, rule_details)
							}
						}

						/**
						 * Add new rule group to the wokflow
						 *
						 * @param group_id
						 * @param rule_id
						 * @param include_default_rule
						 * @returns {boolean|*}
						 */
						const add_new_rule_group = function (group_id, rule_id = 0, include_default_rule = true) {
							$rules_container.find('.ig-es-rules-empty-message').remove();
							$rules_container.find('.ig-es-no-rules-message').remove();

							let total_rule_groups = $rules_container.find('.ig-es-rule-group').length;
							if (!group_id) {
								group_id = get_unique_number();
							}

							//For this release allow only one rule group. Remove IF/ELSE statement to allow multiple rule groups
							if (total_rule_groups >= 1) {
								return false;
							} else {
								$meta_box_footer.addClass('hidden');
							}

							let $new_rule_group = $rule_template_container.find('.ig-es-rule-group').clone();
							$new_rule_group.data('group-id', group_id);

							if (include_default_rule) {
								let $new_rule_container = $rule_template_container.find('.ig-es-rule-container').clone();
								$new_rule_container.data('rule-id', 0);

								add_rule_container_to_rule_group($new_rule_container, group_id, rule_id);
								$new_rule_group.append($new_rule_container)
							}

							return $new_rule_group;
						}

						/**
						 * Add rule to rule group
						 * @param $rule_container
						 * @param rule_group_id
						 * @param rule_id
						 * @param rule_details
						 */
						const add_rule_container_to_rule_group = function ($rule_container, rule_group_id, rule_id, rule_details = {}) {
							let $rule_select_field = $rule_container.find('.rule-select-field')
							if (Object.keys(grouped_rules).length > 0) {
								for (const group_name in grouped_rules) {
									if (grouped_rules.hasOwnProperty(group_name)) {
										let $option_group = $(`<optgroup label='${group_name}'>`);
										let rules = grouped_rules[group_name];
										for (i = 0; i < rules.length; i++) {
											var option = "<option value='" + rules[i].name + "'>" + rules[i].title + "</option>";
											$option_group.append(option);
										}
										$rule_select_field.append($option_group);
									}
									$rule_select_field.removeAttr('disabled');
								}
							}
							let rule_name = rule_details?.name
							$rule_select_field.attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][name]');
							$rule_select_field.val(rule_name);

							$rule_compare_field = $rule_container.find('.rule-compare-field');
							$rule_compare_field.attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][compare]');
							render_compare_field($rule_container, $rule_compare_field, rule_group_id, rule_id, rule_details);

							$rule_container.find('.rule-value-field').attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][value]');
						}

						/**
						 * Render the rule value field based on rule settings
						 * @param $rule_group_container
						 * @param rule
						 * @param rule_group_id
						 * @param rule_id
						 * @param rule_details
						 */
						const add_rule_value_field = function ($rule_group_container, rule, rule_group_id, rule_id, rule_details = {}) {
							let rule_value = rule_details?.value;
							
							switch (rule.type) {
								case "select":
									let $select_value_field = $rule_template_container.find('.rule-value-select-field').clone();
									if (rule.placeholder) {
										$select_value_field.data('placeholder', rule.placeholder)
									}
									if (rule.is_single_select) {
										$select_value_field.attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][value]');
									} else {
										$select_value_field.attr('multiple', 'multiple');
										$select_value_field.attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][value][]');
										$select_value_field.addClass('ig-es-form-multiselect');
									}
									let select_choices = rule?.select_choices;
									if (select_choices) {
										for (const value in select_choices) {
											if (select_choices.hasOwnProperty(value)) {
												let selected = false;
												if (rule_value) {
													if (Array.isArray(rule_value)) {
														if (rule_value.includes(value)) {
															selected = true;
														}
													} else {
														if (rule_value === value) {
															selected = true;
														}
													}
												}
												let $option = `<option value='${value}' ${selected?'selected':''}>${select_choices[value]}</option>`;
												$select_value_field.append($option);
											}
										}
									}
									$rule_group_container.find('.ig-es-rule-field-value').html($select_value_field);
									// $('body').trigger('wc-enhanced-select-init');
									$select_value_field.ig_es_select2();
									break;
								case "object":
									let selected_values = rule_details?.selected;
									let $object_value_field = $rule_template_container.find('.rule-value-object-field').clone();
									if (rule.class) {
										$object_value_field.addClass(rule.class)
									}
									if (rule.placeholder) {
										$object_value_field.data('placeholder', rule.placeholder)
									}
									if (rule.ajax_action) {
										$object_value_field.data('action', rule.ajax_action)
									}
									if (rule.is_multi) {
										$object_value_field.attr('multiple', 'multiple')
										$object_value_field.attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][value][]');
										if(rule_value) {
											for (let i = 0; i < rule_value.length; i++) {
												var option = "<option value='" + rule_value[i] + "' selected>" + selected_values[i] + "</option>";
												$object_value_field.append(option);
											}
										}
									} else {
										$object_value_field.attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][value]');
										if(rule_value) {
											var option = "<option value='" + rule_value + "' selected>" + selected_values + "</option>";
											$object_value_field.append(option);
										}
									}
									$rule_group_container.find('.ig-es-rule-field-value').html($object_value_field)
									$('body').trigger('wc-enhanced-select-init');
									break;

								case "number":
									let $number_value_field = $main_rule_container.find('.rule-value-number-field').clone();
									$number_value_field.removeAttr('disabled');
									$number_value_field.attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][value]');
									if (rule_value) {
										$number_value_field.val(rule_value);
									}
									if (rule.placeholder) {
										$number_value_field.data('placeholder', rule.placeholder)
									}
									$rule_group_container.find('.ig-es-rule-field-value').html($number_value_field);
									break;

								default:
									let $default_value_field = $rule_template_container.find('.rule-value-text-field').clone();
									$default_value_field.attr('name', 'ig_es_workflow_data[rules][' + rule_group_id + '][' + rule_id + '][value]');
									$rule_group_container.find('.ig-es-rule-field-value').html($default_value_field)
									break;
							}
						}

						/**
						 * Validate the rules for trigger and toggle action buttons for the rules
						 * @param force_reset
						 */
						const validate_and_show_option_to_add_rules = function (force_reset = false) {
							let rule_groups_count = $rules_container.find('.ig-es-rule-group').length;
							if (rule_groups_count > 0 && !force_reset) {
								return;
							}
							let $rule_groups = $rules_container.find('.ig-es-rule-group');
							let has_valid_rules = false;
							if ($rule_groups.length > 0) {
								$rule_groups.each(function (index) {
									let $rule_group = $(this);
									let $rules = $rule_group.find('.ig-es-rule-container');
									let total_rules_count = $rules.length;
									let valid_rules_count = 0;
									if (total_rules_count > 0) {
										$rules.each(function (index) {
											let $rule = $(this);
											let rule_name = $rule.find('.rule-select-field').val();
											if (valid_rules.hasOwnProperty(rule_name)) {
												has_valid_rules = true;
												valid_rules_count += 1;
											} else {
												$rule.remove();
											}
										});
									}
									if (valid_rules_count <= 0) {
										$rule_group.remove();
									}
								});
							}
							if (has_valid_rules) {
								return;
							} else {
								$rules_container.html('')
							}
							if (Object.keys(grouped_rules).length > 0) {
								$rule_template_container.find('.ig-es-rules-empty-message').clone().appendTo($rules_container)
								$meta_box_footer.removeClass('hidden');
							} else {
								$meta_box_footer.addClass('hidden');
								$rule_template_container.find('.ig-es-no-rules-message').clone().appendTo($rules_container);
							}
						}

						/**
						 * Filter the valid rules for the trigger
						 * @param supplied_data_items
						 */
						const filter_valid_rules = function (supplied_data_items,trigger_name) {
							grouped_rules = [];
							valid_rules = {};
							if (supplied_data_items) {
								for (const rule_name in all_rules) {
									if (all_rules.hasOwnProperty(rule_name)) {
										let rule = all_rules[rule_name];
										let excluded_triggers = rule.excluded_triggers;
										if (supplied_data_items.includes(rule.data_item) && !excluded_triggers.includes(trigger_name)) {
											if (!grouped_rules[rule.group]) {
												grouped_rules[rule.group] = []
											}
											grouped_rules[rule.group].push(rule);
											valid_rules[rule_name] = rule;
										}
									}
								}
							}
						}

						/**
						 * While editing the workflow, render the rules
						 */
						const render_existing_rules = function () {
							let workflow_rules = rule_details?.workflow_rules;
							filter_valid_rules(rule_details?.supplied_data_items,rule_details?.trigger_name);
							validate_and_show_option_to_add_rules(true);
							if (workflow_rules.length > 0) {
								for (let group_id = 0; group_id < workflow_rules.length; group_id++) {
									let rules = workflow_rules[group_id];
									let rule_group_id = get_unique_number() + group_id;
									let $new_rule_group = add_new_rule_group(rule_group_id, 0, false);
									for (let rule_id = 0; rule_id < rules.length; rule_id++) {
										let saved_rule = rules[rule_id];
										let original_rule = all_rules[saved_rule.name];

										let $new_rule_container = $rule_template_container.find('.ig-es-rule-container').clone();
										$new_rule_container.data('rule-id', rule_id);
										let unique_rule_id = get_unique_number() + group_id;

										add_rule_container_to_rule_group($new_rule_container, rule_group_id, unique_rule_id, saved_rule);

										$new_rule_group.append($new_rule_container)
									}
									$rules_container.append($new_rule_group)
								}
							}
						}

						render_existing_rules();
					} catch (e) {

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
						IG_ES_Workflows.maybe_show_run_option();
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

					$('input[name="ig_es_workflow_data[actions][{action_id}][attachments][]"]').each( function(){
						let action_number = $(this).closest('.ig-es-action').data('action-number');
						$(this).attr('name','ig_es_workflow_data[actions]['+action_number+'][attachments][]');
					});

					// Delete action
					$(document).on('click', '.js-delete-action', function (e) {
						e.preventDefault();
						let $action = $(this).parents('.ig-es-action').first();
						IG_ES_Workflows.action_delete($action);
						IG_ES_Workflows.maybe_show_run_option();
					});

					// Preview action
					$(document).on('click', '.js-preview-action', function (e) {
						e.preventDefault();
						e.stopImmediatePropagation();
						let preview_action_button = $(this);
						let $action = preview_action_button.parents('.ig-es-action').first();
						let action_id = $action.data('action-number');
						ig_es_sync_wp_editor_content();
						let content_container = $('#workflow-email-preview-container');
						let content_loader_container = $('#workflow-email-preview-loader');

						let content  = $('textarea[name="ig_es_workflow_data[actions][' + action_id + '][ig-es-email-content]"]').val();
						let subject  = $('input[name="ig_es_workflow_data[actions][' + action_id + '][ig-es-email-subject]"]').val();
						let template = $('select[name="ig_es_workflow_data[actions][' + action_id + '][ig-es-email-template]"]').val();
						let heading  = $('input[name="ig_es_workflow_data[actions][' + action_id + '][ig-es-email-heading]"]').val();

						if (!content) {
							alert(ig_es_js_data.i18n_data.empty_template_message);
							return;
						}
						preview_action_button.attr('disabled', true);
						$('#browser-preview-tab').trigger('click');
						let form_data = {
							'action': 'ig_es_get_workflow_email_preview',
							'security': ig_es_js_data.security,
							'content': content,
							'subject': subject,
							'template': template,
							'heading': heading,
							'action_id': action_id,
							'preview_type': 'inline',
							'trigger': $('select[name="ig_es_workflow_data[trigger_name]"]').val()
						};
						content_container.addClass('hidden');
						content_loader_container.removeClass('hidden');

						$('#workflow-email-preview-popup').removeClass('hidden');
						$('#workflow-email-preview-popup').css('visibility', 'visible');
						$('#send-workflow-preview-email-btn').data('action', action_id);
						$.ajax({
							method: 'POST',
							url: ajaxurl,
							data: form_data,
							dataType: 'json',
							success: function (response) {
								preview_action_button.attr('disabled', false);
								if (response.success) {
									if ('undefined' !== typeof response.data) {
										let response_data = response.data;
										let preview_html = response_data.preview_html;
										let workflow_email_subject = response_data.subject;
										ig_es_load_iframe_preview('#workflow-preview-iframe-container', preview_html);
										$(".workflow-subject-preview").text(workflow_email_subject);
										// We are setting popup visiblity hidden so that we can calculate iframe width/height before it is shown to user.
									}
								} else {
									alert(ig_es_js_data.i18n_data.ajax_error_message);
								}
							},
							error: function (err) {
								preview_action_button.attr('disabled', false);
								alert(ig_es_js_data.i18n_data.ajax_error_message);
							}
						}).done(function () {
							preview_action_button.attr('disabled', false);
							content_container.removeClass('hidden');
							content_loader_container.addClass('hidden');
						});
					});

					$('#close-workflow-email-preview-popup').on('click', function (event) {
						event.preventDefault();
						$('#workflow-email-preview-popup').addClass('hidden');
						$('#workflow-email-preview-popup').css('visibility', 'hidden');
					});

					$('.ig-es-actions-container').on('change', 'select[data-name="ig-es-email-template"]', function (e) {
						let selected_email_template = $(this).val();
						let $action                 = $(this).closest('.ig-es-action').first();
						let is_woocommerce_template = 'woocommerce' === selected_email_template;

						if ( is_woocommerce_template ) {
							$action.find('tr[data-name="ig-es-email-heading"]').show();
						} else {
							$action.find('tr[data-name="ig-es-email-heading"]').hide();
						}
					});

					$('#ig_es_workflow_save #publish').on('click', function(e){
						let trigger_name = $('.js-trigger-select').val();

						if ( '' === trigger_name) {
							e.preventDefault();
							alert( ig_es_js_data.i18n_data.no_trigger_message );
							return;
						}

						let actions = $('.ig-es-action:not([data-action-number=""]) .js-action-select');
						if ( 0 === $( actions ).length ) {
							e.preventDefault();
							alert( ig_es_js_data.i18n_data.no_actions_message );
							return;
						} else {
							$(actions).each(function() {
								let action_name = $(this).val();
								// Check if user have selected an action or not.
								if( '' === action_name ) {
									e.preventDefault();
									// Open the action accordion if is not already open.
									$(this).closest('.ig-es-action:not(.js-open)').find('.ig-es-action__header').trigger('click');
									alert( ig_es_js_data.i18n_data.no_action_selected_message );
									return false;
								}
							});
						}
					});
					this.maybe_show_action_preview_on_init();
				},

				maybe_show_action_preview_on_init: function () {
					let actions = $('.ig-es-action:not([data-action-number = ""]) .js-action-select');
					$(actions).each(function (action) {
						let selected_action = $(this).val();
						let $action = $(this).parents('.ig-es-action').first();
						IG_ES_Workflows.maybe_show_action_preview_option($action, selected_action);
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
					$action.removeClass('js-open');
					$action.find('.ig-es-action__fields').slideUp(150);
				},

				action_edit_open: function( $action ) {
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
							security: ig_es_js_data.security
						}
					}).done(function(response){

						$action.find('.ig-es-table tbody').append( response.data.fields );
						IG_ES_Workflows.$actions_box.removeClass('ig-es-loading');

						// Fill select box name
						$select.attr('name', 'ig_es_workflow_data[actions][' + action_number + '][action_name]' );

						// Pre fill title
						$action.find('.action-title').text( response.data.title );

						$action.find('.js-action-description').html( response.data.description );

						IG_ES_Workflows.maybe_show_action_preview_option( $action, selected_action );
					});

				},

				maybe_show_action_preview_option: function ($action, selected_action) {
					var preview_option = $action.find('.ig-es-action__header .row-options .js-preview-action');
					if ('ig_es_send_email' === selected_action) {
						preview_option.removeClass('hidden');
					} else {
						preview_option.addClass('hidden');
					}
				},

				get_number_of_actions: function () {
					return $('.ig-es-action:not([data-action-number=""])').length;
				},

				remove_actions: function() {
					let number_of_actions = IG_ES_Workflows.get_number_of_actions();
					if ( number_of_actions > 0 ) {
						$('.ig-es-action:not([data-action-number=""])').remove();
					}
				},

				maybe_show_run_option: function() {
					let trigger_name        = IG_ES_Workflows.$trigger_select.val();
					let runnable_triggers   = [ 'ig_es_wc_order_created', 'ig_es_wc_order_completed', 'ig_es_wc_order_refunded' ];
					let is_trigger_runnable = runnable_triggers.includes( trigger_name );
					let actions             = $('.ig-es-action:not([data-action-number = ""]) .js-action-select');
					let has_runnable_action = false;
					$(actions).each(function(action){
						let action_name = $(this).val();
						if ( 'ig_es_add_to_list' === action_name ) {
							has_runnable_action = true;
							return false;
						}
					});
					if ( is_trigger_runnable && has_runnable_action ) {
						$('#run-workflow-checkbox-wrapper').show();
					} else {
						$('#run-workflow-checkbox-wrapper').hide();
					}
				},

				init_variables_box: function() {
					this.init_clipboard();

					$(document.body).on( 'change keyup', '.ig-es-workflow-variable-parameter', this.update_preview_field );
					$(document.body).on( 'keypress', 'input.ig-es-workflow-variable-parameter', this.restrict_parameter_chars );

					this.$variables_box.on( 'click', '.ig-es-workflow-variable', this.open_modal );
					this.$variables_box.on( 'click', '.ig-es-close-variable-info-popup', this.close_modal );

					$(document).keydown(function(e) {
						if ( 27 === e.keyCode ) {
							IG_ES_Workflows.close_modal();
						}
					});

					if ( ! ig_es_workflows_data.is_new ) {
						IG_ES_Workflows.refine_variables();
					}
				},

				/**
				 * Show or hide text var groups based on the selected trigger
				 */
				refine_variables: function() {

					let trigger = ig_es_workflows_data.trigger;
					$('.ig-es-variables-group').each(function( i, el ){

						let group = $(el).data('ig-es-variable-group');

					$.each($(el).data(), function (key, value) {
						if (key === 'igEsVariableGroup' && value === 'subscriber') {
							if (trigger.name !== undefined && trigger.name !== null && trigger.name !== '') {
							if (trigger.name !== 'ig_es_user_unsubscribed') {
								$(el).find('span[data-ig-es-variable-slug="subscriber.unsubscriber_reason"]').parent().addClass('hidden');
							}else{
								$(el).find('span[data-ig-es-variable-slug="subscriber.unsubscriber_reason"]').parent().removeClass('hidden');
							}
						  }
							
						}
					});

						if ( -1 === $.inArray( group, trigger.supplied_data_items ) ) {
							$(el).addClass('hidden');
						} else {
							$(el).removeClass('hidden');
						}
					});

					IG_ES_Workflows.toggle_no_variable_message();
				},

				/**
				 * Show / hide "no variables found" message for trigger
				 */
				toggle_no_variable_message: function () {
					let shown_group = $('.ig-es-variables-group:not(.hidden)');
					if ( 0 === shown_group.length ) {
						$('.js-ig-es-no-variables-message').show();
					} else {
						$('.js-ig-es-no-variables-message').hide();
					}
				},

				/**
				 *
				 */
				init_clipboard: function() {

					var clipboard = new ClipboardJS('.ig-es-clipboard-btn', {
						text: function(trigger) {
							if (IG_ES_Workflows.validate_keywords()) {
								return $('#ig_es_workflow_variable_preview_field').text();
							} else {
								return '';
							}
						}
					});

					clipboard.on('success', function(e) {

						$('.ig-es-clipboard-btn').html( ig_es_js_data.i18n_data.placeholder_copied_message );
						setTimeout(function(){
							IG_ES_Workflows.close_modal();
						}, 500 );
					});

				},

				validate_keywords: function () {
					var variable_info_container = $('#ig-es-variable-info-popup #ig-es-workflow-variable-info-body [data-required="yes"]');
					if (variable_info_container.length > 0) {
						for (let index = 0; index < variable_info_container.length; index++) {
							let element = $(variable_info_container[index]);
							let value = element.val();
							if (!value || value == '' || value.length === 0) {
								let field_label = element.attr('name');
								if (field_label) {
									field_label = ig_es_uc_first(field_label);
									let message = (ig_es_js_data.i18n_data.keyword_field_is_required).replaceAll('{{field_name}}', field_label);
									alert(message);
								} else {
									alert(ig_es_js_data.i18n_data.required_field_is_empty);
								}
								return false;
							}
						}
					}

					return true;
				},


                open_modal: function () {

                    var ajax_data = {
                        action: 'ig_es_modal_variable_info',
                        variable: $(this).text(),
                        security: ig_es_js_data.security,
                    };

                    var variable_info_container = $('#ig-es-variable-info-popup #ig-es-workflow-variable-info-body');
                    var variable_info_close_button = $('#ig-es-variable-info-popup .ig-es-close-variable-info-popup');

                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: ajax_data,
                        beforeSend: function () {
                            variable_info_close_button.hide();
                            let loader = variable_info_container.data('loader')
                            variable_info_container.html('<div class="p-13"><img class="es-loader pl-2 h-5 w-7" src="' + loader + '" /></div>').show();
                            IG_ES_Workflows.show_modal();
                        },
                        success: function (response) {
                            variable_info_container.html(response).show();
                            IG_ES_Workflows.update_preview_field();
                            variable_info_close_button.show();
                        }
                    });
                },

				show_modal: function() {
					jQuery('#ig-es-variable-info-popup').show();
				},

				close_modal: function() {
					$('#ig-es-variable-info-popup').hide('fast');
				},


				/**
				 * Updates the variable preview text field
				 */
				update_preview_field: function() {

					var $preview_field = $('#ig_es_workflow_variable_preview_field');
					var variable = $preview_field.data('variable');
					var parameters = [];

					$('.ig-es-workflow-variable-parameter').each(function(){

						var $param_row = $(this).parents('.ig-es-workflow-variables-parameter-row:first');

						// Check 'show' logic
						if ( $param_row.data('parameter-show') ) {

							var show_logic = $param_row.data('parameter-show').split('=');

							var $condition_field = $('.ig-es-workflow-variable-parameter[name="' + show_logic[0] + '"]');

							if ( $condition_field.length && $condition_field.val() == show_logic[1] ) {
								$param_row.show();
							} else {
								$param_row.hide();
								return; // don't add parameter to preview
							}
						}

						var param = {
							name: $(this).attr('name'),
							required: $param_row.data('is-required'),
							value: $(this).val()
						};

						parameters.push( param );
					});

					var string = IG_ES_Workflows.generate_variable_string( variable, parameters );

					$preview_field.text( string );
				},


				/**
				*
				* @param variable
				* @param parameters
				*/
				generate_variable_string: function( variable, parameters ) {

					var string = '{{ ' + variable;

					if ( parameters.length ) {
						var param_parts = [];

						$.each( parameters, function( i, param ) {

							if ( param.value ) {
								param_parts.push( param.name + ": '" + param.value + "'" );
							}
							else if ( param.required ) {
								param_parts.push( param.name + ": '...'" );
							}
						});


						if ( param_parts.length > 0 ) {
							string += ' | ';
							string += param_parts.join( ', ' );
						}
					}

					return string + ' }}';
				},


				/**
				*
				* @param e
				*/
				restrict_parameter_chars: function(e) {

					var restricted = [ 39, 123, 124, 125 ];

					if ( $.inArray( e.which, restricted ) !== -1 ) {
						return false;
					}
				}

			}

			if ( 'undefined' !== typeof ig_es_workflows_data ) {
				IG_ES_Workflows.init();
			}

			// Import Subscribers JS
			let importstatus = $('.es-import-step1 .import-status'),
				progress = $('#progress'),
				progressbar = progress.find('.bar'),
				importprogress,
				importprogressbar,
				import_percentage,
				wpnonce = ig_es_js_data.security,
				importerrors = 0,
				importstarttime,
				importidentifier,
				import_option,

			uploader_init = function () {
				let uploader = new plupload.Uploader(wpUploaderInit);

				uploader.bind('Init', function (up) {
					let uploaddiv = $('#plupload-upload-ui');

					if (up.features.dragdrop && !$(document.body).hasClass('mobile')) {
						uploaddiv.addClass('drag-drop');
						$('#drag-drop-area').bind('dragover.wp-uploader', function () { // dragenter doesn't fire right :(
							uploaddiv.addClass('drag-over');
						}).bind('dragleave.wp-uploader, drop.wp-uploader', function () {
							uploaddiv.removeClass('drag-over');
						});
					} else {
						uploaddiv.removeClass('drag-drop');
						$('#drag-drop-area').unbind('.wp-uploader');
					}

				});

				uploader.bind('FilesAdded', function (up, files) {
					$('#media-upload-error').html('');

					setTimeout(function () {
						up.refresh();
						up.start();
					}, 1);

				});

				uploader.bind('BeforeUpload', function (up, file) {
					progress.removeClass('finished error hidden');
					importstatus.show().removeClass('text-red-600');
					importstatus.html('Uploading');
				});

				uploader.bind('UploadFile', function (up, file) {});

				uploader.bind('UploadProgress', function (up, file) {
					importstatus.show().removeClass('text-red-600');
					importstatus.html(sprintf(ig_es_js_data.i18n_data.uploading, file.percent + '%'));
					progressbar.stop().animate({
						'width': file.percent + '%'
					}, 100);
				});

				uploader.bind('Error', function (up, err) {
					importstatus.show().addClass('text-red-600 text-base font-medium');
					importstatus.html(err.message);
					progress.addClass('error');
					up.refresh();
				});

				uploader.bind('FileUploaded', function (up, file, response) {
					response = $.parseJSON(response.response);
					importidentifier = response.identifier;
					if (!response.success) {
						importstatus.html(response.message);
						progress.addClass('error');
						up.refresh();
						uploader.unbind('UploadComplete');
					}
				});

				uploader.bind('UploadComplete', function (up, files) {
					importstatus.removeClass('text-red-600').html(ig_es_js_data.i18n_data.prepare_data);
					progress.addClass('finished');
					jQuery(document).trigger('ig_es_get_import_data',[{identifier:importidentifier}]);
				});

				uploader.init();
			}

			let get_import_data = function (e, data) {

				progress.removeClass('finished error');
				import_option = jQuery('[name="es-import-subscribers"]:checked').val();
				identifier = data.identifier;
				$.post( ajaxurl, {
					action: 'ig_es_get_import_data',
					identifier: identifier,
					security: wpnonce,
					dataType: 'json'
				}, function( response ) {
					progress.addClass('hidden');
					$(".es-import-step1").slideUp();
					$('.es-import-option, .mailchimp_import_step_1').hide();
					$('.step2-body').html(response.html).parent().show();
					if( 'es-import-mailchimp-users' !== import_option ){
						$('.step2-status, .step2-list, .step2-update-existing-subscribers').show();
					}
					$('.wrapper-start-contacts-import').show();
					importstatus.html('');
				});
			}

			let trigger_import = function(e, data) {

				let _this = $('.start-import').prop('disabled', true),
					loader = $('#import-ajax-loading').css({
						'display': 'inline-block'
					});
					
				importprogress = $('#importing-progress'),
				importprogressbar = importprogress.find('.bar'),
				import_percentage = importprogress.find('.import_percentage')

				progress.removeClass('hidden');
				progressbar.stop().width(0);
				$('.es-import-step1').slideUp();
				$('.es-import-option').hide();
				$('.step2-body').html('<br><br>').parent().show();
				$('.step2-status,.step2-list, .step2-update-existing-subscribers, .step2-send-optin-emails, .es-import-processing, .wrapper-start-contacts-import').hide();

				let import_data = {
					id: 0,
					options: {
						identifier   		   : data.identifier,
						mapping_order		   : data.mapping_order,
						list_id      		   : data.list_id,
						status				   : data.status,
						send_optin_emails 	   : data.send_optin_emails ? data.send_optin_emails : 'no',
						update_subscribers_data: data.update_subscribers_data
					}
				}
				importstarttime = new Date();
				$(document).trigger('ig_es_do_import',[import_data]);

				importstatus = $('.step2 .import-status');

				importstatus.html(ig_es_js_data.i18n_data.prepare_import);

				window.onbeforeunload = function () {
					return ig_es_js_data.i18n_data.onbeforeunloadimport;
				};
			}

			let do_import = function(e, import_data) {
				let percentage = 0;
				importprogress.removeClass('hidden');
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					dataType: 'json',
					data: {
						action: 'ig_es_do_import',
						id: import_data.id,
						options: import_data.options,
						security: wpnonce,
					},
					success: function( response ) {
						percentage = (Math.min(1, (response.imported + response.errors + response.duplicate_emails_count) / response.total) * 100);

						$('.step2-body').html('<p class="pt-3 pb-2 text-sm text-gray-600">' + get_stats(response.f_imported, response.f_errors, response.f_duplicate_emails, response.f_total, percentage, response.memoryusage) + '</p>');

						importerrors = 0;
						let finished = percentage >= 100;

						if (response.success) {

							if (!finished) {
								import_data.id += 1;
								$(document).trigger('ig_es_do_import', [import_data] );
							}

							importprogressbar.animate({
								'width': (percentage) + '%'
							}, {
								duration: 1000,
								easing: 'swing',
								queue: false,
								step: function (percentage) {
									importstatus.show().addClass('text-lg').html( sprintf(ig_es_js_data.i18n_data.import_contacts, '' ) );
									$('.import-instruction').html( ig_es_js_data.i18n_data.no_windowclose );
									import_percentage.html( Math.ceil(percentage) + '%' );
								},
								complete: function () {
									if (finished) {
										window.onbeforeunload = null;
										$('.import-instruction').hide();
										importprogress.addClass('finished');
										if ( jQuery('#form_import_subscribers').length ) {
											$('.step2-body').html(response.html).slideDown();
											importstatus.addClass('text-xl');
										} else {
											$('.step2-body').hide();
										}
										importstatus.html(sprintf(ig_es_js_data.i18n_data.import_complete,'<svg class=" w-6 h-6 inline-block text-indigo-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'));
										$(document).trigger('ig_es_import_finished').off('ig_es_import_finished');
									}
								}
							});
						} else {
							import_error_handler(percentage, import_data);
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						import_error_handler(percentage, import_data);
					}
				});
			}

			let get_stats = function(f_imported, f_errors, f_duplicate_emails,f_total, percentage, memoryusage) {
				let timepast = new Date().getTime() - importstarttime.getTime(),
					timeleft = Math.ceil(((100 - percentage) * (timepast / percentage)) / 60000);

				let imported_html = '<span class="font-medium">' + f_imported + '</span>';
				let total_html = '<span class="font-medium">' + f_total + '</span>';
				let error_html = '<span class="font-medium">' + f_errors + '</span>';
				let duplicate_html = '<span class="font-medium">' + sprintf( ig_es_js_data.i18n_data.duplicate_emails_found_message, f_duplicate_emails ) + '</span>';
				let memoryusage_html = '<span class="font-medium">' + memoryusage + '</span>';
				return sprintf(
					ig_es_js_data.i18n_data.current_stats,
					imported_html,
					total_html,
					error_html,
					duplicate_html,
					memoryusage_html)
					+ '<br>' +
					sprintf(
						ig_es_js_data.i18n_data.estimate_time, timeleft
					);
			}

			let import_error_handler = function(percentage, import_data) {
				importerrors++;
				if (importerrors >= 5) {

					alert(ig_es_js_data.i18n_data.error_importing);
					importstatus.html(sprintf(ig_es_js_data.i18n_data.import_failed, '<svg class=" w-6 h-6 inline-block text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'));
					window.onbeforeunload = null;
					return;
				}

				let i = importerrors * 5,
					str = '',
					errorint = setInterval(function () {
						if (i <= 0) {
							clearInterval(errorint);
							progress.removeClass('paused');
							jQuery(document).trigger('ig_es_do_import', [import_data] );
						} else {
							progress.addClass('paused');
							str = '<span class="error">' + sprintf(ig_es_js_data.i18n_data.continues_in, (i--)) + '</span>';
						}
						importstatus.html(sprintf(ig_es_js_data.i18n_data.import_contacts, str));
					}, 1000);
			}

			let sprintf = function() {
				let a = Array.prototype.slice.call(arguments),
					str = a.shift(),
					total = a.length,
					reg;
				for (let i = 0; i < total; i++) {
					reg = new RegExp('%(' + (i + 1) + '\\$)?(s|d|f)');
					str = str.replace(reg, a[i]);
				}
				return str;
			}

			let _ajax = function(action, data, callback, errorCallback) {

				if ($.isFunction(data)) {
					if ($.isFunction(callback)) {
						errorCallback = callback;
					}
					callback = data;
					data = {};
				}
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: $.extend({
						action: 'ig_es_' + action,
						security: wpnonce
					}, data),
					success: function (data, textStatus, jqXHR) {
						callback && callback.call(this, data, textStatus, jqXHR);
					},
					error: function (jqXHR, textStatus, errorThrown) {
						if (textStatus == 'error' && !errorThrown) return;
						if (console) console.error($.trim(jqXHR.responseText));
						errorCallback && errorCallback.call(this, jqXHR, textStatus, errorThrown);
					},
					dataType: "JSON"
				});
			}

			if ( 'object' === typeof (wpUploaderInit) ) {
				uploader_init();
			}

			$(document).on('ig_es_get_import_data', get_import_data );
			$(document).on('ig_es_trigger_import', trigger_import );
			$(document).on('ig_es_do_import', do_import );

			$('#form_import_subscribers').on('submit', function(e){
				e.preventDefault();

				let is_email_field_set, is_list_name_field_set, is_subscriber_status_field_set = false;
				let mapping_order = [];
				let identifier = $('#identifier').val();

				$('select[name="mapping_order[]"').each(function(){
					let mapped_field = $(this).val();
					mapping_order.push(mapped_field);
					if ( 'email' === mapped_field ) {
						is_email_field_set = true;
					} else if( 'list_name' === mapped_field ){
						is_list_name_field_set = true;
					} else if( 'status' === mapped_field ){
						is_subscriber_status_field_set = true;
					}
				});


				if ( ! is_email_field_set ) {
					alert(ig_es_js_data.i18n_data.select_email_column);
					return false;
				}

				let send_optin_emails_checkbox = $('#send_optin_emails');
				let send_optin_emails = send_optin_emails_checkbox.is(':checked') ? 'yes' : 'no';

				let update_subscribers_data = $('input[name="ig-es-update-subscriber-data"]:checked').val();

				let status = $('#es_email_status').val();
				if ( 'es-import-mailchimp-users' !== import_option && ('' === status || '0' === status) && ! is_subscriber_status_field_set  ) {
					alert(ig_es_js_data.i18n_data.select_status);
					return false;
				}

				let list_id = $('#list_id').val();
				if ( 'es-import-mailchimp-users' !== import_option && ( '0' === list_id || ( Array.isArray(list_id) && 0 === list_id.length ) ) && ! is_list_name_field_set ) {
					alert(ig_es_js_data.i18n_data.select_list);
					return false;
				}

				if ( ! confirm(ig_es_js_data.i18n_data.confirm_import) ) {
					return false;
				}

				let import_data = {
					identifier: identifier,
					list_id: list_id,
					status: status,
					mapping_order: mapping_order,
					send_optin_emails: send_optin_emails,
					update_subscribers_data: update_subscribers_data
				}
				$(document).trigger('ig_es_trigger_import', [import_data]);
			});

			$('#ig-es-import-wordpress-users-btn')
				.on('click', function () {
					let elem = jQuery(this);
					jQuery(elem).find('.es-btn-arrow').hide();
					jQuery(elem).find('.es-btn-loader').show().addClass('animate-spin').attr('disabled', true);

					let selected_roles = [];
					$('#ig-es-wordpress-user-roles input[name="roles[]"]:checked').each(function(){
						let selected_role = $(this).val();
						selected_roles.push(selected_role);
					});

					_ajax('import_subscribers_upload_handler', {
						selected_roles: selected_roles,
						importing_from: 'wordpress_users'
					}, function (response) {
						if (response.success) {
							importidentifier = response.identifier;
							$('#wordpress-users').fadeOut();
							jQuery(document).trigger('ig_es_get_import_data',[{identifier:importidentifier}]);
						} else {
							jQuery(elem).find('.es-btn-arrow').show();
							jQuery(elem).find('.es-btn-loader').hide().removeClass('animate-spin').removeAttr('disabled');
							progress.addClass('error');
							alert(response.message);
						}
					}, function () {
						importstatus.html('Error');
					});

					return false;
				});

				$("input:radio[name='es-import-subscribers']").click(function() {
					let import_option = $(this).attr("value");
					$('.es-import').hide();
					$('.' + import_option).show();
				});

				$('#es_mailchimp_verify_api_key').click(function(e){
					e.preventDefault();
					let btn_elem = $(this);
					let mailchimp_api_key = $('#api-key').val();
					let api_import_status = $('.es-api-import-status');
					let steps_loader      = $('.es-import-loader');
					let data = {
						action       		: 'ig_es_mailchimp_verify_api_key',
						security     		: ig_es_js_data.security,
						mailchimp_api_key   : mailchimp_api_key,
					};

					jQuery.ajax({
						method: 'POST',
						url: ajaxurl,
						data: data,
						dataType: 'json',
						beforeSend: function() {
							steps_loader.show().addClass('animate-spin').attr('disabled', true);
							jQuery(btn_elem).attr('disabled', true);
						},
						success: function (response) {
							if (response.success) {
								api_import_status.show().html(ig_es_js_data.i18n_data.api_verification_success).addClass('text-green-500').removeClass('text-red-600');
								get_mailchimp_lists( mailchimp_api_key );
							} else {
								api_import_status.show().html(response.data.error).addClass('text-red-600');
							}
						},
						error: function (err) {
							api_import_status.show().html(err.responseJSON.data.error).addClass('text-red-600');
							jQuery(btn_elem).attr('disabled', false);
						}
					}).always(function(){
						steps_loader.hide().removeClass('animate-spin').attr('disabled', false);
					});
				});

				$('#es_import_mailchimp_list_members').on('click', function (e) {
					e.preventDefault();
					Import_Mailchimp_Lists();
				});

				function get_mailchimp_lists( api_key = '' ){
					let data = {
						action       		: 'ig_es_mailchimp_lists',
						security     		: ig_es_js_data.security,
						mailchimp_api_key   : api_key,
					};

					jQuery.ajax({
						method: 'POST',
						url: ajaxurl,
						data: data,
						dataType: 'json',
						success: function (response) {
							if (response.success) {

								var wrap = jQuery('.mailchimp-lists'),
								tmpl = wrap.find('li');

								jQuery.each(response.data.lists, function (i, list) {
									var clone = tmpl.clone().removeClass('hidden').addClass('lead').data('id', list.id);
									clone.attr('data-listname', list.name);
									clone.find('.mailchimp_list_name').html(list.name);
									clone.find('label').attr('for', 'list-' + list.id);
									clone.find('input').attr('id', 'list-' + list.id).prop('checked', list.stats.member_count);
									setTimeout(function () {
										clone.hide().appendTo(wrap).slideDown();
									}, 10 * i);
								});

									jQuery('.mailchimp_import_step_1').show();
									jQuery(".es-import-step1").slideUp();
									jQuery('.es-import-option').hide();
							} else {
								alert( ig_es_js_data.i18n_data.ajax_error_message );
							}
						},
						error: function (err) {
							alert( ig_es_js_data.i18n_data.ajax_error_message );
						}
					});
				}

				function Import_Mailchimp_Lists(){
					var items_completed = 0;
					var current_offset = 0;
					var current_limit = 1000;
					var current_item = "";
					var $current_node;
					var current_status;
					var mailchimp_api_key = jQuery('#api-key').val();
					var total_list_subscribers_added = 0;

					jQuery(".es_mailchimp_lists_and_status_input").addClass("installing");
					jQuery(".es_mailchimp_lists_and_status_input").find("input").prop("disabled", true);
					jQuery(".mailchimp_notice_nowindow_close").show().html(ig_es_js_data.i18n_data.mailchimp_notice_nowindow_close);

					current_status = jQuery('input[name="options"]:checked').map(function () {
						return jQuery(this).val()
					}).get();

					jQuery('.es-list-import-loader').show().addClass('animate-spin').attr('disabled', true);
					find_next_list();

					function ajax_callback(response) {
						var currentSpan = $current_node.find("label");

						if( response.success ) {
							importidentifier = response.data.identifier;
							if ( ! response.data.added ) { // If no subscribers left
								currentSpan.addClass("success");
								current_offset = 0;
								total_list_subscribers_added = 0;
								find_next_list();
							} else {
								current_offset += current_limit;
								total_list_subscribers_added += response.data.added;
								$current_node.find('.mailchimp_list_contact_fetch_count').html( '(' + total_list_subscribers_added + '/ ' + response.data.total + ')' );
								process_current();
							}
						} else {
							current_limit = 500;
							var error_counter = jQuery($current_node).data('error-counter');
							error_counter++;
							jQuery($current_node).data('error-counter', error_counter);
							if( error_counter < 3 ){
								process_current();
							} else {
								jQuery(".mailchimp_notice_nowindow_close").hide();
								jQuery('#es_import_mailchimp_list_members').text('Try again');
								jQuery($current_node).addClass('text-red-600').removeClass('installing');
								jQuery('.es-list-import-loader').hide().removeClass('animate-spin error').attr('disabled', false);
								alert( ig_es_js_data.i18n_data.ajax_error_message );
							}
						}
						return;
					}

					function process_current() {
						if (current_item) {
							var $check = $current_node.find("input:checkbox");
							var currentSpan = $current_node.find("label");
							currentSpan.removeClass('installing success error');
							if ($check.is(":checked")) {
								var listname = $current_node.data('listname');
								currentSpan.addClass('installing');
								jQuery.post(ajaxurl, {
									action 			  : "ig_es_mailchimp_import_list",
									security 	      : ig_es_js_data.security,
									id 				  : current_item,
									offset 		   	  : current_offset,
									limit 			  : current_limit,
									status 		 	  : current_status,
									mailchimp_api_key : mailchimp_api_key,
									list_name 	 	  : listname,
									identifier 	 	  : importidentifier
								}, ajax_callback).fail(function(){
									var error_counter = jQuery($current_node).data('error-counter');
									error_counter++;
									jQuery($current_node).data('error-counter', error_counter);
									if( error_counter < 3 ){
										process_current();
									} else{
										jQuery($current_node).addClass('text-red-600');
									}
								});
							} else {
								$current_node.addClass("skipping").removeClass("installing");
								setTimeout(find_next_list, 300);
							}
						}
					}

					function mailchimp_list_import_complete(){
						jQuery(document).trigger('ig_es_get_import_data',[{identifier:importidentifier}]);
						jQuery(".mailchimp_notice_nowindow_close").hide();
						jQuery('.es-list-import-loader').hide().removeClass('animate-spin').attr('disabled', false);
					}

					function find_next_list() {
						if ($current_node) {
							if (!$current_node.data("done_item")) {
								items_completed++;
								$current_node.data("done_item", 1);
							}
							$current_node.find(".spinner").css("visibility", "hidden");
						}
						var $li = jQuery(".es_mailchimp_lists_and_status_input.mailchimp-lists li:visible");
						$li.each(function () {
							var $item = jQuery(this);
							if ($item.data("done_item")) {
								return true;
							}

							current_item = $item.data("id");
							if (!current_item) {
								return true;
							}
							$current_node = $item;
							process_current();
							return false;
						});
						if (items_completed >= $li.length) {
							//Finished importing all lists to temporary table
							mailchimp_list_import_complete();
						}
					}
				}

				var link_activity_rows = $("#es_reports_link_activity tbody tr");
				var link_activity_more = $("#es_link_activity_more");
				var link_activity_less = $("#es_link_activity_less");
				var link_activity_table_length = link_activity_rows.length;
				var link_activity_currentIndex = 5;

				link_activity_rows.hide();
				link_activity_rows.slice(0, 5).show();
				check_link_activity_rows();

				link_activity_more.click(function (e) {
				    e.preventDefault();
				    $("#es_reports_link_activity tbody tr").slice(link_activity_currentIndex, link_activity_currentIndex + 10).show();
				    link_activity_currentIndex += 10;
				    check_link_activity_rows();
				});

				link_activity_less.click(function (e) {
				    e.preventDefault();

					link_activity_rows.hide();
					link_activity_rows.slice(0, 5).show();
					link_activity_currentIndex = 5;
				    check_link_activity_rows();
				});

				function check_link_activity_rows() {
				    var currentLength = $("#es_reports_link_activity tbody tr:visible").length;
				    if (currentLength >= link_activity_table_length) {
				        link_activity_more.hide();
				    } else {
				        link_activity_more.show();
				    }

				    if (link_activity_table_length > 5 && currentLength > 5) {
				        link_activity_less.show();
				    } else {
				        link_activity_less.hide();
				    }

				}


			// Find al rating items
			const ratings = document.querySelectorAll(".es-engagement-score");

			// Iterate over all rating items
			ratings.forEach((rating) => {
			// Get content and get score as an int
			const ratingContent = rating.innerHTML;
			const ratingScore = ratingContent;
			const ratingPercentage = ( ratingScore / 5 ) * 100;

			// Define if the score is good, meh or bad according to its value
			//   const scoreClass =
			//     ratingScore < 40 ? "bad" : ratingScore < 60 ? "meh" : "good";

			//   // Add score class to the rating
			//   rating.classList.add(scoreClass);

			// After adding the class, get its color
			const ratingColor = window.getComputedStyle(rating).backgroundColor;

			// Define the background gradient according to the score and color
			const gradient = `background: conic-gradient(${ratingColor} ${ratingPercentage}%, transparent 0 100%)`;

			// Set the gradient as the rating background
			rating.setAttribute("style", gradient);

			// Wrap the content in a tag to show it above the pseudo element that masks the bar
			rating.innerHTML = `<span>${ratingScore} ${
				ratingContent.indexOf("%") >= 0 ? "<small>%</small>" : ""
			}</span>`;
			});

			/* DND form builder code start */
			jQuery('#es-form-name,#es-toggle-form-name-edit').click(function(){
				jQuery('#es-form-name').removeAttr('readonly','readonly').focus();
				jQuery('#es-toggle-form-name-edit').hide();
			});

			jQuery('#es-form-name').blur(function(){
				jQuery('#es-toggle-form-name-edit').show();
				jQuery(this).attr('readonly','readonly');
			});

			$('#es-edit-form-container').on('click','#form_settings_menu,#view_form_summary_button',function(e){

				let form_html       = window.esVisualEditor.getHtml();
				let form_css        = window.esVisualEditor.getCss();
				let form_components = window.esVisualEditor.getComponents();

				$('#ig-es-export-html-data-textarea').val(form_html);
				$('#ig-es-export-css-data-textarea').val(form_css);
				$('#form-dnd-editor-data').val(JSON.stringify(form_components));

				let captcha = window.esVisualEditor.Canvas.getDocument().getElementsByClassName('es_captcha').length > 0 ? 'yes' : 'no';
				$('input[name="form_data[settings][captcha]"]').val(captcha);

				let form_contains_lists_block = window.esVisualEditor.Canvas.getDocument().getElementsByClassName('es-list').length > 0;
				if ( form_contains_lists_block ) {
					// Hide lists settings section from Form's settings
					$('.es-form-lists').addClass('hidden');
					$('.es-form-lists input[name="form_data[settings][lists][]"]').prop('checked', false);
				} else {
					// Show lists settings section in Form's settings
					$('.es-form-lists').removeClass('hidden');
				}

				let form_data = $(this).closest('form').serialize();

				// Add action to form data
				form_data += form_data + '&action=ig_es_get_form_preview&security='  + ig_es_js_data.security;
				jQuery.ajax({
					method: 'POST',
					url: ajaxurl,
					data: form_data,
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							if ( 'undefined' !== typeof response.data ) {
								let response_data    = response.data;
								let preview_html     = response_data.preview_html;
								preview_html         = ig_es_preprare_iframe_preview_html( preview_html );
								ig_es_load_iframe_preview('.form_preview_content', preview_html);
							}
						} else {
							alert( ig_es_js_data.i18n_data.ajax_error_message );
						}
					},
					error: function (err) {
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					}
				});
			});

			$('#es-edit-form-container form').on('submit', function(e) {
				let list_required = !$('.es-form-lists').hasClass('hidden');
				if (list_required) {
					let selected_lists_count = $('#ig-es-multiselect-lists').val() ? $('#ig-es-multiselect-lists').val().length : 0;
					if (selected_lists_count === 0) {
						alert(ig_es_form_editor_data.i18n.no_list_selected_message);
						e.preventDefault();
						return false;
					}
				}
			});
			
			/* DND form builder code end */

		jQuery('body')
		.on('click', '#ig-es-delete-template-image', function (e) {
			e.preventDefault();
			jQuery('#ig-es-template-image-attachment-container').addClass('hidden');
			jQuery('#ig-es-add-template-image').removeClass('hidden');
		})
		.on('click', '#ig-es-add-template-image', function (e) {
			e.preventDefault();
			jQuery(this).addClass('clicked');
			if ( ! wp.media.frames.ig_es_attachments ) {
				// Create the media frame.
				wp.media.frames.ig_es_attachments = wp.media({
					// Set the title of the modal.
					title: ig_es_js_data.i18n_data.add_attachment_text,
					button: {
						text: ig_es_js_data.i18n_data.add_attachment_text,
					},
					multiple: false,
					states: [
						new wp.media.controller.Library({
							filterable: 'png,jpg',
							multiple: false
						})
					]
				});

				// When a user click on Add file button.
				wp.media.frames.ig_es_attachments.on('select', function () {
					let attachment = wp.media.frames.ig_es_attachments.state().get('selection').first().toJSON();
					jQuery('#ig-es-template-image-attachment-container').removeClass('hidden');
					jQuery('#ig_es_template_attachment_image').attr('src', attachment.url);
					jQuery('#ig_es_template_attachment_id').val(attachment.id);
					jQuery('#ig-es-template-image-attachment-container').removeClass('hidden');
					jQuery('#ig-es-add-template-image').addClass('hidden');
				});
			}
			wp.media.frames.ig_es_attachments.open();
		});
		$('#es_template_type').on('change',function(){
			let template_type = $(this).val();
			$('#edit-campaign-form-container').attr('data-campaign-type', template_type);
			ig_es_add_dnd_rte_tags(template_type);
		});
		$('#es_template_type').trigger('change');

		$('#es-dashboard-stats #filter_by_list').on('change',function() {
			let list_id     = $(this).val();
			let days        = 60;
			let action_data = {
				action  : 'ig_es_get_subscribers_stats',
				list_id : list_id,
				days    : days,
				security: ig_es_js_data.security
			}
			$.ajax({
				method    : 'POST',
				url       : ajaxurl,
				data      : action_data,
				dataType  : 'json',
				beforeSend: function() {
					$('#subscribers-stats').addClass('loading es-pulse-animation').css({'filter': 'blur(1px)', '-webkit-filter' : 'blur(1px)'});
				},
				success: function (response) {
					if (response.success) {
						if ( 'undefined' !== typeof response.data ) {
							let response_data = response.data;
							let html          = response_data.html;
							$('#subscribers-stats').replaceWith(html);
						} else {
							alert( ig_es_js_data.i18n_data.ajax_error_message );
						}
					} else {
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					}
				},
				error: function (err) {
					alert( ig_es_js_data.i18n_data.ajax_error_message );
				}
			}).always(function(){
				$('#subscribers-stats').removeClass('loading es-pulse-animation').css({'filter': 'blur(0px)', '-webkit-filter' : 'blur(0px)'});
			});
		});

		$(document).on('es_drag_and_drop_editor_loaded', function(){
			/**
			 * When typing 'n' key in the DnD editor text-block, Jetpack captures the key event and shows its notification panel.
			 * This prevents our users from typing 'n' key in the text-block.
			 * To prevent this, we are setting iframe element name to 'editor-canvas' because Jetpack doesn't triggers its code for elements with this name attribute.
			 */
			window.esVisualEditor.Canvas.getFrameEl().name = 'editor-canvas';
			if ( typeof CKEDITOR !== 'undefined' ) {
				CKEDITOR.dtd.$editable.a = 1;   
   }
		   
		});

	});

	function ig_es_uc_first(string){
		return string.charAt(0).toUpperCase() + string.slice(1);
	}

	let drafting_campaign = false;
	function ig_es_draft_campaign( trigger_elem ) {

		if( drafting_campaign ){
			return;
		}

		drafting_campaign = true;
		let is_draft_bttuon = $(trigger_elem).hasClass('ig_es_draft_campaign');
		let is_save_bttuon  = $(trigger_elem).hasClass('ig_es_save_campaign');

		let campaign_subject = $('#ig_es_campaign_subject').val();
		if ( '' === campaign_subject ) {
			if ( is_draft_bttuon ) {
				alert( ig_es_js_data.i18n_data.campaign_subject_empty_message );
			}
			drafting_campaign = false;
			return;
		}

		// If draft button is clicked then change campaign status to draft..
		if ( is_draft_bttuon ) {
			$('#campaign_status').val(0);
		}

		ig_es_process_campaign_action( 'save' ).then( response => {
			if (response.success) {
				if ( 'undefined' !== typeof response.data ) {
					let response_data = response.data;
					let campaign_id  = response_data.campaign_id;
					$('#campaign_id').val( campaign_id );
					if ( is_draft_bttuon || is_save_bttuon ) {
						alert( ig_es_js_data.i18n_data.campaign_saved_message );
					}
				} else {
					if ( is_draft_bttuon ) {
						alert( ig_es_js_data.i18n_data.campaign_error_message );
					}
				}
			} else {
				alert( ig_es_js_data.i18n_data.ajax_error_message );
			}
		}, response => {
			alert( ig_es_js_data.i18n_data.ajax_error_message );
		});
	}

	let save_campaign = false;
	function ig_es_save_campaign( trigger_elem ) {

		if( save_campaign ){
			return;
		}

		save_campaign = true;
		$(trigger_elem).addClass('loading');

		let campaign_subject = $('#ig_es_campaign_subject').val();
		if ( '' === campaign_subject ) {
			alert( ig_es_js_data.i18n_data.campaign_subject_empty_message );
			save_campaign = false;
			return;
		}
		
		//$('#campaign_status').val(ig_es_js_data.campaign_statuses.active);
		
		ig_es_process_campaign_action( 'save' ).then( response => {
			$(trigger_elem).removeClass('loading');
			save_campaign = false;
			if (response.success) {
				alert( ig_es_js_data.i18n_data.campaign_saved_message );
				window.location.href = ig_es_js_data.campaigns_page_url;
			} else {
				alert( response.message );
			}
		}, response => {
			scheduling_campaign = false;
			alert( ig_es_js_data.i18n_data.ajax_error_message );
		});
	}

	// Flag to prevent multiple scheduling requests when user clicks mutliple time in short interval.
	let scheduling_campaign = false;
	function ig_es_save_and_schedule_campaign( trigger_elem ) {

		if( scheduling_campaign ){
			return;
		}

		scheduling_campaign = true;
		$(trigger_elem).addClass('loading');

		let campaign_subject = $('#ig_es_campaign_subject').val();
		if ( '' === campaign_subject ) {
			alert( ig_es_js_data.i18n_data.campaign_subject_empty_message );
			scheduling_campaign = false;
			return;
		}
		
		$('#campaign_status').val(ig_es_js_data.campaign_statuses.scheduled);
		
		ig_es_process_campaign_action( 'save_and_schedule' ).then( response => {
			$(trigger_elem).removeClass('loading');
			scheduling_campaign = false;
			if (response.success) {
				alert( ig_es_js_data.i18n_data.campaign_scheduled_message );
				window.location.href = ig_es_js_data.campaigns_page_url;
			} else {
				alert( response.message );
			}
		}, response => {
			scheduling_campaign = false;
			alert( ig_es_js_data.i18n_data.ajax_error_message );
		});
	}

	let activate_campaign = false;
	function ig_es_activate_campaign( trigger_elem ) {

		if( activate_campaign ){
			return;
		}

		activate_campaign = true;
		$(trigger_elem).addClass('loading');

		let campaign_subject = $('#ig_es_campaign_subject').val();
		if ( '' === campaign_subject ) {
			alert( ig_es_js_data.i18n_data.campaign_subject_empty_message );
			activate_campaign = false;
			return;
		}
		
		$('#campaign_status').val(ig_es_js_data.campaign_statuses.active);
		
		ig_es_process_campaign_action( 'activate' ).then( response => {
			$(trigger_elem).removeClass('loading');
			activate_campaign = false;
			if (response.success) {
				alert( ig_es_js_data.i18n_data.campaign_activated_message );
				window.location.href = ig_es_js_data.campaigns_page_url;
			} else {
				alert( response.message );
			}
		}, response => {
			scheduling_campaign = false;
			alert( ig_es_js_data.i18n_data.ajax_error_message );
		});
	}

	function ig_es_process_campaign_action( action_method ) {
		ig_es_sync_wp_editor_content();
		let campaign_data = $('#campaign_form').serialize();
		campaign_data += '&action=icegram-express&handler=campaign&method=' + action_method + '&security='  + ig_es_js_data.security;
		return jQuery.ajax({
			method: 'POST',
			url: ajaxurl,
			data: campaign_data,
			dataType: 'json',
			beforeSend: function() {
				// Prevent submit button untill saving is complete.
				$('#ig_es_campaign_submitted').addClass('opacity-50 cursor-not-allowed').attr('disabled','disabled');
			}
		});
	}

	function ig_es_save_campaign_as_template() {

		ig_es_sync_wp_editor_content();

		let campaign_subject = $('#ig_es_campaign_subject').val();
		let campaign_content = $('textarea[name="data[body]"]').val();

		if ( '' === campaign_subject || '' === campaign_content ) {
			return;
		}

		let save_template_button = $('#save_campaign_as_template_button');

		let form_data = $('form#campaign_form').serialize();
		// Add action to form data
		form_data += '&action=ig_es_save_as_template&security='  + ig_es_js_data.security;
		jQuery.ajax({
			method: 'POST',
			url: ajaxurl,
			data: form_data,
			dataType: 'json',
			beforeSend: function() {
				$(save_template_button).next('.es-loader').show();
			},
			success: function (response) {
				if ( response.success ) {
					$(save_template_button).parent().find('.es-saved-success').show();
				} else {
					$(save_template_button).parent().find('.es-saved-error').show();
				}
			},
			error: function (err) {
				alert( ig_es_js_data.i18n_data.ajax_error_message );
			}
		}).always(function(){
			$(save_template_button).next('.es-loader').hide();
		});
	}
})(jQuery);


function checkDelete() {
	return confirm( ig_es_js_data.i18n_data.delete_confirmation_message );
}

function ig_es_show_broadcast_preview_in_popup() {
	ig_es_sync_wp_editor_content();

	let content = jQuery('.wp-editor-boradcast').val();
	if (jQuery("#wp-edit-es-broadcast-body-wrap").hasClass("tmce-active")) {
		content = tinyMCE.activeEditor.getContent();
	} else {
		content = jQuery('.wp-editor-boradcast').val();
	}


	if ( !content ) {
		alert( ig_es_js_data.i18n_data.empty_template_message );
		return;
	}

	let template_button = jQuery('#es_test_email_btn');
	jQuery(template_button).next('.es-loader').show();
	let form_data = jQuery('#es_test_email_btn').closest('form').serialize();
	// Add action to form data
	form_data += form_data + '&action=ig_es_preview_broadcast&security='  + ig_es_js_data.security;
	jQuery.ajax({
		method: 'POST',
		url: ajaxurl,
		data: form_data,
		dataType: 'json',
		success: function (response) {
			if (response.success) {
				if ( 'undefined' !== typeof response.data ) {
					let response_data = response.data;
					let template_html = response_data.template_html;
					jQuery('#preview_template').load().show();
					ig_es_load_iframe_preview( '.broadcast_preview_container', template_html );
				}
			} else {
				alert( ig_es_js_data.i18n_data.ajax_error_message );
			}
		},
		error: function (err) {
			alert( ig_es_js_data.i18n_data.ajax_error_message );
		}
	}).done(function(){
		jQuery(template_button).next('.es-loader').hide();
	});
}

function ig_es_show_campaign_preview_in_popup() {
	ig_es_sync_wp_editor_content();

	let content = jQuery('textarea[name="data[body]"],textarea[name="data[body]"]').val();
	if (jQuery("#edit-es-campaign-body-wrap").hasClass("tmce-active")) {
		content = tinyMCE.activeEditor.getContent();
	}


	if ( !content ) {
		alert( ig_es_js_data.i18n_data.empty_template_message );
		return;
	}

	
	let form_data = jQuery('#view_campaign_preview_button').closest('form').serialize();
	// Add action to form data
	form_data += form_data + '&action=ig_es_get_campaign_preview&security='  + ig_es_js_data.security;
	jQuery.ajax({
		method: 'POST',
		url: ajaxurl,
		data: form_data,
		dataType: 'json',
		success: function (response) {
			if (response.success) {
				if ( 'undefined' !== typeof response.data ) {
					let response_data = response.data;
					let template_html = response_data.preview_html;
					jQuery('#browser-preview-tab').trigger('click');
					ig_es_load_iframe_preview( '#campaign-preview-iframe-container', template_html );
					// We are setting popup visiblity hidden so that we can calculate iframe width/height before it is shown to user.
					jQuery('#campaign-preview-popup').css('visibility','hidden').show();
					setTimeout(()=>{
						jQuery('#campaign-preview-popup').css('visibility','visible');
					},100);
				}
			} else {
				alert( ig_es_js_data.i18n_data.ajax_error_message );
			}
		},
		error: function (err) {
			alert( ig_es_js_data.i18n_data.ajax_error_message );
		}
	}).done(function(){
		jQuery('#view_campaign_preview_button').removeClass('loading');
	});
}

function ig_es_sync_wp_editor_content() {
	// When visual mode is disabled in wp user profile, tinyMCE library isn't enqueued.
	// We aren't triggering the save event in that case
	if ( 'undefined' !== typeof window.tinyMCE ) {
		// Trigger save event for content of wp_editor instances to sync its content with actual textarea field
		window.tinyMCE.triggerSave();
	}

	ig_es_sync_dnd_editor_content( '#campaign-dnd-editor-data' );
}

function ig_es_sync_dnd_editor_content( data_field_id ) {
	// Save the editor content to textarea
	if ( 'undefined' !== typeof window.esVisualEditor ) {
		let dnd_editor_data = window.esVisualEditor.exportEditorContent();
		jQuery(data_field_id).val(dnd_editor_data.data);
	}
}
window.ig_es_sync_dnd_editor_content = ig_es_sync_dnd_editor_content;

function ig_es_load_iframe_preview( parent_selector, iframe_html ) {
	jQuery( parent_selector + ' iframe').remove();

	let iframe = document.createElement('iframe');

	jQuery(parent_selector).html(iframe);

	let should_set_max_height = jQuery(parent_selector).hasClass('popup-preview');

	if ( should_set_max_height ) {
		// Provide height and width to it
		iframe.setAttribute("style","margin:auto;max-height:60vh;height:auto;width:100%;");
	} else {
		iframe.setAttribute("style","height:auto;width:100%;");
	}

	iframe.setAttribute("onload","ig_es_resize_iframe(this)");
	jQuery(iframe).attr("srcdoc", iframe_html);
}

window.ig_es_load_iframe_preview = ig_es_load_iframe_preview

function ig_es_resize_iframe( ifram_elem ) {

	let iframe_width  = ifram_elem.contentWindow.document.documentElement.offsetWidth;
	let iframe_height = ifram_elem.contentWindow.document.documentElement.offsetHeight;

    ifram_elem.style.width  = iframe_width + 'px';
    ifram_elem.style.height = iframe_height + 'px';
}

function ig_es_is_valid_json( string ) {
	try {
		JSON.parse( string );
	} catch (e) {
		return false;
	}
	return true;
}

function ig_es_is_valid_email( email ) {
	let regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	return regex.test(email);
}

window.ig_es_is_valid_json = ig_es_is_valid_json;

function renderESClassicEditor( containerId, content ) {
	if ( jQuery.fn.wp_js_editor ) {
		jQuery( '#' + containerId ).val(content).wp_js_editor();
	} else {
		setTimeout(() => {
			jQuery( '#' + containerId ).val(content).wp_js_editor();
		},300)
	}
}

window.renderESClassicEditor = renderESClassicEditor

function removeESClassicEditor( containerId ) {
	jQuery( '#' + containerId ).wp_js_editor('remove');
}

window.removeESClassicEditor = removeESClassicEditor

function ig_es_add_dnd_rte_tags(CampaignOrEditorType) {
	let option_html = '';
  
	const addOptgroup = (label, tags) => {
	  if (Array.isArray(tags)) {
		option_html += `<optgroup label="${label}">`;
		tags.forEach((tag) => {
		  option_html += `<option value="{{${tag.keyword}}}">${tag.label}</option>`;
		});
		option_html += '</optgroup>';
	  }
	};
  
	if (CampaignOrEditorType === 'post_notification') {
	  addOptgroup('Post', ig_es_campaign_editor_data.postTags);
	  addOptgroup('Subscriber', ig_es_campaign_editor_data.subscriberTags);
	  addOptgroup('Site', ig_es_campaign_editor_data.siteTags);
	}
  
	if (CampaignOrEditorType === 'newsletter') {
	  addOptgroup('Subscriber', ig_es_campaign_editor_data.subscriberTags);
	  addOptgroup('Site', ig_es_campaign_editor_data.siteTags);
	}
  
	if (CampaignOrEditorType === 'DND_editor_form') {
	  addOptgroup('Site', ig_es_form_editor_data.siteTags);
	}
  
	// Remove to avoid duplicates.
	window.esVisualEditor.RichTextEditor.remove('es-rte-tags');
	window.esVisualEditor.RichTextEditor.add('es-rte-tags', {
	  icon: `<select class="gjs-field">
		<option value="">Select keyword</option>
		${option_html}
	  </select>`,
	  title: 'Keyword',
	  // Bind the 'result' on 'change' listener
	  event: 'change',
	  result: (rte, action) => {
		rte.insertHTML(action.btn.firstChild.value);
	  },
	  // Reset the select on change
	  update: (rte, action) => {
		action.btn.firstChild.value = '';
	  },
	});
  }
  
window.ig_es_add_dnd_rte_tags = ig_es_add_dnd_rte_tags;

ig_es_preprare_iframe_preview_html = preview_html => {
	let frontend_css = ig_es_get_frontend_css();
    let iframe_html = `<!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta http-equiv="X-UA-Compatible" content="ie=edge">
			<title>Document</title>
			${frontend_css}
		</head>
		<body style="background-color:#fff;padding:0">
			<div class="ig-es-form-preview">
				${preview_html}
			</div>
		</body>
	</html>`;
	return iframe_html;
}

ig_es_get_frontend_css = () => {
	return ig_es_js_data.frontend_css;
}

jQuery.fn.extend({
	ig_es_select2: function() {
		return this.each(function() {
			let multiselect_elem = jQuery(this);

			let first_option_elem   = jQuery(multiselect_elem).find('option:first');
			let first_option_vallue = jQuery(first_option_elem).attr('value');
			let placeholder_label   = '';

			if ( '' === first_option_vallue || '0' === first_option_vallue ) {

				// Get placeholder label from the first option.
				placeholder_label = jQuery(first_option_elem).text();
				placeholder_label = placeholder_label.trim();

				// Remove it from option to avoid being shown and allowing users to select it as an option in Select2's options panel.
				jQuery(first_option_elem).remove();
			}

			jQuery(multiselect_elem).select2({
				placeholder: placeholder_label, // Add placeholder label using first option's text.
			});
		});
	}
});

//app.js code started
// jQuery(document).ready(function(){

// 	$('.ig-es-campaigns-recipient, .ig-es-campaigns-recipient2, .ig-es-campaigns-recipient3, .ig-es-post-types, .ig-es-select-post-category').select2({
// 	  placeholder: "Select",
// 	  closeOnSelect : false
// 	})
  
//   });
  
  
  // Get all buttons with class="btn" inside the container
//   var nav_btns = document.getElementsByClassName("ig-es-campaign-sidebar-nav-menu");
//   // Loop through the buttons and add the active class to the current/clicked button
//   for (var i = 0; i < nav_btns.length; i++) {
	
// 	nav_btns[i].addEventListener("click", function( event ) {
// 	  event.preventDefault();
// 	  for (let element of nav_btns) {
// 		element.classList.remove('active');
// 		let section = element.getAttribute("href");
// 		document.getElementById(section.replace("#", "")).classList.add("hidden");
// 	  }
	 
// 	  this.className += " active"; 
// 	  let section_display = this.getAttribute("href");
// 	  document.getElementById(section_display.replace("#", "")).classList.remove("hidden")
// 	  document.getElementById(this).classList.add("active")
	
// 	});
//   }
  
//   function setupAccordion() {
// 		const accordionButtons = document.querySelectorAll(".accordion button, .accordion p");
// 		Array.prototype.forEach.call(accordionButtons, btn => {
// 		let target = btn.parentElement.nextElementSibling;
// 		btn.onclick = () => {
// 		  let expanded = btn.getAttribute('aria-expanded') === 'true' || false;
// 		  btn.setAttribute('aria-expanded', !expanded);
// 		  target.hidden = expanded;
// 		  let indicator = btn.querySelectorAll('svg')[0];
// 		  indicator.classList.toggle("transform");
// 		  indicator.classList.toggle("rotate-90");
// 		}
// 		target.hidden = true;
// 	  })
// 	} 
  
// 	// Do on load setup here
// 	function initializeSiteCode() {
// 	  setupAccordion();
// 	}
  
  
// 	// If document is ready already, just initialize
// 	if (document.readyState !== 'loading') {
// 	  initializeSiteCode();
// 	} else {
// 	  // Else wait for ready state
// 	  document.addEventListener('DOMContentLoaded', function () {
// 		initializeSiteCode();
// 	  });
// 	}
  
	function showDropDown(){
		const dropdownButton = document.getElementById('menu-button');
		const dropdownMenu = document.getElementById('dropdown-menu');
        if( dropdownButton && dropdownMenu){
			dropdownButton.addEventListener('click', () => {
			const isExpanded = dropdownButton.getAttribute('aria-expanded') === 'true';
			dropdownButton.setAttribute('aria-expanded', !isExpanded);
		
			dropdownMenu.classList.toggle('hidden');
			dropdownMenu.classList.toggle('scale-100');
			dropdownMenu.classList.toggle('opacity-100');
			dropdownMenu.classList.toggle('scale-95');
			dropdownMenu.classList.toggle('opacity-0');
			});
			// Close the dropdown menu when clicking outside
			document.addEventListener('click', (event) => {
			if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
				dropdownButton.setAttribute('aria-expanded', 'false');
				dropdownMenu.classList.add('hidden');
				dropdownMenu.classList.remove('scale-100', 'opacity-100');
				dropdownMenu.classList.add('scale-95', 'opacity-0');
			}
			});
		}
  
	}
//   document.addEventListener('DOMContentLoaded', () => {
// 	const dropdownButton = document.getElementById('menu-button');
// 	const dropdownMenu = document.getElementById('dropdown-menu');
  
// 	dropdownButton.addEventListener('click', () => {
// 	  const isExpanded = dropdownButton.getAttribute('aria-expanded') === 'true';
// 	  dropdownButton.setAttribute('aria-expanded', !isExpanded);
  
// 	  dropdownMenu.classList.toggle('hidden');
// 	  dropdownMenu.classList.toggle('scale-100');
// 	  dropdownMenu.classList.toggle('opacity-100');
// 	  dropdownMenu.classList.toggle('scale-95');
// 	  dropdownMenu.classList.toggle('opacity-0');
// 	});
  
// 	// Close the dropdown menu when clicking outside
// 	document.addEventListener('click', (event) => {
// 	  if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
// 		dropdownButton.setAttribute('aria-expanded', 'false');
  
// 		dropdownMenu.classList.add('hidden');
// 		dropdownMenu.classList.remove('scale-100', 'opacity-100');
// 		dropdownMenu.classList.add('scale-95', 'opacity-0');
// 	  }
// 	});
//   });
  
//   const viewAllTags = document.getElementById('view-all-tags');
//   const secondaryList = document.querySelector('.secondary-list');
//   const dropdownIcon = document.querySelector('.transition-transform');
  
//   viewAllTags.addEventListener('click', () => {
// 	secondaryList.classList.toggle('hidden');
// 	dropdownIcon.classList.toggle('rotate-180');
//   });
  
//app.js code ended

jQuery(document).ready(function(){

	jQuery(".es-export-report").click(function(){
		var reports_id = '';
		var reportIdAttr = jQuery(this).attr('data-report-id');

		if (reportIdAttr && reportIdAttr.trim() !== "") {
			reports_id = reportIdAttr;
		} else {
			jQuery('table.reports th input[type=checkbox]').each(function(){
				if(jQuery(this).val().trim().length !== 0){
					reports_id += jQuery(this).val() + ",";
				}
			});
		}

		let reports_export_data = {
			action: 'ig_es_reports_export',
			security: ig_es_js_data.security,
			all_reports_id: reports_id,
		};

		jQuery.ajax({
			method: 'POST',
			url: ajaxurl,
			data: reports_export_data,
			xhrFields: {
				responseType: 'blob' // Ensure the response is treated as binary data
			},
			success: function(response, status, xhr) {
				var disposition = xhr.getResponseHeader('Content-Disposition');
				var filename = '';

				if (disposition && disposition.indexOf('attachment') !== -1) {
					var matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
					if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
				}

				var blob = new Blob([response], { type: 'text/csv' });
				var url = window.URL.createObjectURL(blob);
				var a = document.createElement('a');
				a.href = url;
				a.download = filename || 'export.csv';
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				window.URL.revokeObjectURL(url);
			},
			error: function(xhr, status, error) {
				alert('An error occurred during the CSV export.');
				console.error('Error:', error);
				console.error('Status:', status);
				console.error('XHR:', xhr);
			}
		});
	});
});

// Add new list popup functionality
jQuery(document).on('click', '#ig-es-open-add-list-modal', function (e) {
    e.preventDefault();
    jQuery('#ig-es-add-list-modal').removeClass('inactive');
});

jQuery(document).on('click', '#ig-es-list-close-modal, #ig-es-list-cancel-modal', function (e) {
    e.preventDefault();
    jQuery('#ig-es-add-list-modal').addClass('inactive');
	jQuery('#list-message').html(''); 
});

jQuery(document).on('click', '#es-add-list', function (e) {
    e.preventDefault();

    var listName   = jQuery('#add-list-form #es-list-name').val();
    var listDesc   = jQuery('#add-list-form #es-list-desc').val();

    if (listName) {
        var params = {
            es_list_name: listName,
            es_list_desc: listDesc,
            action      : 'ig_es_add_list',
            security    : ig_es_js_data.security 
        };

        // Show the spinner and disable the button
        jQuery('#spinner-image').show();
        jQuery('#es-add-list').prop('disabled', true); 
        jQuery.ajax({
            method: 'POST',
            url: ajaxurl,
            data: params,
            dataType: 'json',
            success: function (response) {
                // Hide the spinner and enable the button
                jQuery('#spinner-image').hide();
                jQuery('#es-add-list').prop('disabled', false); 

                // Handle success response
                if (response.success) {
                    let successMessageHTML = '<span style="color:green">' + response.data.message + '</span>';
                    jQuery('#ig-es-list-message').html(successMessageHTML);
                    // Clear the input fields
                    jQuery('#es-list-name').val('');
                    jQuery('#es-list-desc').val('');

					 // If the multi-select does not exist, create it dynamically
					 if (!jQuery('#ig-es-multiselect-lists').length) {
						let selectHTML = `
							<div class="max-w-sm mx-auto bg-white shadow-md rounded-lg p-6 pt-0.5">
								<label for="form multi-select" class="block text-gray-700 text-sm font-bold mb-2">Select list(s)*</label>
								<select id="ig-es-multiselect-lists" name="form_data[settings][lists][]" multiple class="block w-full border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-blue-300">
								</select>
							</div>`;
						jQuery('.ig-es-multiselect-container').html(selectHTML);
					}

					let listId    = response.data.list_id;  
					let newOption = new Option(listName, listId, true, true);
					jQuery('#ig-es-multiselect-lists').append(newOption).trigger('change');
					jQuery('#ig-es-multiselect-lists').select2();
                    
                } else {
                    let errorMessageHTML = '<span style="color:#e66060"><strong>' + ig_es_js_data.i18n_data.sending_error_text + '</strong>: ' + response.data + '</span>';
                    jQuery('#ig-es-list-message').html(errorMessageHTML);
                }
            },
            error: function (err) {
                // Hide the spinner and enable the button
                jQuery('#spinner-image').hide();
                jQuery('#es-add-list').prop('disabled', false); 
                let errorMessageHTML = '<span style="color:#e66060"><strong>Error:</strong> ' + err.statusText + '</span>';
                jQuery('#ig-es-list-message').html(errorMessageHTML);
            }
        });
    } else {
        alert( __( 'Please enter a list name','email-subscribers' ) ); 
    }
});




