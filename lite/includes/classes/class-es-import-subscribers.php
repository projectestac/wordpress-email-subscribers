<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Import_Subscribers {

	private $api;
	/**
	 * ES_Import_Subscribers constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Method to hook ajax handler for import process
	 */
	public function init() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_ig_es_import_subscribers_upload_handler', array( &$this, 'ajax_import_subscribers_upload_handler' ) );
			add_action( 'wp_ajax_ig_es_get_import_data', array( &$this, 'ajax_get_import_data' ) );
			add_action( 'wp_ajax_ig_es_do_import', array( &$this, 'ajax_do_import' ) );
			add_action( 'wp_ajax_ig_es_mailchimp_verify_api_key', array( &$this, 'api_request' ) );
			add_action( 'wp_ajax_ig_es_mailchimp_lists', array( &$this, 'api_request' ) );
			add_action( 'wp_ajax_ig_es_mailchimp_import_list', array( &$this, 'api_request' ) );

		}
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

		$this->prepare_import_subscriber_form();
	}

	public function prepare_import_subscriber_form() {
		
		if ( is_multisite() && ! is_upload_space_available() ) {
			return;
		}

		$max_upload_size = $this->get_max_upload_size();
		$post_params     = array(
			'action'   => 'ig_es_import_subscribers_upload_handler',
			'security' => wp_create_nonce( 'ig-es-admin-ajax-nonce' ),
		);
		
		$upload_action_url = admin_url( 'admin-ajax.php' );
		$plupload_init = array(
			'browse_button'    => 'plupload-browse-button',
			'container'        => 'plupload-upload-ui',
			'drop_element'     => 'drag-drop-area',
			'file_data_name'   => 'async-upload',
			'url'              => $upload_action_url,
			'filters'          => array( 
				'max_file_size' => $max_upload_size . 'b',
				'mime_types'    => array( array( 'extensions' => 'csv' ) ),
			),
			'multipart_params' => $post_params,
		);

		$allowedtags = ig_es_allowed_html_tags_in_esc();
		?>
		<script type="text/javascript">
			let wpUploaderInit = <?php echo wp_json_encode( $plupload_init ); ?>;
		</script>
		<div class="tool-box">
			<div class="meta-box-sortables ui-sortable bg-white shadow-md mt-8 rounded-lg">
				<div class="es-import-option bg-gray-50 rounded-lg">
					<div class="mx-auto flex justify-center pt-2">
						<label class="inline-flex items-center cursor-pointer mr-3 h-22 w-48">
							<input type="radio" class="absolute w-0 h-0 opacity-0 es_mailer" name="es-import-subscribers" value="es-import-with-csv" checked />
							<div class="mt-4 px-3 py-1 border border-gray-200 rounded-lg shadow-md es-mailer-logo es-importer-logo h-18 bg-white">
								<div class="border-0 es-logo-wrapper">
									<svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
								</div>
								<p class="mb-2 text-sm inline-block font-medium text-gray-600">
									<?php echo esc_html__( 'Import CSV', 'email-subscribers' ); ?>
								</p>
							</div>
						</label>
						<label class="inline-flex items-center cursor-pointer w-56">
							<input type="radio" class="absolute w-0 h-0 opacity-0 es_mailer" name="es-import-subscribers" value="es-import-mailchimp-users" />
							<div class="mt-4 px-1 mx-4 border border-gray-200 rounded-lg shadow-md es-mailer-logo es-importer-logo bg-white">
								<div class="border-0 es-logo-wrapper">
									<svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
								</div>
								<p class="mb-2 text-sm inline-block font-medium text-gray-600">
									<?php echo esc_html__( 'Import from MailChimp', 'email-subscribers' ); ?>
								</p>
							</div>
						</label>
						<?php
							do_action( 'ig_es_subscriber_import_method_tab_heading' );
						?>
						
					</div>  
					<hr class="mx-10 border-gray-100 mt-6">    
				</div>
				<form class="ml-7 mr-4 text-left py-4 my-2 item-center" method="post" name="form_import_subscribers" id="form_import_subscribers" action="#" enctype="multipart/form-data">		
					<div class="es-import-step1 flex flex-row">
						<div class="w-5/6 flex flex-row es-import-with-csv es-import">
							<div class="es-import-processing flex w-1/4">
								<div class="ml-6 pt-6">
									<label for="select_csv">
										<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">
											<?php esc_html_e( 'Select CSV file', 'email-subscribers' ); ?>
										</span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug">
											<?php 
											/* translators: %s: Max upload size */
											echo sprintf( esc_html__( 'File size should be less than %s', 'email-subscribers' ), esc_html( size_format( $max_upload_size ) ) ); 
											?>
										</p>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug">
											<?php esc_html_e( 'Check CSV structure', 'email-subscribers' ); ?>
											<a class="font-medium" target="_blank" href="<?php echo esc_attr( plugin_dir_url( __FILE__ ) ) . '../../admin/partials/sample.csv'; ?>"><?php esc_html_e( 'from here', 'email-subscribers' ); ?></a>
										</p>
									</label>
								</div>
							</div>
							<div class="w-3/4 ml-12 xl:ml-32 my-6 mr-4">			
								<div class="es-import-step1-body">
									<div class="upload-method">
										<div id="media-upload-error"></div>
										<div id="plupload-upload-ui" class="hide-if-no-js">
											<div id="drag-drop-area">
												<div class="drag-drop-inside">
													<p class="drag-drop-info"><?php esc_html_e( 'Drop your CSV here', 'email-subscribers' ); ?></p>
													<p><?php echo esc_html_x( 'or', 'Uploader: Drop files here - or - Select Files', 'email-subscribers' ); ?></p>
													<p class="drag-drop-buttons"><input id="plupload-browse-button" type="button" value="<?php esc_attr_e( 'Select File', 'email-subscribers' ); ?>" class="button" /></p>
												</div>
											</div>
										</div>
									</div>
								</div>
								<p class="import-status pt-4 pb-1 text-base font-medium text-gray-600 tracking-wide hidden">&nbsp;</p>
								<div id="progress" class="progress hidden"><span class="bar" style="width:0%"><span></span></span></div>
							</div>
						</div>

						<div class="w-5/6 flex flex-row es-import-mailchimp-users es-import" style="display: none">
							<div class="es-import-processing flex w-1/4">
								<div class="ml-6 pt-6">
									<label for="select_mailchimp_users">
										<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">
											<?php esc_html_e( 'Enter your API Key', 'email-subscribers' ); ?>
										</span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug" id="apikey-info-text"><?php esc_html_e( 'You need your API key from Mailchimp to import your data.', 'email-subscribers' ); ?>
										</p>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug font-medium">
											 <a href="https://admin.mailchimp.com/account/api-key-popup/" onclick="window.open(this.href, 'email-subscribers', 'width=600,height=600');return false;"><?php esc_html_e( 'Click here to get it.', 'email-subscribers' ); ?>
											 </a>
										</p>
									</label>
								</div>
							</div>
							<div class="w-3/4 ml-12 xl:ml-32 my-6 mr-4">
								<div>
									<label><input name="apikey" type="text" id="api-key" class="form-input text-sm w-1/2" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" autofocus tabindex="1" placeholder="12345678901234567890123456789012-xx1" class=""></label>
								</div>	
								<p class="es-api-import-status pt-4 text-sm font-medium text-gray-600 tracking-wide hidden">&nbsp;</p>			
								<div class="clearfix clear mt-10 -mb-4 ">
									<button id="es_mailchimp_verify_api_key" class="ig-es-primary-button px-2 py-1" data-callback="verify_api_key">
										<?php echo esc_html_e('Next', 'email-subscribers'); ?>
											&nbsp;
										<svg style="display:none" class="es-import-loader mr-1 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
										  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
										</svg>
									</button>
								</div>
								
							</div>
						</div>

						<?php 
							do_action( 'ig_es_subscriber_import_method_tab_content' );
						?>


					</div>
					
					<div class="mailchimp_import_step_1 w-5/6" style="display: none">
						<div class="flex flex-row pt-6 pb-4 border-b border-gray-100">
							<div class="flex w-1/4">
								<div class="ml-6">
									<label for="select_mailchimp_list">
										<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">
											<?php echo esc_html_e('Select list', 'email-subscribers'); ?>
										</span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug" id="apikey-info-text"><?php esc_html_e( 'Select all the lists that you want to import from MailChimp', 'email-subscribers' ); ?>
										</p>
									</label>
								</div>
							</div>

							<div class="ml-6">
								<ul class="es_mailchimp_lists_and_status_input mailchimp-lists">
									<li class="hidden" data-error-counter="0">
										<input type="checkbox" name="lists[]" class="form-checkbox" value="" id="">

										<label for="">
											<i></i>

											<span class="mailchimp_list_name"></span>
											<span class="mailchimp_list_contact_fetch_count"></span>
										</label>
									</li>
								</ul>
							</div>
						</div>

						<div class="flex flex-row">
							<div class="flex w-1/4 pt-6">
								<div class="ml-6">
									<label for="select_mailchimp_list">
										<span class="block pr-4 text-sm font-medium text-gray-600 pb-1"><?php esc_html_e( 'Select Status', 'email-subscribers' ); ?></span><span class="chevron"></span>
										<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug" id="apikey-info-text"><?php esc_html_e( 'Select the status of the contacts that you want to import from MailChimp', 'email-subscribers' ); ?>
										</p>
									</label>
								</div>
							</div>
							<div class="ml-6">
								<div>
									<ul class="es_mailchimp_lists_and_status_input pt-6">
										<li>
											<input type="checkbox" name="options" class="form-checkbox" value="subscribed" checked id="import_subscribed">
											<label for="import_subscribed">
												<i></i>
												<span><?php esc_html_e( 'Import with status "subscribed"', 'email-subscribers' ); ?></span>
											</label>
										</li>

										<li>
											<input type="checkbox" name="options" class="form-checkbox" value="pending" id="import_pending">
											<label for="import_pending">
												<i></i>
												<span><?php esc_html_e( 'Import with status "pending"', 'email-subscribers' ); ?></span>
											</label>
										</li>

										<li>
											<input type="checkbox" name="options" class="form-checkbox" value="unsubscribed" id="import_unsubscribed">
											<label for="import_unsubscribed">
												<i></i>
												<span><?php esc_html_e( 'Import with status "unsubscribed"', 'email-subscribers' ); ?></span>
											</label>
										</li>

										<li>
											<input type="checkbox" name="options" class="form-checkbox" value="cleaned" id="import_cleaned">
											<label for="import_cleaned">
												<i></i>
												<span><?php esc_html_e( 'Import with status "cleaned"', 'email-subscribers' ); ?></span>
											</label>
										</li>
									</ul>
								</div>
								<div class="mt-10"> <span class="mailchimp_notice_nowindow_close text-sm font-medium text-yellow-600 tracking-wide"></span></div>
								<div class="clearfix clear mt-10">
									<button id="es_import_mailchimp_list_members" class="ig-es-primary-button px-2 py-1" data-callback="import_lists">
										<?php esc_html_e( 'Next', 'email-subscribers' ); ?> &nbsp;
										<svg style="display:none" class="es-list-import-loader mr-1 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
										  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
										</svg>
									</button>
								</div>
							</div>	
						</div>
						
					</div>
					
					<div class="step2 w-full overflow-auto mb-6 mr-4 mt-4 border-b border-gray-100">
						<h2 class="import-status text-base font-medium text-gray-600 tracking-wide"></h2>
						<div class="step2-body overflow-auto pb-4"></div>
						<p class="import-instruction text-base font-medium text-yellow-600 tracking-wide"></p>
						<div id="importing-progress" class="importing-progress hidden mb-4 mr-2 text-center"><span class="bar" style="width:0%"><p class="block import_percentage text-white font-medium text-sm"></p></span></div>
					</div>
					<div class="step2-status">
						<div class="step2-status flex flex-row border-b border-gray-100">
							<div class="flex w-1/4">
								<div class="ml-6 pt-6">
									<label for="import_contact_list_status"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Select status', 'email-subscribers' ); ?> </span>
									</label>
								</div>
							</div>
							<div class="w-3/4 mb-6 mr-4 mt-4">
								<select class="relative form-select shadow-sm border border-gray-400 sm:w-32 lg:w-48 ml-4" name="es_email_status" id="es_email_status">
									<?php 
									$statuses_dropdown 	= ES_Common::prepare_statuses_dropdown_options();
									echo wp_kses( $statuses_dropdown , $allowedtags );
									?>
								</select>
							</div>
						</div>
					</div>
					<div class="step2-list">
						<div class="step2-list flex flex-row border-b border-gray-100">
							<div class="flex w-1/4">
								<div class="ml-6 pt-6">
									<label for="tag-email-group"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Select list', 'email-subscribers' ); ?></span>
									</label>
								</div>
							</div>
							<div class="w-3/4 mb-6 mr-4 mt-4">
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
								<div class="ml-4">
									<select name="<?php echo esc_attr( $select_list_name ); ?>" id="list_id" class="relative shadow-sm border border-gray-400 sm:w-32 lg:w-48 <?php echo esc_attr( $select_list_class ); ?>" <?php echo esc_attr( $select_list_attr ); ?>>
										<?php 
										$lists_dropdown 	= ES_Common::prepare_list_dropdown_options();
										echo wp_kses( $lists_dropdown , $allowedtags );
										?>
									</select>
								</div>
							</div>
						</div>
						
					</div>
					<div class="wrapper-start-contacts-import" style="padding-top:10px;">
							<?php wp_nonce_field( 'import-contacts', 'import_contacts' ); ?>
							<input type="submit" name="submit" class="start-import cursor-pointer ig-es-primary-button px-4 py-2 ml-6 mr-2 my-4" value="<?php esc_html_e( 'Import', 'email-subscribers' ); ?>" />
					</div>
				</form>
			</div>
			<div class="import-progress">
			</div>
			<!-- <div id="progress" class="progress hidden"><span class="bar" style="width:0%"><span></span></span></div> -->
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

		$max_upload_size    = 5242880; // 5MB.
		$wp_max_upload_size = wp_max_upload_size();
		$max_upload_size    = min( $max_upload_size, $wp_max_upload_size );

		return apply_filters( 'ig_es_max_upload_size', $max_upload_size );
	}

	/**
	 * Ajax handler to insert import CSV data into temporary table.
	 * 
	 * @since 4.6.6
	 */
	public function ajax_import_subscribers_upload_handler() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$response = array(
			'success' => false,
		);

		global $wpdb;

		$memory_limit       = @ini_get( 'memory_limit' );
		$max_execution_time = @ini_get( 'max_execution_time' );

		@set_time_limit( 0 );

		if ( (int) $max_execution_time < 300 ) {
			@set_time_limit( 300 );
		}
		if ( (int) $memory_limit < 256 ) {
			// Add filter to increase memory limit
			add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

			wp_raise_memory_limit( 'ig_es' );

			// Remove the added filter function so that it won't be called again if wp_raise_memory_limit called later on.
			remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
		}

		$importing_from = '';
		if ( isset( $_FILES['async-upload'] ) ) {

			$importing_from = 'csv';
			if ( isset( $_FILES['async-upload']['tmp_name'] ) && is_uploaded_file( sanitize_text_field( $_FILES['async-upload']['tmp_name'] ) ) ) {
				$tmp_file  = sanitize_text_field( $_FILES['async-upload']['tmp_name'] );
				$raw_data  = file_get_contents( $tmp_file );
				$seperator = $this->get_delimiter( $tmp_file );

				$handle = fopen( $tmp_file, 'r' );
				// Get Headers.
				$headers = array_map( 'trim', fgetcsv( $handle, 0, $seperator ) );

				// Remove BOM characters from the first item.
				if ( isset( $headers[0] ) ) {
					$headers[0] = ig_es_remove_utf8_bom( $headers[0] );
				}

				$data_contain_headers = true;
				if ( ! empty( $headers ) ) {
					foreach ( $headers as $header ) {
						if ( ! empty( $header ) && is_email( $header ) ) {
							$data_contain_headers = false;
							break;
						}
					}
				}
				fclose( $handle );

				if ( ! $data_contain_headers ) {
					$headers = array();
				}

				if ( function_exists( 'mb_convert_encoding' ) ) {
					$raw_data = mb_convert_encoding( $raw_data, 'UTF-8', mb_detect_encoding( $raw_data, 'UTF-8, ISO-8859-1', true ) );
				}
			}
		} elseif ( isset( $_POST['selected_roles'] ) ) {
			$importing_from = 'wordpress_users';

			$roles       = ig_es_get_request_data( 'selected_roles' );

			$users = $wpdb->get_results(
				"SELECT u.user_email, IF(meta_role.meta_value = 'a:0:{}',NULL,meta_role.meta_value) AS '_role', meta_firstname.meta_value AS 'firstname', meta_lastname.meta_value AS 'lastname', u.display_name, u.user_nicename
				 FROM {$wpdb->users} AS u
				 LEFT JOIN {$wpdb->usermeta} AS meta_role ON meta_role.user_id = u.id AND meta_role.meta_key = '{$wpdb->prefix}capabilities'
				 LEFT JOIN {$wpdb->usermeta} AS meta_firstname ON meta_firstname.user_id = u.id AND meta_firstname.meta_key = 'first_name'
				 LEFT JOIN {$wpdb->usermeta} AS meta_lastname ON meta_lastname.user_id = u.id AND meta_lastname.meta_key = 'last_name'
				 WHERE meta_role.user_id IS NOT NULL"
			);

			$raw_data = '';
			$seperator = ';';
			$data_contain_headers = false;
			$headers = array(
				__( 'Email', 'email-subscribers' ),
				__( 'First Name', 'email-subscribers' ),
				__( 'Last Name', 'email-subscribers' ),
				__( 'Nick Name', 'email-subscribers' ),
				__( 'Display Name', 'email-subscribers' ),
			);

			foreach ( $users as $user ) {

				// User must have a role assigned.
				if ( ! $user->_role ) {
					continue;
				}

				// Role is set but not in the list
				if ( $user->_role && ! array_intersect( array_keys( unserialize( $user->_role ) ), $roles ) ) {
					continue;
				}

				$user_data = array();

				foreach ( $user as $key => $data ) {
					if ( '_role' === $key ) {
						continue;
					}

					if ( 'firstname' === $key && ! $data ) {
						$data = $user->display_name;
					}

					$user_data[] = $data;
				}

				$raw_data .= implode( ';', $user_data );
				$raw_data .= "\n";
			}
		}

		if ( empty( $raw_data ) ) {
			wp_send_json( $response );
		}
		
		$response = $this->insert_into_temp_table( $raw_data, $seperator, $data_contain_headers, $headers, '', $importing_from );
		$response['success']     = true;
		$response['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
		

		wp_send_json( $response );
	}

	/**
	 * Ajax handler to get import data from temporary table.
	 * 
	 * @since 4.6.6
	 */
	public function ajax_get_import_data() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$response = array(
			'success' => false,
		);

		global $wpdb;

		$identifier = '';
		if ( isset( $_POST['identifier'] ) ) {
			$identifier =  sanitize_text_field( $_POST['identifier'] );
		}

		if ( ! empty( $identifier ) ) {
			
			$response['identifier'] = $identifier;
			$response['data'] = get_option( 'ig_es_bulk_import' );
			// get first and last entry
			$entries = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
					(SELECT data FROM {$wpdb->prefix}ig_temp_import WHERE identifier = %s ORDER BY ID ASC LIMIT 1) AS first, (SELECT data FROM {$wpdb->prefix}ig_temp_import WHERE identifier = %s ORDER BY ID DESC LIMIT 1) AS last",
					$identifier,
					$identifier
				)
			);

			$first = unserialize( base64_decode( $entries->first ) );
			$last  = unserialize( base64_decode( $entries->last ) );

			$data         = str_getcsv( $first[0], $response['data']['separator'], '"' );
			$cols         = count( $data );
			$contactcount = $response['data']['lines'];
			$fields     = array(	
				'email'      => __( 'Email', 'email-subscribers' ),
				'first_name' => __( 'First Name', 'email-subscribers' ),
				'last_name'  => __( 'Last Name', 'email-subscribers' ),
				'first_last' => __( '(First Name) (Last Name)', 'email-subscribers' ),
				'last_first' => __( '(Last Name) (First Name)', 'email-subscribers' ),
			);
			if ( ! empty( $response['data']['importing_from'] ) && 'wordpress_users' !== $response['data']['importing_from']  ) {
				$fields['list_name'] = __( 'List Name', 'email-subscribers' );
				$fields['status'] = __( 'Status', 'email-subscribers' );
			}

			$html      = '<div class="flex flex-row mb-6">
			<div class="es-import-processing flex w-1/4">
			<div class="ml-6 mr-2 pt-6">
			<label for="select_csv">
			<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">'
			. esc_html__( 'Select columns for mapping', 'email-subscribers' ) .
			'</span>
			<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug">'
			. esc_html__( 'Define which column represents which field', 'email-subscribers' ) . '

			</p>

			</label>
			</div>
			</div>' ;
			$html      .= '<div class="w-3/4 mx-4 border-b border-gray-200 shadow rounded-lg"><table class="w-full bg-white rounded-lg shadow overflow-hidden ">';
			$html      .= '<thead><tr class="border-b border-gray-200 bg-gray-50 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider"><th class="pl-3 py-4" style="width:20px;">#</th>';
			$emailfield = false;
			$headers = array();
			if ( ! empty( $response['data']['headers'] ) ) {
				$headers = $response['data']['headers'];
			}
			for ( $i = 0; $i < $cols; $i++ ) {
				$is_email  = is_email( trim( $data[ $i ] ) );
				$select  = '<select class="form-select font-normal text-gray-600 h-8 shadow-sm" name="mapping_order[]">';
				$select .= '<option value="-1">' . esc_html__( 'Ignore column', 'email-subscribers' ) . '</option>';
				foreach ( $fields as $key => $value ) {
					$is_selected = false;
					if ( $is_email && 'email' === $key ) {
						$is_selected = true;
					} else if ( ! empty( $headers[ $i ] ) ) {
						if ( strip_tags( $headers[ $i ] ) === $fields[ $key ] ) {
							$is_selected = ( 'first_name' === $key ) || ( 'last_name'  === $key ) || ( 'list_name'  === $key && 'mailchimp-api' === $response['data']['importing_from'] ) || ( 'status'  === $key && 'mailchimp-api' === $response['data']['importing_from'] );
						}
					}
					$select     .= '<option value="' . $key . '" ' . ( $is_selected ? 'selected' : '' ) . '>' . $value . '</option>';
				}
				$select .= '</select>';
				$html   .= '<th class="pl-3 py-4 font-medium">' . $select . '</th>';
			}
			$html .= '</tr>';
			if ( ! empty( $headers ) ) {
				$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider rounded-md"><th></th>';
				foreach ( $headers as $header ) {
					$html .= '<th class="pl-3 py-3 font-medium">' . $header . '</th>';
				}
				$html .= '</tr>';
			}
			$html .= '</thead><tbody>';
			for ( $i = 0; $i < min( 3, $contactcount ); $i++ ) {
				$data  = str_getcsv(  ( $first[ $i ] ), $response['data']['separator'], '"' );
				$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 text-gray-500 tracking-wide"><td class="pl-3">' . number_format_i18n( $i + 1 ) . '</td>';
				foreach ( $data as $cell ) {
					if ( ! empty( $cell ) && is_email( $cell ) ) {
						$cell = sanitize_email( strtolower( $cell ) );
					}
					$html .= '<td class="pl-3 py-3" title="' . strip_tags( $cell ) . '">' . ( $cell ) . '</td>';
				}
				$html .= '<tr>';
			}
			if ( $contactcount > 3 ) {
				$hidden_contacts_count = $contactcount - 4;
				if ( $hidden_contacts_count > 0 ) {
					/* translators: %s: Hidden contacts count */
					$html .= '<tr class="alternate bg-gray-50 pl-3 py-3 border-b border-gray-200 text-gray-500"><td class="pl-2 py-3">&nbsp;</td><td colspan="' . ( $cols ) . '"><span class="description">&hellip;' . sprintf( esc_html__( '%s contacts are hidden', 'email-subscribers' ), number_format_i18n( $contactcount - 4 ) ) . '&hellip;</span></td>';
				}

				$data  = str_getcsv( array_pop( $last ), $response['data']['separator'], '"' );
				$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 text-gray-500 tracking-wider"><td class="pl-3 py-3">' . number_format_i18n( $contactcount ) . '</td>';
				foreach ( $data as $cell ) {
					$html .= '<td class="pl-3 py-3 " title="' . strip_tags( $cell ) . '">' . ( $cell ) . '</td>';
				}
				$html .= '<tr>';
			}
			$html .= '</tbody>';

			$html .= '</table>';
			$html .= '<input type="hidden" id="identifier" value="' . $identifier . '">';
			$html .= '</div></div>';

			$response['html']    = $html;
			$response['success'] =  true;
		}

		wp_send_json( $response );
	}

	/**
	 * Ajax handler to import subscirbers from temporary table
	 * 
	 * @since 4.6.6
	 */
	public function ajax_do_import() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		global $wpdb;

		$memory_limit       = @ini_get( 'memory_limit' );
		$max_execution_time = @ini_get( 'max_execution_time' );

		@set_time_limit( 0 );

		if ( (int) $max_execution_time < 300 ) {
			@set_time_limit( 300 );
		}

		if ( (int) $memory_limit < 256 ) {
			// Add filter to increase memory limit
			add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

			wp_raise_memory_limit( 'ig_es' );

			// Remove the added filter function so that it won't be called again if wp_raise_memory_limit called later on.
			remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
		}

		$return['success'] = false;

		$bulkdata = array();
		if ( isset( $_POST['options'] ) ) {
			$bulkdata = ig_es_get_data( $_POST, 'options', array() );
		}
		
		$bulkdata      		= wp_parse_args( $bulkdata, get_option( 'ig_es_bulk_import' ) );
		$erroremails   		= get_option( 'ig_es_bulk_import_errors', array() );
		$order         		= isset( $bulkdata['mapping_order'] ) ? $bulkdata['mapping_order']: array();
		$list_id       		= isset( $bulkdata['list_id'] ) ? $bulkdata['list_id']            : array();
		$parts_at_once 		= 10;
		$selected_status   	= $bulkdata['status'];
		$error_codes   = array(
			'invalid'   => __( 'Email address is invalid.', 'email-subscribers' ),
			'empty'     => __( 'Email address is empty.', 'email-subscribers' ),
			'duplicate' => __( 'Duplicate email in the CSV file. Only the first record imported.', 'email-subscribers' ),
		);

		if ( ! empty( $list_id ) && ! is_array( $list_id ) ) {
			$list_id = array( $list_id );
		}

		if ( isset( $_POST['id'] ) ) {

			$bulkdata['current'] = (int) sanitize_text_field( $_POST['id'] );
			$raw_list_data = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT data FROM {$wpdb->prefix}ig_temp_import 
					WHERE identifier = %s ORDER BY ID ASC LIMIT %d, %d",
					$bulkdata['identifier'],
					$bulkdata['current'] * $parts_at_once,
					$parts_at_once
				)
			);
			if ( $raw_list_data ) {

				$contacts_data     = array();
				$current_date_time = ig_get_current_date_time();
				$contact_emails    = array();
				$processed_emails  = array();
				$list_contact_data = array();
				$es_status_mapping = array(
					 __( 'Subscribed', 'email-subscribers' ) => 'subscribed',
					 __( 'Unubscribed', 'email-subscribers' ) => 'unsubscribed',
					 __( 'Unconfirmed', 'email-subscribers' ) => 'unconfirmed',
					 __( 'Hard Bounced', 'email-subscribers' ) => 'hard_bounced' ,

				);
				
					
				foreach ( $raw_list_data as $raw_list ) {
					$raw_list = unserialize( base64_decode( $raw_list ) );
					// each entry
					foreach ( $raw_list as $line ) {
						if ( ! trim( $line ) ) {
							$bulkdata['lines']--;
							continue;
						}
						$data       = str_getcsv( $line, $bulkdata['separator'], '"' );
						$cols_count = count( $data );
						$insert     = array();
						for ( $col = 0; $col < $cols_count; $col++ ) {
							$d = trim( $data[ $col ] );
							if ( ! isset( $order[ $col ] ) ) {
								continue;
							}
							switch ( $order[ $col ] ) {
								case 'first_last':
									$name = explode( ' ', $d );
									if ( ! empty( $name[0] ) ) {
										$insert['first_name'] = $name[0];
									}
									if ( ! empty( $name[1] ) ) {
										$insert['last_name']  = $name[1];
									}
									break;
								case 'last_first':
									$name = explode( ' ', $d );
									if ( ! empty( $name[1] ) ) {
										$insert['first_name'] = $name[1];
									}
									if ( ! empty( $name[0] ) ) {
										$insert['last_name']  = $name[0];
									}
									break;
								case '-1':
									// ignored column
									break;
								default:
									$insert[ $order[ $col ] ] = $d;
							}
						}
						
						if ( empty( $insert['email'] ) || ! is_email( $insert['email'] ) ) {
							$error_data = array();
							if ( empty( $insert['email'] ) ) {
								$error_data['error_code'] = 'empty';
							} else if ( ! is_email( $insert['email'] ) ) {
								$error_data['error_code'] = 'invalid';
								$error_data['email'] = $insert['email'];
							}
							if ( ! empty( $insert['first_name'] ) ) {
								$error_data['first_name'] = $insert['first_name'];
							}
							if ( ! empty( $insert['last_name'] ) ) {
								$error_data['last_name'] = $insert['last_name'];
							}
							$bulkdata['errors']++;
							$erroremails[] = $error_data;
							continue;
						}

						$email = sanitize_email( strtolower( $insert['email'] ) );

						if ( ! in_array( $email, $processed_emails, true ) ) {
							$first_name = isset( $insert['first_name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $insert['first_name'] ) ) ) : '';
							$last_name  = isset( $insert['last_name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $insert['last_name'] ) ) ) : '';
							$list_names  = isset( $insert['list_name'] ) ? sanitize_text_field( trim( $insert['list_name'] ) ) : '';

							if ( empty( $insert['list_name'] ) ) {
								$list_names_arr = ES()->lists_db->get_lists_by_id( $list_id );
								$list_names = implode( ',', array_column( $list_names_arr, 'name' ));
							}

							$status = 'unconfirmed';
							$list_names = array_map('trim', explode(',', $list_names));

							
							if ( isset( $insert['status'] ) ) {
								$map_status = strtolower( str_replace( ' ', '_', $insert['status'] ) );
							}
							
							if ( isset( $insert['status'] ) && in_array( $map_status, $es_status_mapping )  ) {
								$status = sanitize_text_field( trim( $map_status ) );
							} elseif ( ! empty( $selected_status ) ) {
								$status = $selected_status;
							} 

							if ( ! empty( $es_status_mapping[ $status ] ) ) {
								$status = $es_status_mapping[ $status ];
							}

							foreach ( $list_names as $key => $list_name ) {
								if ( ! empty( $list_name ) ) {
									$list_contact_data[$list_name][$status][] = $email;
								}
							}

							// If name empty, get the name from Email.
							if ( empty( $first_name ) && empty( $last_name ) ) {
								$name       = ES_Common::get_name_from_email( $email );
								$names      = ES_Common::prepare_first_name_last_name( $name );
								$first_name = sanitize_text_field( $names['first_name'] );
								$last_name  = sanitize_text_field( $names['last_name'] );
							}

							$guid = ES_Common::generate_guid();

							$contacts_data[$email]['first_name'] = $first_name;
							$contacts_data[$email]['last_name']  = $last_name;
							$contacts_data[$email]['email']      = $email;
							$contacts_data[$email]['source']     = 'import';
							$contacts_data[$email]['status']     = 'verified';
							$contacts_data[$email]['hash']       = $guid;
							$contacts_data[$email]['created_at'] = $current_date_time;

							$processed_emails[] = $email;
							$bulkdata['imported']++;
						} else {
							$error_data = array(
								'email'      => $email,
								'error_code' => 'duplicate',
							);
							if ( ! empty( $insert['first_name'] ) ) {
								$error_data['first_name'] = $insert['first_name'];
							}
							if ( ! empty( $insert['last_name'] ) ) {
								$error_data['last_name'] = $insert['last_name'];
							}
							$erroremails[] = $error_data;
							$bulkdata['errors']++;
						}
						$contact_emails[] = $email;
					}
				}
				
				if ( count( $contact_emails ) > 0 ) {

					$contact_emails = array_unique( $contact_emails );

					$existing_contacts_email_id_map = ES()->contacts_db->get_email_id_map( $processed_emails );
					$existing_contacts_count        = count( $existing_contacts_email_id_map );
					if ( ! empty( $existing_contacts_email_id_map ) ) {
						$contacts_data = array_diff_key( $contacts_data, $existing_contacts_email_id_map ); 
					}

					if ( ! empty( $contacts_data ) ) {
						ES()->contacts_db->bulk_insert( $contacts_data );
					}

					if ( ! empty( $list_contact_data ) ) {
						foreach ($list_contact_data as $list_name => $list_data ) {
							$list = ES()->lists_db->get_list_by_name( $list_name );
					
							if ( ! empty( $list ) ) {
								$list_id = $list['id'];
							} else {
								$list_id = ES()->lists_db->add_list( $list_name );

							}

							foreach ($list_data as $status => $contact_emails) {
								$contact_ids = ES()->contacts_db->get_contact_ids_by_emails( $contact_emails );
								if ( count( $contact_ids ) > 0 ) {
									ES()->lists_contacts_db->remove_contacts_from_lists( $contact_ids, $list_id );
									ES()->lists_contacts_db->do_import_contacts_into_list( $list_id, $contact_ids, $status, 1, $current_date_time );
								}
							}
						}
					} 
				}
			}

			$return['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
			$return['errors']      = isset( $bulkdata['errors'] ) ? $bulkdata['errors'] : 0;
			$return['imported']    = ( $bulkdata['imported'] );
			$return['total']       = ( $bulkdata['lines'] );
			$return['f_errors']    = number_format_i18n( $bulkdata['errors'] );
			$return['f_imported']  = number_format_i18n( $bulkdata['imported'] );
			$return['f_total']     = number_format_i18n( $bulkdata['lines'] );

			$return['html'] = '';

			if ( $bulkdata['imported'] + $bulkdata['errors'] >= $bulkdata['lines'] ) {
				/* translators: 1. Total imported contacts 2. Total contacts */
				$return['html'] .= '<p class="text-base text-gray-600 pt-2 pb-1.5">' . sprintf( esc_html__( '%1$s of %2$s contacts imported', 'email-subscribers' ), '<span class="font-medium">' . number_format_i18n( $bulkdata['imported'] ) . '</span>', '<span class="font-medium">' . number_format_i18n( $bulkdata['lines'] ) . '</span>' ) . '<p>';
				
				if ( $bulkdata['errors'] ) {
					$i      = 0;
					$table  = '<p class="text-sm text-gray-600 pt-2 pb-1.5">' . esc_html__( 'The following contacts were skipped', 'email-subscribers' ) . ':</p>';
					$table .= '<table class="w-full bg-white rounded-lg shadow overflow-hidden mt-1.5">';
					$table .= '<thead class="rounded-md"><tr class="border-b border-gray-200 bg-gray-50 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider"><th class="pl-4 py-4" width="5%">#</th>';

					$first_name_column_choosen = in_array( 'first_name', $order, true );
					if ( $first_name_column_choosen ) {
						$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'First Name', 'email-subscribers' ) . '</th>';
					}

					$last_name_column_choosen = in_array( 'last_name', $order, true );
					if ( $last_name_column_choosen ) {
						$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'Last Name', 'email-subscribers' ) . '</th>';
					}
					
					$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'Email', 'email-subscribers' ) . '</th>';
					$table .= '<th class="pl-3 pr-1 py-3 font-medium">' . esc_html__( 'Reason', 'email-subscribers' ) . '</th>';
					$table .= '</tr></thead><tbody>';
					foreach ( $erroremails as $error_data ) {
						$table .= '<tr class="border-b border-gray-200 text-left leading-4 text-gray-800 tracking-wide">';
						$table .= '<td class="pl-4">' . ( ++$i ) . '</td>';
						$email  = ! empty( $error_data['email'] ) ? $error_data['email'] : '-';
						if ( $first_name_column_choosen ) {
							$first_name = ! empty( $error_data['first_name'] ) ? $error_data['first_name'] : '-';
							$table .= '<td class="pl-3 py-3">' . esc_html( $first_name ) . '</td>';
						}
						if ( $last_name_column_choosen ) {
							$last_name = ! empty( $error_data['last_name'] ) ? $error_data['last_name'] : '-';
							$table .= '<td class="pl-3 py-3">' . esc_html( $last_name ) . '</td>';
						}
						$error_code = ! empty( $error_data['error_code'] ) ? $error_data['error_code'] : '-';
						$reason     = ! empty( $error_codes[$error_code] ) ? $error_codes[$error_code] : '-';
						$table .= '<td class="pl-3 py-3">' . esc_html( $email ) . '</td><td class="pl-3 py-3">' . esc_html( $reason ) . '</td></tr>';
					}
					$table          .= '</tbody></table>';
					$return['html'] .= $table;
				}
				$this->do_cleanup();
			} else {
				update_option( 'ig_es_bulk_import', $bulkdata );
				update_option( 'ig_es_bulk_import_errors', $erroremails );
			}
			$return['success'] = true;
		}

		wp_send_json( $return );
	}

	/**
	 * Method to create temporary table if not already exists
	 * 
	 * @since 4.6.6
	 */
	public function maybe_create_temporary_import_table() {

		global $wpdb;
		
		require_once  ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$charset_collate    = $wpdb->get_charset_collate();
		$create_table_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ig_temp_import (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			data longtext NOT NULL,
			identifier char(13) NOT NULL,
			PRIMARY KEY (ID)
		) $charset_collate";

		dbDelta( $create_table_query );
	}

	/**
	 * Method to truncate table and options used during import process
	 * 
	 * @since 4.6.6
	 */
	public function do_cleanup( $identifier = '' ) {

		global $wpdb;
		if ( empty( $identifier ) ) {
			// Delete options used during import.
			delete_option( 'ig_es_bulk_import' );
			delete_option( 'ig_es_bulk_import_errors' );

			// We are trancating table so that primary key is reset to 1 otherwise ID column's value will increase on every insert and at some point ID column's data type may not be able to accomodate its value resulting in insert to fail. 
			$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}ig_temp_import" );
		}

		
	}

	public function api() {

		$mailchimp_apikey = ig_es_get_request_data('mailchimp_api_key');
		if ( ! $this->api ) {
			$this->api = new ES_Mailchimp_API( $mailchimp_apikey );
		}

		return $this->api;
	}

	public function api_request() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );
		$endpoint = str_replace( 'wp_ajax_ig_es_mailchimp_', '', current_filter() );

		switch ( $endpoint ) {
			case 'lists':
				$lists = $this->api()->lists();
				wp_send_json_success(
					array(
						'lists' => $lists,
					)
				);
				break;
			case 'import_list':
				$limit = ig_es_get_request_data('limit');
				$offset = ig_es_get_request_data('offset');
				$contact_status = ig_es_get_request_data('status');
				$import_identifier = ig_es_get_request_data('identifier');
				
				if ( ! isset( $_POST['id'] ) ) {
					wp_send_json_error(
						array(
							'message' => 'no list',
						)
					);
				}

				$limit  	= isset( $limit ) ? (int) $limit : 1000;
				$offset 	= isset( $offset ) ? (int) $offset : 0;
				$status 	= isset( $contact_status) ? (array) $contact_status : array( 'subscribed' );
				$identifier = isset( $import_identifier ) ? $import_identifier : '';
				$list_id 	= ig_es_get_request_data( 'id' );

				$subscribers = $this->api()->members(
					$list_id,
					array(
						'count'  => $limit,
						'offset' => $offset,
						'status' => $status,
					)
				);

				$list_name = ig_es_get_request_data('list_name');

				$importing_from = 'mailchimp-api';
				$raw_data = '';
				$seperator = ';';
				$data_contain_headers = false;
				$headers = array(
					__( 'Email', 'email-subscribers' ),
					__( 'First Name', 'email-subscribers' ),
					__( 'Last Name', 'email-subscribers' ),
					__( 'Status', 'email-subscribers' ),
					__( 'List Name', 'email-subscribers' ),

				);

				$raw_data = '';
				$es_mailchimp_status_mapping = array(
					'subscribed'	=> __( 'Subscribed', 'email-subscribers' ),
					'unsubscribed' 	=> __( 'Unsubscribed', 'email-subscribers' ),
					'pending'		=> __( 'Unconfirmed', 'email-subscribers' ),
					'cleaned' 		=> __( 'Hard Bounced', 'email-subscribers' ),

				);
				foreach ( $subscribers as $subscriber ) {
					if ( ! $subscriber->email_address ) {
						continue;
					}
					$user_data = array();

					$list_name = ! empty( $list_name ) ? $list_name : 'Test';
					$status = ! empty( $subscriber->status ) ? $subscriber->status : 'subscribed';
					if ( ! empty( $es_mailchimp_status_mapping[ $status ] ) ) {
						$status = $es_mailchimp_status_mapping[ $status ];
					}
					$user_data = array(
						$subscriber->email_address,
						$subscriber->merge_fields->FNAME,
						$subscriber->merge_fields->LNAME,
						$status,
						$list_name,
					);
					$raw_data .= implode( $seperator, $user_data );
					$raw_data .= "\n";

				}

				$response = array();

				if ( ! empty( $raw_data ) ) {
					$result = $this->insert_into_temp_table( $raw_data, $seperator, $data_contain_headers, $headers, $identifier, 'mailchimp-api'  );	
					$identifier = $result['identifier'];
				}
				$response = array(
					'total' => $this->api()->get_total_items(),
					'added' => count( $subscribers ),
					'subscribers' => count( $subscribers ),
					'identifier' => $identifier,
				);
				
				wp_send_json_success( $response );
				break;
			case 'verify_api_key':
				$result = $this->api()->ping();
				if ( $result ) {
					wp_send_json_success(
						array(
							'message' => $result->health_status,
						)
					);
				}

				break;
		}

		wp_send_json_error();

	}

	public function insert_into_temp_table( $raw_data, $seperator = ',', $data_contain_headers = false, $headers = array(), $identifier = '', $importing_from = 'csv' ) {
		global $wpdb;
		$raw_data = ( trim( str_replace( array( "\r", "\r\n", "\n\n" ), "\n", $raw_data ) ) );


		if ( function_exists( 'mb_convert_encoding' ) ) {
			$encoding = mb_detect_encoding( $raw_data, 'auto' );
		} else {
			$encoding = 'UTF-8';
		}
		
		$lines = explode( "\n", $raw_data );

		if ( $data_contain_headers ) {
			array_shift( $lines );
		}
		
		$lines_count = count( $lines );
		
		$batch_size = min( 500, max( 200, round( count( $lines ) / 200 ) ) ); // Each entry in temporary import table will have this much of subscribers data
		$parts      = array_chunk( $lines, $batch_size );
		$partcount  = count( $parts );

		$this->do_cleanup( $identifier );

		$identifier = empty( $identifier ) ? uniqid() : $identifier;
		$response['identifier'] = $identifier;

		for ( $i = 0; $i < $partcount; $i++ ) {

			$part = $parts[ $i ];
		
			$new_value = base64_encode( serialize( $part ) );

			$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}ig_temp_import (data, identifier) VALUES (%s, %s)", $new_value, $identifier ) );
		}

		$bulk_import_data = get_option( 'ig_es_bulk_import', array() );
		if ( ! empty( $bulk_import_data ) ) {
			$partcount += $bulk_import_data['parts'];
			$lines_count += $bulk_import_data['lines'];
		}
		
		$bulkimport = array(
			'imported'        => 0,
			'errors'          => 0,
			'encoding'        => $encoding,
			'parts'           => $partcount,
			'lines'           => $lines_count,
			'separator'       => $seperator,
			'importing_from'  => $importing_from,
			'data_contain_headers'  => $data_contain_headers,
			'headers'  		=> $headers,
		);

		$response['success']     = true;
		$response['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
		update_option( 'ig_es_bulk_import', $bulkimport, 'no' );

		return $response;
	}
}
