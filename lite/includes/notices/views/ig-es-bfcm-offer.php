<style type="text/css">
	.ig_es_offer {	
		width:70%;
		margin: 0 auto;
		text-align: center;
		padding-top: 0.8em;
	}

</style>
<?php
$plan    = ES()->get_plan();
$offer_name = 'offer_bfcm_2022';
$img_url = esc_url( ES_PLUGIN_URL ) . 'lite/admin/images/bfcm-pro-banner-2022.png';
if ( ES()->is_pro() ) {
	$img_url = esc_url( ES_PLUGIN_URL ) . 'lite/admin/images/bfcm-common-banner-2022.png';
}

if ( ( get_option( 'ig_es_offer_bfcm_2022' ) !== 'yes' ) && ES()->is_offer_period( 'bfcm' ) ) { 
	$notice_dismiss_url = wp_nonce_url(
		add_query_arg(
			array(
				'es_dismiss_admin_notice' => 1,
				'option_name'             => $offer_name,
			) 
		),
		'es_dismiss_admin_notice'
	);
	?>
	<div class="wrap">
		<div class="ig_es_offer">
			<a target="_blank" href="<?php echo esc_url( $notice_dismiss_url ); ?>"><img style="margin:0 auto" src="<?php echo esc_url( $img_url ); ?>"/></a>
		</div>
	</div>

<?php } ?>
