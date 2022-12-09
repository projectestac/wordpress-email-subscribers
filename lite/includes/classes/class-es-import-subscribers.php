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

			add_action( 'ig_es_remove_import_data', array( __CLASS__, 'remove_import_data' ) );
		}
		add_action( 'ig_es_after_bulk_contact_import', array( $this, 'handle_after_bulk_contact_import' ) );
		add_action( 'ig_es_new_contact_inserted', array( $this, 'handle_new_contact_inserted' ) );
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
			'action'         => 'ig_es_import_subscribers_upload_handler',
			'importing_from' => 'csv',
			'security'       => wp_create_nonce( 'ig-es-admin-ajax-nonce' ),
		);

		$upload_action_url = admin_url( 'admin-ajax.php' );
		$plupload_init     = array(
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
									<?php echo esc_html__( 'Import from CSV', 'email-subscribers' ); ?>
								</p>
							</div>
						</label>
						<label class="inline-flex items-center cursor-pointer w-56">
							<input type="radio" class="absolute w-0 h-0 opacity-0 es_mailer" name="es-import-subscribers" value="es-import-mailchimp-users" />
							<div class="mt-4 px-1 mx-4 border border-gray-200 rounded-lg shadow-md es-mailer-logo es-importer-logo bg-white">
								<div class="border-0 es-logo-wrapper">
									<img class="h-full w-24" src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/admin/images/mailchimp_logo.png' ); ?>" alt="Icegram.com" />
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
										<p class="mt-2 es_helper_text">
											<?php
											/* translators: %s: Max upload size */
											echo sprintf( esc_html__( 'File size should be less than %s', 'email-subscribers' ), esc_html( size_format( $max_upload_size ) ) );
											?>
										</p>
										<p class="mt-2 es_helper_text">
											<?php esc_html_e( 'Check CSV structure', 'email-subscribers' ); ?>
											<a class="font-medium hover:underline" target="_blank" href="<?php echo esc_attr( plugin_dir_url( __FILE__ ) ) . '../../admin/partials/sample.csv'; ?>"><?php esc_html_e( 'from here', 'email-subscribers' ); ?></a>
										</p>
										<p class="mt-4 es_helper_text">
											<a class="hover:underline text-sm font-medium" href="https://www.icegram.com/documentation/es-how-to-import-or-export-email-addresses/?utm_source=in_app&utm_medium=import_contacts&utm_campaign=es_doc" target="_blank">
											<?php esc_html_e( 'How to import contacts using CSV?', 'email-subscribers' ); ?>&rarr;
										</a>
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
										<?php echo esc_html__( 'Next', 'email-subscribers' ); ?>
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
											<?php echo esc_html_e( 'Select list', 'email-subscribers' ); ?>
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
					<div class="step2-status es-email-status-container">
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
									$statuses_dropdown = ES_Common::prepare_statuses_dropdown_options();
									echo wp_kses( $statuses_dropdown, $allowedtags );
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
										$lists_dropdown = ES_Common::prepare_list_dropdown_options();
										echo wp_kses( $lists_dropdown, $allowedtags );
										?>
									</select>
								</div>
							</div>
						</div>

					</div>
					<div class="step2-send-optin-emails hidden">
						<div class="step2-send-optin-emails flex flex-row border-b border-gray-100">
							<div class="flex w-1/4">
								<div class="ml-6 pt-6">
									<label for="import_contact_list_status"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Send Confirmation/Welcome emails for this import?', 'email-subscribers' ); ?> </span>
									</label>
								</div>
							</div>
							<div class="w-3/4 mb-6 mr-4 mt-4">
								<label for="send_optin_emails"
									   class="inline-flex items-center mt-4 mb-1 cursor-pointer">
									<span class="relative">
										<input id="send_optin_emails" type="checkbox" name="send_optin_emails"
											   value="yes" class="absolute w-0 h-0 mt-6 opacity-0 es-check-toggle ">
										<span class="es-mail-toggle-line"></span>
										<span class="es-mail-toggle-dot"></span>
									</span>
								</label>
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

		$importing_from = ig_es_get_request_data( 'importing_from' );
		if ( 'csv' === $importing_from && isset( $_FILES['async-upload'] ) ) {
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
				$phpmailer            = ES()->mailer->get_phpmailer();
				if ( ! empty( $headers ) ) {
					foreach ( $headers as $header ) {
						if ( ! empty( $header ) ) {
							// Convert special characters in the email domain name to ascii.
							if ( is_callable( array( $phpmailer, 'punyencodeAddress' ) ) ) {
								$header = $phpmailer->punyencodeAddress( $header );
							}
							if ( is_email( $header ) ) {
								$data_contain_headers = false;
								break;
							}
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
		} elseif ( 'wordpress_users' === $importing_from ) {
			$roles = ig_es_get_request_data( 'selected_roles' );

			$users = $wpdb->get_results(
				"SELECT u.user_email, IF(meta_role.meta_value = 'a:0:{}',NULL,meta_role.meta_value) AS '_role', meta_firstname.meta_value AS 'firstname', meta_lastname.meta_value AS 'lastname', u.display_name, u.user_nicename
				 FROM {$wpdb->users} AS u
				 LEFT JOIN {$wpdb->usermeta} AS meta_role ON meta_role.user_id = u.id AND meta_role.meta_key = '{$wpdb->prefix}capabilities'
				 LEFT JOIN {$wpdb->usermeta} AS meta_firstname ON meta_firstname.user_id = u.id AND meta_firstname.meta_key = 'first_name'
				 LEFT JOIN {$wpdb->usermeta} AS meta_lastname ON meta_lastname.user_id = u.id AND meta_lastname.meta_key = 'last_name'
				 WHERE meta_role.user_id IS NOT NULL"
			);

			if ( ! empty( $users ) ) {
				$raw_data             = '';
				$seperator            = ';';
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
					if ( ! empty( $roles ) && ! array_intersect( array_keys( unserialize( $user->_role ) ), $roles ) ) {
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
				$response['message'] = __( 'We can\'t find any matching users. Please update your preferences and try again.', 'email-subscribers' );
			}
		}

		if ( empty( $raw_data ) ) {
			wp_send_json( $response );
		}

		$response                = self::insert_into_temp_table( $raw_data, $seperator, $data_contain_headers, $headers, '', $importing_from );
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
			$identifier = sanitize_text_field( $_POST['identifier'] );
		}

		if ( ! empty( $identifier ) ) {

			$response['identifier'] = $identifier;
			$response['data']       = get_option( 'ig_es_bulk_import' );
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
			$fields       = array(
				'email'      => __( 'Email', 'email-subscribers' ),
				'first_name' => __( 'First Name', 'email-subscribers' ),
				'last_name'  => __( 'Last Name', 'email-subscribers' ),
				'first_last' => __( '(First Name) (Last Name)', 'email-subscribers' ),
				'last_first' => __( '(Last Name) (First Name)', 'email-subscribers' ),
				'created_at' => __( 'Subscribed at', 'email-subscribers' ),
			);
			if ( ! empty( $response['data']['importing_from'] ) && 'wordpress_users' !== $response['data']['importing_from'] ) {
				$fields['list_name'] = __( 'List Name', 'email-subscribers' );
				$fields['status']    = __( 'Status', 'email-subscribers' );
			}

			$fields = apply_filters( 'es_import_show_more_fields_for_mapping', $fields );

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
			</div>';
			$html     .= '<div class="w-3/4 mx-4 border-b border-gray-200 shadow rounded-lg"><table class="w-full bg-white rounded-lg shadow overflow-hidden ">';
			$html     .= '<thead><tr class="border-b border-gray-200 bg-gray-50 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider"><th class="pl-3 py-4" style="width:20px;">#</th>';
			$phpmailer = ES()->mailer->get_phpmailer();
			$headers   = array();
			if ( ! empty( $response['data']['headers'] ) ) {
				$headers = $response['data']['headers'];
			}
			for ( $i = 0; $i < $cols; $i++ ) {
				$col_data = trim( $data[ $i ] );
				// Convert special characters in the email domain name to ascii.
				if ( is_callable( array( $phpmailer, 'punyencodeAddress' ) ) ) {
					$col_data = $phpmailer->punyencodeAddress( $col_data );
				}
				$is_email = is_email( trim( $col_data ) );
				$select   = '<select class="form-select font-normal text-gray-600 h-8 shadow-sm" name="mapping_order[]">';
				$select  .= '<option value="-1">' . esc_html__( 'Ignore column', 'email-subscribers' ) . '</option>';
				foreach ( $fields as $key => $value ) {
					$is_selected = false;
					if ( $is_email && 'email' === $key ) {
						$is_selected = true;
					} elseif ( ! empty( $headers[ $i ] ) ) {
						if ( strip_tags( $headers[ $i ] ) === $fields[ $key ] ) {
							$is_selected = ( 'first_name' === $key ) || ( 'last_name' === $key ) || ( 'list_name' === $key && 'mailchimp-api' === $response['data']['importing_from'] ) || ( 'status' === $key && 'mailchimp-api' === $response['data']['importing_from'] );
						}
					}
					$select .= '<option value="' . $key . '" ' . ( $is_selected ? 'selected' : '' ) . '>' . $value . '</option>';
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
				$data  = str_getcsv( ( $first[ $i ] ), $response['data']['separator'], '"' );
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
			$response['success'] = true;
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

		$phpmailer = ES()->mailer->get_phpmailer();

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
			$bulkdata = ig_es_get_data( $_POST, 'options', array(), true );
		}

		$bulkdata                    = wp_parse_args( $bulkdata, get_option( 'ig_es_bulk_import' ) );
		$erroremails                 = get_option( 'ig_es_bulk_import_errors', array() );
		$order                       = isset( $bulkdata['mapping_order'] ) ? $bulkdata['mapping_order'] : array();
		$list_id                     = isset( $bulkdata['list_id'] ) ? $bulkdata['list_id'] : array();
		$parts_at_once               = 10;
		$selected_status             = $bulkdata['status'];
		$send_optin_emails           = isset( $bulkdata['send_optin_emails'] ) ? $bulkdata['send_optin_emails'] : 'no';
		$need_to_send_welcome_emails = ( 'yes' === $send_optin_emails );

		$error_codes = array(
			'invalid' => __( 'Email address is invalid.', 'email-subscribers' ),
			'empty'   => __( 'Email address is empty.', 'email-subscribers' ),
		);

		if ( ! empty( $list_id ) && ! is_array( $list_id ) ) {
			$list_id = array( $list_id );
		}

		if ( isset( $_POST['id'] ) ) {
			set_transient( 'ig_es_contact_import_is_running', 'yes' );
			$batch_id            = (int) sanitize_text_field( $_POST['id'] );
			$bulkdata['current'] = $batch_id;
			
			$raw_list_data       = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT data FROM {$wpdb->prefix}ig_temp_import 
					WHERE identifier = %s ORDER BY ID ASC LIMIT %d, %d",
					$bulkdata['identifier'],
					$bulkdata['current'] * $parts_at_once,
					$parts_at_once
				)
			);
			if ( $raw_list_data ) {

				$contacts_data        = array();
				$gmt_offset           = ig_es_get_gmt_offset( true );
				$current_date_time    = gmdate( 'Y-m-d H:i:s', time() - $gmt_offset );
				$current_batch_emails = array();
				$processed_emails     = ! empty( $bulkdata['processed_emails'] ) ? $bulkdata['processed_emails'] : array();
				$list_contact_data    = array();
				$es_status_mapping    = array(
					__( 'Subscribed', 'email-subscribers' )   => 'subscribed',
					__( 'Unsubscribed', 'email-subscribers' ) => 'unsubscribed',
					__( 'Unconfirmed', 'email-subscribers' )  => 'unconfirmed',
					__( 'Hard Bounced', 'email-subscribers' ) => 'hard_bounced',
				);

				$is_starting_import = 0 === $batch_id;
				if ( $is_starting_import ) {
					do_action( 'ig_es_before_bulk_contact_import' );
				}
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
								case 'email':
									$insert['email'] = $d;
									// Convert special characters in the email domain name to ascii.
									if ( is_callable( array( $phpmailer, 'punyencodeAddress' ) ) ) {
										$encoded_email = $phpmailer->punyencodeAddress( $insert['email'] );
										if ( ! empty( $encoded_email ) ) {
											$insert['email'] = $encoded_email;
										}
									}
									break;
								case 'first_last':
									$name = explode( ' ', $d );
									if ( ! empty( $name[0] ) ) {
										$insert['first_name'] = $name[0];
									}
									if ( ! empty( $name[1] ) ) {
										$insert['last_name'] = $name[1];
									}
									break;
								case 'last_first':
									$name = explode( ' ', $d );
									if ( ! empty( $name[1] ) ) {
										$insert['first_name'] = $name[1];
									}
									if ( ! empty( $name[0] ) ) {
										$insert['last_name'] = $name[0];
									}
									break;
								case 'created_at':
									if ( ! is_numeric( $d ) && ! empty( $d ) ) {
										$d                    = sanitize_text_field( $d );
										$insert['created_at'] = gmdate( 'Y-m-d H:i:s', strtotime( $d ) - $gmt_offset );
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
								$error_data['cd'] = 'empty';
							} elseif ( ! is_email( $insert['email'] ) ) {
								$error_data['cd']    = 'invalid';
								$error_data['email'] = $insert['email'];
							}
							if ( ! empty( $insert['first_name'] ) ) {
								$error_data['fn'] = $insert['first_name'];
							}
							if ( ! empty( $insert['last_name'] ) ) {
								$error_data['ln'] = $insert['last_name'];
							}
							$bulkdata['errors']++;
							$erroremails[] = $error_data;
							continue;
						}

						$email = sanitize_email( strtolower( $insert['email'] ) );
						if ( ! in_array( $email, $current_batch_emails, true ) && ! in_array( $email, $processed_emails, true ) ) {
							$first_name = isset( $insert['first_name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $insert['first_name'] ) ) ) : '';
							$last_name  = isset( $insert['last_name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $insert['last_name'] ) ) ) : '';
							$created_at = isset( $insert['created_at'] ) ? $insert['created_at'] : $current_date_time;

							$guid = ES_Common::generate_guid();

							$contact_data['first_name'] = $first_name;
							$contact_data['last_name']  = $last_name;
							$contact_data['email']      = $email;
							$contact_data['source']     = 'import';
							$contact_data['status']     = 'verified';
							$contact_data['hash']       = $guid;
							$contact_data['created_at'] = $created_at;

							$additional_contacts_data = apply_filters( 'es_prepare_additional_contacts_data_for_import', array(), $insert );

							$contacts_data[$email] = array_merge( $contact_data, $additional_contacts_data );
							$bulkdata['imported']++;
						} else {
							$bulkdata['duplicate_emails_count']++;
						}

						$list_names = isset( $insert['list_name'] ) ? sanitize_text_field( trim( $insert['list_name'] ) ) : '';
						if ( empty( $insert['list_name'] ) ) {
							$list_names_arr = ES()->lists_db->get_lists_by_id( $list_id );
							$list_names     = implode( ',', array_column( $list_names_arr, 'name' ) );
						}

						$status     = 'unconfirmed';
						$list_names = array_map( 'trim', explode( ',', $list_names ) );

						if ( isset( $insert['status'] ) ) {
							$map_status = strtolower( str_replace( ' ', '_', $insert['status'] ) );
						}

						if ( isset( $insert['status'] ) && in_array( $map_status, $es_status_mapping ) ) {
							$status = sanitize_text_field( trim( $map_status ) );
						} elseif ( ! empty( $selected_status ) ) {
							$status = $selected_status;
						}

						if ( ! empty( $es_status_mapping[ $status ] ) ) {
							$status = $es_status_mapping[ $status ];
						}

						foreach ( $list_names as $key => $list_name ) {
							if ( ! empty( $list_name ) ) {
								$list_contact_data[ $list_name ][ $status ][] = $email;
							}
						}

						$current_batch_emails[] = $email;
					}
				}

				if ( count( $current_batch_emails ) > 0 ) {

					$current_batch_emails = array_unique( $current_batch_emails );

					$existing_contacts_email_id_map = ES()->contacts_db->get_email_id_map( $current_batch_emails );
					if ( ! empty( $existing_contacts_email_id_map ) ) {
						$contacts_data = array_diff_key( $contacts_data, $existing_contacts_email_id_map );
					}

					if ( ! empty( $contacts_data ) ) {
						$insert_ids = ES()->contacts_db->bulk_insert( $contacts_data, 100, true );
						if ( ! empty( $insert_ids ) && $need_to_send_welcome_emails ) {
							$imported_contacts_transient = get_transient( 'ig_es_imported_contact_ids_range' );
							if ( ! empty( $imported_contacts_transient ) && is_array( $imported_contacts_transient ) && isset( $imported_contacts_transient['rows'] ) ) {
								$old_rows   = is_array( $imported_contacts_transient['rows'] ) ? $imported_contacts_transient['rows'] : array();
								$all_data   = array_merge( $old_rows, $insert_ids );
								$insert_ids = array( min( $all_data ), max( $all_data ) );
							}
							$imported_contact_details = array(
								'rows'  => $insert_ids,
								'lists' => $list_id
							);
							set_transient( 'ig_es_imported_contact_ids_range', $imported_contact_details );
						}
					}

					if ( ! empty( $list_contact_data ) ) {
						foreach ( $list_contact_data as $list_name => $list_data ) {
							$list = ES()->lists_db->get_list_by_name( $list_name );

							if ( ! empty( $list ) ) {
								$list_id = $list['id'];
							} else {
								$list_id = ES()->lists_db->add_list( $list_name );
							}

							foreach ( $list_data as $status => $contact_emails ) {
								$contact_id_date = ES()->contacts_db->get_contact_ids_created_at_date_by_emails( $contact_emails );
								$contact_ids     = array_keys( $contact_id_date );
								if ( count( $contact_ids ) > 0 ) {
									ES()->lists_contacts_db->remove_contacts_from_lists( $contact_ids, $list_id );
									ES()->lists_contacts_db->do_import_contacts_into_list( $list_id, $contact_id_date, $status, 1 );
								}
							}
						}
					}
				}
			}

			$return['memoryusage']            = size_format( memory_get_peak_usage( true ), 2 );
			$return['errors']                 = isset( $bulkdata['errors'] ) ? $bulkdata['errors'] : 0;
			$return['duplicate_emails_count'] = isset( $bulkdata['duplicate_emails_count'] ) ? $bulkdata['duplicate_emails_count'] : 0;
			$return['imported']               = ( $bulkdata['imported'] );
			$return['total']                  = ( $bulkdata['lines'] );
			$return['f_errors']               = number_format_i18n( $bulkdata['errors'] );
			$return['f_imported']             = number_format_i18n( $bulkdata['imported'] );
			$return['f_total']                = number_format_i18n( $bulkdata['lines'] );
			$return['f_duplicate_emails']     = number_format_i18n( $bulkdata['duplicate_emails_count'] );

			$return['html'] = '';

			if ( ( $bulkdata['imported'] + $bulkdata['errors'] + $bulkdata['duplicate_emails_count'] ) >= $bulkdata['lines'] ) {
				/* translators: 1. Total imported contacts 2. Total contacts */
				$return['html'] .= '<p class="text-base text-gray-600 pt-2 pb-1.5">' . sprintf( esc_html__( '%1$s of %2$s contacts imported.', 'email-subscribers' ), '<span class="font-medium">' . number_format_i18n( $bulkdata['imported'] ) . '</span>', '<span class="font-medium">' . number_format_i18n( $bulkdata['lines'] ) . '</span>' );

				if ( $bulkdata['duplicate_emails_count'] ) {
					$duplicate_email_string = _n( 'email', 'emails', $bulkdata['duplicate_emails_count'], 'email-subscribers' );
					/* translators: 1. Duplicate emails count. 2. Email or emails string based on duplicate email count. */
					$return['html'] .= sprintf( esc_html__( '%1$s duplicate %2$s found.', 'email-subscribers' ), '<span class="font-medium">' . number_format_i18n( $bulkdata['duplicate_emails_count'] ) . '</span>', $duplicate_email_string );
				}
				$return['html'] .= '</p>';
				if ( $bulkdata['errors'] ) {
					$i                      = 0;
					$skipped_contact_string = _n( 'contact was', 'contacts were', $bulkdata['errors'], 'email-subscribers' );

					/* translators: %d Skipped emails count %s Skipped contacts string */
					$table  = '<p class="text-sm text-gray-600 pt-2 pb-1.5">' . __( sprintf( 'The following %d %s skipped', $bulkdata['errors'], $skipped_contact_string ), 'email-subscribers' ) . ':</p>';
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
							$first_name = ! empty( $error_data['fn'] ) ? $error_data['fn'] : '-';
							$table     .= '<td class="pl-3 py-3">' . esc_html( $first_name ) . '</td>';
						}
						if ( $last_name_column_choosen ) {
							$last_name = ! empty( $error_data['ln'] ) ? $error_data['ln'] : '-';
							$table    .= '<td class="pl-3 py-3">' . esc_html( $last_name ) . '</td>';
						}
						$error_code = ! empty( $error_data['cd'] ) ? $error_data['cd'] : '-';
						$reason     = ! empty( $error_codes[ $error_code ] ) ? $error_codes[ $error_code ] : '-';
						$table     .= '<td class="pl-3 py-3">' . esc_html( $email ) . '</td><td class="pl-3 py-3">' . esc_html( $reason ) . '</td></tr>';
					}
					$table          .= '</tbody></table>';
					$return['html'] .= $table;
				}
				do_action( 'ig_es_remove_import_data' );
				$next_task_time = time() + ( 1 * MINUTE_IN_SECONDS ); // Schedule next task after 1 minute from current time.
				IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_after_bulk_contact_import', array(), false, false, $next_task_time );
			} else {
				// Add current batch emails into the processed email list
				$processed_emails             = array_merge( $processed_emails, $current_batch_emails );
				$bulkdata['processed_emails'] = $processed_emails;

				update_option( 'ig_es_bulk_import', $bulkdata );
				update_option( 'ig_es_bulk_import_errors', $erroremails );
			}
			$return['success'] = true;
			delete_transient( 'ig_es_contact_import_is_running');
		}

		wp_send_json( $return );
	}

	/**
	 * Handle adding contact id to excluded contact list
	 *
	 * @param $contact_id
	 */
	public function handle_new_contact_inserted( $contact_id ) {
		$import_status = get_transient( 'ig_es_contact_import_is_running' );
		if ( ! empty( $import_status ) && 'yes' == $import_status && ! empty( $contact_id ) ) {
			$old_excluded_contact_ids = $this->get_excluded_contact_id_on_import();
			array_push( $old_excluded_contact_ids, $contact_id );
			$this->set_excluded_contact_id_on_import($old_excluded_contact_ids);
		}
	}

	/**
	 * Get the excluded contact ID's list
	 *
	 * @return array|mixed
	 */
	public function get_excluded_contact_id_on_import() {
		$old_excluded_contact_ids = get_transient( 'ig_es_excluded_contact_ids_on_import' );
		if ( empty( $old_excluded_contact_ids ) || ! is_array( $old_excluded_contact_ids ) ) {
			$old_excluded_contact_ids = array();
		}

		return $old_excluded_contact_ids;
	}

	/**
	 * Set the excluded contact ID's list in transient
	 */
	public function set_excluded_contact_id_on_import( $list ) {
		if ( ! is_array( $list ) ) {
			return false;
		}
		if ( empty( $list ) ) {
			delete_transient( 'ig_es_excluded_contact_ids_on_import' );
		} else {
			set_transient( 'ig_es_excluded_contact_ids_on_import', $list, 24 * HOUR_IN_SECONDS );
		}

		return true;
	}

	/**
	 * Handle sending bulk welcome and confirmation email to customers using cron job
	 */
	public function handle_after_bulk_contact_import() {
		global $wpbd;
		$imported_contact_details = get_transient( 'ig_es_imported_contact_ids_range' );
		if ( ! empty( $imported_contact_details ) && isset( $imported_contact_details['rows'] )) {
			$imported_row_details   = is_array( $imported_contact_details['rows'] ) ? $imported_contact_details['rows'] : array();
			if (2 == count( $imported_row_details ) ) {
				$first_row  = intval( $imported_row_details[0] );
				$last_row   = intval( $imported_row_details[1] );
				$total_rows = ( $last_row - $first_row ) + 1;
				if ( 0 < $total_rows ) {
					$per_batch                     = 100;
					$total_batches                 = ceil( $total_rows / $per_batch );
					$excluded_contact_ids          = $this->get_excluded_contact_id_on_import();
					$excluded_contact_ids_in_range = ig_es_get_values_in_range( $excluded_contact_ids, $first_row, $first_row + $per_batch );

					$sql = "SELECT contacts.id, lists_contacts.list_id, lists_contacts.status FROM {$wpbd->prefix}ig_contacts AS contacts";
					$sql .= " LEFT JOIN {$wpbd->prefix}ig_lists_contacts AS lists_contacts ON contacts.id = lists_contacts.contact_id";
					$sql .= " LEFT JOIN {$wpbd->prefix}ig_queue AS queue ON contacts.id = queue.contact_id AND queue.campaign_id = 0";
					$sql .= ' WHERE 1=1';
					$sql .= ' AND queue.contact_id IS NULL';
					$sql .= ' AND contacts.id >= %d AND contacts.id <= %d ';
					if ( ! empty( $excluded_contact_ids_in_range ) ) {
						$excluded_ids_for_next_batch = array_diff( $excluded_contact_ids, $excluded_contact_ids_in_range );
						$this->set_excluded_contact_id_on_import( $excluded_ids_for_next_batch );
						$excluded_contact_ids_in_range = array_map( 'esc_sql', $excluded_contact_ids_in_range );
						$sql                           .= ' AND contacts.id NOT IN (' . implode( ',', $excluded_contact_ids_in_range ) . ')';
					}
					$sql     .= ' GROUP BY contacts.id LIMIT %d';
					$query   = $wpbd->prepare( $sql, [ $first_row, $first_row + $per_batch, $per_batch ] );
					$entries = $wpbd->get_results( $query );
					if ( 0 < count( $entries ) ) {
						$subscriber_ids     = array();
						$subscriber_options = array();
						foreach ( $entries as $entry ) {
							if ( in_array( $entry->status, array( 'subscribed', 'unconfirmed' ) ) ) {
								$subscriber_id                                = $entry->id;
								$subscriber_ids[]                             = $subscriber_id;
								$subscriber_options[ $subscriber_id ]['type'] = 'unconfirmed' === $entry->status ? 'optin_confirmation' : 'optin_welcome_email';
							}
						}
						if ( ! empty( $subscriber_ids ) ) {
							$timestamp = time();
							ES()->queue->bulk_add(
								0,
								$subscriber_ids,
								$timestamp,
								20,
								false,
								1,
								false,
								$subscriber_options
							);
						}
					}
					if ( 1 == $total_batches ) {
						delete_transient( 'ig_es_imported_contact_ids_range' );
					} else {
						$imported_contact_details = get_transient( 'ig_es_imported_contact_ids_range' );
						$insert_ids = array( $first_row + $per_batch, $last_row );
						$imported_contact_details['rows'] = $insert_ids;
						set_transient( 'ig_es_imported_contact_ids_range', $imported_contact_details );
						$next_task_time = time() + ( 1 * MINUTE_IN_SECONDS ); // Schedule next task after 1 minute from current time.
						IG_ES_Background_Process_Helper::add_action_scheduler_task( 'ig_es_after_bulk_contact_import', array(), false, false, $next_task_time );
						//Process queued Welcome and Confirmation emails immidetly
						$request_args = array(
							'action' => 'ig_es_process_queue',
						);
						// Send an asynchronous request to trigger sending of confirmation emails.
						IG_ES_Background_Process_Helper::send_async_ajax_request( $request_args, true );
					}
				}
			}
		}
	}

	/**
	 * Method to truncate temp import table and options used during import process
	 *
	 * @param string import identifier
	 *
	 * @since 4.6.6
	 *
	 * @since 4.7.5 Renamed the function, converted to static method
	 */
	public static function remove_import_data( $identifier = '' ) {

		global $wpdb;

		// If identifier is empty that means, there isn't any importer running. We can safely delete the import data.
		if ( empty( $identifier ) ) {
			// Delete options used during import.
			delete_option( 'ig_es_bulk_import' );
			delete_option( 'ig_es_bulk_import_errors' );

			// We are trancating table so that primary key is reset to 1 otherwise ID column's value will increase on every insert and at some point ID column's data type may not be able to accomodate its value resulting in insert to fail.
			$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}ig_temp_import" );
		}

	}

	public function api() {

		$mailchimp_apikey = ig_es_get_request_data( 'mailchimp_api_key' );
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
				$limit             = ig_es_get_request_data( 'limit' );
				$offset            = ig_es_get_request_data( 'offset' );
				$contact_status    = ig_es_get_request_data( 'status' );
				$import_identifier = ig_es_get_request_data( 'identifier' );

				if ( ! isset( $_POST['id'] ) ) {
					wp_send_json_error(
						array(
							'message' => 'no list',
						)
					);
				}

				$limit      = isset( $limit ) ? (int) $limit : 1000;
				$offset     = isset( $offset ) ? (int) $offset : 0;
				$status     = isset( $contact_status ) ? (array) $contact_status : array( 'subscribed' );
				$identifier = isset( $import_identifier ) ? $import_identifier : '';
				$list_id    = ig_es_get_request_data( 'id' );

				$subscribers = $this->api()->members(
					$list_id,
					array(
						'count'  => $limit,
						'offset' => $offset,
						'status' => $status,
					)
				);

				$list_name = ig_es_get_request_data( 'list_name' );

				$importing_from       = 'mailchimp-api';
				$raw_data             = '';
				$seperator            = ';';
				$data_contain_headers = false;

				$headers = array(
					__( 'Email', 'email-subscribers' ),
					__( 'First Name', 'email-subscribers' ),
					__( 'Last Name', 'email-subscribers' ),
					__( 'Status', 'email-subscribers' ),
					__( 'List Name', 'email-subscribers' ),
				);

				$es_mailchimp_status_mapping = array(
					'subscribed'   => __( 'Subscribed', 'email-subscribers' ),
					'unsubscribed' => __( 'Unsubscribed', 'email-subscribers' ),
					'pending'      => __( 'Unconfirmed', 'email-subscribers' ),
					'cleaned'      => __( 'Hard Bounced', 'email-subscribers' ),
				);
				foreach ( $subscribers as $subscriber ) {
					if ( ! $subscriber->email_address ) {
						continue;
					}
					$user_data = array();

					$list_name = ! empty( $list_name ) ? $list_name : 'Test';
					$status    = ! empty( $subscriber->status ) ? $subscriber->status : 'subscribed';
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
					$result     = self::insert_into_temp_table( $raw_data, $seperator, $data_contain_headers, $headers, $identifier, 'mailchimp-api' );
					$identifier = $result['identifier'];
				}
				$response = array(
					'total'       => $this->api()->get_total_items(),
					'added'       => count( $subscribers ),
					'subscribers' => count( $subscribers ),
					'identifier'  => $identifier,
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

	public static function insert_into_temp_table( $raw_data, $seperator = ',', $data_contain_headers = false, $headers = array(), $identifier = '', $importing_from = 'csv' ) {
		global $wpdb;
		$raw_data = ( trim( str_replace( array( "\r", "\r\n", "\n\n" ), "\n", $raw_data ) ) );

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$encoding = mb_detect_encoding( $raw_data, 'auto' );
		} else {
			$encoding = 'UTF-8';
		}

		$lines = explode( "\n", $raw_data );

		// If data itself contains headers(in case of CSV), then remove it.
		if ( $data_contain_headers ) {
			array_shift( $lines );
		}

		$lines_count = count( $lines );

		$batch_size = min( 500, max( 200, round( count( $lines ) / 200 ) ) ); // Each entry in temporary import table will have this much of subscribers data
		$parts      = array_chunk( $lines, $batch_size );
		$partcount  = count( $parts );

		do_action( 'ig_es_remove_import_data', $identifier );

		$identifier             = empty( $identifier ) ? uniqid() : $identifier;
		$response['identifier'] = $identifier;

		for ( $i = 0; $i < $partcount; $i++ ) {

			$part = $parts[ $i ];

			$new_value = base64_encode( serialize( $part ) );

			$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}ig_temp_import (data, identifier) VALUES (%s, %s)", $new_value, $identifier ) );
		}

		$bulk_import_data = get_option( 'ig_es_bulk_import', array() );
		if ( ! empty( $bulk_import_data ) ) {
			$partcount   += $bulk_import_data['parts'];
			$lines_count += $bulk_import_data['lines'];
		}

		$bulkimport = array(
			'imported'               => 0,
			'errors'                 => 0,
			'duplicate_emails_count' => 0,
			'encoding'               => $encoding,
			'parts'                  => $partcount,
			'lines'                  => $lines_count,
			'separator'              => $seperator,
			'importing_from'         => $importing_from,
			'data_contain_headers'   => $data_contain_headers,
			'headers'                => $headers,
		);

		$response['success']     = true;
		$response['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
		update_option( 'ig_es_bulk_import', $bulkimport, 'no' );

		return $response;
	}
}
