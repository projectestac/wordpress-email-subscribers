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

if ( ! class_exists( 'Es_Rule_Select' ) ) {
	abstract class Es_Rule_Select extends Es_Workflow_Rule {
		/**
		 * The rule type.
		 *
		 * @var string
		 */
		public $type = 'select';

		/**
		 * Allow multiple selections?
		 *
		 * @var bool
		 */
		public $is_multi = false;

		/**
		 * Init rule.
		 */
		public function init() {
			if ( $this->is_multi ) {
				$this->compare_types = $this->get_multi_select_compare_types();
			} else {
				$this->compare_types = $this->get_is_or_not_compare_types();
			}
		}


		/**
		 * Validate a select rule.
		 *
		 * @param string|array $actual Will be an array when is_multi prop is true.
		 * @param string $compare_type
		 * @param array|string $expected
		 *
		 * @return bool
		 */
		public function validate_select( $actual, $compare_type, $expected ) {

			if ( $this->is_multi ) {

				// actual can be empty
				if ( ! $actual ) {
					$actual = [];
				}

				// expected must have a value
				if ( ! $expected ) {
					return false;
				}

				$actual   = (array) $actual;
				$expected = (array) $expected;

				switch ( $compare_type ) {
					case 'matches_all':
						return count( array_intersect( $expected, $actual ) ) === count( $expected );

					case 'matches_none':
						return count( array_intersect( $expected, $actual ) ) === 0;

					case 'matches_any':
						return count( array_intersect( $expected, $actual ) ) >= 1;
				}
			} else {

				// actual must be scalar, but expected could be multiple values
				if ( ! is_scalar( $actual ) ) {
					return false;
				}

				// TODO review above exclusions
				// phpcs:disable WordPress.PHP.StrictComparisons.LooseComparison
				// phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict

				if ( is_array( $expected ) ) {
					$is_equal = in_array( $actual, $expected );
				} else {
					$is_equal = $expected == $actual;
				}

				// phpcs:enable

				switch ( $compare_type ) {
					case 'is':
						return $is_equal;

					case 'is_not':
						return ! $is_equal;
				}
			}

			return false;
		}


		/**
		 * Validate select rule, but case insensitive.
		 *
		 * @param array|string $actual Will be an array when is_multi prop is true.
		 * @param string $compare_type
		 * @param array|string $expected
		 *
		 * @return bool
		 * @since 4.4.0
		 *
		 */
		public function validate_select_case_insensitive( $actual, $compare_type, $expected ) {
			if ( is_array( $actual ) ) {
				$actual = array_map( 'wc_strtolower', $actual );
			} else {
				$actual = strtolower( (string) $actual );
			}
			$expected = array_map( 'wc_strtolower', (array) $expected );

			return $this->validate_select( $actual, $compare_type, $expected );
		}
	}
}
