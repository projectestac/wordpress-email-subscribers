<?php

/**
 * Process variables into values. Is used on workflows and action options.
 *
 * @class Variable_Processor
 */
class IG_ES_Variables_Processor {

	/** 
	 * Workflow object
	 * 
	 * @var Workflow
	 */
	public $workflow;


	/**
	 * Class constructor
	 * 
	 * @param $workflow
	 */
	public function __construct( $workflow ) {
		$this->workflow = $workflow;
	}


	/**
	 * Process field's value for placeholder tags
	 * 
	 * @param $text string
	 * @param bool $allow_html
	 * @return string
	 */
	public function process_field( $text, $allow_html = false ) {

		$replacer = new IG_ES_Replace_Helper( $text, array( $this, 'callback_process_field' ), 'variables' );
		$value = $replacer->process();

		if ( ! $allow_html ) {
			$value = html_entity_decode( wp_strip_all_tags( $value ) );
		}

		return $value;
	}


	/**
	 * Callback public function to process a variable string.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function callback_process_field( $string ) {

		$string = $this->sanitize( $string );

		if ( self::is_excluded( $string ) ) {
			return '{{' . $string . '}}';
		}
		
		$variable = self::parse_variable( $string );

		if ( ! $variable ) {
			return '';
		}

		$parameters = $variable->parameters;
		$value      = $this->get_variable_value( $variable->type, $variable->field, $parameters );
		$value      = (string) apply_filters( 'ig_es_variables_after_get_value', $value, $variable->type, $variable->field, $parameters, $this->workflow );

		if ( '' === $value ) {
			// backwards compatibility
			if ( isset( $parameters['default'] ) ) {
				$parameters['fallback'] = $parameters['default'];
			}

			// show default if set and no real value
			if ( isset( $parameters['fallback'] ) ) {
				$value = $parameters['fallback'];
			}
		}

		return $value;
	}


	/**
	 * Get related variable which handles parsing of placeholder string
	 * 
	 * @param $string
	 * @return IG_ES_Workflow_Variable_Parser|bool
	 */
	public static function parse_variable( $string ) {
		$variable = new IG_ES_Workflow_Variable_Parser();
		if ( $variable->parse( $string ) ) {
			return $variable;
		}
		return false;
	}

	/**
	 * Get the value of a variable.
	 *
	 * @param string $data_type
	 * @param string $data_field
	 * @param array $parameters
	 *
	 * @return string
	 */
	public function get_variable_value( $data_type, $data_field, $parameters = array() ) {

		// Short circuit filter for the variable value
		$short_circuit = (string) apply_filters( 'ig_es_text_variable_value', false, $data_type, $data_field );

		if ( $short_circuit ) {
			return $short_circuit;
		}

		$variable_name = "$data_type.$data_field";
		$variable      = IG_ES_Variables::get_variable( $variable_name );

		$value = '';

		if ( $variable instanceof IG_ES_Workflow_Variable && method_exists( $variable, 'get_value' ) ) {

			if ( in_array( $data_type, ES_Workflow_Data_Types::get_non_stored_data_types(), true ) ) {
				$value = $variable->get_value( $parameters, $this->workflow );
			} else {
				$data_item = $this->workflow->get_data_item( $variable->get_data_type() );
				if ( $data_item ) {
					$value = $variable->get_value( $data_item, $parameters, $this->workflow );
				}
			}
		}

		return (string) apply_filters( 'ig_es_get_variable_value', (string) $value, $this, $variable );
	}


	/**
	 * Based on sanitize_title()
	 *
	 * @param $string
	 * @return mixed|string
	 */
	public static function sanitize( $string ) {

		// remove style and script tags
		$string = wp_strip_all_tags( $string, true );
		$string = remove_accents( $string );

		// remove unicode white spaces
		$string = preg_replace( "#\x{00a0}#siu", ' ', $string );

		$string = trim($string);

		return $string;
	}


	/**
	 * Certain variables can be excluded from processing.
	 *
	 * @param string $variable
	 * @return bool
	 */
	public static function is_excluded( $variable ) {
		 
		$excluded = apply_filters('ig_es_variables_processor_excluded', array(
		   'EMAIL',
		   'NAME',
		   'FIRSTNAME',
		   'LASTNAME',
		   'LINK',
		   'SUBSCRIBE-LINK',
		   'UNSUBSCRIBE-LINK',
		   'TOTAL-CONTACTS',
		   'GROUP',
		   'LIST',
		   'SITENAME',
		   'SITEURL',
		   'SUBJECT',
		   'COUNT',
		   'DATE',
		));

		return in_array( $variable, $excluded );
	}

}

