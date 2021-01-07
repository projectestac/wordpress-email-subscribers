(function ($) {

	$(document).ready(
		function () {
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

			$('#menu-nav li:first-child').addClass('active').find('a').addClass('active');
			$('.setting-content').hide();
			$('.setting-content:first').show();

			$('#menu-nav li').click(function(){
				$('#menu-nav li,#menu-nav li a').removeClass('active');
				$(this).addClass('active').find('a').addClass('active');
				$('.setting-content').hide();
				var activeTab = $(this).find('a').attr('href');
				$(activeTab).show();
				return false;
			});

			/*$('#tabs-signup_confirmation, #tabs-email_sending, #tabs-security_settings, #tabs-user_roles').hide();

			$('#tabs-general').show();

			$('a[href^="#"]#menu-content-change').addClass('text-white').parent('li').eq(0).addClass('bg-indigo-600 ').siblings().find('a').addClass('text-gray-700').removeClass('text-white').parent('li').removeClass('bg-indigo-600');

			$('a[href^="#"]#menu-content-change').on('click', function (event) {
				$(this).addClass('text-white').removeClass('text-gray-700').parent('li').addClass('bg-indigo-600').siblings().find('a').addClass('text-gray-700').removeClass('text-white').parent('li').removeClass('bg-indigo-600');
				$('.setting-content').hide();
				var target = $(this).attr('href');
				$('.setting-content' + target).show();
				return false;
			});*/

			$(".pre_btn, #content_menu").click(function() {
				var $fieldset = $(this).closest('.es_fieldset');
				$fieldset.find('.es_broadcast_first').fadeIn('normal');
				$fieldset.next().find('.es_broadcast_second').hide();

				$fieldset.find('#broadcast_button').show();
				$fieldset.find('#broadcast_button1, #broadcast_button2').hide();

				$('#summary_menu').removeClass("active");
				$('#content_menu').addClass("active");
				//$('.active').removeClass('active').prev().addClass('active');

			});

			let schedule_option = $('input:radio[name="broadcast_data[scheduling_option]"]:checked').val()
			broadcast_send_option_change_text(schedule_option);

			$("input:radio[name='broadcast_data[scheduling_option]']").click(function() {
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

			if (jQuery('.statusesselect').length) {
				var statusselect = jQuery('.statusesselect')[0].outerHTML;
			}

			if (jQuery('.groupsselect').length) {
				var groupselect = jQuery('.groupsselect')[0].outerHTML;
			}

			jQuery(".es-audience-view .bulkactions #bulk-action-selector-top").after(statusselect);
			jQuery(".es-audience-view .bulkactions #bulk-action-selector-top").after(groupselect);

			//jQuery(".es-audience-view .bulkactions #bulk-action-selector-bottom").after(statusselect);
			// jQuery(".es-audience-view .bulkactions #bulk-action-selector-bottom").after(groupselect);

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
								$('#es-send-test').parent().find('.helper').html('<span style="color:green">' + response.message + '</span>');
							} else {
								$('#es-send-test').parent().find('.helper').html('<span style="color:#e66060">' + response.message + '</span>');
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
					list_id: selected_list_id
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


			// Broadcast Setttings
			// Get count by list
			$('#ig_es_campaign_submit_button').attr("disabled", true);
			$('#ig_es_broadcast_list_ids').change(function () {
				var selected_list_id = $(this).val();

				if ( ! selected_list_id ) {
					jQuery('.ig_es_list_contacts_count').text(0);
					return;
				}

				// Update total count in lists
				var params = {
					action: 'count_contacts_by_list',
					list_id: selected_list_id,
					status: 'subscribed'
				};

				$.ajax({
					method: 'POST',
					url: ajaxurl,
					async: true,
					data: params,
					success: function (response) {
						if (response !== '') {
							response = JSON.parse(response);
							if (response.hasOwnProperty('total')) {
								var total                 = response.total;
								var total_contacts_text   = "<h2 class='text-sm font-normal text-gray-600'>Total Contacts: <span class='text-base font-medium text-gray-700'> <span class='ig_es_list_contacts_count'>" + total + "</span></span></h2>";
								var total_recipients_text = "<div class='mt-1.5 py-2'><span class='font-medium text-base text-gray-700'><span class='ig_es_list_contacts_count'>" + total + "</span> <span class='text-base font-medium text-gray-700'></span><span class='font-normal text-sm text-gray-500'> recipients </span></div>";
								$('#ig_es_total_contacts').html(total_contacts_text);
								$('#ig_es_total_recipients').html(total_recipients_text);
								if (total == 0) {
									$('#ig_es_campaign_submit_button').attr("disabled", true);
								} else {
									$('#ig_es_campaign_submit_button').attr("disabled", false);
								}
							}
						}
					}
				});
			});

			jQuery(document).on('change', '#base_template_id', function () {
				var template_id = $(this).val();
				// Update total count in lists
				var params = {
					action: 'get_template_content',
					template_id: template_id,
				};
				$.ajax({
					method: 'POST',
					url: ajaxurl,
					async: false,
					data: params,
					success: function (response) {
						if (response !== '') {
							response = JSON.parse(response);
							if (response.hasOwnProperty('subject')) {
								jQuery('.wp-editor-boradcast').val(response.body);
								if ('undefined' !== typeof tinyMCE) {

									var activeEditor = tinyMCE.get('edit-es-boradcast-body');

									if (activeEditor !== null) { // Make sure we're not calling setContent on null
										response.body = response.body.replace(/\n/g, "<br />");
										activeEditor.setContent(response.body); // Update tinyMCE's content

									}
								}

								if (response.inline_css && jQuery('#inline_css').length) {
									jQuery('#inline_css').val(response.inline_css);
								}
								if (response.es_utm_campaign && jQuery('#es_utm_campaign').length) {
									jQuery('#es_utm_campaign').val(response.es_utm_campaign);
								}

								if ( 1 === $('#edit-es-boradcast-body').length ) {
									tinyMCE.triggerSave();
									$('#edit-es-boradcast-body').trigger('change');
								}
							}
						}
					}
				});
			});

			//post notification category select
			jQuery(document).on('change', '.es-note-category-parent', function () {
				var val = jQuery('.es-note-category-parent:checked').val();
				if ('{a}All{a}' === val) {
					jQuery('input[name="es_note_cat[]"]').not('.es_custom_post_type').closest('tr').hide();
				} else {
					jQuery('input[name="es_note_cat[]"]').not('.es_custom_post_type').closest('tr').show();
				}

			});

			jQuery('.es-note-category-parent').trigger('change');


			//es mailer settings
			jQuery(document).on('change', '.es_mailer', function (e) {
				var val = jQuery('.es_mailer:checked').val();
				jQuery('[name*="ig_es_mailer_settings"], .es_sub_headline').not('.es_mailer').hide();
				jQuery(document).find('.' + val).show();
			});
			jQuery('.es_mailer').trigger('change');

			//preview broadcast
			// ig_es_preview_broadcast
			jQuery(document).on('click', '#ig_es_preview_broadcast', function (e) {
				// Trigger save event for content of wp_editor instances before processing it.
				window.tinyMCE.triggerSave();
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
			//add target new to go pro
			jQuery('a[href="admin.php?page=es_pricing"]').attr('target', '_blank').attr('href', 'https://www.icegram.com/email-subscribers-pricing/');

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

			$('.ig_es_draft_broadcast, .next_btn, #summary_menu').on('click', function(e) {

				let trigger_elem    = $(this);
				let is_draft_bttuon = $(trigger_elem).hasClass('ig_es_draft_broadcast');

				let broadcast_subject = $('#ig_es_broadcast_subject').val();
				if ( '' === broadcast_subject ) {
					if ( is_draft_bttuon ) {
						alert( ig_es_js_data.i18n_data.broadcast_subject_empty_message );
					}
					return;
				}

				// If draft button is clicked then change broadcast status to draft..
				if ( is_draft_bttuon ) {
					$('#broadcast_status').val(0);
				}

				// Trigger save event for content of wp_editor instances.
				window.tinyMCE.triggerSave();

				let form_data = $(this).closest('form').serialize();
				// Add action to form data
				form_data += form_data + '&action=ig_es_draft_broadcast&security='  + ig_es_js_data.security;
				jQuery.ajax({
					method: 'POST',
					url: ajaxurl,
					data: form_data,
					dataType: 'json',
					beforeSend: function() {
						// Prevent submit button untill saving is complete.
						$('#ig_es_broadcast_submitted').addClass('opacity-50 cursor-not-allowed').attr('disabled','disabled');
					},
					success: function (response) {
						if (response.success) {
							if ( 'undefined' !== typeof response.data ) {
								let response_data = response.data;
								let broadcast_id  = response_data.broadcast_id;
								$('#broadcast_id').val( broadcast_id );
								if ( is_draft_bttuon ) {
									alert( ig_es_js_data.i18n_data.broadcast_draft_success_message );
								}
							} else {
								if ( is_draft_bttuon ) {
									alert( ig_es_js_data.i18n_data.broadcast_draft_error_message );
								}
							}
						} else {
							alert( ig_es_js_data.i18n_data.ajax_error_message );
						}
					},
					error: function (err) {
						alert( ig_es_js_data.i18n_data.ajax_error_message );
					}
				}).always(function(){
					$('#ig_es_broadcast_submitted').removeClass('opacity-50 cursor-not-allowed').removeAttr('disabled');
				});
			});

			$(".next_btn, #summary_menu").click(function() {
				var $fieldset = $(this).closest('.es_fieldset');
				$fieldset.next().find('div.es_broadcast_second').fadeIn('normal');
				$fieldset.find('.es_broadcast_first').hide();

				$fieldset.find('#broadcast_button1,#broadcast_button2').show();
				$fieldset.find('#broadcast_button').hide();

				$('#content_menu').removeClass("active");
				$('#summary_menu').addClass("active");
				//$('.active').removeClass('active').next().addClass('active');

				// Trigger template content changed event to update email preview.
				$('.wp-editor-boradcast').trigger('change');

			});
			
			$('.wp-editor-boradcast, #edit-es-boradcast-body,#ig_es_broadcast_subject,#ig_es_broadcast_list_ids').on('change',function(event){

				// Trigger save event for content of wp_editor instances before processing it.
				window.tinyMCE.triggerSave();

				let form_data = $(this).closest('form').serialize();
				// Add action to form data
				form_data += form_data + '&action=ig_es_preview_broadcast&preview_type=inline&security='  + ig_es_js_data.security;
				jQuery.ajax({
					method: 'POST',
					url: ajaxurl,
					data: form_data,
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							if ( 'undefined' !== typeof response.data ) {
								let response_data     = response.data;
								let template_html     = response_data.template_html;
								let broadcast_subject = response_data.broadcast_subject;
								let contact_name      = response_data.contact_name;
								let contact_email     = response_data.contact_email;
								$('.broadcast_preview_subject').html(broadcast_subject);
								$('.broadcast_preview_contact_name').html(contact_name);
								if ( '' !== contact_email ) {
									$('.broadcast_preview_contact_email').html( '&lt;' + contact_email + '&gt;');
								}
								$('.broadcast_preview_content').html(template_html);
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

			$('#broadcast_form [name="preview_option"]').on('click',function(){
				let preview_option = $('[name="preview_option"]:checked').val();

				if ( 'preview_in_email' === preview_option ) {
					$('#es_test_send_email').show();
				} else {
					$('#es_test_send_email').hide();
				}
			});

			// Check spam score
			jQuery(document).on('click', '.es_spam' , function(e) {
				e.preventDefault();
				var tmpl_id = jQuery('.es_spam').next().next('#es_template_id').val();
				var subject = jQuery('#ig_es_broadcast_subject').val();
				var content = jQuery('.wp-editor-boradcast').val();
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
					jQuery('.ig_es_broadcast_campaign_name_wrapper').removeClass('hidden');
				} else {
					jQuery('.ig_es_broadcast_campaign_name_wrapper').addClass('hidden');
				}
			});
			
			// Hide trial to premium offer notice when accepted. This function just hide it from frontend, actual notice gets hidden when reloading page in new tab.
			jQuery('#ig-es-optin-trial-to-premium-offer').on('click', function(){
				jQuery(this).closest('.notice').hide('slow');
			});
		});

})(jQuery);

function checkDelete() {
	return confirm('Are you sure?');
}

function ig_es_show_broadcast_preview_in_popup() {
	// Trigger save event for content of wp_editor instances before processing it.
	window.tinyMCE.triggerSave();

	let content = jQuery('.wp-editor-boradcast').val();
	if (jQuery("#wp-edit-es-boradcast-body-wrap").hasClass("tmce-active")) {
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
					jQuery('.broadcast_preview_container').html(template_html);
					jQuery('#preview_template').load().show();
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
	
				// Remove it from option to avoid being shown and allowing users to select it as an option in Select2's options panel. 
				jQuery(first_option_elem).remove();
			}

			jQuery(multiselect_elem).select2({
				placeholder: placeholder_label, // Add placeholder label using first option's text.
			});
		});
	}
});
