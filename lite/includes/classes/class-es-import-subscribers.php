<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Import_Subscribers {
	/**
	 * ES_Import_Subscribers constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
	}

	/**
	 * Import Contacts
	 *
	 * @since 4.0,0
	 *
	 * @modify 4.3.1
	 *
	 * @modfiy 4.4.4 Moved importing code section to maybe_start_import method.
	 */
	public function import_callback() {

		// Check if nonce value is not empty.
		if ( ! empty( $_POST['import_contacts'] ) ) {
			// Verify nonce value.
			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['import_contacts'] ), 'import-contacts' ) ) {
				$message = __( 'Sorry, you do not have permission to import contacts.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
				$submit = ig_es_get_data( $_POST, 'submit', '', true );
				if ( $submit ) {
		
					if ( isset( $_FILES['file'] ) ) {
		
						$max_upload_size = $this->get_max_upload_size();
						if ( isset( $_FILES['file']['tmp_name'] ) && is_uploaded_file( sanitize_text_field( $_FILES['file']['tmp_name'] ) ) ) {
		
							$tmp_file = sanitize_text_field( $_FILES['file']['tmp_name'] );
							$file     = isset( $_FILES['file']['name'] ) ? sanitize_text_field( $_FILES['file']['name'] ) : '';
		
							$ext = strtolower( substr( $file, strrpos( $file, '.' ), ( strlen( $file ) - strrpos( $file, '.' ) ) ) );
		
							if ( '.csv' == $ext ) {
								$file_size 		 = isset( $_FILES['file']['size'] ) ? sanitize_text_field( $_FILES['file']['size'] ) : '';
		
								// Check if CSV file size is less than or equal to max upload size.
								if ( $file_size <= $max_upload_size ) {
									if ( ! ini_get( 'auto_detect_line_endings' ) ) {
										ini_set( 'auto_detect_line_endings', '1' );
									}
		
									$statuses        = ES_Common::get_statuses_key_name_map();
									$es_email_status = ig_es_get_data( $_POST, 'es_email_status', '', true );
		
									$status = '';
									if ( in_array( $es_email_status, array_keys( $statuses ) ) ) {
										$status = $es_email_status;
									}
		
									if ( ! empty( $status ) ) {
		
										$lists   = ES()->lists_db->get_id_name_map();
										$list_id = ig_es_get_data( $_POST, 'list_id', '', true );

										if ( ! empty( $list_id ) && ! is_array( $list_id ) ) {
											$list_id = array( $list_id );
										}
		
										$invalid_list_ids = array();
										if ( ! empty( $list_id ) ) {
											$invalid_list_ids = array_diff( $list_id, array_keys( $lists ) );
	
											if ( ! empty( $invalid_list_ids ) ) {
												$list_id = array();
											}
										}
		
										if ( ! empty( $list_id ) ) {
		
											$delimiter = $this->get_delimiter( $tmp_file );
		
											$handle = fopen( $tmp_file, 'r' );
		
											// Get Headers.
											$headers = array_map( 'trim', fgetcsv( $handle, 0, $delimiter ) );
		
											// Remove BOM characters from the first item.
											if ( isset( $headers[0] ) ) {
												$headers[0] = ig_es_remove_utf8_bom( $headers[0] );
											}
		
											$existing_contacts_email_id_map = ES()->contacts_db->get_email_id_map();
		
											$existing_contacts = array();
											if ( count( $existing_contacts_email_id_map ) > 0 ) {
												$existing_contacts = array_keys( $existing_contacts_email_id_map );
												$existing_contacts = array_map( 'strtolower', $existing_contacts );
											}
		
											$invalid_emails_count 		= 0; 
											$imported_subscribers_count = 0;
											$existing_contacts_count 	= 0;
											$emails              	    = array();
		
											$values            = array();
											$place_holders     = array();
											$contacts_data     = array();
											$current_date_time = ig_get_current_date_time();
		
											$headers_column_count = count( $headers );
											$use_mb               = function_exists( 'mb_convert_encoding' );
											while ( ( $data = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
		
												$data              = array_map( 'trim', $data );
												$data_column_count = count( $data );
		
												// Verify if number of headers columns are equal to number of data columns.
												if ( $headers_column_count !== $data_column_count ) {
													$invalid_emails_count ++;
													continue;
												}
												
												foreach ( $data as $data_index => $data_value ) {
													$data[ $data_index ] = ig_es_covert_to_utf8_encoding( $data_value, $use_mb );
												}

												$data = array_combine( $headers, $data );
		
												$email = isset( $data['Email'] ) ? strtolower( sanitize_email( trim( $data['Email'] ) ) ) : '';
		
												if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
													$invalid_emails_count ++;
													continue;
												}
		
												if ( ! in_array( $email, $existing_contacts ) ) {
		
													// Convert emoji characters to equivalent HTML entities to avoid WordPress sanitization error in SQL query while bulk inserting contacts.
													$name       = isset( $data['Name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $data['Name'] ) ) ) : '';
													$first_name = isset( $data['First Name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $data['First Name'] ) ) ) : '';
													$last_name  = isset( $data['Last Name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $data['Last Name'] ) ) ) : '';
		
													// If we don't get the first_name & last_name, consider Name field.
													// If name empty, get the name from Email.
													if ( empty( $first_name ) && empty( $last_name ) ) {
		
														if ( empty( $name ) ) {
															$name = ES_Common::get_name_from_email( $email );
														}
		
														$names      = ES_Common::prepare_first_name_last_name( $name );
														$first_name = sanitize_text_field( $names['first_name'] );
														$last_name  = sanitize_text_field( $names['last_name'] );
													}
		
													$guid = ES_Common::generate_guid();
		
													$contacts_data[ $imported_subscribers_count ]['first_name'] = $first_name;
													$contacts_data[ $imported_subscribers_count ]['last_name']  = $last_name;
													$contacts_data[ $imported_subscribers_count ]['email']      = $email;
													$contacts_data[ $imported_subscribers_count ]['source']     = 'import';
													$contacts_data[ $imported_subscribers_count ]['status']     = 'verified';
													$contacts_data[ $imported_subscribers_count ]['hash']       = $guid;
													$contacts_data[ $imported_subscribers_count ]['created_at'] = $current_date_time;
		
													$existing_contacts[] = $email;
													
													$imported_subscribers_count ++;
												} else {
													$existing_contacts_count ++;
												}
												
												$emails[] = $email;
											}
		
											$message         = '';
											$response_status = 'error';
											
											if ( count( $emails ) > 0 ) {
		
												$response_status = 'success';
		
												ES()->contacts_db->bulk_insert( $contacts_data );
		
												$contact_ids = ES()->contacts_db->get_contact_ids_by_emails( $emails );
												if ( count( $contact_ids ) > 0 ) {
													ES()->lists_contacts_db->remove_contacts_from_lists( $contact_ids, $list_id );
													ES()->lists_contacts_db->do_import_contacts_into_list( $list_id, $contact_ids, $status, 1, $current_date_time );
												}
												/* translators: %s: Total imported contacts */
												$message = sprintf( __( '%d new contacts imported successfully!', 'email-subscribers' ), $imported_subscribers_count );
		
											}
		
											if ( $existing_contacts_count > 0 ) {
												$message .= ' ';
												/* translators: %s: Exisiting contacts count */
												$message .= sprintf( __( '%d contact(s) already exists.', 'email-subscribers' ), $existing_contacts_count );
											}
		
											if ( $invalid_emails_count > 0 ) {
												$message .= ' ';
												/* translators: %s: Invalid contacts count */
												$message .= sprintf( __( '%d contact(s) are invalid.', 'email-subscribers' ), $invalid_emails_count );
											}
		
											fclose( $handle );
											
											ES_Common::show_message( $message, $response_status );
		
										} else {
											$message = __( 'Error: Please select list', 'email-subscribers' );
											ES_Common::show_message( $message, 'error' );
										}
									} else {
										$message = __( 'Error: Please select status', 'email-subscribers' );
										ES_Common::show_message( $message, 'error' );
									}
								} else {
									/* translators: %s: Max upload file size */
									$message = sprintf( __( 'The file you are trying to upload is larger than %s. Please upload a smaller file.', 'email-subscribers' ), esc_html( size_format( $max_upload_size ) ) );
									ES_Common::show_message( $message, 'error' );
								}
							} else {
								$message = __( 'Error: Please upload only CSV file', 'email-subscribers' );
								ES_Common::show_message( $message, 'error' );
							}
						} else {
							if ( ! empty( $_FILES['file']['error'] ) ) {
								switch ( $_FILES['file']['error'] ) {
									case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini 
										/* translators: %s: Max upload file size */
										$message = sprintf( __( 'The file you are trying to upload is larger than %s. Please upload a smaller file.', 'email-subscribers' ), esc_html( size_format( $max_upload_size ) ) );
										break;
									default: // a default error, just in case!  :)
										$message = __( 'There was a problem with your upload.', 'email-subscribers' );
										break;
								}
							} else {
								$message = __( 'Error: Please upload file', 'email-subscribers' );
							}
		
							ES_Common::show_message( $message, 'error' );
						}
					} else {
						$message = __( 'Error: Please upload file', 'email-subscribers' );
						ES_Common::show_message( $message, 'error' );
					}
				}
			}
		}

		$this->prepare_import_subscriber_form();

	}

	public function prepare_import_subscriber_form() {
		$max_upload_size = $this->get_max_upload_size();
		$allowedtags 		= ig_es_allowed_html_tags_in_esc();
		?>

		<div class="tool-box">
			<div class="meta-box-sortables ui-sortable bg-white shadow-md mt-8 rounded-lg">
				<form class="ml-7 mr-4 text-left pt-4 mt-2 item-center" method="post" name="form_addemail" id="form_addemail" action="#" enctype="multipart/form-data">
					<table class="max-w-full form-table">
						<tbody>

							<tr class="border-b  border-gray-100">
								<th scope="row" class="w-3/12 pt-3 pb-8 text-left">
									<label for="tag-image"><span class="block ml-6 pr-4 text-sm font-medium text-gray-600 pb-1">
										<?php esc_html_e( 'Select CSV file', 'email-subscribers' ); ?>
										</span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 ml-6 leading-snug">
										<?php 
										/* translators: %s: Max upload size */
										echo sprintf( esc_html__( 'File size should be less than %s', 'email-subscribers' ), esc_html( size_format( $max_upload_size ) ) ); 
										?>
										</p>
										<p class="italic text-xs font-normal text-gray-400 mt-2 ml-6 leading-snug">
										<?php esc_html_e( 'Check CSV structure', 'email-subscribers' ); ?>
										<a class="font-medium" target="_blank" href="<?php echo esc_attr( plugin_dir_url( __FILE__ ) ) . '../../admin/partials/sample.csv'; ?>"><?php esc_html_e( 'from here', 'email-subscribers' ); ?></a></p></label>
									</th>
									<td class="w-9/12 pb-3 ">
										<input class="ml-12" type="file" name="file" id="file"/>
									</td>
								</tr>
								<tr class="border-b border-gray-100">
									<th scope="row" class="w-3/12 pt-3 pb-8 text-left">
										<label for="tag-email-status"><span class="block ml-6 pr-4 text-sm font-medium text-gray-600 pb-2">
											<?php esc_html_e( 'Select status', 'email-subscribers' ); ?> </span><p></p>
										</label>
									</th>
									<td class="w-9/12 pb-3">
										<select class="relative form-select shadow-sm border border-gray-400 sm:w-32 lg:w-48 ml-12" name="es_email_status" id="es_email_status">
											<?php 
											$statuses_dropdown 	= ES_Common::prepare_statuses_dropdown_options();
											echo wp_kses( $statuses_dropdown , $allowedtags );
											?>
										</select>
									</td>
								</tr>
								<tr class="border-b border-gray-100">
									<th scope="row" class="w-3/12 pt-3 pb-8 text-left">
										<label for="tag-email-group"><span class="block ml-6 pr-4 text-sm font-medium text-gray-600 pb-2">
											<?php esc_html_e( 'Select list', 'email-subscribers' ); ?>
										</label>
									</th>
									<td class="w-9/12 pb-3">
										<?php
											// Allow multiselect for lists field in the pro version by changing list field's class,name and adding multiple attribute.
										if ( ES()->is_pro() ) {
											$select_list_attr  = 'multiple="multiple"';
											$select_list_name  = 'list_id[]';
											$select_list_class = 'ig-es-form-multiselect';
										} else {
											$select_list_attr  = '';
											$select_list_name  = 'list_id';
											$select_list_class = 'form-select';
										}
										?>
										<div class="ml-12">
											<select name="<?php echo esc_attr( $select_list_name ); ?>" id="list_id" class="relative shadow-sm border border-gray-400 sm:w-32 lg:w-48 <?php echo esc_attr( $select_list_class ); ?>" <?php echo esc_attr( $select_list_attr ); ?>>
												<?php 
												$lists_dropdown 	= ES_Common::prepare_list_dropdown_options();
												echo wp_kses( $lists_dropdown , $allowedtags );
												?>
											</select>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
						<p style="padding-top:10px;">
							<?php wp_nonce_field( 'import-contacts', 'import_contacts' ); ?>
							<input type="submit" name="submit" class="cursor-pointer ig-es-primary-button px-4 py-2 ml-6 mr-2 my-4" value="<?php esc_html_e( 'Import', 'email-subscribers' ); ?>" />
						</p>
					</form>
				</div>

				<?php
	}

	/**
	 * Show import contacts
	 *
	 * @since 4.0.0
	 */
	public function import_subscribers_page() {

		$audience_tab_main_navigation = array();
		$active_tab                   = 'import';
		$audience_tab_main_navigation = apply_filters( 'ig_es_audience_tab_main_navigation', $active_tab, $audience_tab_main_navigation );

		?>

		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="flex">
					<div>
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
							<ol class="list-none p-0 inline-flex">
								<li class="flex items-center text-sm tracking-wide">
								<a class="hover:underline " href="admin.php?page=es_subscribers"><?php esc_html_e( 'Audience ', 'email-subscribers' ); ?></a>
								<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
								</li>
							</ol>
						</nav>
						<h2 class="-mt-1.5 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate">
						 <?php esc_html_e( 'Import Contacts', 'email-subscribers' ); ?>
						</h2>
					</div>

					<div class="mt-4 ml-2">
						<?php
						ES_Common::prepare_main_header_navigation( $audience_tab_main_navigation );
						?>
					</div>
				</div>
			</header>

		<div><hr class="wp-header-end"></div>
		<?php $this->import_callback(); ?>
	</div>

		<?php
	}

	/**
	 * Get CSV file delimiter
	 *
	 * @param $file
	 * @param int  $check_lines
	 *
	 * @return mixed
	 *
	 * @since 4.3.1
	 */
	public function get_delimiter( $file, $check_lines = 2 ) {

		$file = new SplFileObject( $file );

		$delimiters = array( ',', '\t', ';', '|', ':' );
		$results    = array();
		$i          = 0;
		while ( $file->valid() && $i <= $check_lines ) {
			$line = $file->fgets();
			foreach ( $delimiters as $delimiter ) {
				$regExp = '/[' . $delimiter . ']/';
				$fields = preg_split( $regExp, $line );
				if ( count( $fields ) > 1 ) {
					if ( ! empty( $results[ $delimiter ] ) ) {
						$results[ $delimiter ] ++;
					} else {
						$results[ $delimiter ] = 1;
					}
				}
			}
			$i ++;
		}

		if ( count( $results ) > 0 ) {

			$results = array_keys( $results, max( $results ) );

			return $results[0];
		}

		return ',';

	}

	/**
	 * Method to get max upload size
	 *
	 * @return int $max_upload_size
	 *
	 * @since 4.4.6
	 */
	public function get_max_upload_size() {

		$max_upload_size    = 2097152; // 2MB.
		$wp_max_upload_size = wp_max_upload_size();
		$max_upload_size    = min( $max_upload_size, $wp_max_upload_size );

		return apply_filters( 'ig_es_max_upload_size', $max_upload_size );
	}

}

