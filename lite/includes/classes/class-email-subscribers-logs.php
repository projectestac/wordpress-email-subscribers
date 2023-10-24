<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ES_Logs' ) ) {

	/**
	 * Class to handle es logs
	 * 
	 * @since 5.6.6
	 * @version 1.0    
	 * @package Email Subscribers 
	 */
	class ES_Logs {        

		/**
		 * Method to show es logs
		 * 
		 * @since 5.6.6
		 * 
		 * @modify 5.6.7
		 */
		public static function show_es_logs() {          
			$log_files         = IG_Log_Handler_File::get_log_files();
			$log_nonce         = ig_es_get_request_data( 'ig-es-log-nonce' );            
			$log_files         = IG_Log_Handler_File::sort_log_files_by_created_time( $log_files );
			$reverse_log_files = array_reverse( $log_files );
			$log_file_name     = $reverse_log_files[0];

			if ( isset( $_POST['submit'] ) ) {
				if ( wp_verify_nonce( $log_nonce, 'ig-es-log' ) ) {  
					if ( isset( $_POST['log_file'] ) ) {
						$log_file_name = sanitize_text_field( $_POST['log_file'] );
					}        
				} else {
					$message = __( 'Sorry, you are not allowed to view logs.', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					exit();
				}
			}       
			
			?>
			<style>
				.select2-container{
					width: 100%!important;
				}
				.select2-search__field {
					width: 100%!important;
				}
			</style>

			<h2 class="wp-heading-inline text-3xl font-bold text-gray-700 sm:leading-9 sm:truncate pr-4 mt-6">
				<?php
					echo esc_html__( 'Logs', 'email-subscribers' );
				?>
			</h2>
			<div class="flex flex-col space-y-4 pr-4">
				<div class="mt-8">
					<div class="font-bold text-xl float-left ig-es-log-file-name"><?php echo esc_html( $log_file_name ); ?></div>                
					<div class="float-right">
						<form action="#" method="POST" id="ig-es-log-files-container">
							<?php 
								// logs nonce.
								wp_nonce_field( 'ig-es-log', 'ig-es-log-nonce', false );
							?>
							<span class="inline-block mr-1 ig-es-log-files-dropdown">
								<select name="log_file" id="ig-es-log-files">
									<?php
									$i = 0;
									foreach ( $reverse_log_files as $key => $file_name ) {
										$files[$i] = $file_name;
										?>
											<option value="<?php echo esc_attr( $files[$i] ); ?>" <?php selected( $log_file_name, $file_name ); ?>><?php echo esc_html( $files[$i] ); ?></option>                            
										<?php
										$i++; 
									}
									?>
																	
								</select>
							</span>                            
							<button type="submit" name="submit" class="inline-flex justify-center py-1.5 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-indigo-500 rounded-md cursor-pointer select-none focus:outline-none focus:shadow-outline-indigo focus:shadow-lg hover:bg-indigo-500 hover:text-white  hover:shadow-md md:px-2 lg:px-3 xl:px-4 ml-2">
									<?php echo esc_html__( 'View', 'email-subscribers' ); ?>
							</button>
						</form>                    
					</div>
				</div>
				<div class="w-full bg-white rounded shadow">
					<div class="px-6 py-4">
						<p class="text-gray-700 text-base">
							<?php  
							if ( in_array( $log_file_name, $log_files, true ) ) {
								?>
								<pre id="ig_es_log_content"><?php echo esc_html__( file_get_contents( IG_LOG_DIR . $log_file_name ) ); ?></pre>
								<?php
							} else {
								$message = __( 'Sorry, you are not allowed to view logs.', 'email-subscribers' );
								ES_Common::show_message( $message, 'error' );
								exit();
							}
							?>
						</p>
					</div>                
				</div>
			</div>
			<?php
		}

	}

	new ES_Logs();    
}
