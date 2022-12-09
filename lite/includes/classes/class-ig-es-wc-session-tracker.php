<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks logged out customers via cookies.
 *
 * @class IG_ES_WC_Session_Tracker
 */
class IG_ES_WC_Session_Tracker {

	/**
	 * Tracking cookie expiry
	 *
	 * @var int (days)
	 **/
	private static $tracking_cookie_expiry;

	/**
	 * Tracking cookie name
	 *
	 * @var string - cookie name
	 **/
	private static $tracking_key_cookie_name;

	/**
	 * Tracking key
	 *
	 * @var string - This key WILL BE saved
	 **/
	private static $tracking_key_to_set = '';


	/**
	 * Returns true if a session tracking cookie has been set.
	 *
	 * Note: Includes any changes to the cookie in the current request.
	 *
	 * @since 4.6.5
	 *
	 * @return bool
	 */
	public static function is_tracking_cookie_set() {
		return (bool) IG_ES_WC_Cookies::get( self::$tracking_key_cookie_name );
	}


	/**
	 * Returns true if a session tracking cookie has been set.
	 *
	 * Note: Includes any changes to the cookie in the current request.
	 *
	 * @since 4.2
	 *
	 * @return bool
	 */
	public static function is_session_started_cookie_set() {
		return (bool) IG_ES_WC_Cookies::get( 'wp_ig_es_session_started' );
	}


	/**
	 * Returns the tracking key as currently stored in the cookie.
	 *
	 * @since 4.3
	 *
	 * @return string
	 */
	public static function get_tracking_cookie() {
		return ES_Clean::string( IG_ES_WC_Cookies::get( self::$tracking_key_cookie_name ) );
	}


	/**
	 * This method doesn't actually set the cookie, rather it initiates the cookie setting.
	 * Cookies are set only on 'wp', 'shutdown' or 'ig_es/ajax/before_send_json'.
	 *
	 * @since 4.3
	 *
	 * @param string $tracking_key
	 *
	 * @return bool
	 */
	public static function set_tracking_key_to_be_set( $tracking_key ) {
		if ( headers_sent() ) {
			return false; // cookies can't be set
		}

		self::$tracking_key_to_set = $tracking_key;
		return true;
	}

	/**
	 * Get current session key
	 *
	 * @return string|false
	 */
	public static function get_current_tracking_key() {
		if ( ! self::session_tracking_enabled() ) {
			return false;
		}

		// If a new tracking key will be set in the request, use that in favour of current cookie value
		if ( self::$tracking_key_to_set && ! headers_sent() ) {
			return self::$tracking_key_to_set;
		}

		return self::get_tracking_cookie();
	}


	/**
	 * Returns the current user ID factoring in any session cookies.
	 *
	 * @return int
	 */
	public static function get_detected_user_id() {

		if ( is_user_logged_in() ) {
			return get_current_user_id();
		}
		return 0;
	}


	/**
	 * Returns the current guest from tracking cookie.
	 *
	 * @return IG_ES_Guest|bool
	 */
	public static function get_current_guest() {
		if ( ! self::session_tracking_enabled() ) {
			return false;
		}

		if ( is_user_logged_in() ) {
			return false;
		}

		global $woocommerce;

		// Can't look up the guest in this situation.
		if ( ! isset( $woocommerce->session ) ) {
			return false;
		}

		$tracking_key = $woocommerce->session->get_customer_id();
		if ( $tracking_key ) {
			$guest = IG_ES_Guest_Factory::get_by_key( $tracking_key );
			return $guest;
		}

		return false;
	}

