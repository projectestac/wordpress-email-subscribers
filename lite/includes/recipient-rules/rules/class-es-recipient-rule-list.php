<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ES_Recipient_Rule_List' ) ) {

	class ES_Recipient_Rule_List extends ES_Recipient_Rule_Boolean {

		public function set_rule_details() {
			$this->set_name( 'list' );
			$this->set_title( __( 'List', 'email-subscribers' ) );
			$this->set_group( __( 'List', 'email-subscribers' ) );
			$lists = ES()->lists_db->get_list_id_name_map();
			$this->set_allowed_values( $lists );
			if ( ES()->is_pro() ) {
				$this->set_value_field_type( 'multi-select' );
			} else {
				$this->set_value_field_type( 'select' );
			}
		}
	}
}

