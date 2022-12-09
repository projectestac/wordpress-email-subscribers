<?php
/**
 * Select Abstract rule class.
 *
 * @since       5.4.15
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Es_Rule_Number_Abstract' ) ) {
	abstract class Es_Rule_Number_Abstract extends Es_Workflow_Rule {
		/**
		 * The rule type.
		 *
		 * @var string
		 */
		public $type = 'number';

		

		/**
		 * Init.
		 */
		public function init() {

				$this->compare_types = $this->get_numeric_select_compare_types();
						
		}


		/**
		 * Validate a number rule.
		 *
		 * @param string|array $actual Will be an array
		 * @param string $compare_type
		 * @param array|string $expected
		 *
		 * @return bool
		 */
		public function validate_number( $actual, $compare_type, $expected ) {

			$expected = (float) $expected;			
			$actual   = (float) $actual;			

			switch ( $compare_type ) {
				case 'exactly_equal_to':					
					return $actual === $expected;

				case 'less_than_or_equal_to':
					return $actual <= $expected;

				case 'more_than_or_equal_to':
					return $actual >= $expected;
			}

			return false;

		}


	
	}
}
