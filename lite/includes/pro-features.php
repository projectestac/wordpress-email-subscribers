<?php

add_filter( 'ig_es_settings_tabs', 'ig_es_add_settings_tabs', 10, 1 );
add_filter( 'ig_es_registered_settings', 'ig_es_add_upsale', 10, 2 );
add_filter( 'ig_es_mailers', 'ig_es_mailers_promo', 11, 1 );

// Add additional tab "Comments" in Audience > Sync
add_filter( 'ig_es_sync_users_tabs', 'ig_es_add_sync_users_tabs', 11, 1 );

add_action( 'ig_es_sync_users_tabs_comments', 'ig_es_add_comments_tab_settings' );
add_action( 'ig_es_sync_users_tabs_woocommerce', 'ig_es_add_woocommerce_tab_settings' );
add_action( 'ig_es_sync_users_tabs_cf7', 'ig_es_add_cf7_tab_settings' );
add_action( 'ig_es_sync_users_tabs_give', 'ig_es_add_give_tab_settings' );
add_action( 'ig_es_sync_users_tabs_wpforms', 'ig_es_add_wpforms_tab_settings' );
add_action( 'ig_es_sync_users_tabs_ninja_forms', 'ig_es_add_ninja_forms_tab_settings' );
add_action( 'ig_es_sync_users_tabs_edd', 'ig_es_add_edd_tab_settings' );

add_action( 'ig_es_workflows_integration', 'ig_es_workflows_integration_metabox', 10, 1 );
add_filter( 'ig_es_display_hidden_workflow_metabox', 'ig_es_show_hidden_workflow_metabox', 10, 1 );

add_action( 'edit_form_advanced', 'add_spam_score_utm_link' );

add_action( 'ig_es_additional_form_options', 'ig_es_add_captcha_option', 10, 1 );
add_action( 'ig_es_campaign_preview_tab_options', 'ig_es_upsale_send_campaign_preview_email_option' );
add_action( 'ig_es_after_campaign_tracking_options_settings', 'ig_es_upsale_campaign_tracking_options', 11 );
// add_action( 'ig_es_broadcast_scheduling_options_settings', 'ig_es_additional_schedule_option');
// add_action( 'ig_es_after_broadcast_right_pan_settings', 'ig_es_additional_spam_score_option');
add_action( 'ig_es_add_multilist_options', 'ig_es_additional_multilist_and_post_digest' );
add_action( 'ig_es_before_' . IG_CAMPAIGN_TYPE_POST_NOTIFICATION . '_content_settings', 'ig_es_upsale_post_digest' );
add_action( 'ig_es_view_report_data', 'ig_es_view_additional_reports_data' );
add_action( 'ig_es_redirect_to_url', 'ig_es_upsell_redirect_to_url' );
add_action( 'ig_es_view_upsell_send_test_email_feature', 'ig_es_upsell_send_test_email_feature', 10, 2 );

// Upsell add attachment feature.
add_action( 'media_buttons', 'ig_es_upsell_add_attachment_feature', 11 );

// Upsell pro import features.
add_action( 'ig_es_subscriber_import_method_tab_heading', 'ig_es_upsell_pro_import_features' );

add_filter( 'ig_es_campaign_rules', 'ig_es_upsell_pro_campaign_rules' );
add_action( 'ig_es_upsell_campaign_rules', 'ig_es_upsell_campaign_rules_message' );
add_filter( 'ig_es_contacts_bulk_action', 'ig_es_upsell_contacts_bulk_action' );

add_action( 'ig_es_after_form_buttons', 'ig_es_upsell_cf_button');
add_action( 'ig_es_additional_form_fields', 'ig_es_upsell_cf_form_field');

add_action( 'ig_es_show_bounced_contacts_stats', 'ig_es_upsell_bounced_dashboard_stats', 10, 2 );
add_action( 'ig_es_show_sequence_message_stats', 'ig_es_upsell_sequence_message_stats' );
add_action( 'ig_es_show_top_countries_stats', 'ig_es_upsell_top_countries_stats' );
add_action( 'ig_es_show_recent_activities', 'ig_es_upsell_recent_activities' );
/**
 * Promote SMTP mailer for free
 *
 * @param $mailers
 *
 * @return mixed
 *
 * @since 4.4.5
 */
function ig_es_mailers_promo( $mailers ) {

	if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {

		$mailers['smtp'] = array(
			'name'       => 'SMTP',
			'logo'       => ES_PLUGIN_URL . 'lite/admin/images/smtp.png',
			'is_premium' => true,
			'plan'       => 'pro',
			'url'        => ES_Common::get_utm_tracking_url(
			array(
				'url'=>'https://www.icegram.com/documentation/how-to-configure-smtp-to-send-emails-in-email-subscribers-plugin/',
				'utm_medium' => 'smtp_mailer'
			)),
		);

		$mailers['gmail'] = array(
			'name'       => 'Gmail',
			'logo'       => ES_PLUGIN_URL . 'lite/admin/images/gmail.png',
			'is_premium' => true,
			'plan'       => 'pro',
			'url'        => ES_Common::get_utm_tracking_url(
			array(
				'url'=>'https://www.icegram.com/documentation/how-to-configure-gmail-to-send-emails-in-email-subscribers/',
				'utm_medium' => 'gmail_mailer'
			)),
		);

	}

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {

		$pro_mailers = array(
			'Amazon_SES' => array(
				'name'       => 'Amazon SES',
				'logo'       => ES_PLUGIN_URL . 'lite/admin/images/aws.svg',
				'is_premium' => true,
				'plan'       => 'max',
				'url'        => ES_Common::get_utm_tracking_url(
					array(
						'url'        => 'https://www.icegram.com/documentation/how-to-configure-amazon-ses-to-send-emails-in-the-email-subscribers-plugin/',
						'utm_medium' => 'amazon_ses_mailer',
					)
				),
			),
			'Mailgun'    => array(
				'name'       => 'Mailgun',
				'logo'       => ES_PLUGIN_URL . 'lite/admin/images/mailgun.svg',
				'is_premium' => true,
				'plan'       => 'max',
				'url'        => ES_Common::get_utm_tracking_url(
					array(
						'url'        => 'https://www.icegram.com/documentation/how-to-configure-mailgun-to-send-emails-in-the-email-subscribers-plugin/',
						'utm_medium' => 'mailgun_mailer',
					)
				),
			),
			'SendGrid'   => array(
				'name'       => 'SendGrid',
				'logo'       => ES_PLUGIN_URL . 'lite/admin/images/sendgrid.svg',
				'is_premium' => true,
				'plan'       => 'max',
				'url'        => ES_Common::get_utm_tracking_url(
					array(
						'url'        => 'https://www.icegram.com/documentation/how-to-configure-sendgrid-to-send-emails-in-the-email-subscribers-plugin/',
						'utm_medium' => 'sendgrid_mailer',
					)
				),
			),
			'SparkPost'  => array(
				'name'       => 'SparkPost',
				'logo'       => ES_PLUGIN_URL . 'lite/admin/images/sparkpost.png',
				'is_premium' => true,
				'plan'       => 'max',
				'url'        => ES_Common::get_utm_tracking_url(
					array(
						'url'        => 'https://www.icegram.com/documentation/how-to-configure-sparkpost-to-send-emails-in-the-email-subscribers-plugin/',
						'utm_medium' => 'sparkpost_mailer',
					)
				),
			),
			'Postmark'   => array(
				'name'       => 'Postmark',
				'logo'       => ES_PLUGIN_URL . 'lite/admin/images/postmark.png',
				'is_premium' => true,
				'plan'       => 'max',
				'url'        => ES_Common::get_utm_tracking_url(
					array(
						'url'        => 'https://www.icegram.com/documentation/how-to-configure-postmark-to-send-emails-in-the-email-subscribers-plugin/',
						'utm_medium' => 'postmark_mailer',
					)
				),
			),
			'Sendinblue'   => array(
				'name'       => 'Sendinblue',
				'logo'       => ES_PLUGIN_URL . 'lite/admin/images/sendinblue.png',
				'is_premium' => true,
				'plan'       => 'max',
				'url'        => ES_Common::get_utm_tracking_url(
					array(
						'url'        => 'https://www.icegram.com/documentation/how-to-configure-sendinblue-to-send-emails-in-the-email-subscribers-plugin/',
						'utm_medium' => 'sendinblue_mailer',
					)
				),
			),
			'Mailjet'   => array(
				'name'       => 'Mailjet',
				'logo'       => ES_PLUGIN_URL . 'lite/admin/images/mailjet.png',
				'is_premium' => true,
				'plan'       => 'max',
				'url'        => ES_Common::get_utm_tracking_url(
					array(
						'url'        => 'https://www.icegram.com/documentation/how-to-configure-mailjet-to-send-emails-in-the-email-subscribers-plugin/',
						'utm_medium' => 'mailjet_mailer',
					)
				),
			),
			'Mailersend'   => array(
				'name'       => 'Mailersend',
				'logo'       => ES_PLUGIN_URL . 'lite/admin/images/mailersend.svg',
				'is_premium' => true,
				'plan'       => 'max',
				'url'        => ES_Common::get_utm_tracking_url(
					array(
						'url'        => 'https://www.icegram.com/express/pricing/?utm_source=in_app&utm_medium=track_clicks&utm_campaign=es_upsell/',
						'utm_medium' => 'mailersend_mailer',
					)
				),
			),
		);
		$mailers     = array_merge( $mailers, $pro_mailers );

	}

	return $mailers;
}

/**
 * Promote User Permission Settings
 *
 * @return false|string
 *
 * @since 4.4.5
 */
