<style type="text/css">
	.ig_es_offer {	
		width:70%;
		margin: 0 auto;
		text-align: center;
		padding-top: 0.8em;
	}

</style>
<?php

if ( ( get_option( 'ig_es_offer_bfcm_2020' ) !== 'yes' ) && ( $ig_current_date >= strtotime( '2020-11-24' ) ) && ( $ig_current_date <= strtotime( '2020-12-02' ) )) { ?>
	<div class="wrap">
		<div class="ig_es_offer">
			<a target="_blank" href="?es_dismiss_admin_notice=1&option_name=offer_bfcm_2020"><img style="margin:0 auto" src="<?php echo esc_url ( ES_PLUGIN_URL ); ?>lite/admin/images/bfcm_2020.jpg"/></a>
		</div>
	</div>

<?php } ?>
