<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Dashboard' ) ) {
	
	/**
	 * Get dashboard statistics
	 *
	 * @since 5.5.5
	 */
	class ES_Dashboard {

		public function show() {
			$source         = 'es_dashboard';
			$override_cache = true;
			$days           = 60;
			$args           = array(
				'days' => $days,
			);
			$reports_data   = ES_Reports_Data::get_dashboard_reports_data( $source, $override_cache, $args );
			
			$can_show_ess_optin = ES_Service_Email_Sending::can_show_ess_optin();
			if ( $can_show_ess_optin ) {
				ES_Admin::get_view(
					'dashboard/dashboard-ess',
					array(
						'reports_data' => $reports_data,
						'days'         => $days,
					)
				);
				ES_Service_Email_Sending::set_ess_optin_shown_flag();
			} else {
				ES_Admin::get_view(
					'dashboard/dashboard',
					array(
						'reports_data' => $reports_data,
						'days'         => $days,
					)
				);
			}
		}

		public static function get_subscribers_stats() {

			check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
			$page           = 'es_dashboard';
			$days           = ig_es_get_request_data( 'days' );
			$list_id        = ig_es_get_request_data( 'list_id' );
			$args           = array(
				'list_id' => $list_id,
				'days'    => $days,
			);
			$override_cache = true;
			$reports_data   = ES_Reports_Data::get_dashboard_reports_data( $page, $override_cache, $args );
			ob_start();
			ES_Admin::get_view(
				'dashboard/subscribers-stats',
				array(
					'reports_data'   => $reports_data,
					'days'           => $days
				)
			);
			$html             = ob_get_clean();
			$response['html'] = $html;
			wp_send_json_success( $response );
		}
	}
}