function render_user_permissions_settings_fields_premium() {
	$wp_roles   = new WP_Roles();
	$roles      = $wp_roles->get_names();
	$user_roles = array();

	$url = ES_Common::get_utm_tracking_url( array(
		'url'=>'https://www.icegram.com/documentation/how-to-set-custom-permissions-for-user-roles-for-menu-in-email-susbcribers/',
		'utm_medium' => 'user_roles' )
	);

	ob_start();
	?>

	<div class="text-center py-3 lg:px-4">
		<div class="p-2 bg-indigo-800 items-center text-indigo-100 leading-none lg:rounded-full flex lg:inline-flex mx-4 leading-normal" role="alert">
			<span class="font-semibold text-left flex-auto">
				<?php esc_html_e( 'Customize user roles permissions with ', 'email-subscribers' ); ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" class="text-indigo-400"><?php esc_html_e( 'Icegram Express PRO', 'email-subscribers' ); ?></a>
			</span>
		</div>
	</div>

	<p class="py-2 text-sm font-normal text-gray-500"><?php echo esc_html__( 'You can allow different user roles access to different operations within Icegram Express plugin. Please select which roles should have what access below.', 'email-subscribers' ); ?> </p>
	<table class="min-w-full rounded-lg">
		<thead>
			<tr class="bg-gray-100 leading-4 text-gray-500 tracking-wider">
				<th class="pl-10 py-4 text-left font-semibold text-sm"><?php esc_html_e( 'Roles', 'email-subscribers' ); ?></th>
				<th class="px-2 py-4 text-center font-semibold text-sm"><?php esc_html_e( 'Audience', 'email-subscribers' ); ?></th>
				<th class="px-2 py-4 text-center font-semibold text-sm"><?php esc_html_e( 'Forms', 'email-subscribers' ); ?></th>
				<th class="px-2 py-4 text-center font-semibold text-sm"><?php esc_html_e( 'Campaigns', 'email-subscribers' ); ?></th>
				<th class="px-2 py-4 text-center font-semibold text-sm"><?php esc_html_e( 'Reports', 'email-subscribers' ); ?></th>
				<th class="px-2 py-4 text-center font-semibold text-sm"><?php esc_html_e( 'Sequences', 'email-subscribers' ); ?></th>
				<th class="px-2 py-4 text-center font-semibold text-sm"><?php esc_html_e( 'Workflows', 'email-subscribers' ); ?></th>
			</tr>
		</thead>
		<tbody class="bg-white">
			<?php
			foreach ( $roles as $key => $value ) {
				?>
				<tr class="border-b border-gray-200">
					<td class="pl-8 py-4 ">
						<div class="flex items-center">
							<div class="flex-shrink-0">
								<span class="text-sm leading-5 font-medium text-center text-gray-800"><?php echo esc_html( $value ); ?></span>
							</div>
						</div>
					</td>
					<td class="whitespace-no-wrap text-center">
						<input type="checkbox" name="" disabled <?php ! empty( $user_roles['audience'][ $key ] ) ? checked( 'yes', $user_roles['audience'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
					</td>
					<td class="whitespace-no-wrap text-center">
						<input type="checkbox" name="" disabled<?php ! empty( $user_roles['forms'][ $key ] ) ? checked( 'yes', $user_roles['forms'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
					</td>
					<td class="whitespace-no-wrap text-center">
						<input type="checkbox" name="" disabled <?php ! empty( $user_roles['campaigns'][ $key ] ) ? checked( 'yes', $user_roles['campaigns'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
					</td>
					<td class="whitespace-no-wrap text-center">
						<input type="checkbox" name="" disabled <?php ! empty( $user_roles['reports'][ $key ] ) ? checked( 'yes', $user_roles['reports'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
					</td>
					<td class="whitespace-no-wrap text-center">
						<input type="checkbox" name="" disabled <?php ! empty( $user_roles['sequences'][ $key ] ) ? checked( 'yes', $user_roles['sequences'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
					</td>
					<td class="whitespace-no-wrap text-center">
						<input type="checkbox" name="" disabled <?php ! empty( $user_roles['workflows'][ $key ] ) ? checked( 'yes', $user_roles['workflows'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>


	<?php
	$html = ob_get_clean();

	return $html;
}

/**
 * Promote Settings
 *
 * @param $es_settings_tabs
 *
 * @return mixed
 */
function ig_es_add_settings_tabs( $es_settings_tabs ) {

	if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {
		$es_settings_tabs['user_roles'] = array(
			'icon' => '<svg class="w-6 h-6 inline -mt-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
			'name' => __( 'Access Control', 'email-subscribers' ),
		);
	}

	return $es_settings_tabs;
}

/**
 * Promote Features in settings
 *
 * @param $fields
 *
 * @return mixed
 */
function ig_es_add_upsale( $fields ) {

	$general_fields = $fields['general'];

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {

		$utm_args = array(
			'utm_medium' => 'track_clicks',
		);


		$premium_url = ES_Common::get_utm_tracking_url( $utm_args );
		// General Settings
		$general_settings_field = array(
			'ig_es_track_link_click'              => array(
				'id'            => 'ig_es_track_link_click_p',
				'name'          => __( 'Track clicks', 'email-subscribers' ),
				'info'          => __( 'Do you want to track when people click links in your emails? (We recommend keeping it enabled)', 'email-subscribers' ),
				'type'          => 'checkbox',
				'default'       => 'no',
				'is_premium'    => true,
				'plan'          => 'max',
				'link'          => $premium_url,
				'disabled'      => true,
				/* translators: %s: Icegram Pricing page url with utm tracking */
				'upgrade_title' => __( 'Track key insight behaviour with MAX', 'email-subscribers' ),
				'upgrade_desc'  => __( 'Enable Link Tracking, UTM tracking and understand customer behavior to plan your next campaign accordingly.', 'email-subscribers' ),
			),
		);

		$general_fields = ig_es_array_insert_after( $general_fields, 'ig_es_track_email_opens', $general_settings_field );

		if ( ES()->can_upsell_features( array( 'lite', 'starter' ) ) ) {

			$track_utm = array(
				'ig_es_track_utm' => array(
					'id'         => 'ig_es_track_utm_p',
					'name'       => __( 'Google Analytics UTM tracking', 'email-subscribers' ),
					'info'       => __( 'Do you want to automatically add campaign tracking parameters in emails to track performance in Google Analytics? (We recommend keeping it enabled)', 'email-subscribers' ),
					'type'       => 'checkbox',
					'default'    => 'no',
					'is_premium' => true,
					'plan'       => 'max',
					'link'       => ES_Common::get_utm_tracking_url( array(
						'url'=>'https://www.icegram.com/documentation/how-to-add-utm-parameters-to-email/',
						'utm_medium' => 'utm_tracking' ) ),
					'disabled'   => true,
				),
				'ig_es_summary_automation' => array(
					'id'         => 'summary_automation',
					'name'       => __( 'Weekly summary', 'email-subscribers' ),
					'info'       => __( 'Would you like to receive an automated weekly summary?', 'email-subscribers' ),
					'type'       => 'checkbox',
					'default'    => 'no',
					'is_premium' => true,
					'plan'       => 'max',
					'link'       => ES_Common::get_utm_tracking_url( array(
						'url'		 => 'https://www.icegram.com/documentation/enabling-and-understanding-the-weekly-summary-report-in-the-email-subscribers/',
						'utm_medium' => 'summary_automation' ) ),
					'disabled'   => true,
				),

			);

			$general_fields = ig_es_array_insert_after( $general_fields, 'ig_es_track_link_click', $track_utm );
		}
		
	}

	if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {

		$starter_general_setting_fields = array(
			'ig_es_intermediate_unsubscribe_page' => array(
				'id'         => 'ig_es_intermediate_unsubscribe_page_p',
				'name'       => __( 'Allow user to select list(s) while unsubscribing', 'email-subscribers' ),
				'info'       => __( 'Enabling this will let users unsubscribe from multiple lists at once. (We recommend keeping it enabled)', 'email-subscribers' ),
				'type'       => 'checkbox',
				'default'    => 'no',
				'is_premium' => true,
				'plan'       => 'pro',
				'link'       => ES_Common::get_utm_tracking_url( array(
					'url' => 'https://www.icegram.com/documentation/how-to-allow-user-to-select-list-while-unsubscribe/',
					'utm_medium' => 'intermediate_unsubscribe_page' ) ),
				'disabled'   => true,
			),

			'ig_es_opt_in_consent'                => array(
				'id'         => 'ig_es_opt_in_consent_p',
				'name'       => __( 'Nudge people to subscribe while performing some actions', 'email-subscribers' ),
				'info'       => __( 'For example : Adds a checkbox to subscribe when people post a comment.', 'email-subscribers' ),
				'sub_fields' => array(
					'ig_es_show_opt_in_consent' => array(
						'id'       => 'ig_es_show_opt_in_consent_p',
						'name'     => '',
						'info'     => __( '(toggle to enable this)', 'email-subscribers' ),
						'type'     => 'checkbox',
						'default'  => 'no',
						'disabled' => true,
					),
					'ig_es_opt_in_consent_text' => array(
						'type'         => 'textarea',
						'options'      => false,
						'placeholder'  => __( 'Opt-in consent message text', 'email-subscribers' ),
						'supplemental' => '',
						'default'      => __( 'Subscribe to our email updates as well.', 'email-subscribers' ),
						'id'           => 'ig_es_opt_in_consent_text_p',
						'name'         => __( 'Opt-in consent text', 'email-subscribers' ),
						'disabled'     => true,
					),
				),
				'is_premium' => true,
				'plan'       => 'pro',
				'link'       => ES_Common::get_utm_tracking_url( array( 'utm_medium' => 'opt_in_consent_text' ) ),
				'disabled'   => true,
			),
		);

		$general_fields = ig_es_array_insert_after( $general_fields, 'ig_es_track_link_click', $starter_general_setting_fields );

		$utm_args = array(
			'url' => 'https://www.icegram.com/documentation/how-do-i-enable-captcha/',
			'utm_medium' => 'enable_captcha',
		);

		$premium_url = ES_Common::get_utm_tracking_url( $utm_args );

		// Security Settings
		$fake_domains['ig_es_enable_known_attackers_domains'] = array(
			'id'            => 'ig_es_enable_known_attackers_domains_p',
			'name'          => __( 'Block known attackers', 'email-subscribers' ),
			'info'          => __( 'Stop spam bot attacker domains from signing up. Icegram maintains a blacklist of such attackers and enabling this option will keep the blacklist updated.', 'email-subscribers' ),
			'type'          => 'checkbox',
			'default'       => 'no',
			'is_premium'    => true,
			'plan'          => 'pro',
			'link'          => ES_Common::get_utm_tracking_url( array(
				'url'=>'https://www.icegram.com/documentation/preventing-spammers/',
				'utm_medium' => 'known_attackers' ) ),
			'disabled'      => true,
			/* translators: %s: Icegram Pricing page url with utm tracking */
			'upgrade_title' => __( 'Prevent spam attacks with PRO', 'email-subscribers' ),
			'upgrade_desc'  => __( 'Secure your list from known spam bot attacker domains, fake email addresses and bot signups.', 'email-subscribers' ),
		);

		$managed_blocked_domains['ig_es_enable_disposable_domains'] = array(
			'id'         => 'ig_es_enable_disposable_domains_p',
			'name'       => __( 'Block temporary / fake emails', 'email-subscribers' ),
			'info'       => __( 'Plenty of sites provide disposable / fake / temporary email addresses. People use them when they don\'t want to give you their real email. Block such emails to keep your list clean. Turning this on will update the blacklist automatically.', 'email-subscribers' ),
			'type'       => 'checkbox',
			'default'    => 'no',
			'is_premium' => true,
			'plan'       => 'pro',
			'link'       => ES_Common::get_utm_tracking_url( array(
				'url'=>'https://www.icegram.com/documentation/preventing-spammers/',
				'utm_medium' => 'disposable_domains' ) ),
			'disabled'   => true,
		);

		// add captcha setting
		$field_captcha['enable_captcha'] = array(
			'id'         => 'ig_es_enable_captcha_p',
			'name'       => __( 'Enable Captcha', 'email-subscribers' ),
			'info'       => __( 'Prevent bot signups even further. Set default captcha option for new subscription forms.', 'email-subscribers' ),
			'type'       => 'checkbox',
			'default'    => 'no',
			'is_premium' => true,
			'plan'       => 'pro',
			'link'       => $premium_url,
			'disabled'   => true,
		);

		$fields['security_settings'] = array_merge( $fields['security_settings'], $fake_domains, $managed_blocked_domains, $field_captcha );

		$fields['user_roles'] = array(
			'ig_es_user_roles' => array(
				'id'   => 'ig_es_user_roles',
				'name' => '',
				'type' => 'html',
				'html' => render_user_permissions_settings_fields_premium(),
			),
		);

	}

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$track_ip_address['ig_es_track_ip_address'] = array(
			'id'            => 'ig_es_track_ip_address_p',
			'name'          => __( 'Track IP address', 'email-subscribers' ),
			'info'          => __( 'Record user\'s IP address on subscription.', 'email-subscribers' ),
			'type'          => 'checkbox',
			'default'       => 'no',
			'is_premium'    => true,
			'plan'          => 'max',
			'link'          => ES_Common::get_utm_tracking_url( array( 'utm_medium' => 'ip_tracking' ) ),
			'disabled'      => true,
			/* translators: %s: Icegram Pricing page url with utm tracking */
			'upgrade_title' => __( 'Track subscribers IP addresses with MAX', 'email-subscribers' ),
			'upgrade_desc'  => __( 'Enable IP tracking to store IP addresses and country name of subscribers. With this, you can target campaigns like Broadcasts, Sequences to subscribers from specific countries.', 'email-subscribers' ),
		);

		$fields['security_settings'] = array_merge( $fields['security_settings'], $track_ip_address );
	}

	$fields['general'] = $general_fields;

	return $fields;
}

function ig_es_add_sync_users_tabs( $tabs ) {
	global $ig_es_tracker;

	// Show integrations only if ES Premium is not installed.
	if ( ! ES()->is_starter() ) {

		$tabs['comments'] = array(
			'name'             => __( 'Comments', 'email-subscribers' ),
			'indicator_option' => 'ig_es_show_sync_comment_users_indicator',
			'indicator_label'  => 'Starter',
		);

		$woocommerce_plugin = 'woocommerce/woocommerce.php';

		// Is WooCommmerce active? Show WooCommerce integration
		$active_plugins = $ig_es_tracker::get_active_plugins();
		if ( in_array( $woocommerce_plugin, $active_plugins, true ) ) {
			$tabs['woocommerce'] = array(
				'name'             => __( 'WooCommerce', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_woocommerce_users_indicator',
				'indicator_label'  => 'Starter',
			);
		}

		// Is Contact Form 7 active? Show CF7 integration.
		$contact_form_7 = 'contact-form-7/wp-contact-form-7.php';
		if ( in_array( $contact_form_7, $active_plugins, true ) ) {
			$tabs['cf7'] = array(
				'name'             => __( 'Contact Form 7', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_cf7_users_indicator',
				'indicator_label'  => 'Starter',
			);
		}

		$wpforms_lite_plugin = 'wpforms-lite/wpforms.php';
		$wpforms_plugin      = 'wpforms/wpforms.php';
		if ( in_array( $wpforms_lite_plugin, $active_plugins, true ) || in_array( $wpforms_plugin, $active_plugins, true ) ) {
			$tabs['wpforms'] = array(
				'name'             => __( 'WPForms', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_wpforms_users_indicator',
				'indicator_label'  => 'Starter',
			);
		}

		// Show only if Give is installed & activated
		$give_plugin = 'give/give.php';
		if ( in_array( $give_plugin, $active_plugins, true ) ) {
			$tabs['give'] = array(
				'name'             => __( 'Give', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_give_users_indicator',
				'indicator_label'  => 'Starter',
			);
		}

		// Show only if Ninja Forms is installed & activated
		$ninja_forms_plugin = 'ninja-forms/ninja-forms.php';
		if ( in_array( $ninja_forms_plugin, $active_plugins, true ) ) {
			$tabs['ninja_forms'] = array(
				'name'             => __( 'Ninja Forms', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_ninja_forms_users_indicator',
				'indicator_label'  => 'Starter',
			);
		}

		// Show only if EDD is installed & activated
		$edd_plugin = 'easy-digital-downloads/easy-digital-downloads.php';
		if ( in_array( $edd_plugin, $active_plugins, true ) ) {
			$tabs['edd'] = array(
				'name'             => __( 'EDD', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_edd_users_indicator',
				'indicator_label'  => 'Starter',
			);
		}
	}

	return $tabs;
}

function ig_es_add_comments_tab_settings( $tab_options ) {

	// If you want to hide once shown. Set it to 'no'
	// If you don't want to hide. do not use following code or set value as 'yes'
	/*
	if ( ! empty( $tab_options['indicator_option'] ) ) {
		update_option( $tab_options['indicator_option'], 'yes' ); // yes/no
	}
	*/

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
	<div class="">
		<h2><?php esc_html_e( 'Sync Comment Users', 'email-subscribers' ); ?></h2>
		<p><?php esc_html_e( 'Quickly add to your mailing list when someone post a comment on your website.', 'email-subscribers' ); ?></p>
		<h2><?php esc_html_e( 'How to setup?', 'email-subscribers' ); ?></h2>
		<p><?php esc_html_e( 'Once you upgrade to ', 'email-subscribers' ); ?><a href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=comment_sync&utm_campaign=es_upsell#sync_comment_users"><?php esc_html_e( 'Icegram Express Starter', 'email-subscribers' ); ?></a>,
		<?php
			esc_html_e('you will have settings panel where you need to enable Comment user sync and select the list in which you want to add people whenever someone post a
		comment.', 'email-subscribers')
		?>
		</p>
		<hr>
		<p class="help"><?php esc_html_e( 'Checkout ', 'email-subscribers' ); ?><a href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=comment_sync&utm_campaign=es_upsell#sync_comment_users"><?php esc_html_e( 'Icegram Express Starter', 'email-subscribers' ); ?></a> <?php esc_html_e( 'now', 'email-subscribers' ); ?></p>
	</div>
	<?php

	$content = ob_get_clean();

	?>
	<a target="_blank" href="https://www.icegram.com/quickly-add-people-to-your-mailing-list-whenever-someone-post-a-comment/?utm_source=in_app&utm_medium=es_comment_upsale&utm_campaign=es_upsell#sync_comment_users">
		<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/es-comments.png"/>
	</a>
	<?php
	ES_Common::prepare_information_box( $info, $content );
}

function ig_es_add_woocommerce_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
	<div class="">
		<h2><?php esc_html_e( 'Sync WooCommerce Customers', 'email-subscribers' ); ?></h2>
		<p><?php esc_html_e( 'Are you using WooCommerce for your online business? You can use this integration to add to a specific list whenever someone make a purchase from you', 'email-subscribers' ); ?></p>
		<h2><?php esc_html_e( 'How to setup?', 'email-subscribers' ); ?></h2>
		<p><?php esc_html_e( 'Once you upgrade to ', 'email-subscribers' ); ?><a href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=woocommerce_sync&utm_campaign=es_upsell#sync_woocommerce_customers"><?php esc_html_e( 'Icegram Express Starter', 'email-subscribers' ); ?></a>,
					 <?php
						esc_html_e(
							'you will have settings panel where you need to enable WooCommerce sync and select the list in which you want to add people whenever they
			purchase something
			from you.',
							'email-subscribers'
						)
						?>
						</p>
			<hr>
			<p class="help"><?php esc_html_e( 'Checkout ', 'email-subscribers' ); ?><a href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=woocommerce_sync&utm_campaign=es_upsell#sync_woocommerce_customers"><?php esc_html_e( 'Icegram Express Starter', 'email-subscribers' ); ?></a><?php esc_html_e( ' Now', 'email-subscribers' ); ?></p>
		</div>
		<?php $content = ob_get_clean(); ?>

		<a target="_blank" href="https://www.icegram.com/quickly-add-customers-to-your-mailing-list/?utm_source=in_app&utm_medium=woocommerce_sync&utm_campaign=es_upsell#sync_woocommerce_customers">
			<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/woocommerce-sync.png"/>
		</a>

		<?php

		ES_Common::prepare_information_box( $info, $content );

		?>

		<?php
}

function ig_es_add_cf7_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
		<div class="">
			<h2><?php esc_html_e( 'Sync Contact Form 7 users', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'Are you using Contact Form 7 for your list building? You can use this integration to add to a specific list whenever new subscribers added from Contact Form 7', 'email-subscribers' ); ?></p>
			<h2><?php esc_html_e( 'How to setup?', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'Once you upgrade to ', 'email-subscribers' ); ?><a href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=cf7_sync&utm_campaign=es_upsell#sync_cf7_subscribers">
						 <?php
							esc_html_e(
								'Icegram Express Starter',
								'email-subscribers'
							)
							?>
			</a>, <?php esc_html_e( 'you will have settings panel where you need to enable Contact form 7 sync and select the list in which you want to add people whenever they fill any of the Contact Form.', 'email-subscribers' ); ?></p>
			<hr>
			<p class="help"><?php esc_html_e( 'Checkout ', 'email-subscribers' ); ?><a href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=cf7_sync&utm_campaign=es_upsell#sync_cf7_subscribers">Icegram Express Starter</a> Now</p>
		</div>
		<?php $content = ob_get_clean(); ?>

		<a target="_blank" href="https://www.icegram.com/add-people-to-your-mailing-list-whenever-they-submit-any-of-the-contact-form-7-form/?utm_source=in_app&utm_medium=cf7_sync&utm_campaign=es_upsell#sync_cf7_subscribers">
			<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/cf7-sync.png"/>
		</a>

		<?php

		ES_Common::prepare_information_box( $info, $content );

		?>

		<?php
}

function ig_es_add_give_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
		<div class="">
			<h2><?php esc_html_e( 'Sync Donors', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'We found that you are using Give WordPress plugin to collect donations. Now, with this integration, you can add your donors to any of your subscriber list and send them Newsletters in future.', 'email-subscribers' ); ?></p>
			<h2><?php esc_html_e( 'How to setup?', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'Once you upgrade to ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=give_sync&utm_campaign=es_upsell#sync_give_donors">
						 <?php
							esc_html_e(
								'Icegram Express Starter',
								'email-subscribers'
							)
							?>
			</a>, <?php esc_html_e( 'you will have settings panel where you need to enable Give integration and select the list in which you want to add people whenever they make donation.', 'email-subscribers' ); ?></p>
			<hr>
			<p class="help"><?php esc_html_e( 'Checkout ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=give_sync&utm_campaign=es_upsell#sync_give_donors">Icegram Express Starter</a> Now</p>
		</div>
		<?php $content = ob_get_clean(); ?>

		<a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=give_sync&utm_campaign=es_upsell#sync_give_donors">
			<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/give-sync.png"/>
		</a>

		<?php

		ES_Common::prepare_information_box( $info, $content );

		?>

		<?php
}

function ig_es_add_wpforms_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
		<div class="">
			<h2><?php esc_html_e( 'Sync Donors', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'Are you using Give WordPress plugin to collect donations? Want to send Thank You email to them? You can use this integration to be in touch with them.', 'email-subscribers' ); ?></p>
			<h2><?php esc_html_e( 'How to setup?', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'Once you upgrade to ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=wpforms_sync&utm_campaign=es_upsell#sync_wpforms_contacts">
						 <?php
							esc_html_e(
								'Icegram Express Starter',
								'email-subscribers'
							)
							?>
			</a>, <?php esc_html_e( 'you will have settings panel where you need to enable Give sync and select the list in which you want to add people whenever they make donation.', 'email-subscribers' ); ?></p>
			<hr>
			<p class="help"><?php esc_html_e( 'Checkout ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=wpforms_sync&utm_campaign=es_upsell#sync_wpforms_contacts"><?php esc_html_e( 'Icegram Express Starter', 'email-subscribers' ); ?></a><?php esc_html_e( ' Now', 'email-subscribers' ); ?></p>
		</div>
		<?php $content = ob_get_clean(); ?>

		<a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=wpforms_sync&utm_campaign=es_upsell#sync_wpforms_contacts">
			<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/wpforms-sync.png"/>
		</a>

		<?php

		ES_Common::prepare_information_box( $info, $content );

		?>

		<?php
}

function ig_es_add_ninja_forms_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
		<div class="">
			<h2><?php esc_html_e( 'Sync Contacts', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'We found that you are using Ninja Forms. Want to add your contact to a mailing list? You can use this integration to add your contact to add into mailing list', 'email-subscribers' ); ?></p>
			<h2><?php esc_html_e( 'How to setup?', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'Once you upgrade to ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=ninja_forms_sync&utm_campaign=es_upsell#sync_ninja_forms_contacts">
						 <?php
							esc_html_e(
								'Icegram Express Starter',
								'email-subscribers'
							)
							?>
			</a>, <?php esc_html_e( 'you will have settings panel where you need to enable Give sync and select the list in which you want to add people whenever they make donation.', 'email-subscribers' ); ?></p>
			<hr>
			<p class="help"><?php esc_html_e( 'Checkout ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=ninja_forms_sync&utm_campaign=es_upsell#sync_ninja_forms_contacts">Icegram Express Starter</a> Now</p>
		</div>
		<?php $content = ob_get_clean(); ?>

		<a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=ninja_forms_sync&utm_campaign=es_upsell#sync_ninja_forms_contacts">
			<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/ninja-forms-sync.png"/>
		</a>

		<?php

		ES_Common::prepare_information_box( $info, $content );

		?>

		<?php
}

function ig_es_add_edd_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
		<div class="">
			<h2><?php esc_html_e( 'Sync Customers', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'We found that you are using EDD to sell digital goods online. You can use this integration to send Newsletters/ Post Notifications to your customers.', 'email-subscribers' ); ?></p>
			<h2><?php esc_html_e( 'How to setup?', 'email-subscribers' ); ?></h2>
			<p><?php esc_html_e( 'Once you upgrade to ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=edd_sync&utm_campaign=es_upsell#sync_edd_customers">
						 <?php
							esc_html_e(
								'Icegram Express Starter',
								'email-subscribers'
							)
							?>
			</a>, <?php esc_html_e( 'you will have settings panel where you need to enable EDD sync and select the list in which you want to add people whenever they purchase something from you.', 'email-subscribers' ); ?></p>
			<hr>
			<p class="help"><?php esc_html_e( 'Checkout ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=edd_sync&utm_campaign=es_upsell#sync_edd_customers">Icegram Express Starter</a> Now</p>
		</div>
		<?php $content = ob_get_clean(); ?>

		<a target="_blank" href="https://www.icegram.com/email-subscribers/?utm_source=in_app&utm_medium=edd_sync&utm_campaign=es_upsell#sync_edd_customers">
			<img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/edd-sync.png"/>
		</a>

		<?php

		ES_Common::prepare_information_box( $info, $content );

		?>

		<?php
}

function ig_es_workflows_integration_metabox( $page_prefix = '' ) {
	if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {

		add_meta_box( 'ig_es_workflow_integration_information', __( 'ES PRO Integrations', 'email-subscribers' ), 'ig_es_workflows_integration_upsell', $page_prefix . '_page_es_workflows', 'side', 'default' );
	}
}

function ig_es_show_hidden_workflow_metabox( $es_workflow_metaboxes ) {

	if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {
		$es_workflow_metaboxes[] = 'ig_es_workflow_integration_description';
	}

	return $es_workflow_metaboxes;
}

function ig_es_workflows_integration_upsell() {

	$utm_args = array(
		'url'	=> 'https://www.icegram.com/documentation/available-triggers/',
		'utm_medium' => 'es_workflow_integration',
	);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );

		$plugin_integrations = array(
			'WooCommerce',
			'YITH WooCommerce Wishlist',
			'Contact Form 7',
			'Easy Digital Downloads',
			'Ninja Forms',
			'Give WP',
			'Ninja Forms',
			'WPForms',
			'Gravity Forms',
			'Forminator',
		);

		$upsell_message = '<div class="pt-1.5">';
		foreach ( $plugin_integrations as $plugin_name ) {
			$upsell_message .= '<div class="flex items-start space-x-3 -ml-8">
			            <div class="flex-shrink-0 h-5 w-5 relative flex justify-center">
			              <span class="block h-1.5 w-1.5 mt-1.5 bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400 transition ease-in-out duration-150"></span>
			            </div>
			            <p class="text-sm leading-5 py-0.5 text-gray-500 group-hover:text-gray-900 group-focus:text-gray-900 transition ease-in-out duration-150">' . esc_html( $plugin_name ) . '</p>
			    </div>';
		}
		$upsell_message .= '</div><br>' . esc_html__( 'Avoid manual actions and make your workflow quick, simple and effortless by integrating popular WordPress plugins with Icegram Express MAX.', 'email-subscribers' );
		$upsell_info     = array(
			'upgrade_title'  => __( 'Unlock plugin integrations with MAX', 'email-subscribers' ),
			'pricing_url'    => $pricing_url,
			'upsell_message' => $upsell_message,
			'cta_html'       => false,
		);
		ES_Common::upsell_description_message_box( $upsell_info );
}


function add_spam_score_utm_link() {
	global $post, $pagenow, $ig_es_tracker;
	if ( 'es_template' !== $post->post_type ) {
		return;
	}

	if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {
		?>
			<script>
				jQuery('#submitdiv').after('<div class="es_upsale"><a style="text-decoration:none;" target="_blank" href="https://www.icegram.com/documentation/how-ready-made-template-in-in-email-subscribers-look/?utm_source=in_app&utm_medium=es_template&utm_campaign=es_upsell"><img title="Get readymade templates" style="width:100%;border:0.3em #d46307 solid" src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/starter-tmpl.png"/><p style="background: #d46307; color: #FFF; padding: 4px; width: 100%; text-align:center">Get readymade beautiful email templates</p></a></div>');
			</script>
			<?php
	}
}

/**
 * Upsell ES PRO on Form Captcha
 *
 * @param $form_data
 *
 * @since 4.4.7
 */
function ig_es_add_captcha_option( $form_data ) {

	if ( ES_Drag_And_Drop_Editor::is_dnd_editor_page() ) {
		return;
	}

	if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {

		$utm_args = array(
			'url'=> 'https://www.icegram.com/documentation/how-do-i-enable-captcha/',
			'utm_medium' => 'es_form_captcha',
		);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		$upsell_info = array(
			'upgrade_title'  => __( 'Protect your subscription list now with PRO', 'email-subscribers' ),
			'pricing_url'    => $pricing_url,
			/* translators: 1. Bold tag 2. Bold close tag */
			'upsell_message' => sprintf( __( 'Get a gatekeeper like %1$sCaptcha%2$s and prevent bot signups from your subscription form.', 'email-subscribers' ), '<b class="font-medium text-teal-800">', '</b>' ),
			'cta_html'       => false,
		);
		?>

		<div class="flex border-b border-gray-100 ">
			<div class="w-4/6 mr-16">
				<div class="flex flex-row w-full">
					<div class="flex w-2/4">
						<div class="ml-4 mr-8 mr-4 pt-4 mb-2">
							<label for="tag-link" class="ml-4 text-sm font-medium text-gray-500 pb-2 cursor-default"><?php echo esc_html__( 'Enable Captcha', 'email-subscribers' ); ?>
							<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank"  >
								<span class="premium-icon max"></span></a>
							</label>
							<p class="italic text-xs text-gray-400 mt-2 ml-4 leading-snug pb-4 cursor-default"><?php esc_html_e( 'Show a captcha to protect from bot signups.', 'email-subscribers' ); ?></p>
						</div>
					</div>
					<div class="flex">
						<div class=" mb-4 mr-4 mt-12">
							<label class=" inline-flex items-center cursor-default">
								<span class="relative">
									<span class="es-mail-toggle-line"></span>
									<span class="es-mail-toggle-dot"></span>
								</span>
							</label>
						</div>
					</div>
				</div>
			</div>

			<div class="w-3/6 pr-4 ml-12">
				<?php ES_Common::upsell_description_message_box( $upsell_info ); ?>
			</div>
		</div>
		<?php
	}
}

function ig_es_additional_multilist_and_post_digest() {

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {

		$utm_args = array(
			'utm_medium' => 'post_notifications_multiple_lists',
		);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		$upsell_info = array(
			'upgrade_title'  => __( 'Enable multiple lists & post digest with PRO', 'email-subscribers' ),
			'pricing_url'    => $pricing_url,
			'upsell_message' => '<div class="flex items-start space-x-3 -ml-8">
			            <div class="flex-shrink-0 h-5 w-5 relative flex justify-center">
			              <span class="block h-1.5 w-1.5 mt-2.5 bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400 transition ease-in-out duration-150"></span>
			            </div>
			            <p class="text-sm leading-5 py-0.5 text-gray-500 group-hover:text-gray-900 group-focus:text-gray-900 transition ease-in-out duration-150">' . esc_html__( 'Want to send notification emails to more than one list? You can select multiple list with', 'email-subscribers' ) . '<b class="font-medium text-teal-800">' . esc_html__( 'Icegram Express MAX.', 'email-subscribers' ) . '</b></p>
			    </div>

			    <div class="flex items-start space-x-3 -ml-8">
			            <div class="flex-shrink-0 h-5 w-5 relative flex justify-center">
			              <span class="block h-1.5 w-1.5 mt-2.5 bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400 transition ease-in-out duration-150"></span>
			            </div>
			            <p class="text-sm leading-5 py-0.5 text-gray-500 group-hover:text-gray-900 group-focus:text-gray-900 transition ease-in-out duration-150">' . esc_html__( 'With post digest, improve post notification by sending one notification for multiple post, schedule it to what you feel is the best time and leave it on the plugin.', 'email-subscribers' ) .
						'</p>
			    </div>',
			'cta_html'       => false,
		);
		?>
		<table>
		<tr>
				<th scope="row" class="w-3/12 text-left pr-3 -my-4">
					<label for="tag-link" class="ml-6 text-sm font-medium text-gray-500 pb-2 cursor-default"><?php echo esc_html__( 'Is a post digest?', 'email-subscribers' ); ?></label>
					<span class="premium-icon max"></span>
					<p class="italic text-xs font-normal text-gray-400 mt-2 ml-6 leading-snug">
					<?php echo esc_html__( 'Schedule one notification email for multiple posts', 'email-subscribers' ); ?>
					</p>
				</th>
				<td class="w-4/12">
					<label for="is_post_digest" class="ml-14 inline-flex items-center cursor-default"><span class="relative">
						<span class="es-mail-toggle-line"></span>
						<span class="es-mail-toggle-dot"></span>
					</span></label>
				</td>
				<td rowspan="2" colspan="2" class="w-5/12 border-b border-gray-100">
					<div class="w-full ml-2 py-2">
						<?php ES_Common::upsell_description_message_box( $upsell_info ); ?>
					</div>
				</td>
				</tr>
				<tr class="es_post_digest border-b border-gray-100 pt-3">
					<th scope="row" class="text-left">
						<label class="ml-6 text-sm font-medium text-gray-500 pb-2 cursor-default"><?php echo esc_html__( 'Schedules at', 'email-subscribers' ); ?></label>
						<span class="premium-icon max"></span>
							<p class="italic text-xs font-normal text-gray-400 mt-2 ml-6 leading-snug"><?php echo esc_html__( 'When to send?', 'email-subscribers' ); ?></p></label>
						</th>
						<td>
							<div class="flex">
									<div class="inline-flex ml-12 relative">
							<label class="ml-2">
								<select class="form-select" disabled="disabled">
									<option><?php echo esc_html__( 'Once a day at', 'email-subscribers' ); ?></option>
								</select>
								<select class="form-select ml-2" disabled="disabled">
									<option><?php echo esc_html( '12:00 pm' ); ?></option>
								</select>
							</label>
						</div>
							</div>
						</td>

					</tr>
				</table>
		<?php
	}

}

function ig_es_upsale_post_digest() {
	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$utm_args = array(
			'url'=>'https://www.icegram.com/documentation/post-digest/',
			'utm_medium' => 'is_a_post_digest',
		);
		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		?>
		<div class="ig-es-campaign-is-post-digest-wrapper pt-4 pb-4 mx-4 border-b border-gray-200">
			<div class="flex w-full">
				<div class="w-11/12 text-sm font-normal text-gray-600">
					<?php echo esc_html__( 'Is a post digest?', 'email-subscribers' ); ?>
					<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank"  >
						<span class="premium-icon max"></span>
					</a>
				</div>
				<div>
					<label for="is_post_digest" class="inline-flex items-center cursor-pointer ">
						<span class="relative">
							<span class="es-mail-toggle-line"></span>
							<span class="es-mail-toggle-dot"></span>
						</span>
					</label>
				</div>
			</div>
		</div>
		<?php
	}
}

function ig_es_upsale_send_campaign_preview_email_option() {

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$utm_args = array(
			'utm_medium' => 'campaign_send_preview_email',
		);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		?>
		<div id="send-preview-email-tab" class="campaign-preview-option cursor-pointer text-sm font-normal text-gray-600" title="<?php echo esc_attr__( 'Send a test email', 'email-subscribers' ); ?>">
			<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
				</svg>
			</a>
		</div>
		<?php
	}
}

function ig_es_upsale_campaign_tracking_options( $campaign_data ) {
	$campaign_type = ! empty( $campaign_data['type'] ) ? $campaign_data['type'] : '';
	?>

	<?php
	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$utm_args = array(
			'utm_medium' => 'campaign_summary',
		);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		$upsell_info = array(
			'upgrade_title'  => __( 'Reduce the possibility to land in spam with MAX', 'email-subscribers' ),
			'pricing_url'    => $pricing_url,
			'upsell_message' => __( 'Build your brand, track your links with the help of Link tracking, UTM tracking and schedule your next campaign accordingly. Also prevent your emails from landing into spam by checking its spam score' ),
			'cta_html'       => false,
		);
		?>
		<div class="flex w-full pt-2">
			<div class="w-11/12 text-sm font-normal text-gray-600">
				<label class="pt-3 text-sm leading-5 text-gray-500 cursor-default"><?php echo esc_html__( 'Link tracking', 'email-subscribers' ); ?></label>
				<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank">
				<span class="premium-icon max"></span></a>
			</div>

			<div>
				<label for="enable_link_tracking" class=" inline-flex items-center cursor-default">
					<span class="relative">
						<span class="es-mail-toggle-line block w-8 h-5 bg-gray-300 rounded-full shadow-inner"></span>
						<span class="es-mail-toggle-dot absolute transition-all duration-300 ease-in-out block w-3 h-3 mt-1 ml-1 bg-white rounded-full shadow inset-y-0 left-0 focus-within:shadow-outline"></span>
					</span>
				</label>
			</div>
		</div>


		<?php
	}

	if ( ES()->can_upsell_features( array( 'lite', 'starter' ) ) ) {
		$utm_tracking_feature_args = array(
			'url' => 'https://www.icegram.com/documentation/how-to-add-utm-parameters-to-email/',
			'utm_medium' => 'campaign_summary',
		);

		$spam_score_args = array(
			'url' => 'https://www.icegram.com/documentation/how-to-get-spam-score-of-the-content/',
			'utm_medium' => 'campaign_summary',
		);

		$utm_feature_url = ES_Common::get_utm_tracking_url( $utm_tracking_feature_args );
		$spam_score_url = ES_Common::get_utm_tracking_url( $spam_score_args );
		?>
		<div class="flex w-full pt-3 pb-3 border-b border-gray-200">
			<div class="w-11/12 text-sm font-normal text-gray-600">
				<label class="pt-3 text-sm leading-5 text-gray-500 cursor-default"><?php echo esc_html__( 'UTM tracking', 'email-subscribers' ); ?></label>
				<a href="<?php echo esc_url( $utm_feature_url ); ?>" target="_blank">
				<span class="premium-icon max"></span></a>
			</div>

			<div>
				<label for="enable_utm_tracking" class=" inline-flex items-center cursor-default">
					<span class="relative">
						<span class="es-mail-toggle-line block w-8 h-5 bg-gray-300 rounded-full shadow-inner"></span>
						<span class="es-mail-toggle-dot absolute transition-all duration-300 ease-in-out block w-3 h-3 mt-1 ml-1 bg-white rounded-full shadow inset-y-0 left-0 focus-within:shadow-outline"></span>
					</span>
				</label>
			</div>
		</div>

		<div class="block my-3">
			<label class="pt-3 text-sm leading-5 font-medium text-gray-500 cursor-default"><?php echo esc_html__( 'Get spam score', 'email-subscribers' ); ?></label>
			<a href="<?php echo esc_url( $spam_score_url ); ?>" target="_blank">
			<span class="premium-icon max"></span></a>
			<button type="button" id="spam_score" disabled class="float-right es_spam rounded-md border text-indigo-400 border-indigo-300 text-sm leading-5 font-medium inline-flex justify-center px-3 py-1 cursor-default"><?php echo esc_html__( 'Check', 'email-subscribers' ); ?>
			</button>
		</div>
		<?php
	}

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		if (  IG_CAMPAIGN_TYPE_NEWSLETTER === $campaign_type ) {
		/* $utm_args = array(
			'utm_medium' => 'send_in_customer_timezone'
		);
		$pricing_url = ES_Common::get_utm_tracking_url($utm_args); */
			?>
		<div class="block w-full pt-3 pb-2">
			<span class="block text-sm font-medium leading-5 text-gray-500"><?php echo esc_html__( 'Send options', 'email-subscribers' ); ?></span>
			<div class="py-2">
				<input type="radio" class="form-radio" id="schedule_later" checked disabled>
				<label for="schedule_later" class="text-sm font-normal text-gray-500 cursor-default"><?php echo esc_html__( 'Schedule for later', 'email-subscribers' ); ?>
				</label>
				<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank">
			<span class="premium-icon max"></span></a>
			<br>
			<div id="schedule_later" class="px-6">
				<div class="flex pt-4" >
					<div class="flex w-full w-11/12">
						<label class="text-sm font-normal leading-5 text-gray-500 pt-1 cursor-default"><?php echo esc_html__( 'Date', 'email-subscribers' ); ?></label>
						<input class="font-normal text-sm py-1 ml-2 form-input cursor-default" type="text" value="<?php echo esc_attr( date_i18n( 'Y-m-d' ) ); ?>" disabled>
					</div>
					<div>
						<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="text-gray-500 w-5 h-5 my-1 ml-2"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
					</div>
				</div>
				<div class="flex pt-3" >
					<div class="flex w-full w-11/12">
						<label class="text-sm font-normal leading-5 text-gray-500 pt-1 cursor-default"><?php echo esc_html__( 'Time', 'email-subscribers' ); ?></label>
						<input class=" font-normal text-sm py-1 ml-2 form-input cursor-default" type="text" value="<?php echo esc_attr( date_i18n( 'h:i A' ) ); ?>" disabled>

					</div>
					<div>
						<svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="text-gray-500 w-5 h-5 my-1 ml-2 float-right"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
					</div>
				</div>
				<div class="pb-3">
					<div class="block px-2 py-2 mt-4 bg-gray-200 rounded-md ">
						<h3 class="text-gray-400 text-sm font-normal cursor-default"><?php echo esc_html__( 'Local Time: ', 'email-subscribers' ); ?>&nbsp;&nbsp;
							<?php echo esc_attr( date_i18n( 'Y-m-d H:i A' ) ); ?>
						</h3>
					</div>
				</div>
			</div>
			<!-- Send in Customer Timezone promotion block -->
			<!-- <div class="block my-3">
				<label class="pt-3 text-sm leading-5 font-medium text-gray-500 cursor-default">
					<?php //echo esc_html__( 'Send Email in Customer Timezone', 'email-subscribers' ); ?></label>
				<a href = "<?php //echo esc_url($pricing_url); ?>">
					<span class="premium-icon"></span>
				</a>
				<p for="" class="text-sm font-normal text-gray-500 cursor-default">
					<?php //echo esc_html__( 'Do you want to send email in Customers Timezone?', 'email-subscribers' ); ?>
				</p>
			</div> -->

			<div class="block py-2 mt-2 ">
				<?php ES_Common::upsell_description_message_box( $upsell_info ); ?>
			</div>
		</div>
		<?php
		}
	}
}


/**
 * Upsell send test email feature
 *
 * @since 5.4.4.1.
 */
function ig_es_upsell_send_test_email_feature( $type, $test_email ) {

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$utm_args = array(
			'utm_medium' => 'send_campaign_preview_email',
		);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		?>
				<div>
					<input id="ig_es_preview_email_address" value="<?php echo esc_attr( $test_email ); ?>" placeholder="<?php echo esc_attr( 'Enter email address'); ?>" class="campaign-preview-option inline-block text-sm leading-5 border-gray-400 rounded-md shadow-sm form-input mr-2" name="ig_es_preview_email_address" autocomplete="email" style="min-width: 238px;">
					<button id="send-<?php echo esc_attr($type); ?>-preview-email-btn" type="button" class="ig-es-inline-loader rounded-md border text-indigo-600 border-indigo-500 text-sm leading-5 font-medium transition ease-in-out duration-150 select-none inline-flex justify-center hover:text-indigo-500 hover:border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg mt-1 px-2 py-2">
						<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank">
						<span><?php echo esc_html__( 'Send email', 'email-subscribers' ); ?></span></a>
					</button>
					<span class = "premium-icon max"></span>
				</div>
				<?php
	}

}


/**
 * Campaign reports data
 *
 * @since 4.5.0
 */

function ig_es_view_additional_reports_data() {
	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		 $utm_args = array(
			 'url'=>'https://www.icegram.com/documentation/what-analytics-does-email-subscribers-track/',
			 'utm_medium' => 'campaign_insight',
		 );

		 $pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		 $upsell_info = array(
			 'upgrade_title'  => __( 'Get campaign analytics with MAX', 'email-subscribers' ),
			 'pricing_url'    => $pricing_url,
			 /* translators: 1. Bold tag 2. Bold close tag */
			 'upsell_message' => sprintf( __( 'Want to track some very useful statistics of your campaigns and improve your future campaign ? Upgrade to %1$s Icegram Express MAX %2$s and measure the effectiveness of your campaigns.', 'email-subscribers' ), '<b class="font-medium text-teal-800">', '</b>' ),
			 'cta_html'       => true,
		 );
			?>
<div>
	<div class="campaign_open_overlay lg:w-3/5 xl:w-2/5 h-0 z-40 sticky">
			<div class="tracking-wide campaign-report">
				<?php ES_Common::upsell_description_message_box( $upsell_info ); ?>
			</div>
		</div>
	<div class="wrap max-w-7xl cursor-default campaign_open_blur font-sans">
			<div class="flex items-center justify-between">
				<div class="flex-shrink-0">
				   <span class="text-xl font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'Report', 'email-subscribers' ); ?><svg class="ml-3 align-middle w-5 h-5 text-indigo-600 inline-block" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg></span>
				</div>
			</div>
			<div class="mt-3 pb-2 w-full bg-white rounded-md shadow flex">
					<div class="w-3/4">
						<div class="flex pl-6 pt-4">
							<div class="w-auto inline-block text-xl text-gray-600 font-medium leading-7 truncate"><?php esc_html_e( 'Take a look into Icegram' ); ?>
								<svg class="inline-block mt-1 ml-1 h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
									<title><?php echo esc_attr_e( 'Sent', 'email-subscribers' ); ?></title>
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
								</svg>
							</div>
						</div>
					<div class="w-full text-gray-600 italic font-medium pt-4 text-sm leading-5 overflow-hidden">
						<p class="pl-6 truncate"><?php esc_html_e( 'Type: ', 'email-subscribers' ); ?>
							  <span class="pl-1 font-normal not-italic text-gray-900"><?php esc_html_e( 'Broadcast', 'email-subscribers' ); ?></span>
						   </p>
						<p class="pl-6 pt-2 truncate"><?php esc_html_e( 'From: ', 'email-subscribers' ); ?>
							   <span class="pl-1 font-normal not-italic text-gray-900"><?php echo esc_html( 'hello@icegram.com', 'email-subscribers' ); ?>,</span>
						</p>
						<p class="pl-6 pt-2 truncate"><?php esc_html_e( 'List(s): ', 'email-subscribers' ); ?>
							<span class="pl-1 font-normal not-italic text-gray-900 "><?php esc_html_e( 'Test, Main ', 'email-subscribers' ); ?></span>
						</p>
						 <p class="pl-6 pt-2 text-gray-600 "><?php esc_html_e( 'Date: ', 'email-subscribers' ); ?>
							   <span class="pl-1 font-normal not-italic text-gray-900"><?php esc_html_e( 'July 1, 2020 10:00 AM', 'email-subscribers' ); ?></span>
							</p>
					</div>
				</div>
				 <div class="w-1/2">
						<div class="flex-1 min-w-0">
							<p class="pt-4 pl-8 text-lg font-medium leading-6 text-gray-400">
								<?php esc_html_e( 'Statistics', 'email-subscribers' ); ?>
							</p>
							<div class="sm:grid sm:grid-cols-2 ml-6 mr-8">
								<div class="p-2">
									<p class="text-2xl font-bold leading-none text-indigo-600">
										4,294
									</p>
									<p class="mt-1 font-medium leading-6 text-gray-500">
										<?php esc_html_e( 'Opened', 'email-subscribers' ); ?>
									</p>
								</div>
								<div class="p-2">
									<p class="text-2xl font-bold leading-none text-indigo-600">
										42.94 %
									</p>
									<p class="mt-1 font-medium leading-6 text-gray-500">
										<?php esc_html_e( 'Avg Open Rate', 'email-subscribers' ); ?>
									</p>
								</div>
								<div class="p-2">
									<p class="text-2xl font-bold leading-none text-indigo-600">
										10,000
									</p>
									<p class="mt-1 font-medium leading-6 text-gray-500">
										<?php esc_html_e( 'Sent', 'email-subscribers' ); ?>
									</p>
								</div>
								<div class="p-2">
									<p class="text-2xl font-bold leading-none text-indigo-600">
										48.00 %
									</p>
									<p class="mt-1 font-medium leading-6 text-gray-500">
										<?php esc_html_e( 'Avg Click Rate', 'email-subscribers' ); ?>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="mt-6 mb-4">
					<div class="pt-3">
						<span class="text-left text-lg font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'Open and click activity', 'email-subscribers' ); ?></span>
					</div>
					<div class="bg-white mt-2 w-full rounded-md">
						<img style="display: block;" class="mx-auto" src="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/public/images/link-activity-graph.png"/>
					</div>

				</div>
				<div class="mt-6 mb-2 flex ">
					<div class="w-1/2 pt-3">
						<span class="text-left text-lg font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'Country Opens', 'email-subscribers' ); ?></span>
					</div>
					<div class="pt-3 pl-4">
						<span class="text-left text-lg font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'Mail Client Info', 'email-subscribers' ); ?></span>
					</div>
				</div>
				<?php
				$country_opens = array(
					array(
						'code'    => 'US',
						'country' => 'United States',
						'open'    => 1500,
					),
					array(
						'code'    => 'AU',
						'country' => 'Australia',
						'open'    => 1200,
					),
					array(
						'code'    => 'ES',
						'country' => 'Spain',
						'open'    => 800,
					),
					array(
						'code'    => 'FR',
						'country' => 'France',
						'open'    => 650,
					),
					array(
						'code'    => 'RU',
						'country' => 'Russia',
						'open'    => 144,
					),
				);
				?>
				<div class="mt-2 mb-4 flex">
					<div class="flex mb-4 mr-4 w-1/2 bg-white shadow rounded-md">
						<table class="w-full table-fixed">
							<thead>
								<tr class="border-b border-gray-200 bg-gray-200 text-xs leading-4 text-gray-500 uppercase tracking-wider text-left">
										<th width="10%"></th>
										<th class="w-1/3 px-4 py-3 font-medium ">
											<?php esc_html_e( 'Country', 'email-subscribers' ); ?>
										</th>
										<th class="w-1/3 px-6 py-3 font-medium">
											<?php esc_html_e( 'Opens', 'email-subscribers' ); ?>
										</th>
								</tr>
							</thead>
							<tbody>
							<?php
							foreach ( $country_opens as $country_data ) {
								?>
								<tr class="border-b border-gray-200 text-sm leading-5">
									<td class="mx-4 my-3 px-6 py-1 flag-icon flag-icon-<?php echo esc_html( strtolower( $country_data['code'] ) ); ?>">
									</td>
									<td class="pl-4 py-3 text-gray-500">
										<?php echo esc_html( $country_data['country'] ); ?>
									</td>
									<td class="px-6 py-3  border-b border-gray-200 text-sm leading-5 text-gray-600">
										<?php echo esc_html( $country_data['open'] ); ?>
									</td>
								</tr>
							<?php } ?>
							</tbody>
						</table>

					</div>
					<?php
					$mail_clients = array(
						'Gmail'               => 2294,
						'Gmail App (Android)' => 1500,
						'Thunderbird'         => 500,
					);
					?>
					<div class=" flex ml-4 w-1/2 bg-white shadow rounded-md self-start">
						<table class="w-full table-fixed">
							<thead>
						<tr>
							<th class="w-2/3 px-6 py-3 border-b border-gray-200 bg-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
								<?php esc_html_e( 'Mail Client', 'email-subscribers' ); ?>
							</th>
							<th class="w-1/3 px-6 py-3 border-b border-gray-200 bg-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
								<?php esc_html_e( 'Opens', 'email-subscribers' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $mail_clients as $mail_client => $opens ) {
							?>
						<tr>
							<td class="pl-8 py-3 border-b border-gray-200 text-sm leading-5 text-gray-500">
								<?php echo esc_html( $mail_client ); ?>
							</td>
							<td class="px-6 py-3  border-b border-gray-200 text-sm leading-5 text-gray-600">
								<?php echo esc_html( $opens ); ?>
							</td>
						</tr>
						<?php } ?>

					</tbody>
				</table>

				</div>
			</div>
			<?php
			$graph_open_data = array(
				array(
					'title'     => __( 'Device Info', 'email-subscribers' ),
					'graph_img' => 'lite/public/images/device_opens.png',
				),

				array(
					'title'     => __( 'Browser Info', 'email-subscribers' ),
					'graph_img' => 'lite/public/images/browser_opens.png',
				),

				array(
					'title'     => __( 'OS Info', 'email-subscribers' ),
					'graph_img' => 'lite/public/images/os_opens.png',
				),
			);
			?>

				<div class="mt-6 mb-4 grid w-full gap-8 grid-cols-3">
					<?php foreach ( $graph_open_data as $data ) { ?>
						<div class="w-full">
						<p class="pt-3 text-lg font-medium leading-7 tracking-wide text-gray-600"><?php echo esc_html( $data['title'] ); ?></p>
						<div class="relative mt-2">
							<img class=" w-full rounded-md overflow-hidden" src="<?php echo esc_url( ES_PLUGIN_URL ) . esc_attr( $data['graph_img'] ); ?>"/>
						</div>
					</div>
				<?php } ?>
				</div>


				<div class="mt-6 mb-2">
						<span class="text-left text-lg font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'Link Activity', 'email-subscribers' ); ?></span>
					</div>
				<div class="mt-2 mb-4 flex">
			<div class="flex w-full bg-white shadow rounded-md break-words self-start">
				<table class="w-full table-fixed">
					<thead>
						<tr>
							<th class="w-3/5 px-6 py-3 border-b border-gray-200 bg-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__( 'Link (URL)', 'email-subscribers' ); ?>
							</th>
							<th class=" w-1/5 px-6 py-3 border-b border-gray-200 bg-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__( 'Unique Clicks', 'email-subscribers' ); ?>
							</th>
							<th class=" w-1/5 px-6 py-3 border-b border-gray-200 bg-gray-200 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__( 'Total Clicks', 'email-subscribers' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-900">
											<?php
												echo esc_html( 'https://www.icegram.com/automate-workflow-and-reduce-chaos/', 'email-subscribers' );
											?>
										</td>
										<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-600">
											1400
										</td>
										<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-600">
											2000
										</td>
									</tr>
									<tr>
								<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-900">
											<?php
												echo esc_html( 'https://www.icegram.com/how-to-keep-email-out-of-spam-folder/', 'email-subscribers' );
											?>
										</td>
										<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-600">
											1200
										</td>
										<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-600">
											1800
										</td>
									</tr>
									<tr>
								<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-900">
											<?php
												echo esc_html( 'https://www.icegram.com/8-effective-tips-to-grow-your-open-rates/' );
											?>
										</td>
										<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-600">
											800
										</td>
										<td class="px-6 py-3 border-b border-gray-200 text-sm leading-5 text-gray-600">
											1000
										</td>
									</tr>
							</tbody>
						</table>
					</div>
				</div>
				<?php
				$last_open_activity = array(
					array(
						'code'        => 'US',
						'country'     => 'United States',
						'email'       => 'bernardlane@gmail.com',
						'device'      => 'desktop',
						'mail_client' => 'Gmail',
						'os'          => 'Windows',
					),
					array(
						'code'        => 'US',
						'country'     => 'United States',
						'email'       => 'john@gmail.com',
						'device'      => 'desktop',
						'mail_client' => 'Gmail',
						'os'          => 'Android',
					),
					array(
						'code'        => 'AU',
						'country'     => 'Australia',
						'email'       => 'pasha@gmail.com',
						'device'      => 'mobile',
						'mail_client' => 'Gmail App (Android)',
						'os'          => 'Android',
					),
					array(
						'code'        => 'ES',
						'country'     => 'Spain',
						'email'       => 'mark@twone.com',
						'device'      => 'mobile',
						'mail_client' => 'Gmail App (Android)',
						'os'          => 'Android',
					),
					array(
						'code'        => 'FR',
						'country'     => 'France',
						'email'       => 'smith@gmail.com',
						'device'      => 'mobile',
						'mail_client' => 'Gmail App (Android)',
						'os'          => 'Android',
					),
					array(
						'code'        => 'AU',
						'country'     => 'Australia',
						'email'       => 'bradtke@gmail.com',
						'device'      => 'tablet',
						'mail_client' => 'Gmail',
						'os'          => 'Windows',
					),
					array(
						'code'        => 'US',
						'country'     => 'United States',
						'email'       => 'bveum@gmail.com',
						'device'      => 'desktop',
						'mail_client' => 'Thunderbird',
						'os'          => 'Windows',
					),
					array(
						'code'        => 'RU',
						'country'     => 'Russia',
						'email'       => 'tracy@gmail.com',
						'device'      => 'desktop',
						'mail_client' => 'Gmail',
						'os'          => 'Windows',
					),
					array(
						'code'        => 'ES',
						'country'     => 'Spain',
						'email'       => 'domenick52@twone.com',
						'device'      => 'tablet',
						'mail_client' => 'Gmail',
						'os'          => 'Windows',
					),
					array(
						'code'        => 'AU',
						'country'     => 'Australia',
						'email'       => 'stanton@gmail.com',
						'device'      => 'desktop',
						'mail_client' => 'Thunderbird',
						'os'          => 'Windows',
					),

				);
				?>
				<div class="mt-8 mb-2">
					<span class="text-left text-lg font-medium leading-7 tracking-wide text-gray-600"><?php esc_html_e( 'Last 10 Open Activity', 'email-subscribers' ); ?></span>
				</div>
				<div class="mt-2 mb-2 flex">
					<div class="flex w-full bg-white shadow rounded-md break-all">
						<table class="w-full table-fixed">
							<thead>
								<tr class="border-b border-gray-200 bg-gray-200 text-left text-xs leading-4 text-gray-500 uppercase tracking-wider">
									<th width="6%"></th>
									<th width="16%" class="pl-4 py-3 font-medium"><?php esc_html_e( 'Country', 'email-subscribers' ); ?></th>
									<th width="26%" class="pl-6 py-3 font-medium"><?php esc_html_e( 'Email', 'email-subscribers' ); ?></th>
									<th width="10%" class="pl-3 py-3 font-medium"><?php esc_html_e( 'Device', 'email-subscribers' ); ?></th>
									<th class="pl-3 py-3 font-medium"><?php esc_html_e( 'Mail Client', 'email-subscribers' ); ?></th>
									<th class="pl-3 py-3 font-medium"><?php esc_html_e( 'OS', 'email-subscribers' ); ?></th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php
								foreach ( $last_open_activity as $activity ) {
									?>
									<tr class="border-b border-gray-200 text-sm leading-5 text-gray-500">
											<td class="mx-4 my-3 px-6 py-1 flag-icon flag-icon-<?php echo esc_attr( strtolower( $activity['code'] ) ); ?> ">
											</td>
											<td class="pl-4 pr-2 py-3 truncate">
												<?php
												echo esc_html( $activity['country'] );
												?>
											</td>
											<td class="pl-6 pr-2 py-3 truncate">
												<?php
												echo esc_html( $activity['email'] );
												?>
											</td>
											<td class="pl-3 py-3 font-medium ">
												<span class="pl-2 inline-flex text-xs leading-5 font-semibold text-gray-500">
													<?php
													switch ( $activity['device'] ) {
														case 'desktop':
															?>
															<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
																<title><?php echo esc_html__( 'Desktop', 'email-subscribers' ); ?></title>
																<path fill="currentColor"
																	  d="M19,2H5A3,3,0,0,0,2,5V15a3,3,0,0,0,3,3H7.64l-.58,1a2,2,0,0,0,0,2,2,2,0,0,0,1.75,1h6.46A2,2,0,0,0,17,21a2,2,0,0,0,0-2l-.59-1H19a3,3,0,0,0,3-3V5A3,3,0,0,0,19,2ZM8.77,20,10,18H14l1.2,2ZM20,15a1,1,0,0,1-1,1H5a1,1,0,0,1-1-1V14H20Zm0-3H4V5A1,1,0,0,1,5,4H19a1,1,0,0,1,1,1Z"></path>
															</svg>
															<?php
															break;
														case 'tablet':
															?>
															<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" width="512px" height="512px" viewBox="0 0 512 512" xml:space="preserve">
																<title><?php echo esc_html__( 'Tablet', 'email-subscribers' ); ?></title>
																<path fill-rule="evenodd" clip-rule="evenodd" fill="currentColor"
																	  d="M416,0H96C78.313,0,64,14.328,64,32v448c0,17.688,14.313,32,32,32  h320c17.688,0,32-14.313,32-32V32C448,14.328,433.688,0,416,0z M256,496c-13.25,0-24-10.75-24-24s10.75-24,24-24s24,10.75,24,24  S269.25,496,256,496z M400,432H112V48h288V432z"></path>
															</svg>
															<?php
															break;
														case 'mobile':
															?>
															<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
																<title><?php echo esc_html__( 'Mobile', 'email-subscribers' ); ?></title>
																<path fill="currentColor" d="M16,2H8A3,3,0,0,0,5,5V19a3,3,0,0,0,3,3h8a3,3,0,0,0,3-3V5A3,3,0,0,0,16,2Zm1,17a1,1,0,0,1-1,1H8a1,1,0,0,1-1-1V18H17Zm0-3H7V5A1,1,0,0,1,8,4h8a1,1,0,0,1,1,1Z"></path>
															</svg>
															<?php
															break;
														default:
															?>
															<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
																<path fill="currentColor"
																	  d="M19,2H5A3,3,0,0,0,2,5V15a3,3,0,0,0,3,3H7.64l-.58,1a2,2,0,0,0,0,2,2,2,0,0,0,1.75,1h6.46A2,2,0,0,0,17,21a2,2,0,0,0,0-2l-.59-1H19a3,3,0,0,0,3-3V5A3,3,0,0,0,19,2ZM8.77,20,10,18H14l1.2,2ZM20,15a1,1,0,0,1-1,1H5a1,1,0,0,1-1-1V14H20Zm0-3H4V5A1,1,0,0,1,5,4H19a1,1,0,0,1,1,1Z"></path>
															</svg>
															<?php
													}
													?>
												</span>
											</td>

											<td class="pl-3 py-3 text-gray-600 truncate">
												<?php
												echo esc_html( $activity['mail_client'] );
												?>
											</td>
											<td class="pl-3 pr-2 py-3 truncate">
												<?php
												echo esc_html( $activity['os'] );
												?>
											</td>
										</tr>
									<?php } ?>
						</tbody>
					</table>

				</div>
		</div>
	</div>
</div>

		<?php
	}
}


/**
 * Upsell add attachment feature in lite/starter/trial versions.
 *
 * @param string $editor_id Editor ID
 *
 * @since 4.6.7
 */
function ig_es_upsell_add_attachment_feature( $editor_id ) {

	// fetch page info to restrict upsell to es_newsletters
	$editor_page = ig_es_get_request_data('page');

	$utm_args = array(
		'utm_medium' => 'add_attachments',
	);

	$url = ES_Common::get_utm_tracking_url($utm_args);

	if ( 'edit-es-campaign-body' === $editor_id && 'es_newsletters' === $editor_page) {
		if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
			?>
			<div class="ig-es-attachments-wrapper bg-white inline-block">
			<a href = "<?php echo esc_url( $url ); ?>" target = "_blank" >
				<button type="button" class="ig-es-add-attachment button" >
					<svg class="flex-shrink-0 h-5 text-gray-400 inline-block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
						<path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
					</svg>
					<?php echo esc_html__( 'Add Attachments', 'email-subscribers' ); ?>
				</button></a>
				<span class="premium-icon max"></span>
			</div>
			<?php
		}
	}
}

/**
 * Upsell redirect to url feature
 *
 * @since 5.6.2
 */
function ig_es_upsell_redirect_to_url() {
	if ( ES()->can_upsell_features( array( 'lite', 'trial' ) ) ) {
		$utm_args = array(
			'utm_medium' => 'redirect_to_url',
		);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		?>
		<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank">
			<div class="block w-full mx-4 pb-8">
				<div class="py-2">
					<input type="radio" class="form-radio" id="redirect_to_url" />					
					<label for="redirect_to_url"
						class="text-sm font-medium text-gray-500"><?php echo esc_html__( 'Redirect to url', 'email-subscribers' ); ?>
					</label>
					<span class="premium-icon inline-block"></span>
				</div>
			</div>
		</a>
		<?php
	}
}


/**
 * Upsell pro import features
 *
 * @since 4.6.7
 */
function ig_es_upsell_pro_import_features() {

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$utm_args = array(
			'url'=>'https://www.icegram.com/documentation/how-to-import-wordpress-users-to-an-email-subscribers-list/',
			'utm_medium' => 'import_existing_wp_users',
		);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		?>
		<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank">
			<label class="inline-flex items-center cursor-pointer w-56">
				<div class="mt-4 px-1 mx-4 border border-gray-200 rounded-lg shadow-md es-mailer-logo es-importer-logo bg-white">
					<div class="border-0 es-logo-wrapper">
						<svg class="w-6 h-6 text-gray-500 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
					</div>
					<p class="mb-2 text-sm inline-block font-medium text-gray-600">
						<?php echo esc_html__( 'Import existing WordPress users', 'email-subscribers' ); ?>
						<span class="premium-icon max"></span>
					</p>
				</div>
			</label>
		</a>
		<?php

		global $ig_es_tracker;
		$is_woocommerce_active = $ig_es_tracker::is_plugin_activated( 'woocommerce/woocommerce.php' );
		if ( $is_woocommerce_active ) {

			$utm_args = array(
				'utm_medium' => 'import_from_wc_orders',
			);

			$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
			?>
			<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank">
				<label class="wc-importer-heading inline-flex items-center cursor-pointer w-56">
					<div class="mt-4 px-1 mx-4 border border-gray-200 rounded-lg shadow-md es-mailer-logo es-importer-logo bg-white">
						<div class="border-0 es-logo-wrapper">
							<img src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/admin/images/wc-logo.svg' ); ?>" />
						</div>
						<p class="mb-2 text-sm inline-block font-medium text-gray-600">
							<?php echo esc_html__( 'Import from', 'email-subscribers' ); ?>
							<span class="text-xs"><?php echo esc_html__( 'WooCommerce orders', 'email-subscribers' ); ?></span>
							<span class="premium-icon max"></span>
						</p>
					</div>
				</label>
			</a>
			<?php
		}

		$is_buddyboss_active = $ig_es_tracker::is_plugin_activated( 'buddyboss-platform/bp-loader.php' );
		if ( $is_buddyboss_active ) {

			$utm_args = array(
				'utm_medium' => 'import_buddyboss_members',
			);

			$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
			?>
			<a href="<?php echo esc_url( $pricing_url ); ?>" target="_blank">
				<label class="bb-importer-heading inline-flex items-center cursor-pointer w-56">
					<div class="mt-4 px-1.5 mx-4 border border-gray-200 rounded-lg shadow-md es-mailer-logo es-importer-logo bg-white">
						<div class="border-0 es-logo-wrapper">
							<img src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/admin/images/bb-logo.png' ); ?>"/>
						</div>
						<p class="mb-2 text-sm inline-block font-medium text-gray-600">
							<?php echo esc_html__( 'Import BuddyBoss members', 'email-subscribers' ); ?>
						</p>
						<span class="premium-icon max"></span>
					</div>
				</label>
			</a>
			<?php
		}
	}
}

/**
 * Upsell campaign Pro rules
 *
 * @param array Campaign rules
 *
 * @return array Campaign rules
 *
 * @since 4.6.12
 */
function ig_es_upsell_pro_campaign_rules( $campaign_rules = array() ) {

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {

		$pro_campaign_rules = array(
			'List'       => array(
				array(
					'name'     => esc_html__( 'is not in List [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
			),
			'Subscriber' => array(
				array(
					'name'     => esc_html__( 'Email [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
				array(
					'name'     => esc_html__( 'Country [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
				array(
					'name'     => esc_html__( 'Engagement score [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
				array(
					'name'     => esc_html__( 'Bounce status [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
			),
			'Campaign'   => array(
				array(
					'name'     => esc_html__( 'has received [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
				array(
					'name'     => esc_html__( 'has not received [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
				array(
					'name'     => esc_html__( 'has received and opened [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
				array(
					'name'     => esc_html__( 'has received but not opened [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
				array(
					'name'     => esc_html__( 'has received and clicked [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
				array(
					'name'     => esc_html__( 'has received and not clicked [MAX]', 'email-subscribers' ),
					'disabled' => true,
				),
			),
		);

		$campaign_rules = array_merge_recursive( $campaign_rules, $pro_campaign_rules );
	}

	return $campaign_rules;
}

function ig_es_upsell_campaign_rules_message() {

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$utm_args = array(
			'url'=>'https://www.icegram.com/documentation/how-to-send-broadcast-post-notification-post-digest-to-multiple-lists-in-one-campaign/',
			'utm_medium' => 'campaign_rules',
		);

		$pricing_url = ES_Common::get_utm_tracking_url( $utm_args );
		$upsell_info = array(
			'upgrade_title'  => __( 'Send campaign to specific audience with MAX', 'email-subscribers' ),
			'pricing_url'    => $pricing_url,
			'upsell_message' => __( 'Now, you can select multiple lists and also filter your subscribers based on their country, emails and whether they have received, opened or clicked a specific campaign or not and then send campaign emails to them.', 'email-subscribers' ),
			'cta_html'       => false,
		);

		?>
			<div class="block w-2/3 py-2 px-6 mt-2 ">
			<?php ES_Common::upsell_description_message_box( $upsell_info ); ?>
			</div>
		<?php
	}
}

function ig_es_upsell_contacts_bulk_action( $actions = array() ) {

	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$actions['bulk_send_confirmation_email_upsell'] = __( 'Send confirmation email [MAX]', 'email-subscribers' );
	}

	return $actions;
}

function ig_es_upsell_cf_button() {

	if ( ES()->can_upsell_features( array( 'lite','starter', 'trial' ) ) ) {
		$utm_args = array(
			'url'  		 => 'https://www.icegram.com/documentation/how-to-manage-custom-fields-in-email-subscribers',
			'utm_medium' => 'manage_custom_fields'
		);
		$url = ES_Common::get_utm_tracking_url($utm_args);
		?>
		<a href = "<?php echo esc_url( $url ); ?>" target = "_blank" class="inline-flex justify-center border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 transition duration-150 ease-in-out px-3 py-1 ml-2 leading-5 align-middle">
			<?php esc_html_e( 'Manage Custom Fields', 'email-subscribers' ); ?>
		</a>
		<span class="premium-icon max"></span>

		<?php
	}
}

function ig_es_upsell_cf_form_field() {
	if ( ES()->can_upsell_features( array( 'lite', 'starter', 'trial' ) ) ) {
		$utm_args = array(
			'url'  		 => 'https://www.icegram.com/documentation/how-to-manage-custom-fields-in-email-subscribers',
			'utm_medium' => 'custom_form_field'
		);
		$url = ES_Common::get_utm_tracking_url($utm_args);
		?>
		<tr class="form-field">
			<td class="pr-6 pb-6" colspan = "5">
				<b class="text-gray-500 text-sm font-normal pb-2">
					<?php esc_html_e( 'Want to add more form fields?', 'email-subscribers' ); ?>
				</b>
				<a href = "<?php echo esc_url( $url ); ?>" target = "_blank" >
					<span class = "premium-icon ml-2 mb-1 max"></span>
				</a>
			</td>
		</tr>
		<?php
	}
}

function ig_es_upsell_bounced_dashboard_stats( $days, $report_data = array() ) {
	if ( ES()->can_upsell_features( array( 'lite', 'trial', 'starter' ) ) ) {
		$total_hard_bounced_contacts = 0;
		$bounces_before_two_months   = 0;
		$bounces_percentage_growth   = 0;
		$convert_date_format         = get_option( 'date_format' );
		$last_period_start_date      = gmdate( $convert_date_format, strtotime( '-' . ( 2 * $days ) . ' days' ) );
		$last_period_end_date        = gmdate( $convert_date_format, strtotime( '-' . $days . ' days' ) );
		$upsell                      = true;
		ES_Admin::get_view(
			'dashboard/bounce-stats',
			array(
				'total_hard_bounced_contacts' => $total_hard_bounced_contacts,
				'bounces_before_two_months'   => $bounces_before_two_months,
				'bounces_percentage_growth'   => $bounces_percentage_growth,
				'last_period_start_date'      => $last_period_start_date,
				'last_period_end_date'        => $last_period_end_date,
				'upsell'					  => $upsell,
			)
		);
	}
}

function ig_es_upsell_sequence_message_stats( $days, $report_data = array() ) {
	if ( ES()->can_upsell_features( array( 'lite', 'trial', 'starter' ) ) ) {
		$total_hard_bounced_contacts = 0;
		$bounces_before_two_months   = 0;
		$bounces_percentage_growth   = 0;
		$convert_date_format         = get_option( 'date_format' );
		$last_period_start_date      = gmdate( $convert_date_format, strtotime( '-' . ( 2 * $days ) . ' days' ) );
		$last_period_end_date        = gmdate( $convert_date_format, strtotime( '-' . $days . ' days' ) );
		$upsell                      = true;
		ES_Admin::get_view(
			'dashboard/bounce-stats',
			array(
				'total_hard_bounced_contacts' => $total_hard_bounced_contacts,
				'bounces_before_two_months'   => $bounces_before_two_months,
				'bounces_percentage_growth'   => $bounces_percentage_growth,
				'last_period_start_date'      => $last_period_start_date,
				'last_period_end_date'        => $last_period_end_date,
				'upsell'					  => $upsell,
			)
		);
	}
}

function ig_es_upsell_top_countries_stats( $days, $report_data = array() ) {
	if ( ES()->can_upsell_features( array( 'lite', 'trial', 'starter' ) ) ) {
		$top_countries = array(
			'US' => 1500,
			'AU' => 1200,
			'ES' => 800,
			'FR' => 650,
			'RU' => 144,	
		);
		ES_Admin::get_view(
			'dashboard/top-countries',
			array(
				'top_countries' => $top_countries,
				'upsell'        => true,
			)
		);
	}
}

function ig_es_upsell_recent_activities( $days, $report_data = array() ) {
	if ( ES()->can_upsell_features( array( 'lite', 'trial', 'starter' ) ) ) {
		$recent_activities = array(
			array(
				/* translators: %s. Anchors tag */
				'text' => sprintf( __( '%1$sJohn%2$s subscribed to %1$sMain%2$s list', 'email-subscribers' ), '<a href="#">', '</a>' ),
				'time' => '1 ' . __( 'minute ago', 'email-subscribers' ),
			),

			array(
				/* translators: %s. Strong tag */
				'text' => sprintf( __( '%1$sRiley%2$s clicked on %1$shttps://example.com%2$s 2 times in BFCM campaign', 'email-subscribers' ), '<a href="#">', '</a>' ),
				'time' => '32 ' . __( 'minutes ago', 'email-subscribers' ),
			),
			array(
				/* translators: %s. Strong tag */
				'text' => sprintf( __( '%1$sRoanna%2$s received BFCM campaign', 'email-subscribers' ), '<a href="#">', '</a>', '<strong>', '</strong>' ),
				'time' => '1 ' . __( 'hour ago', 'email-subscribers' ),
			),
			array(
				/* translators: %s. Strong tag */
				'text' => sprintf( __( '%1$stewart@example.com%2$s marked as hard bounced', 'email-subscribers' ), '<a href="#">', '</a>' ),
				'time' => '3 ' . __( 'hours ago', 'email-subscribers' ),
			),
			array(
				/* translators: %s. Strong tag */
				'text' => sprintf( __( '%1$sjoel.doe@example.com%2$s unsubscribed from %1$sMain%2$s list', 'email-subscribers' ), '<a href="#">', '</a>' ),
				'time' => '1 ' . __( 'week ago', 'email-subscribers' ),
			),
			array(
				/* translators: %s. Anchors tag */
				'text' => sprintf( __( '%1$sJohn%2$s subscribed to %1$sPublic%2$s list', 'email-subscribers' ), '<a href="#">', '</a>' ),
				'time' => '1 ' . __( 'week ago', 'email-subscribers' ),
			),
		);
		ES_Admin::get_view(
			'dashboard/recent-activities',
			array(
				'recent_activities' => $recent_activities,
				'upsell'            => true,
			)
		);
	}
}
