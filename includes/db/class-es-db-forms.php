<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Forms {

	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

	}

	public static function do_insert( $place_holders, $values ) {
		global $wpdb;

		$forms_table = IG_FORMS_TABLE;

		$query = "INSERT INTO {$forms_table} (`name`, `body`, `settings`, `styles`, `created_at`, `updated_at`, `deleted_at`, `af_id`) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql   = $wpdb->prepare( "$query ", $values );

		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}

	}

	public static function get_forms_id_name_map() {
		global $wpdb;

		$query = "SELECT id, name FROM {$wpdb->prefix}ig_forms WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') ";
		$forms = $wpdb->get_results($query, ARRAY_A);

		$id_name_map = array();
		if(count($forms) > 0) {
			foreach ( $forms as $form ) {
				$id_name_map[$form['id']] = $form['name'];
			}
		}

		return $id_name_map;
	}

	public static function add_form( $data ) {
		global $wpdb;
		$insert = $wpdb->insert( IG_FORMS_TABLE, $data );

		if ( $insert ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	public static function get_form_by_id( $id ) {
		global $wpdb;



		$query = "SELECT * FROM " . IG_FORMS_TABLE . " WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') AND id = {$id}";

		$form  = $wpdb->get_results( $query, ARRAY_A );
		if ( $form ) {
			$form = array_shift( $form );
		}

		return $form;

	}

	public static function get_form_by_af_id( $af_id ) {
		global $wpdb;

		$query = "SELECT * FROM " . IG_FORMS_TABLE . " WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') AND af_id = {$af_id}";
		$form  = $wpdb->get_results( $query, ARRAY_A );

		if ( $form ) {
			$form = array_shift( $form );
		}

		return $form;

	}

	public static function migrate_advanced_forms() {
		global $wpdb;

		$table           = sanitize_text_field( EMAIL_SUBSCRIBERS_ADVANCED_FORM );
		$is_table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;

		$lists_name_id_map = ES_DB_Lists::get_list_id_name_map( '', true );
		if ( $is_table_exists ) {
			$query = "SELECT * FROM " . EMAIL_SUBSCRIBERS_ADVANCED_FORM;
			$forms = $wpdb->get_results( $query, ARRAY_A );

			if ( count( $forms ) > 0 ) {
				$place_holders = $values = array();
				foreach ( $forms as $form ) {

					$es_af_id         = $form['es_af_id'];
					$es_af_title      = $form['es_af_title'];
					$es_af_desc       = $form['es_af_desc'];
					$es_af_name       = $form['es_af_name'];
					$es_af_name_mand  = $form['es_af_name_mand'];
					$es_af_email      = $form['es_af_email'];
					$es_af_email_mand = $form['es_af_email_mand'];
					$es_af_group      = $form['es_af_group'];
					$es_af_group_mand = $form['es_af_group_mand'];
					$es_af_group_list = $form['es_af_group_list'];

					$es_af_group_lists = explode( ',', $es_af_group_list );
					$list_ids          = array();
					if ( count( $es_af_group_lists ) > 0 ) {
						foreach ( $es_af_group_lists as $list ) {

							if ( ! isset( $lists_name_id_map[ $list ] ) ) {
								$list_id                    = ES_DB_Lists::add_list( $list );
								$lists_name_id_map[ $list ] = $list_id;
							}

							$list_ids[] = $lists_name_id_map[ $list ];
						}
					}

					$body = array(
						array(
							'type'   => 'text',
							'name'   => 'Name',
							'id'     => 'name',
							'params' => array(
								'label'    => 'Name',
								'show'     => ( $es_af_name === 'YES' ) ? true : false,
								'required' => ( $es_af_name_mand === 'YES' ) ? true : false
							),

							'position' => 1
						),

						array(
							'type'   => 'text',
							'name'   => 'Email',
							'id'     => 'email',
							'params' => array(
								'label'    => 'Email',
								'show'     => ( $es_af_email === 'YES' ) ? true : false,
								'required' => ( $es_af_email_mand === 'YES' ) ? true : false
							),

							'position' => 2
						),

						array(
							'type'   => 'checkbox',
							'name'   => 'Lists',
							'id'     => 'lists',
							'params' => array(
								'label'    => 'Lists',
								'show'     => ( $es_af_group === 'YES' ) ? true : false,
								'required' => ( $es_af_group_mand === 'YES' ) ? true : false,
								'values'   => $list_ids
							),

							'position' => 3
						),

						array(
							'type'   => 'submit',
							'name'   => 'submit',
							'id'     => 'submit',
							'params' => array(
								'label' => 'Submit',
								'show'  => true
							),

							'position' => 4
						),

					);

					$settings = array(
						'lists' => $list_ids,
						'desc'  => $es_af_desc
					);

					$data['name']       = $es_af_title;
					$data['body']       = maybe_serialize( $body );
					$data['settings']   = maybe_serialize( $settings );
					$data['styles']     = null;
					$data['created_at'] = ig_get_current_date_time();
					$data['updated_at'] = null;
					$data['deleted_at'] = null;
					$data['af_id']      = $es_af_id;

					array_push( $values, $data['name'], $data['body'], $data['settings'], $data['styles'], $data['created_at'], $data['updated_at'], $data['deleted_at'], $data['af_id'] );
					$place_holders[] = "( %s, %s, %s, %s, %s, %s, %s, %d )";

				}

				ES_DB_Forms::do_insert( $place_holders, $values );
			}
		}
	}


}