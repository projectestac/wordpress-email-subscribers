<?php
/**
 * Select Abstract rule class.
 *
 * @since       5.5.0
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Es_Rule_Preloaded_Select_Abstract' ) ) {
	abstract class Es_Rule_Preloaded_Select_Abstract extends Es_Rule_Select {
		/**
		 * Cached select options. Leave public for JSON.
		 *
		 * @var array
		 */
		public $select_choices;

		/**
		 * Load select choices for rule.
		 *
		 * @return array
		 */
		public function get_select_choices() {
			return [];
		}

		/**
		 * Get the select choices for the rule.
		 *
		 * Choices are cached in memory.
		 *
		 * @return array
		 */
		public function load_select_choices() {
			if ( ! isset( $this->select_choices ) ) {
				$this->select_choices = apply_filters( 'ig_es_rules_preloaded_select_choices', $this->get_select_choices(), $this );
			}

			return $this->select_choices;
		}
	}
}
