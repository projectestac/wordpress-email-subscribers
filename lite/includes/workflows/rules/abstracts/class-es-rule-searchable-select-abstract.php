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

if ( ! class_exists( 'Es_Rule_Searchable_Select_Abstract' ) ) {
	abstract class Es_Rule_Searchable_Select_Abstract extends Es_Rule_Select {
		/**
		 * The rule type.
		 *
		 * @var string
		 */
		public $type = 'object';

		/**
		 * The CSS class to use on the search field.
		 *
		 * @var string
		 */
		public $class = 'ig-es-json-search';

		/**
		 * The field placeholder.
		 *
		 * @var string
		 */
		public $placeholder;

		/**
		 * Get the ajax action to use for the AJAX search.
		 *
		 * @return string
		 */
		abstract public function get_search_ajax_action();

		/**
		 * Init.
		 */
		public function init() {
			parent::init();

			$this->placeholder = __( 'Search...', 'email-subscribers' );

			if ( ! $this->is_multi ) {
				$this->compare_types = $this->get_includes_or_not_compare_types();
			}
		}


		/**
		 * Override this method to alter how saved values are displayed.
		 *
		 * @param string $value
		 *
		 * @return string
		 */
		public function get_object_display_value( $value ) {
			return $value;
		}
	}
}
