<?php 
$trial_block      = array();
$show_trial_optin = !  ES()->trial->is_trial() && ! ES()->is_premium();
$allowed_tags     = ig_es_allowed_html_tags_in_esc();
if ( $show_trial_optin ) {
	$trial_period_in_days = ES()->trial->get_trial_period( 'in_days' );

	$trial_block = array(
		'trial-optin' => array(
			'title'        => __( 'Try Icegram Express - Premium', 'email-subscribers' ),
			/* translators: %d: Trial period in days */
			'desc'         => __( 'You\'re currently on the "Free, Basic" plan. But you can start a 14 days free trial of Premium plan and unlock:', 'email-subscribers' ),
			'cta_text'     => __( 'Start my free trial', 'email-subscribers' ),
			'feature_url'  => '#',
			/* translators: %s Trial days */
			//'features_heading' => sprintf( esc_html__( 'Here is what you get for the next %s', 'email-subscribers' ), $trial_period_in_days . ' days' ),
			'features_list' => array(
				'feature_first' => __( 'Reliable email sending with background batch processing', 'email-subscribers' ),
				'feature_second' => __( 'Spam check - prevent your emails from hitting the junk folder', 'email-subscribers' ),
				'feature_third' => __( 'UTM link tracking with Google Analytics', 'email-subscribers' ),
				'feature_fourth' => __( 'Much better email marketing results', 'email-subscribers' ),
			),
		),
	);
} elseif ( ! ES()->is_premium() && ES()->trial->is_trial() && ES()->trial->is_trial_valid() ) {
	$trial_period_in_days        = ES()->trial->get_trial_period( 'in_days' );
	$trial_remaining_in_days = ES()->trial->get_remaining_trial_days();
	$trial_expiry_date           = ES()->trial->get_trial_expiry_date();
	$formatted_trial_expiry_date = ig_es_format_date_time( $trial_expiry_date );

	if (gmdate('Y-m-d', ES()->trial->get_trial_started_at()) == gmdate('Y-m-d', time())) {
		$trial_block = array(
			'trial-active' => array(
				/* translators: %d: Trial period in days */
				'title'        => sprintf( esc_html__( 'Your free %s trial is on ', 'email-subscribers' ), $trial_period_in_days . ' days' ),
				/* translators: %s: Number of days remaining in trial */
				'desc'         => sprintf( __( 'Hope you are enjoying the premium features of Icegram Express. It will expire on %s. You can anytime upgrade it to MAX.', 'email-subscribers' ), $formatted_trial_expiry_date ),
				'cta_text'     => __( 'Upgrade to Max', 'email-subscribers' ),
				'feature_url'  => 'https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=upsell&utm_campaign=es_upsell',
			),
		);
	} else {
		$trial_block = array(
			'trial-active' => array(
				/* translators: %d: Trial period in days */
				'title'        => sprintf( esc_html__( '%s remaining of your free trial', 'email-subscribers' ), '<b class="text-indigo-600">' . $trial_remaining_in_days . ' days </b>' ),
				/* translators: %s: Number of days remaining in trial */
				'desc'         => sprintf( __( 'Hope you are enjoying the premium features of Icegram Express. It will expire on %s. You can anytime upgrade it to MAX.', 'email-subscribers' ), '<b class="text-indigo-600">' . $formatted_trial_expiry_date . '</b>' ),
				'cta_text'     => __( 'Upgrade to Max', 'email-subscribers' ),
				'feature_url'  => 'https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=upsell&utm_campaign=es_upsell',
				/* translators: %s Remaining trial day */
				'features_heading' => sprintf( esc_html__( 'Here is what you get for the next %s', 'email-subscribers' ), $trial_remaining_in_days . ' days' ),
				'features_list' => array(
					'feature_first' => __( "<a class='text-blue-600 font-bold' href='https://www.icegram.com/docs/category/icegram-express/enable-captcha/?utm_source=es&utm_medium=dashboard&utm_campaign=revamp-01' target='_blank'>Captcha</a> - To avoid spam/bot attacks", 'email-subscribers' ),
					'feature_second' => __( "<a class='text-blue-600 font-bold' href='https://www.icegram.com/docs/category/icegram-express-premium/check-spam-score#what-to-do-if-my-spam-score-is-higher-than-5/?utm_source=es&utm_medium=dashboard&utm_campaign=revamp-01' target='_blank'>Spam score checking</a> - Stop lading your email in spams", 'email-subscribers' ),
					'feature_third' => __( "<a class='text-blue-600 font-bold' href='https://www.icegram.com/docs/category/icegram-express-premium/enable-automatic-cron/?utm_source=es&utm_medium=dashboard&utm_campaign=revamp-01' target='_blank'>Background email sending</a> - To ensure reliable email sending", 'email-subscribers' ),
				),
			),
		);
	}
	
} elseif ( ! ES()->is_premium() && ES()->trial->is_trial() && ES()->trial->is_trial_expired() ) {
	$trial_period_in_days = ES()->trial->get_trial_period( 'in_days' );
	$trial_expiry_date           = ES()->trial->get_trial_expiry_date();
	$formatted_trial_expiry_date = ig_es_format_date_time( $trial_expiry_date );
    $plan = ES()->get_plan();
	$trial_block = array(
		'trial-expired' => array(
			/* translators: %d: Trial period in days */
			'title' => sprintf( __( 'Icegram Express - %s', 'email-subscribers' ), $plan ),
			'desc' => sprintf( esc_html__( 'Your free trial of %1$s plan ended on %2$s. So %1$s features like faster email sending, spam check, and UTM tracking are disabled.', 'email-subscribers' ), $plan, $formatted_trial_expiry_date ),
			'cta_text'     => __( 'Choose a plan and reactivate', 'email-subscribers' ),
			'feature_url'  => 'https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=upsell&utm_campaign=es_upsell',
		),
	);
} elseif (ES()->is_premium()) {
	$plan = ES()->get_plan();
	$trial_block = array(
		'trial-expired' => array( 
			'title' => sprintf( __( 'Icegram Express - %s', 'email-subscribers' ), $plan ),
			'desc' => sprintf( __( 'You\'re currently on the %s plan and advanced email marketing features are enabled.', 'email-subscribers' ), $plan ),
			'feature_url'  => '#',
			'features_heading' => '',
			// 'features_list' => array(
			// 	/* translators: 1. Anchor start tag 2. Anchor close tag */
			// 	'feature_first' => sprintf( __( ' %1$sFluentCRM vs. Icegram Express%2$s', 'email-subscribers' ), '<a class="text-indigo-600 font-bold" href="https://www.icegram.com/fluentcrm-vs-icegram-express/?utm_source=es&utm_medium=in_app&utm_campaign=dashboard_help" target="_blank">', '</a>' ),
				
			// 	/* translators: 1. Anchor start tag 2. Anchor close tag */
			// 	'feature_second' => sprintf( __( ' %1$sMailpoet vs. Icegram Express%2$s', 'email-subscribers' ), "<a class='text-indigo-600 font-bold' href='https://www.icegram.com/mailpoet-review-best-mailpoet-alternative/?utm_source=es&utm_medium=in_app&utm_campaign=dashboard_help' target='_blank'>", '</a>' ),
				
			// 	/* translators: 1. Anchor start tag 2. Anchor close tag */
			// 	'feature_third' => sprintf( __( ' %1$sOrganize email newsletter content calendar%2$s', 'email-subscribers' ), "<a class='text-indigo-600 font-bold' href='https://www.icegram.com/how-to-organize-email-newsletter-content-calendar/?utm_source=es&utm_medium=in_app&utm_campaign=dashboard_help' target='_blank'>", '</a>' ),
			// ),
			'features_sub_heading' => sprintf( esc_html__('Try our other products', 'email-subscribers')),
			'features_sub_list' => array(
				/* translators: 1. Anchor start tag 2. Anchor close tag */
				'feature_first' => sprintf( __( ' %1$s%2$s%3$sIcegram Engage%4$s - popups, CTAs and more for onsite conversion%5$s%6$s', 'email-subscribers' ), "<div class='flex'><img class='mr-2 h-6' src='https://ps.w.org/icegram/assets/icon-128x128.png' alt=''>", "<a class='text-gray-500 text-sm hover:text-black-600' href='https://www.icegram.com/engage/?utm_source=es&utm_medium=in_app&utm_campaign=dashboard_help' target='_blank'>", '<b>', '</b>', '</a>', '</div>' ),
				
				/* translators: 1. Anchor start tag 2. Anchor close tag */
				'feature_second' => sprintf( __( ' %1$s%2$s%3$sIcegram Collect%4$s - easy forms and lead capture%5$s%6$s', 'email-subscribers' ), "<div class='flex'><img class='mr-2 h-6' src='https://ps.w.org/icegram-rainmaker/assets/icon-128x128.png' alt=''>", "<a class='text-gray-500 text-sm hover:text-black-600' href='https://www.icegram.com/collect/?utm_source=es&utm_medium=in_app&utm_campaign=dashboard_help' target='_blank'>", '<b>', '</b>', '</a>', '</div>' ),

				'feature_third' => sprintf( __( ' %1$s%2$s%3$sDuplicate Pages and Posts%4$s - A Duplicate Pages and Posts Plugin is a tool for WordPress.%5$s%6$s', 'email-subscribers' ), "<div class='flex'><img class='mr-2 h-6' src='https://s.w.org/plugins/geopattern-icon/duplicate-post-page-copy-clone-wp.svg' alt=''>", "<a class='text-gray-500 text-sm hover:text-black-600' href='https://wordpress.org/plugins/duplicate-post-page-copy-clone-wp/' target='_blank'>", '<b>', '</b>', '</a>', '</div>' ),

				
			),
		),
	);
}

