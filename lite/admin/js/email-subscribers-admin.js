(function ($) {

	$(document).ready(
		function () {

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
								$('.report_preview_container').html(template_html);
								$('#report_preview_template').load().show();
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
				var fieldset = $(this).closest('.es_fieldset');
				fieldset.find('.es_broadcast_first').fadeIn('normal');
				fieldset.next().find('.es_broadcast_second').hide();

				fieldset.find('#broadcast_button').show();
				fieldset.find('#broadcast_button1, #broadcast_button2').hide();

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
								field = jQuery(this),
								operator_field, value_field;
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
							list_rule_text       = list_rule_text.replace(' [PRO]','');
							if ( 'undefined' !== typeof selected_elem ) {
								if( disable_list_rule && ! ( jQuery(selected_elem)[0] === elem ) ) {
									list_rule_text += ' [PRO]';
									jQuery(list_rule_option).prop("selected", false).attr('disabled','disabled');
								} else {
									jQuery(list_rule_option).removeAttr('disabled');
								}
							} else {
								if( index > 0 && disable_list_rule ) {
									list_rule_text += ' [PRO]';
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
				
						value_field = condition.find('div.ig-es-conditions-value-field[data-fields*=",' + jQuery(field).val() + ',"]').addClass('active').find('.condition-value').prop('disabled', false);
						operator_field = condition.find('div.ig-es-conditions-operator-field[data-fields*=",' + jQuery(field).val() + ',"]').addClass('active').find('.condition-operator').prop('disabled', false);
				
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

				let get_count = 'newsletter' === campaign_type ? 'yes' : 'no'; // Get count only when on broadcast screen

				// Update total count in lists
				let params = {
					action: 'count_contacts_by_list',
					list_id: selected_list_id,
					conditions: conditions,
					status: 'subscribed',
					get_count: get_count
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
									let campaing_id = $(this).data('campaign-id');
									if (edited_campaign_data.hasOwnProperty(campaing_id)) {
										let campaing_name = edited_campaign_data[campaing_id];
										$(this).text(campaing_name);
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
				if ( '{a}All{a}' === val || '{a}None{a}' === val ) {
					jQuery('input[name="es_note_cat[]"]').not('.es_custom_post_type').closest('tr').hide();
				} else {
					jQuery('input[name="es_note_cat[]"]').not('.es_custom_post_type').closest('tr').show();
				}

			});

			jQuery(document).trigger('bind_campaign_rules_events');

			jQuery('.es-note-category-parent').trigger('change');


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
				form_data += '&action=ig_es_draft_broadcast&security='  + ig_es_js_data.security;
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
				var fieldset = $(this).closest('.es_fieldset');
				fieldset.next().find('div.es_broadcast_second').fadeIn('normal');
				fieldset.find('.es_broadcast_first').hide();

				fieldset.find('#broadcast_button1,#broadcast_button2').show();
				fieldset.find('#broadcast_button').hide();

				$('#content_menu').removeClass("active");
				$('#summary_menu').addClass("active");
				//$('.active').removeClass('active').next().addClass('active');

				// Trigger template content changed event to update email preview.
				$('.wp-editor-boradcast').trigger('change');

			});
			
			$('.wp-editor-boradcast, #edit-es-boradcast-body,#ig_es_broadcast_subject').on('change',function(event){

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

			jQuery('#es_close_preview').on('click', function (event) {
				event.preventDefault();
				$('#report_preview_template').hide();
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
					IG_ES_Workflows.init_variables_box();
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
							security: ig_es_js_data.security,
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
							security: ig_es_js_data.security
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
						let confirm_trigger_change = window.confirm( ig_es_js_data.i18n_data.trigger_change_message );
						if ( confirm_trigger_change ) {
							$('.ig-es-action:not([data-action-number=""])').remove();
						}
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
	
						let group = $(el).data( 'ig-es-variable-group' );
	
						if ( -1 === $.inArray( group, trigger.supplied_data_items ) ) {
							$(el).addClass('hidden');
						} else {
							$(el).removeClass('hidden');
						}
					});
	
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
							return $('#ig_es_workflow_variable_preview_field').text();
						}
					});

					clipboard.on('success', function(e) {

						$('.ig-es-clipboard-btn').html( ig_es_js_data.i18n_data.placeholder_copied_message );
						setTimeout(function(){
							IG_ES_Workflows.close_modal();
						}, 500 );
					});

				},


				open_modal: function(){

					var ajax_data = {
						action: 'ig_es_modal_variable_info',
						variable: $(this).text(),
						security: ig_es_js_data.security,
					};

					$.post( ajaxurl, ajax_data, function( response ){
						$('#ig-es-variable-info-popup #ig-es-workflow-variable-info-body').html(response).show();
						IG_ES_Workflows.show_modal();
						IG_ES_Workflows.update_preview_field();
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
				importprogress = $('#importing-progress'),
				importprogressbar = importprogress.find('.bar'),
				import_percentage = importprogress.find('.import_percentage'),
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
					get_import_data();
				});

				uploader.init();
			}

			let get_import_data = function () {

				progress.removeClass('finished error');
				import_option = jQuery('[name="es-import-subscribers"]:checked').val();
				$.post( ajaxurl, {
					action: 'ig_es_get_import_data',
					identifier: importidentifier,
					security: wpnonce,
					dataType: 'json'
				}, function( response ) {
					progress.addClass('hidden');
					$(".es-import-step1").slideUp();
					$('.es-import-option, .mailchimp_import_step_1').hide();
					$('.step2-body').html(response.html).parent().show();
					if( 'es-import-mailchimp-users' !== import_option ){
						$('.step2-status, .step2-list').show();
					}
					$('.wrapper-start-contacts-import').show();
					importstatus.html('');
				});
			}

			let start_import = function(e) {
				
				e.preventDefault();

				let is_email_field_set, is_list_name_field_set, is_subscriber_status_field_set = false;
				let mapping_order = [];
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

				let _this = $('.start-import').prop('disabled', true),
					loader = $('#import-ajax-loading').css({
						'display': 'inline-block'
					}),
					identifier = $('#identifier').val(),
					performance = $('#performance').is(':checked') ? 'yes' : 'no';

				progress.removeClass('hidden');
				progressbar.stop().width(0);
				$('.es-import-step1').slideUp();
				$('.es-import-option').hide();
				$('.step2-body').html('<br><br>').parent().show();
				$('.step2-status,.step2-list, .es-import-processing, .wrapper-start-contacts-import').hide();

				importstarttime = new Date();

				do_import(0, {
					identifier: identifier,
					mapping_order: mapping_order,
					list_id: list_id,
					status: status,
					performance: performance
				});

				importstatus = $('.step2 .import-status');

				importstatus.html(ig_es_js_data.i18n_data.prepare_import);

				window.onbeforeunload = function () {
					return ig_es_js_data.i18n_data.onbeforeunloadimport;
				};
			}

			let do_import = function(id, options) {
				let percentage = 0;
				importprogress.removeClass('hidden');
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					dataType: 'json',
					data: {
						action: 'ig_es_do_import',
						id: id,
						options: options,
						security: wpnonce,
					},
					success: function( response ) {
						percentage = (Math.min(1, (response.imported + response.errors) / response.total) * 100);

						$('.step2-body').html('<p class="pt-3 pb-2 text-sm text-gray-600">' + get_stats(response.f_imported, response.f_errors, response.f_total, percentage, response.memoryusage) + '</p>');

						importerrors = 0;
						let finished = percentage >= 100;

						if (response.success) {

							if (!finished) do_import(id + 1, options);

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
										$('.step2-body').html(response.html).slideDown();
										importstatus.addClass('text-xl');
										importstatus.html(sprintf(ig_es_js_data.i18n_data.import_complete,'<svg class=" w-6 h-6 inline-block text-indigo-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'));
									}
								}
							});
						} else {
							import_error_handler(percentage, id, options);
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						import_error_handler(percentage, id, options);
					}
				});
			}

			let get_stats = function(imported, errors, total, percentage, memoryusage) {
				let timepast = new Date().getTime() - importstarttime.getTime(),
					timeleft = Math.ceil(((100 - percentage) * (timepast / percentage)) / 60000);

				return sprintf(ig_es_js_data.i18n_data.current_stats, '<span class="font-medium">' + imported + '</span>', '<span class="font-medium">' + total + '</span>', '<span class="font-medium">' + errors + '</span>', '<span class="font-medium">' + memoryusage + '</span>') + '<br>' +
					sprintf(ig_es_js_data.i18n_data.estimate_time, timeleft);
			}

			let import_error_handler = function(percentage, id, options) {
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
							do_import(id, options);
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

			$('#form_import_subscribers').on('submit', start_import );

			$('#import_wordpress')
				.on('click', function () {

					let selected_roles = [];
					$('#ig-es-wordpress-user-roles input[name="roles[]"]:checked').each(function(){
						let selected_role = $(this).val();
						selected_roles.push(selected_role);
					});
					_ajax('import_subscribers_upload_handler', {
						selected_roles: selected_roles
					}, function (response) {

						if (response.success) {
							importidentifier = response.identifier;
							$('#wordpress-users').fadeOut();
							get_import_data();
						} else {
							importstatus.html(response.message);
							progress.addClass('error');
						}
					}, function () {

						importstatus.html('Error');
					});

					return false;
				});	

				$("input:radio[name='es-import-subscribers']").click(function() {
						let import_option = $(this).attr("value");
						if( "es-sync-wordpress-users" === import_option ){
							$(".es-sync-wordpress-users").show();
							$(".es-import-with-csv, .es-import-mailchimp-users").hide();
						} else if ("es-import-with-csv" === import_option){
							$(".es-import-with-csv").show();
							$(".es-sync-wordpress-users, .es-import-mailchimp-users").hide();
						} else{
							$(".es-import-mailchimp-users").show();
							$(".es-sync-wordpress-users, .es-import-with-csv").hide();
						}
			});

				$('#es_mailchimp_verify_api_key').click(function(e){
					e.preventDefault();
					let btn_elem = $(this);
					let mailchimp_api_key = $('#api-key').val();
					let api_import_status = $('.es-api-import-status');
					let steps_loader      = $('.es-import-loader');
					let api_key = { 
						action       		: 'ig_es_mailchimp_verify_api_key',
						security     		: ig_es_js_data.security,
						mailchimp_api_key   : mailchimp_api_key,
					};
					
					jQuery.ajax({
						method: 'POST',
						url: ajaxurl,
						data: api_key,
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
					var complete;
					var items_completed = 0;
					var current_offset = 0;
					var current_limit = 1000;
					var current_item = "";
					var $current_node;
					var current_item_hash = "";
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
						} else{
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
						get_import_data();
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

		});

})(jQuery);




function checkDelete() {
	return confirm( ig_es_js_data.i18n_data.delete_confirmation_message );
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