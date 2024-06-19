<?php
/**
 * Class for Rule_Boolean
 *
 * @since       2.5.0
 * @version     1.0.0
 *
 * @package     affiliate-for-woocommerce/includes/commission_rules
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ES_Recipient_Rule_Boolean' ) ) {

	abstract class ES_Recipient_Rule_Boolean extends ES_Recipient_Rule {

		/**
		 * Constructor
		 *
		 * @param  array $props props.
		 */
		public function __construct( $rule_name ) {
			parent::__construct( $rule_name);
			$this->operators = array(
				array(
					'op' => 'is',
					'label' => __( 'is', 'email-subscribers' )
				),
				array(
					'op' => 'is_not',
					'label' => __( 'is not', 'email-subscribers' )
				),
			);
		}

	}
}

