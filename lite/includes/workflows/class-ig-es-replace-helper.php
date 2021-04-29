<?php

/**
 * Class to extract placeholder tags from given string
 * 
 * @class IG_ES_Replace_Helper
 */
class IG_ES_Replace_Helper {

	/** 
	 * Search patterns
	 * 
	 * @var array
	 */
	public $patterns = array(
		'variables' => array(
			'match' => 1,
			'expression' => '/{{(.*?)}}/'
		)
	);

	/** 
	 * Pattern to search
	 * 
	 * @var string 
	 */
	public $selected_pattern;

	/**
	 * Pattern string
	 *
	 * @var string
	 */
	public $string;

	/** 
	 * Callback for preg_replace_callback function 
	 * 
	 * @var callable */
	public $callback;


	/**
	 * Constructor
	 * 
	 * @param $string
	 * @param callable $callback
	 * @param string $pattern_name
	 */
	public function __construct( $string, $callback, $pattern_name = '' ) {

		$this->string = $string;
		$this->callback = $callback;

		if ( $pattern_name && isset( $this->patterns[$pattern_name] ) ) {
			$this->selected_pattern = $this->patterns[$pattern_name];
		}
	}


	/**
	 * Process passed string against selected regular expression
	 * 
	 * @return mixed
	 */
	public function process() {

		if ( ! $this->selected_pattern ) {
			return false;
		}

		return preg_replace_callback( $this->selected_pattern['expression'], array( $this, 'callback' ) , $this->string );
	}


	/**
	 * Pre process match before using the actual callback
	 *
	 * @param $match
	 * @return string
	 */
	public function callback( $match ) {
		if ( is_array( $match ) ) {
			$match = $match[ $this->selected_pattern['match'] ];
		}
		return call_user_func( $this->callback, $match );
	}

}
