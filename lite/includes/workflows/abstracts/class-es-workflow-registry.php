<?php
/**
 * Abstract class for workflow actions, triggers and data type related functions.
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Abstract class for workflow actions, triggers and data type related functions.
 *
 * @class ES_Registry
 * @since 4.4.1
 */
abstract class ES_Workflow_Registry {

	/**
	 * Registered include classes
	 *
	 * @since 4.4.1
	 * @var array
	 */
	protected static $includes;

	/**
	 * Loaded registered class objects
	 *
	 * @var array
	 */
	protected static $loaded = array();


	/**
	 *
	 * Implement this method in sub classes
	 *
	 * @since 4.4.1
	 * @return array
	 */
	public static function load_includes() {
		return array();
	}


	/**
	 * Optional method to implement
	 *
	 * @since 4.4.1
	 * @param string $name Class name.
	 * @param mixed  $object Class object.
	 */
	public static function after_loaded( $name, $object ) {}


	/**
	 * Initiate registered include classes.
	 *
	 * @since 4.4.1
	 * @return array
	 */
	public static function get_includes() {
		if ( ! isset( static::$includes ) ) {
			static::$includes = static::load_includes();
		}
		return static::$includes;
	}


	/**
	 * Get objects of all registered classes.
	 *
	 * @since 4.4.1
	 * @return mixed
	 */
	public static function get_all() {
		foreach ( static::get_includes() as $name => $path ) {
			static::load( $name );
		}
		return static::$loaded;
	}


	/**
	 * Get object of specific registered class.
	 *
	 * @since 4.4.1
	 * @param string $name Registered class name.
	 * @return bool|object
	 */
	public static function get( $name ) {
		if ( static::load( $name ) ) {
			return static::$loaded[ $name ];
		}
		return false;
	}


	/**
	 * Returns if class has been initiated or not
	 *
	 * @since 4.4.1
	 * @param string $name Registered class name.
	 * @return bool
	 */
	public static function is_loaded( $name ) {
		return isset( static::$loaded[ $name ] );
	}


	/**
	 * Load an object by name.
	 *
	 * Returns true if the object has been loaded.
	 *
	 * @since 4.4.1
	 * @param string $name Registered class name.
	 *
	 * @return bool
	 */
	public static function load( $name ) {
		if ( self::is_loaded( $name ) ) {
			return true;
		}

		$includes = static::get_includes();
		$object   = false;

		if ( empty( $includes[ $name ] ) ) {
			return false;
		}

		$include = $includes[ $name ];

		// Check if include is a file path or a class name
		// NOTE: the file include method should NOT be used! It is kept for compatibility.
		if ( strstr( $include, '.php' ) ) {
			if ( file_exists( $include ) ) {
				$object = include_once $include;
			}
		} else {
			// If include is not a file path, assume it's a class name.
			if ( class_exists( $include ) ) {
				$object = new $include();
			}
		}

		if ( ! is_object( $object ) ) {
			return false;
		}

		static::after_loaded( $name, $object );
		static::$loaded[ $name ] = $object;

		return true;
	}


	/**
	 * Clear all registry cached data.
	 *
	 * @since 4.4.1
	 */
	public static function reset() {
		static::$includes = null;
		static::$loaded   = array();
	}

}
