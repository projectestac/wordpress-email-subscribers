<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to parse a variable string into separate usable parts
 *
 * @class IG_ES_Workflow_Variable_Parser
 */
class IG_ES_Workflow_Variable_Parser {

	/** 
	 * Variable name
	 * e.g. wc_order.id, cart.link, user.first_name etc
	 *
	 * @var string
	 */
	public $name;

	/** 
	 * Variable data type
	 * e.g. wc_order, cart, user etc
	 * 
	 * @var string
	 */
	public $type;

	/**
	 * Variable field name
	 * e.g. id in wc_order.id, link in cart.link, first_name in user.first_name etc
	 * 
	 * @var string
	 */
	public $field;

	/**
	 * Extra parameters attributes passed in placeholder string
	 * * e.g. array( 'fallback' => 'test' ) in {{ comment.id | fallback: 'test' }}
	 * 
	 * @var array
	 */
	public $parameters;

	/** 
	 * Actual paramter string
	 * e.g. fallback: 'test' in {{ comment.id | fallback: 'test' }}
	 * 
	 * @var string */
	public $parameter_string;


	/**
	 * Returns true on successful parsing
	 *
	 * @param $variable_string
	 * @return bool
	 */
	public function parse( $variable_string ) {

		$matches = array();
		$parameters = array();

		// extract the variable name (first part) of the variable string, e.g. 'customer.email'
		preg_match('/([a-z._0-9])+/', $variable_string, $matches, PREG_OFFSET_CAPTURE );

		if ( ! $matches ) {
			return false;
		}

		$name = $matches[0][0];

		// the name must contain a period
		if ( ! strstr( $name, '.' ) ) {
			return false;
		}

		list( $type, $field ) = explode( '.', $name, 2 );

		$parameter_string = trim( substr( $variable_string, $matches[1][1] + 1 ) );
		$parameter_string = trim( ig_es_str_replace_first_match( $parameter_string, '|' ) ); // remove pipe

		$parameters_split = preg_split('/(,)(?=(?:[^\']|\'[^\']*\')*$)/', $parameter_string );

		foreach ( $parameters_split as $parameter ) {
			if ( ! strstr( $parameter, ':' ) ) {
				continue;
			}

			list( $key, $value ) = explode( ':', $parameter, 2 );

			$key = ES_Clean::string( $key );
			$value = ES_Clean::string( $this->unquote( $value ) );

			$parameters[ $key ] = $value;
		}

		$this->name = $name;
		$this->type = $type;
		$this->field = $field;
		$this->parameters = $parameters;
		$this->parameter_string = $parameter_string;

		return true;

	}


	/**
	 * Remove single quotes from start and end of a string
	 *
	 * @param $string
	 * @return string
	 */
	private function unquote( $string ) {
		return trim( trim( $string ), "'" );
	}
}
