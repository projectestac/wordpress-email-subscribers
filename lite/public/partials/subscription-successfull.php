<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$es_page_request = ig_es_get_request_data('es');
$main_message    = '';

if ( 'optin' === $es_page_request ) {
	$main_message = __('Subscription confirmed !', 'email-subscribers');
} elseif ( 'unsubscribe' === $es_page_request ) {
	$main_message = __('Unsubscription confirmed !', 'email-subscribers');
}

$site_name = get_option( 'blogname' );
$noerror   = true;
$home_url  = home_url( '/' );
?>
<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	  <head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo esc_html( $site_name ); ?></title>
		<meta http-equiv="refresh" content="10; url=<?php echo esc_url( $home_url ); ?>" charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>"/>
		<?php do_action( 'es_message_head' ); ?>
		<?php
			wp_register_style( 'tailwind', ES_PLUGIN_URL . 'lite/admin/dist/main.css', array(), $this->version, 'all' );
			$es_wp_styles = wp_styles();
			$es_wp_styles->do_item( 'tailwind' );
		?>
	  </head>
	  <body class="min-h-screen mt-16 px-4 pt-10 pb-12 mx-auto max-w-7xl bg-gray-200 sm:px-6 lg:px-8">
			<section class="bg-indigo-600 py-12 px-12 text-white shadow-md sm:rounded-lg mx-auto sm:w-2/3 xl:w-7/12" id="ig-es-unsubscribe-message">
			  <div class="leading-6 tracking-wide">
				<h3 class="font-medium text-base">
				  <?php echo esc_html($main_message); ?>
				</h3>
				<p class="pt-4 font-thin text-lg">
				  <?php echo wp_kses_post( $message ); ?>
				</p>
			  </div>
			</section>

			<!-- Start-IG-Code -->
			<?php
			$ig_es_powered_by 	= ! empty( get_option( 'ig_es_powered_by' ) ) ? get_option( 'ig_es_powered_by' ) : 'yes' ;
			if ( 'yes' === $ig_es_powered_by ) {
				?>
			<section class="bg-white mt-8 py-8 shadow-md sm:rounded-lg mx-auto sm:w-2/3 xl:w-7/12">
			  <div class="flex">
				<div class="sm:w-1/3 xl:w-1/4 pl-6 leading-6">
				  <p class="uppercase text-sm text-gray-600 pl-2 pb-2 tracking-wide">
					<?php echo esc_html__('Powered by', 'email-subscribers'); ?>
				  </p>
				  <img class="pt-1" src="https://www.icegram.com/wp-content/uploads/2019/10/icegram-logo-300x80-24bit.png"/>
				</div>
				<div class="pl-8 pr-6 text-gray-700">
					<p class="pb-2 text-base font-bold text-gray-700">
					   <?php echo esc_html__('Want to Engage, Inspire and Convert Your Website Visitors ?', 'email-subscribers'); ?>
					</p>
					<p class="text-sm text-gray-700">
					   <?php echo esc_html__('The most loved WordPress plugins for lead capture, call to action and email marketing.', 'email-subscribers'); ?>
					   <a class="text-sm font-medium text-indigo-600 hover:text-indigo-500" href="https://www.icegram.com/">
						   <?php echo esc_html__(' Take a look here', 'email-subscribers'); ?>
							<svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor" class="w-3 h-3 inline-block align-middle font-medium">
								<path d="M9 5l7 7-7 7"></path>
							</svg>
					  </a>
					</p>
				</div>
			  </div>
			</section>
			<?php } ?>
			<!-- End-IG-Code -->
		</body>
  </html>
  <?php

	die();
