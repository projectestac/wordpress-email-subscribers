<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Campaigns {


	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

	}

	/**
	 * Get columns and formats
	 *
	 * @since   2.1
	 */
	public static function get_columns() {
		return array(
			'id'               => '%d',
			'slug'             => '%s',
			'name'             => '%s',
			'type'             => '%s',
			'from_name'        => '%s',
			'from_email'       => '%s',
			'reply_to_name'    => '%s',
			'reply_to_email'   => '%s',
			'sequence_ids'     => '%s',
			'categories'       => '%s',
			'list_ids'         => '%s',
			'base_template_id' => '%d',
			'status'           => '%d',
			'created_at'       => '%s',
			'updated_at'       => '%s',
			'deleted_at'       => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since   2.1
	 */
	public static function get_column_defaults() {
		return array(
			'slug'             => null,
			'name'             => null,
			'type'             => null,
			'from_name'        => ES_Common::get_ig_option( 'from_name' ),
			'from_email'       => ES_Common::get_ig_option( 'from_email' ),
			'reply_to_name'    => ES_Common::get_ig_option( 'from_name' ),
			'reply_to_email'   => ES_Common::get_ig_option( 'from_email' ),
			'sequence_ids'     => '',
			'categories'       => '',
			'list_ids'         => '',
			'base_template_id' => 0,
			'status'           => 0,
			'created_at'       => ig_get_current_date_time(),
			'updated_at'       => null,
			'deleted_at'       => null
		);
	}

	public static function do_insert( $place_holders, $values ) {
		global $wpdb;


		$campiagns_table = IG_CAMPAIGNS_TABLE;
		$query           = "INSERT INTO {$campiagns_table} (`slug`, `name`, `type`, `from_name`, `from_email`, `reply_to_name`, `reply_to_email`, `sequence_ids`, `categories`, `list_ids`, `base_template_id`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES ";
		$query           .= implode( ', ', $place_holders );
		$sql             = $wpdb->prepare( "$query ", $values );

		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_templateid_by_campaign( $id ) {
		global $wpdb;

		$query = "SELECT base_template_id FROM " . IG_CAMPAIGNS_TABLE . " WHERE id = %d";

		$sql         = $wpdb->prepare( $query, array( $id ) );
		$template_id = $wpdb->get_var( $sql );

		return $template_id;
	}


	public static function save_campaign( $data, $id = null ) {
		global $wpdb;

		$column_formats  = self::get_columns();
		$column_defaults = self::get_column_defaults();
		$insert          = is_null( $id ) ? true : false;
		$prepared_data   = ES_DB::prepare_data( $data, $column_formats, $column_defaults, $insert );

		$campaigns_data = $prepared_data['data'];
		$column_formats = $prepared_data['column_formats'];

		if ( $insert ) {
			$result = $wpdb->insert( IG_CAMPAIGNS_TABLE, $campaigns_data, $column_formats );
			if ( $result ) {
				return $wpdb->insert_id;
			}
		} else {
			$campaigns_data['updated_at'] = ! empty( $campaigns_data['updated_at'] ) ? $campaigns_data['updated_at'] : ig_get_current_date_time();
			$result                       = $wpdb->update( IG_CAMPAIGNS_TABLE, $campaigns_data, array( 'id' => $id ), $column_formats );
		}

		return $result;
	}

	public static function get_campaign_type_by_id( $id ) {
		global $wpdb;

		$query = "SELECT type FROM " . IG_CAMPAIGNS_TABLE . " WHERE id = %d";

		$sql  = $wpdb->prepare( $query, array( $id ) );
		$type = $wpdb->get_var( $sql );

		return $type;
	}

	public static function migrate_post_notifications() {
		/**
		 * - Migrate post notifications from es_notification table
		 *
		 */
		global $wpdb;

		$campaigns_data = array();
		$template_ids   = array();

		$from_name        = ES_Common::get_ig_option( 'from_name' );
		$from_email       = ES_Common::get_ig_option( 'from_email' );
		$list_is_name_map = ES_DB_Lists::get_list_id_name_map( '', true );

		$query = "SELECT count(*) as total FROM " . EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE;
		$total = $wpdb->get_var( $query );

		if ( $total > 0 ) {
			$batch_size = IG_DEFAULT_BATCH_SIZE;

			$total_bataches = ( $total > IG_DEFAULT_BATCH_SIZE ) ? ceil( $total / $batch_size ) : 1;

			for ( $i = 0; $i < $total_bataches; $i ++ ) {
				$batch_start   = $i * $batch_size;
				$query         = "SELECT * FROM " . EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE . " LIMIT {$batch_start}, {$batch_size}";
				$notifications = $wpdb->get_results( $query, ARRAY_A );
				if ( count( $notifications ) > 0 ) {
					foreach ( $notifications as $key => $notification ) {

						$categories = ! empty( $notification['es_note_cat'] ) ? $notification['es_note_cat'] : '';
						if ( ! empty( $categories ) ) {
							$categories = explode( '--', $categories );
							$categories = array_filter( $categories,  function ( $category ) {
                                return trim( trim( $category, '##' ), '' );
                            });

							$categories = ES_Common::convert_categories_array_to_string( $categories );
						}

						$template_id = 0;
						if ( ! empty( $notification['es_note_templ'] ) ) {
							$template_id = $notification['es_note_templ'];

							if ( ! in_array( $template_id, $template_ids ) ) {
								$template_ids[] = $template_id;
							}
						}

						$campaigns_data[ $key ]['slug']             = $template_id; // We don't have slug at this moment. So, we will fetch template's slug and store it later
						$campaigns_data[ $key ]['name']             = $template_id; // We don't have name at this moment. So, we will fetch template's name and store it later
						$campaigns_data[ $key ]['type']             = IG_CAMPAIGN_TYPE_POST_NOTIFICATION;
						$campaigns_data[ $key ]['from_name']        = $from_name;
						$campaigns_data[ $key ]['from_email']       = $from_email;
						$campaigns_data[ $key ]['reply_to_name']    = $from_name; // We don't have this option avaialble. So, setting from_name as reply_to_name
						$campaigns_data[ $key ]['reply_to_email']   = $from_email; // We don't have this option available. So, setting from_email as reply_to_email
						$campaigns_data[ $key ]['sequence_ids']     = null;
						$campaigns_data[ $key ]['categories']       = $categories;
						$campaigns_data[ $key ]['list_ids']         = ( ! empty( $notification['es_note_group'] ) && ! empty( $list_is_name_map[ $notification['es_note_group'] ] ) ) ? $list_is_name_map[ $notification['es_note_group'] ] : 0;
						$campaigns_data[ $key ]['base_template_id'] = $template_id;
						$campaigns_data[ $key ]['status']           = ( ! empty( $notification['es_note_status'] ) && $notification['es_note_status'] === 'Disable' ) ? 0 : 1;
						$campaigns_data[ $key ]['created_at']       = ig_get_current_date_time();
						$campaigns_data[ $key ]['updated_at']       = null;
						$campaigns_data[ $key ]['deleted_at']       = null;

					}

					$templates_data = array();
					// Get Template Name & Slug
					if ( count( $template_ids ) > 0 ) {
						$template_ids_str = "'" . implode( "', '", $template_ids ) . "'";
						$query            = "SELECT ID, post_name, post_title FROM {$wpdb->prefix}posts WHERE id IN ({$template_ids_str})";
						$templates        = $wpdb->get_results( $query, ARRAY_A );
						foreach ( $templates as $template ) {
							$templates_data[ $template['ID'] ] = $template;
						}
					}

					//Do Batach Insert
					$values  = $place_holders = array();
					$columns = self::get_columns();
					unset( $columns['id'] );
					$fields = array_keys( $columns );
					foreach ( $campaigns_data as $campaign_data ) {
						$campaign_data['slug'] = ! empty( $templates_data[ $campaign_data['slug'] ] ) ? $templates_data[ $campaign_data['slug'] ]['post_name'] : '';
						$campaign_data['name'] = ! empty( $templates_data[ $campaign_data['name'] ] ) ? $templates_data[ $campaign_data['name'] ]['post_title'] : '';

						$campaign_data = wp_parse_args( $campaign_data, self::get_column_defaults() );

						$formats = array();
						foreach ( $columns as $column => $format ) {
							$values[]  = $campaign_data[ $column ];
							$formats[] = $format;
						}

						$place_holders[] = "( " . implode( ', ', $formats ) . " )";
					}

					ES_DB::do_insert( IG_CAMPAIGNS_TABLE, $fields, $place_holders, $values );
				}
			}
		}
	}

	public static function migrate_newsletters() {
		global $wpdb;

		$from_name  = ES_Common::get_ig_option( 'from_name' );
		$from_email = ES_Common::get_ig_option( 'from_email' );

		$query = "SELECT count(*) as total FROM " . EMAIL_SUBSCRIBERS_SENT_TABLE . " WHERE es_sent_source = 'Newsletter'";
		$total = $wpdb->get_var( $query );

		if ( $total > 0 ) {

			$list_is_name_map = ES_DB_Lists::get_list_id_name_map( '', true );
			$batch_size       = IG_DEFAULT_BATCH_SIZE;
			$total_bataches   = ceil( $total / $batch_size );

			$values  = $place_holders = array();
			$columns = self::get_columns();
			unset( $columns['id'] );
			$fields = array_keys( $columns );
			for ( $i = 0; $i <= $total_bataches; $i ++ ) {
				$batch_start = $i * $batch_size;

				$query       = "SELECT * FROM " . EMAIL_SUBSCRIBERS_SENT_TABLE . " WHERE es_sent_source = 'Newsletter' LIMIT {$batch_start}, {$batch_size}";
				$newsletters = $wpdb->get_results( $query, ARRAY_A );

				if ( count( $newsletters ) > 0 ) {
					$campaign_data = $values = $place_holders = array();
					foreach ( $newsletters as $key => $newsletter ) {
						$campaign_data['slug']           = sanitize_title( $newsletter['es_sent_subject'] );
						$campaign_data['name']           = $newsletter['es_sent_subject'];
						$campaign_data['type']           = IG_CAMPAIGN_TYPE_NEWSLETTER;
						$campaign_data['from_name']      = $from_name;
						$campaign_data['from_email']     = $from_email;
						$campaign_data['reply_to_name']  = $from_name; // We don't have this option avaialble. So, setting from_name as reply_to_name
						$campaign_data['reply_to_email'] = $from_email; // We don't have this option available. So, setting from_email as reply_to_email
						$campaign_data['list_ids']       = ( ! empty( $newsletter['es_note_group'] ) && ! empty( $list_is_name_map[ $newsletter['es_note_group'] ] ) ) ? $list_is_name_map[ $newsletter['es_note_group'] ] : 0;
						$campaign_data['status']         = 1;
						$campaign_data['created_at']     = $newsletter['es_sent_starttime'];

						$campaign_data = wp_parse_args( $campaign_data, self::get_column_defaults() );

						$formats = array();
						foreach ( $columns as $column => $format ) {
							$values[]  = $campaign_data[ $column ];
							$formats[] = $format;
						}

						$place_holders[] = "( " . implode( ', ', $formats ) . " )";
					}

					ES_DB::do_insert( IG_CAMPAIGNS_TABLE, $fields, $place_holders, $values );
				}
			}
		}

	}

}
