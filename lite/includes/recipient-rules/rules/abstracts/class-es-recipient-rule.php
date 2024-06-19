<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ES_Recipient_Rule' ) ) {

	/**
	 * Base class for ES recipient rules
	 */
	abstract class ES_Recipient_Rule {

		/**
		 * Name of the rule
		 *
		 * @var string
		 */
		protected $name;

		/**
		 * Title of the rule
		 *
		 * @var string
		 */
		protected $title;

		/**
		 * Group that rules belongs to
		 *
		 * @var string
		 */
		protected $group;

		/**
		 * Variable to hold possible operators
		 *
		 * @var $operators
		 */
		protected $operators;

		/**
		 * Variable to hold operator
		 *
		 * @var $operator
		 */
		protected $operator;

		/**
		 * Variable to hold value
		 *
		 * @var $value
		 */
		protected $value;

		/**
		 * Variable to hold allowed_values
		 *
		 * @var $allowed_values
		 */
		protected $allowed_values;

		protected $value_field_type;

		/**
		 * Constructor
		 *
		 * @param  array $props props.
		 */
		public function __construct( $rule_name ) {
			$this->set_rule_details();
		}

		abstract public function set_rule_details();

		public function get_name() {
			return $this->name;
		}

		public function set_name( $name ) {
			$this->name = $name;
		}

		public function get_title() {
			return $this->title;
		}

		public function set_title( $title ) {
			$this->title = $title;
		}

		/**
		 * Function to get rule group
		 *
		 * @return string
		 */
		public function get_group() {
			return $this->group;
		}

		public function set_group( $group ) {
			$this->group = $group;
		}

		/**
		 * Function to get possible values
		 *
		 * @return $allowed_values mixed
		 */
		public function get_allowed_values() {
			return $this->allowed_values;
		}

		/**
		 * Function to set possible values
		 *
		 * @param array $v mixed.
		 */
		public function set_allowed_values( $v ) {
			$this->allowed_values = $v;
		}

		/**
		 * Function to get possible values
		 *
		 * @return $operators mixed
		 */
		public function set_operators( $operators ) {
			$this->operators = $operators;
		}

		/**
		 * Function to get possible values
		 *
		 * @return $operators mixed
		 */
		public function get_operators() {
			return $this->operators;
		}

		public function set_value_field_type( $type ) {
			$this->value_field_type = $type;
		}

		public function get_field_value_type() {
			return $this->value_field_type;
		}
	}

}