require_once 'trial-optin-form.php';

foreach ( $trial_block as $feature => $data ) {
	$is_trial_block = strpos( $feature, 'trial' ) !== false;
	?>
	<div id="ig-es-<?php echo esc_attr( $feature ); ?>-block">
	  
		<p class="sec-title">
			<span>
			<?php echo wp_kses_post( $data['title'] ); ?>
			</span>
			<?php 
			if ($feature == 'trial-active') {
				?>
				<span class="block-status"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
				<title><?php echo esc_html__( 'Icegram Express - Premium Activated', 'email-subscribers' ); ?></title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> &nbsp; <?php echo esc_html__( 'Active', 'email-subscribers' ); ?> &nbsp;</span>
				<?php
			}

			if ( $feature == 'trial-expired' && ! ES()->is_premium() && ES()->trial->is_trial() && ES()->trial->is_trial_expired()) {
				?>
				<span class="trial-ended-block "><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
					<title><?php echo esc_html__( 'Trial Ended', 'email-subscribers' ); ?></title>
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg> &nbsp; Trial ended &nbsp; </span>
				<?php
			}

			if ($feature == 'trial-expired' && ES()->is_premium()) {
				?>
				<span class="block-status"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
				<title><?php echo esc_html__( 'Icegram Express - Premium Activated', 'email-subscribers' ); ?></title>
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> &nbsp; <?php echo esc_html__( 'Active', 'email-subscribers' ); ?> &nbsp;</span>
				<?php
			}
			?>
		</p>
		<?php
		if ( ! empty( $data['graphics_img'] ) ) {
			$extra_css = ! empty( $data['graphics_img_class'] ) ? $data['graphics_img_class'] : '';
			?>
			<img class="absolute bottom-0 right-0 w-24 -mr-3 <?php echo esc_attr( $extra_css ); ?>" src= "<?php echo esc_url( ES_PLUGIN_URL . $data['graphics_img'] ); ?>"/>
			<?php
		}
		?>
		<div class="block-description" style="width: calc(100% - 4rem)">
			<p class="py-3 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
				<?php
				if ( ! empty( $data['html_desc'] ) ) {
					echo wp_kses_post( $data['html_desc'] );
				} else {
					echo wp_kses_post( $data['desc'] );
				}
				?>
			</p>

			<?php
			if ( $feature == 'trial-expired' && ! ES()->is_premium() && ES()->trial->is_trial() && ES()->trial->is_trial_expired()) { 
				?>
				<p class="py-3 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
					<?php echo esc_html__( 'But it\'s not too late yet. You can reactivate all the powerful email marketing features today. 94% users who take up a paid plan are highly satisfied. ', 'email-subscribers'); ?>
				</p>
				<?php
			}

			if ( !empty($data['feature_url'])) {
				$feature_url = $data['feature_url'];
				if ( ! ES()->is_pro() && isset( $data['documentation_url'] ) ) {
					$feature_url = $data['documentation_url'];
				}

				if (!empty($data['features_heading'])) {
					?>
					<p class="py-3 text-lg font-medium leading-6 text-gray-400">
						<span class="leading-7">
						<?php echo esc_html( $data['features_heading'] ); ?>
						</span>
					</p>
					<?php 
				}
				?>

				<?php 
				if (!empty($data['features_list'])) {
					?>
					<ul class="features_list text-sm">
						<?php
						foreach ($data['features_list'] as $key => $val) {
							?>
							<li><?php echo wp_kses( $val, $allowed_tags ); ?></li>
							<?php
						}
						?>
					</ul>
					<?php
				}
					
				if ($feature == 'trial-expired' && ES()->is_premium()) {
					?>
					<a id="ig-es-<?php echo esc_attr( $feature ); ?>-cta" href="<?php echo esc_url( 'https://www.icegram.com/my-account/' ); ?>" target="_blank">
						<button type="button" class="lighter-gray mt-2">
							<?php echo esc_html__( 'Switch / manage your plan', 'email-subscribers' ); ?> &rarr;
						</button>
					</a>
					<?php
				}
				?>
				
				<?php
				if (!empty($data['features_sub_heading'])) {
					?>
					<p class="py-3 mt-6 text-lg font-medium leading-6 text-gray-400">
						<span class="leading-7">
						<?php echo esc_html( $data['features_sub_heading'] ); ?>
						</span>
					</p>
					<?php 
				}
				?>
				<ul class="list-disc">
					<?php 
					if (!empty($data['features_sub_list'])) {
						foreach ($data['features_sub_list'] as $key => $val) {
							?>
							<li><?php echo wp_kses( $val, $allowed_tags ); ?></li>
							<?php
						}
					}
					?>
				</ul>
				<?php if (!empty($data['cta_text']) && !empty($feature_url)) { ?>
				<a id="ig-es-<?php echo esc_attr( $feature ); ?>-cta" href="<?php echo esc_url( $feature_url ); ?>" target="_blank">
					<button type="button" class="lighter-gray mt-2">
						<?php echo esc_html( $data['cta_text'] ); ?> &rarr;
					</button>
				</a>
				<?php } ?>
			<?php
			}
			?>
		</div>
	</div>
	<?php
}?>
