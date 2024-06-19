<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Recipient_Rules' ) ) {

	/**
	 * Class ES_Recipient_Rules
	 *
	 * @since 5.6.0
	 */
	class ES_Recipient_Rules {
		private $rules = array();

		// class constructor
		public function __construct() {
			$this->init();
		}

		public function init() {
			$this->register_rules();
		}

		public function register_rules() {
			$this->rules = array(
				'list' => 'ES_Recipient_Rule_List',
			);
			$this->rules = apply_filters( 'ig_es_recipient_rules', $this->rules );
		}

		/**
		 * Function to get rules
		 *
		 * @return $rules mixed
		 */
		public function get_rules() {
			$rules           = $this->rules;
			$recipient_rules = array();
			foreach ( $rules as $rule_name => $rule_class ) {
				$rule_obj            = new $rule_class( $rule_name );
				$rule_title          = $rule_obj->get_title();
				$rule_operators      = $rule_obj->get_operators();
				$rule_group          = $rule_obj->get_group();
				$rule_allowed_values = $rule_obj->get_allowed_values();
				$value_field_type    = $rule_obj->get_field_value_type();
				if ( ! isset( $recipient_rules[ $rule_group ] ) ) {
					$recipient_rules[ $rule_group ] = array();
				}
				$recipient_rules[ $rule_group ][ $rule_name ] = array(
					'name'             => $rule_name,
					'title'            => $rule_title,
					'group'            => $rule_group,
					'operators'        => $rule_operators,
					'allowed_values'   => $rule_allowed_values,
					'value_field_type' => $value_field_type,
				);
				if ( 'number' === $value_field_type && $rule_obj instanceof ES_Recipient_Rule_Number ) {
					$recipient_rules[ $rule_group ][ $rule_name ]['min_value'] = $rule_obj->get_min_value();
					$recipient_rules[ $rule_group ][ $rule_name ]['max_value'] = $rule_obj->get_max_value();
				}
			}

			return $recipient_rules;
		}
	}
}
