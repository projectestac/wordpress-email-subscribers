<style type="text/css">
	.ig_es_offer {
		width: 60%;
		margin: 0 auto;
		text-align: center;
		padding-top: 1.2em;
	}

</style>
<?php

if ( ( get_option( 'ig_es_offer_covid_19' ) !== 'yes' ) && ( $ig_current_date >= strtotime( '2020-04-08' ) ) && ( $ig_current_date <= strtotime( '2020-04-30' ) ) ) {
	$notice_dismiss_url = wp_nonce_url(
		add_query_arg(
			array(
				'es_dismiss_admin_notice' => 1,
				'option_name'             => 'offer_covid_19',
			) 
		),
		'es_dismiss_admin_notice'
	);
	?>
	<div class="ig_es_offer">
		<a target="_blank" href="<?php echo esc_url( $notice_dismiss_url ); ?>"><img src="<?php echo esc_url( ES_PLUGIN_URL ); ?>/lite/admin/images/covid-19.png"/></a>
	</div>

<?php } ?>
