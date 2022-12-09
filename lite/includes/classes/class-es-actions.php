<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Actions' ) ) {
	/**
	 * Class ES_Actions
	 *
	 * Track all actions
	 *
	 * IG_CONTACT_SUBSCRIBE   => 1,
	 * IG_MESSAGE_SENT        => 2,
	 * IG_MESSAGE_OPEN        => 3,
	 * IG_LINK_CLICK          => 4,
	 * IG_CONTACT_UNSUBSCRIBE => 5,
	 * IG_MESSAGE_SOFT_BOUNCE => 6,
	 * IG_MESSAGE_HARD_BOUNCE => 7,
	 * IG_MESSAGE_ERROR       => 8
	 *
	 * @since 4.2.0
	 */
	class ES_Actions {
		/**
		 * ES_DB_Actions object
		 *
		 * @since 4.2.1
		 * @var $db
		 */
		protected $db;

		/**
		 * ES_Actions constructor.
		 *
		 * @since 4.2.0
		 */
		public function __construct() {

			$this->db = new ES_DB_Actions();

			add_action( 'init', array( &$this, 'init' ), 1 );
		}

		/**
		 * Init Actions
		 *
		 * @since 4.2.0
		 */
		public function init() {
			add_action( 'ig_es_contact_subscribe', array( &$this, 'subscribe' ), 10, 2 );
			add_action( 'ig_es_message_sent', array( &$this, 'sent' ), 10, 3 );
			add_action( 'ig_es_message_open', array( &$this, 'open' ), 10, 3 );
			add_action( 'ig_es_message_click', array( &$this, 'click' ), 10, 5 );
			add_action( 'ig_es_contact_unsubscribe', array( &$this, 'unsubscribe' ), 10, 4 );
		}

		/**
		 * Get action data
		 *
		 * @since 4.2.0
		 */
		public function get_fields( $fields = null, $where = null ) {

			global $wpdb, $wpbd;

			$fields = esc_sql( is_null( $fields ) ? '*' : ( is_array( $fields ) ? implode( ', ', $fields ) : $fields ) );

			$sql = "SELECT $fields FROM {$wpdb->prefix}ig_actions WHERE 1=1";
			if ( is_array( $where ) ) {
				foreach ( $where as $key => $value ) {
					$sql .= ', ' . esc_sql( $key ) . " = '" . esc_sql( $value ) . "'";
				}
			}

			return $wpbd->get_results( $sql, ARRAY_A );

		}

		/**
		 * Add action
		 *
		 * @param $args
		 * @param bool $explicit
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		private function add( $args, $explicit = true ) {

			$args = wp_parse_args(
				$args,
				array(
					'created_at'   => ig_es_get_current_gmt_timestamp(),
					'updated_at'   => ig_es_get_current_gmt_timestamp(),
					'count'        => 1,
					'ip'           => '',
					'country'      => '',
					'browser'      => '',
					'device'       => '',
					'os'           => '',
					'email_client' => '',
				)
			);

			return $this->db->add( $args, $explicit );
		}

		/**
		 * Track Contact Action
		 *
		 * @param $args
		 * @param bool $explicit
		 *
		 * @since 4.2.4
		 */
		private function add_contact_action( $args, $explicit = true ) {

			return $this->add( $args, $explicit );
		}

		/**
		 * Add action
		 *
		 * @param $args
		 * @param bool $explicit
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		private function add_action( $args, $explicit = true ) {
			return $this->add( $args, $explicit );
		}

		/**
		 * Track Subscribe Action
		 *
		 * @param $contact_id
		 * @param array      $list_ids
		 *
		 * @since 4.2.0
		 */
		public function subscribe( $contact_id, $list_ids = array() ) {
			if ( is_array( $list_ids ) && count( $list_ids ) > 0 ) {
				foreach ( $list_ids as $list_id ) {
					$this->add_action(
						array(
							'contact_id' => $contact_id,
							'list_id'    => $list_id,
							'type'       => IG_CONTACT_SUBSCRIBE,
						)
					);
				}
			}

		}

		/**
		 * Track Send Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		public function sent( $contact_id, $campaign_id = 0, $message_id = 0 ) {
			return $this->add_action(
				array(
					'contact_id'  => $contact_id,
					'campaign_id' => $campaign_id,
					'message_id'  => $message_id,
					'type'        => IG_MESSAGE_SENT,
				)
			);
		}

		/**
		 * Track Message Open Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		public function open( $contact_id, $message_id, $campaign_id, $explicit = true ) {

			// Track only if campaign sent.
			if ( $this->is_campaign_sent( $contact_id, $message_id, $campaign_id ) ) {

				$action_data = array(
					'contact_id'  => $contact_id,
					'message_id'  => $message_id,
					'campaign_id' => $campaign_id,
					'type'        => IG_MESSAGE_OPEN,
				);

				$device_info = $this->get_user_device_info();
				$action_data = array_merge( $action_data, $device_info );

				return $this->add_action( $action_data, $explicit );
			}
		}

		/**
		 * Track Link Click Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 * @param $link_id
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		public function click( $link_id, $contact_id, $message_id, $campaign_id, $explicit = true ) {

			// When someone click on link which means they have opened that email
			// Track Email Open
			$this->open( $contact_id, $message_id, $campaign_id, false );

			$action_data = array(
				'contact_id'  => $contact_id,
				'campaign_id' => $campaign_id,
				'message_id'  => $message_id,
				'link_id'     => $link_id,
				'type'        => IG_LINK_CLICK,
			);

			$device_info = $this->get_user_device_info();
			$action_data = array_merge( $action_data, $device_info );

			return $this->add_contact_action( $action_data, $explicit );
		}

		/**
		 * Track Contact Unsubscribe Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 * @param array       $list_ids
		 *
		 * @since 4.2.0
		 */
		public function unsubscribe( $contact_id, $message_id, $campaign_id, $list_ids = array() ) {
			if ( is_array( $list_ids ) && count( $list_ids ) > 0 ) {
				foreach ( $list_ids as $list_id ) {

					$this->add_action(
						array(
							'contact_id'  => $contact_id,
							'message_id'  => $message_id,
							'campaign_id' => $campaign_id,
							'list_id'     => $list_id,
							'type'        => IG_CONTACT_UNSUBSCRIBE,
						)
					);
				}
			}
		}

		/**
		 * Track Message Bounce Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 * @param bool        $hard
		 *
		 * @since 4.2.0
		 */
		public function bounce( $contact_id, $campaign_id, $hard = false ) {
			$this->add_action(
				array(
					'contact_id'  => $contact_id,
					'campaign_id' => $campaign_id,
					'type'        => $hard ? IG_MESSAGE_HARD_BOUNCE : IG_MESSAGE_SOFT_BOUNCE,
				)
			);
		}

		/**
		 * Check whether campaign is sent to specific contact
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 *
		 * @return string|null
		 *
		 * @since 4.2.3
		 */
		public function is_campaign_sent( $contact_id, $message_id, $campaign_id ) {

			global $wpdb;

			$sql = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM {$wpdb->prefix}ig_actions WHERE contact_id = %d AND message_id = %d AND campaign_id = %d AND type = %d ", $contact_id, $message_id, $campaign_id, IG_MESSAGE_SENT ) );
			return $sql;
		}

		/**
		 * Method to update campaign viewed/opened_at status
		 *
		 * @param int $conact_id
		 * @param int $campaign_id
		 * @param int $message_id
		 *
		 * @return bool|false|int|void
		 *
		 * @since 4.4.7
		 */
		public function update_viewed_status( $conact_id = 0, $campaign_id = 0, $message_id = 0 ) {
			global $wpdb;

			if ( empty( $conact_id ) || empty( $campaign_id ) ) {
				return;
			}

			$current_date = ig_get_current_date_time();
			$sql          = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}ig_sending_queue SET opened_at = %s, opened = %d WHERE contact_id = %d AND campaign_id = %d AND mailing_queue_id = %d", $current_date, 1, $conact_id, $campaign_id, $message_id ) );

			return $sql;

		}

		/**
		 * Method to get current device information
		 *
		 * @since 4.5.0
		 */
		public function get_user_device_info() {

			$browser     = new ES_Browser();
			$device_info = array();

			if ( $browser->isMobile() ) {
				$device_info['device'] = 'mobile';
			} elseif ( $browser->isTablet() ) {
				$device_info['device'] = 'tablet';
			} else {
				$device_info['device'] = 'desktop';
			}

			$device_ip_address = ig_es_get_ip();
			if ( ! empty( $device_ip_address ) && 'UNKNOWN' !== $device_ip_address ) {
				$device_location_data   = ES_Geolocation::geolocate_ip( $device_ip_address );
				$device_country_code    = ! empty( $device_location_data['country_code'] ) ? $device_location_data['country_code'] : '';
				$device_info['country'] = $device_country_code;
			} else {
				$device_ip_address      = '';
				$device_info['country'] = '';
			}

			$device_info['ip']           = $device_ip_address;
			$device_info['browser']      = $browser->getBrowser();
			$device_info['os']           = $browser->getPlatform();
			$device_info['email_client'] = $browser->get_email_client();

			return $device_info;
		}
	}
}


