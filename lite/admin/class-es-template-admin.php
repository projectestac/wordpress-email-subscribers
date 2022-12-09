<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Template_Admin extends ES_Admin {

	public static $instance;

	public function __construct() {
		$this->init();
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

ES_Template_Admin::get_instance();