	/**
	 * Updates the current session based on the customer's email.
	 *
	 * Create the customer for the email if needed and contains logic to handle when a customers email changes.
	 *
	 * Cases to handle:
	 *
	 * - Registered user is logged in or remembered via cookie = bail
	 * - Email matches existing customer
	 *      - Cookie customer exists
	 *          - Cookie and matched customer are the same = do nothing
	 *          - Cookie and matched customer are different = cookie must be changed, clear cart from previous key to avoid duplicates
	 *      - No cookie customer = Set new cookie to matched customer key
	 * - Email is new
	 *      - Cookie customer exists
	 *          - Customer data is locked = create new customer, change cookie, clear cart from previous key to avoid duplicates
	 *          - Customer data is not locked = update customer email
	 *      - No cookie customer = Set new cookie to matched customer key
	 *
	 * @param string $new_email
	 * @param string $language
	 *
	 * @return IG_ES_Customer|false
	 */
	public static function set_session_by_captured_email( $new_email, $language = '' ) {

		if ( ! is_email( $new_email ) || headers_sent() || ! self::session_tracking_enabled() ) {
			// must have a valid email, be able to set cookies, have session tracking enabled
			return false;
		}

		$new_email                 = ES_Clean::email( $new_email );
		$existing_session_customer = self::get_session_customer(); // existing session customer from cookie
		$customer_matching_email   = IG_ES_Customer_Factory::get_by_email( $new_email, false ); // important! don't create new customer
		$email_is_new              = false === $customer_matching_email;

		if ( $existing_session_customer && $existing_session_customer->is_registered() ) {
			return $existing_session_customer; // bail if a registered user is already being tracked
		}

		// Check if a customer already exists matching the supplied email
		if ( $customer_matching_email ) {

			if ( ! ( $existing_session_customer && $new_email === $existing_session_customer->get_email() ) ) {
				// Customer has changed so delete the cart for the existing customer
				// To avoid duplicate abandoned cart emails
				if ( $existing_session_customer ) {
					$existing_session_customer->delete_cart();
				}
			}

			// Set the matched customer as the new customer
			$new_customer = $customer_matching_email;
		} else {
			// Is there an existing session customer
			if ( $existing_session_customer ) {
				// Check if existing and new emails are the same
				// This is actually impossible considering the previous logic but it's probably more confusing to omit this
				if ( $existing_session_customer->get_email() === $new_email ) {
					// Nothing to do
					$new_customer = $existing_session_customer;
				} else {
					$guest = $existing_session_customer->get_guest(); // customer can not be a registered user at this point

					if ( $guest->is_locked() ) {
						// email has changed and guest is locked so we must create a new guest
						// first clear the old guests cart, to avoid duplicate abandoned cart emails
						$guest->delete_cart();
						$new_customer = IG_ES_Customer_Factory::get_by_email( $new_email );
					} else {
						// Guest is not locked so we can simply update guest email
						$guest->set_email( $new_email );
						$guest->save();

						// Set the new customer to the existing session customer
						$new_customer = $existing_session_customer;
					}
				}
			} else {
				// There is no session customer, so create one
				$new_customer = IG_ES_Customer_Factory::get_by_email( $new_email );
			}
		}

		// init the new customer tracking, also saves/updates the language
		// if ( $new_customer ) {
		// self::set_session_customer( $new_customer, $language );
		// }

		// update the stored cart
		if ( IG_ES_Abandoned_Cart_Options::is_cart_tracking_enabled() ) {
			IG_ES_WC_Carts::update_stored_customer_cart( $new_customer );
		}

		return $new_customer;
	}

	/**
	 * Returns the current session customer and takes into account session tracking cookies.
	 *
	 * @return Customer|false
	 */
	public static function get_session_customer() {

		global $woocommerce;

		if ( is_user_logged_in() ) {
			return ig_es_get_logged_in_customer();
		}

		if ( ! self::session_tracking_enabled() ) {
			return false;
		}

		// Can't look up the customer in this situation.
		if ( ! isset( $woocommerce->session ) ) {
			return '';
		}

		$tracking_key = $woocommerce->session->get_customer_id();

		// uses the newly set key if it exists and can be set
		if ( $tracking_key ) {
			$guest = IG_ES_Guest_Factory::get_by_key( $tracking_key );
			if ( $guest instanceof IG_ES_Guest ) {
				$customer = new IG_ES_Customer();
				$customer->set_prop( 'guest_id', $guest->get_id() );
				$customer->exists = true;
				return $customer;
			}
		}

		return false;
	}

	/**
	 * Check if we can track user session
	 */
	public static function session_tracking_enabled() {

		if ( isset( $_COOKIE['ig_es_session_tracking_disabled'] ) ) {
			return false;
		}

		return true;
	}

}
