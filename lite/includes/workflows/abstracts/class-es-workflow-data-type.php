<?php
/**
 * Workflow Data Types.
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Abstract Class ES_Workflow_Data_Type
 *
 * @class ES_Workflow_Data_Type
 * @since 4.4.1
 */
abstract class ES_Workflow_Data_Type {

	/**
	 * Data type id
	 *
	 * @var string
	 */
	public $id;


	/**
	 * Returns id of data type
	 *
	 * @since 4.4.1
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Sets id of data type
	 *
	 * @since 4.4.1
	 * @param string $id ID.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}


	/**
	 * Validate given data item
	 *
	 * @since 4.4.1
	 * @param mixed $item Data item object.
	 * @return bool
	 */
	abstract public function validate( $item );


	/**
	 * Returns id of given data item object. Only validated $items should be passed to this method
	 *
	 * @since 4.4.1
	 * @param mixed $item Data item object.
	 * @return mixed
	 */
	abstract public function compress( $item );


	/**
	 * Return data item object from given id.
	 *
	 * @since 4.4.1
	 * @param string $compressed_item Data item object ID.
	 * @param array  $compressed_data_layer Data layer.
	 * @return mixed
	 */
	abstract public function decompress( $compressed_item, $compressed_data_layer );

}
