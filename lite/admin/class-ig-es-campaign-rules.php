<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'IG_ES_Campaign_Rules' ) ) {

	/**
	 * Class IG_ES_Campaign_Rules
	 *
	 * @since 4.6.11
	 */
	class IG_ES_Campaign_Rules {

		/**
		 * Campaign rules
		 */
		private $campaign_rules = array();

		/**
		 * Subscriber related fields
		 */
		private $fields = array();

		/**
		 * Campaign related fields
		 */
		private $campaign_related = array();

		/**
		 * Aggregate campaigns fields
		 */
		private $aggregate_campaigns = array();

		/**
		 * Rule operators
		 */
		private $operators = array();

		/**
		 * Simple operators
		 */
		private $simple_operators = array();

		/**
		 * String operators
		 */
		private $string_operators = array();

		/**
		 * Boolean operators
		 */
		private $bool_operators = array();

		/**
		 * IG_ES_Campaign_Rules constructor.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Initialize campaign rules
		 *
		 * @since 4.6.12
		 */
		public function init() {
			$this->fields              = $this->get_fields();
			$this->campaign_related    = $this->get_campaign_related();
			$this->aggregate_campaigns = $this->get_aggregate_campaigns();
			$this->operators           = $this->get_operators();
			$this->simple_operators    = $this->get_simple_operators();
			$this->string_operators    = $this->get_string_operators();
			$this->bool_operators      = $this->get_bool_operators();
			$this->campaign_rules      = self::get_campaign_rules();

			add_action( 'ig_es_show_campaign_rules', array( $this, 'show_campaign_rules' ), 10, 2 );
			add_action( 'ig_es_campaign_show_conditions', array( $this, 'show_conditions' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Register the JavaScript for campaign rules.
		 */
		public function enqueue_scripts() {

			$current_page = ig_es_get_request_data( 'page' );

			if ( in_array( $current_page, array( 'es_notifications', 'es_newsletters', 'es_sequence' ), true ) ) {
				wp_register_script( 'alpine', plugins_url( '/js/alpine.js', __FILE__ ), array(), '2.8.2', false );
				wp_enqueue_script( 'alpine' );
			}
		}

		/**
		 * Method to show campaign rules
		 *
		 * @param array $campaign_data Broadcast data
		 *
		 * @since 4.6.11
		 */
		public function show_campaign_rules( $campaign_id = 0, $campaign_data = array() ) {

			$current_page = ig_es_get_request_data( 'page' );

			$campaign_type = '';
			if ( 'es_newsletters' === $current_page ) {
				$campaign_type = 'newsletter';
			} elseif ( 'es_notifications' === $current_page ) {
				$campaign_type = 'post_notification';
			} else {
				$campaign_type = 'sequence_message';
			}

			$conditions = array();
			if ( ! empty( $campaign_data['meta'] ) ) {
				$campaign_meta = maybe_unserialize( $campaign_data['meta'] );
				$conditions    = ! empty( $campaign_meta['list_conditions'] ) ? $campaign_meta['list_conditions'] : array();
			}

			$args = array();
			if ( IG_CAMPAIGN_TYPE_NEWSLETTER === $campaign_type ) {
				$args = array(
					'include_types' => array(
						IG_CAMPAIGN_TYPE_NEWSLETTER,
					),
					'status'        => array(
						IG_ES_CAMPAIGN_STATUS_QUEUED,
						IG_ES_CAMPAIGN_STATUS_FINISHED,
					),
				);
			} elseif ( IG_CAMPAIGN_TYPE_POST_NOTIFICATION === $campaign_type ) {
				$args = array(
					'include_types' => array(
						IG_CAMPAIGN_TYPE_POST_NOTIFICATION,
					),
				);
			} elseif ( IG_CAMPAIGN_TYPE_POST_DIGEST === $campaign_type ) {
				$args = array(
					'include_types' => array(
						IG_CAMPAIGN_TYPE_POST_DIGEST,
					),
				);
			} else {
				$args = array(
					'include_types' => array(
						'sequence_message',
					),
				);
			}

			if ( ! empty( $campaign_id ) ) {
				$args['campaigns_not_in'] = array( $campaign_id );
			}
			$all_campaigns = ES()->campaigns_db->get_all_campaigns( $args );

			$lists = ES()->lists_db->get_list_id_name_map();

			$countries_data = ES_Geolocation::get_countries();

			if ( 'es_newsletters' === $current_page ) {
				$input_name = 'campaign_data[meta][list_conditions]';
			} elseif ( 'es_notifications' === $current_page ) {
				$input_name = 'campaign_data[meta][list_conditions]';
			} else {
				$input_name = 'seq_data[' . $campaign_id . '][list_conditions]';
			}

			$select_list_attr  = ES()->is_pro() ? 'multiple="multiple"' : '';
			$select_list_class = ES()->is_pro() ? 'ig-es-campaign-rule-form-multiselect' : 'form-select';

			$sidebar_id = 'sidebar_' . $campaign_id;
			?>
			<style>
			.select2-container{
				width: 100%!important;
			}
			.select2-search__field {
				width: 100%!important;
			}
			</style>
			<div class="ig-es-campaign-rules my-2" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>" data-campaign-type="<?php echo esc_attr( $campaign_type ); ?>" x-data="{ <?php echo esc_attr( $sidebar_id ); ?>: false }">
					<label for="es-campaign-condition" class="text-sm font-medium leading-5 text-gray-700 recipient-text"><?php esc_html_e( 'Recipients', 'email-subscribers' ); ?>:</label>
					<div class="ig-es-conditions-render-wrapper">
						<?php
						if ( ! empty( $conditions ) ) {
							do_action( 'ig_es_campaign_show_conditions', $conditions );
						}
						?>
					</div>
				<p class="clear">
					<a class="block edit-conditions rounded-md border text-indigo-600 border-indigo-500 text-sm leading-5 font-medium transition ease-in-out duration-150 select-none inline-flex justify-center hover:text-indigo-500 hover:border-indigo-600 hover:shadow-md focus:outline-none focus:shadow-outline-indigo focus:shadow-lg mt-1 px-1.5 py-1 mr-1 cursor-pointer" x-on:click="<?php echo esc_attr( $sidebar_id ); ?>=true">
						<?php esc_html_e( 'Add recipients', 'email-subscribers' ); ?>
					</a>
					<span class="remove-all-conditions-wrapper<?php echo empty( $conditions ) ? ' hidden' : ''; ?>">
						<?php esc_html_e( 'or', 'email-subscribers' ); ?> 
						<a class="remove-conditions hover:underline" href="#">
							<?php esc_html_e( 'remove all', 'email-subscribers' ); ?>
						</a>
					</span>
				</p>
				<div class="fixed inset-0 overflow-hidden z-50" id='ig-es-campaign-rules-<?php echo esc_attr( $sidebar_id ); ?>' style="display: none;" x-show="<?php echo esc_attr( $sidebar_id ); ?>">
					<div class="absolute inset-0 overflow-hidden">
						<div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
						<section class="absolute inset-y-0 right-0 pl-10 max-w-full flex" aria-labelledby="slide-over-heading">
							<div class="relative w-screen max-w-3xl mt-8"
							x-transition:enter="ease-out duration-300"
							x-transition:enter-start="opacity-0 -translate-x-full"
							x-transition:enter-end="opacity-100 translate-x-0"
							x-transition:leave="ease-in duration-200"
							x-transition:leave-start="opacity-100 translate-x-0"
							x-transition:leave-end="opacity-0 -translate-x-full">

							<div class="h-full flex flex-col bg-gray-50 shadow-xl overflow-y-auto">
								<div class="flex py-5 px-6 bg-gray-100 shadow-sm sticky">
									<div class="w-9/12">
										<span id="slide-over-heading" class="text-xl font-medium text-gray-600">
											<?php echo esc_html__( 'Campaign Rules', 'email-subscribers' ); ?>
										</span>
									</div>
									<div class="w-3/12 text-right">
										<span class="es_spinner_image_admin inline-block align-middle -mt-1 mr-1" id="spinner-image" style="display:none"><img src="<?php echo esc_url( ES_PLUGIN_URL . 'lite/public/images/spinner.gif' ); ?>" alt="<?php echo esc_attr__( 'Loading...', 'email-subscribers' ); ?>"/></span>
										<a class="-mt-1 mr-2 px-3 py-0.5 ig-es-primary-button cursor-pointer close-conditions" x-on:click=" <?php echo esc_attr( $sidebar_id ); ?> = false"><?php esc_html_e( 'Save Rules', 'email-subscribers' ); ?></a>
										<a x-on:click=" <?php echo esc_attr( $sidebar_id ); ?> = false" class="-mt-1 rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-white cursor-pointer">
											<span class="sr-only"><?php echo esc_html__( 'Close panel', 'email-subscribers' ); ?></span>
											<!-- Heroicon name: outline/x -->
											<svg class="h-6 w-6 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
											</svg>
										</a>
									</div>
								</div>
								<div class="mt-3 px-6 pb-6 relative flex-1 w-full">
									<!-- Replace with your content -->
										<div class="h-full rounded-md" aria-hidden="true">
											<div class="absolute inset-0 z-50">
										<div class="foot pt-2 px-6 text-right">
											<div class="campaign-conditions-total-contacts">
												<p class="inline font-medium text-base tracking-wide pr-2 text-gray-400"><?php esc_html_e( 'Total recipients', 'email-subscribers' ); ?>: </span> <span class="ig-es-total-contacts">&ndash;</span></div>
											</div>
											<div class="ig-es-conditions px-6 h-full">
												<div class="ig-es-condition-container"></div>
												<div class="ig-es-conditions-wrap mt-1 mb-3 overflow-auto">
													<?php

													array_unshift(
														$conditions,
														array(
															array(
																'field'    => '',
																'operator' => '',
																'value'    => '',
															),
														)
													);

													foreach ( $conditions as $i => $condition_group ) :
														?>
														<div class="ig-es-condition-group bg-white border border-gray-200 rounded-md my-2 pb-12 relative block px-4 rounded-lg pt-2 mt-2 mb-12" data-id="<?php echo esc_attr( $i ); ?>" data-operator="<?php esc_attr_e( 'and', 'email-subscribers' ); ?>"<?php echo ( ! $i ) ? ' style="display:none"' : ''; ?>>
															
															<?php
															foreach ( $condition_group as $j => $condition ) :
																$value          = isset( $condition['value'] ) ? $condition['value'] : '';
																$field          = isset( $condition['field'] ) ? $condition['field'] : '';
																$field_operator = $this->get_field_operator( $condition['operator'] );
																?>
																<div class="add-or-condition absolute z-10 bottom-5">
																		<a class="es-add-or-condition bg-gray-100 py-1 px-1 rounded-md leading-4 font-medium cursor-pointer hover:bg-gray-50"><?php esc_html_e( 'Add Condition', 'email-subscribers' ); ?> [<span class="uppercase"><?php esc_html_e( 'or', 'email-subscribers' ); ?></span>]</a>
																	</div>
																<div class="ig-es-condition border-b border-gray-200 relative py-5" data-id="<?php echo esc_attr( $j ); ?>" data-operator="<?php esc_attr_e( 'or', 'email-subscribers' ); ?>">
																	<a class="remove-condition cursor-pointer float-right mb-2 px-1" title="<?php esc_attr_e( 'remove condition', 'email-subscribers' ); ?>">&#10005;</a>
																	<div class="ig-es-conditions-field-fields">
																		<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][field]" class="condition-field form-select" disabled>
																			<?php
																			foreach ( $this->campaign_rules as $rule_group => $rules ) {
																				?>
																				<optgroup label="<?php echo esc_attr( $rule_group ); ?>">
																				<?php
																				foreach ( $rules as $key => $rule ) {
																					echo '<option value="' . esc_attr( $key ) . '"' . selected( $condition['field'], $key, false ) . ( ! empty( $rule['disabled'] ) ? ' disabled="' . esc_attr( 'disabled' ) . '"' : '' ) . ( isset( $rule['count'] ) ? ' data-count="' . esc_attr( $rule['count'] ) . '"' : '' ) . '>' . esc_html( $rule['name'] ) . '</option>';
																				}
																				?>
																				</optgroup>
																				<?php
																			}
																			?>
																		</select>
																	</div>

																	<div class="ig-es-conditions-operator-fields">
																		<div class="ig-es-conditions-operator-field ig-es-conditions-operator-field-default">
																			<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][operator]" class="condition-operator form-select" disabled>
																				<?php
																				foreach ( $this->operators as $key => $name ) :
																					echo '<option value="' . esc_attr( $key ) . '"' . selected( $field_operator, $key, false ) . '>' . esc_html( $name ) . '</option>';
																				endforeach;
																				?>
																			</select>
																		</div>
																		<?php
																		$campaign_rules_data_fields = array(
																			'string_fields' => array( 'email' ),
																		);
																		$campaign_rules_data_fields = apply_filters( 'ig_es_campaign_rules_data_fields', $campaign_rules_data_fields );
																		if ( ! empty( $campaign_rules_data_fields['string_fields'] ) ) {
																			?>
																			<div class="ig-es-conditions-operator-field" data-fields=",<?php echo esc_attr( implode( ',', $campaign_rules_data_fields['string_fields'] ) ); ?>,">
																				<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][operator]" class="condition-operator form-select" disabled>
																					<?php
																					foreach ( $this->string_operators as $key => $name ) :
																						echo '<option value="' . esc_attr( $key ) . '"' . selected( $field_operator, $key, false ) . '>' . esc_html( $name ) . '</option>';
																					endforeach;
																					?>
																				</select>
																			</div>
																			<?php
																		}
																		?>
																		<?php
																		if ( ! empty( $campaign_rules_data_fields['simple_fields'] ) ) {
																			?>
																			<div class="ig-es-conditions-operator-field" data-fields=",<?php echo esc_attr( implode( ',', $campaign_rules_data_fields['simple_fields'] ) ); ?>,">
																				<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][operator]" class="condition-operator form-select" disabled>
																					<?php
																					foreach ( $this->simple_operators as $key => $name ) :
																						echo '<option value="' . esc_attr( $key ) . '"' . selected( $field_operator, $key, false ) . '>' . esc_html( $name ) . '</option>';
																					endforeach;
																					?>
																				</select>
																			</div>
																			<?php
																		}
																		?>
																		<?php
																		if ( ! empty( $campaign_rules_data_fields['date_fields'] ) ) {
																			?>
																			<div class="ig-es-conditions-operator-field" data-fields=",<?php echo esc_attr( implode( ',', $campaign_rules_data_fields['date_fields'] ) ); ?>,">
																				<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][operator]" class="condition-operator form-select" disabled>
																					<?php
																					foreach ( $this->simple_operators as $key => $name ) :
																						echo '<option value="' . esc_attr( $key ) . '"' . selected( $field_operator, $key, false ) . '>' . esc_html( $name ) . '</option>';
																					endforeach;
																					?>
																				</select>
																			</div>
																			<?php
																		}

																		if ( ! empty( $campaign_rules_data_fields['boolean_fields'] ) ) {
																			?>
																			<div class="ig-es-conditions-operator-field" data-fields=",<?php echo esc_attr( implode( ',', $campaign_rules_data_fields['boolean_fields'] ) ); ?>,">
																				<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][operator]" class="condition-operator form-select" disabled>
																					<?php
																					foreach ( $this->bool_operators as $key => $name ) :
																						echo '<option value="' . esc_attr( $key ) . '"' . selected( $field_operator, $key, false ) . '>' . esc_html( $name ) . '</option>';
																					endforeach;
																					?>
																				</select>
																			</div>
																			<?php
																		}
																		?>
																		<div class="ig-es-conditions-operator-field" data-fields=",_sent,_sent__not_in,_open,_open__not_in,_click,_click__not_in,_lists__not_in,_lists__in,">
																			<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][operator]" class="condition-operator" disabled value="is">
																		</div>
																	</div>

																	<div class="ig-es-conditions-value-fields w-full">
																		<?php
																		if ( is_array( $value ) ) {
																			$value_arr = $value;
																			$value     = $value[0];
																		} else {
																			$value_arr = array( $value );
																		}
																		?>
																		<div class="ig-es-conditions-value-field ig-es-conditions-value-field-default">
																			<input type="text" class="regular-text condition-value form-input h-5 text-sm" disabled value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][value]">
																		</div>
																		<div class="ig-es-conditions-value-field" data-fields=",id,wp_user_id,">
																			<input type="text" class="regular-text condition-value form-input h-5 text-sm" disabled value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][value]">
																		</div>
																		<div class="ig-es-conditions-value-field" data-fields=",country_code,">
																			<select class="regular-text condition-value form-select" disabled name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][value]">
																				<option value="">--</option>
																				<?php
																				foreach ( $countries_data as $country_code => $country_name ) {
																					?>
																					<option value="<?php echo esc_attr( $country_code ); ?>" <?php selected( $country_code, $value ); ?>><?php echo esc_html( $country_name ); ?></option>
																					<?php
																				}
																				?>
																			</select>
																		</div>
																		<div class="ig-es-conditions-value-field" data-fields=",_sent,_sent__not_in,_open,_open__not_in,_click,_click__not_in,">
																					<div class="-mr-3">
																						<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][value][]" class="condition-value ig-es-campaign-select-field <?php echo esc_attr( $select_list_class ); ?>" <?php echo esc_attr( $select_list_attr ); ?>>
																							<option value="0"><?php echo esc_html__( 'Any campaign', 'email-subscribers' ); ?></option>
																							<?php if ( $all_campaigns ) : ?>
																								<?php foreach ( $value_arr as $k => $v ) : ?>
																									<?php
																									foreach ( $all_campaigns as $campaign ) :
																										?>
																								<option value="<?php echo esc_attr( $campaign['id'] ); ?>" <?php selected( $v, $campaign['id'] ); ?>><?php echo $campaign['name'] ? esc_html( $campaign['name'] ) : '[' . esc_html__( 'no title', 'email-subscribers' ) . '] (# ' . esc_attr( $campaign['id'] ) . ')'; ?></option>
																								<?php endforeach; ?>
																							<?php endforeach; ?>
																							<?php endif; ?>
																						</select>
																					</div>
																		</div>
																		<div class="ig-es-conditions-value-field" data-fields=",_lists__not_in,_lists__in,">
																		<?php if ( $lists ) : ?>
																			<select name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][value][]" class="condition-value <?php echo esc_attr( $select_list_class ); ?>" <?php echo esc_attr( $select_list_attr ); ?>>
																				<?php
																				if ( ES()->is_pro() ) :
																					?>
																				<option value="0"><?php echo esc_html__( 'Any list', 'email-subscribers' ); ?></option>
																					<?php
																				endif;
																				foreach ( $lists as $list_id => $list_name ) :
																					?>
																				<option value="<?php echo esc_attr( $list_id ); ?>" <?php echo ( in_array( $list_id, $value_arr ) ? 'selected="' . esc_attr( 'selected' ) . '"' : '' ); ?>><?php echo $list_name ? esc_html( $list_name ) : '[' . esc_html__( 'no title', 'email-subscribers' ) . ']'; ?></option>
																				<?php endforeach; ?>
																			</select>
																		<?php else : ?>
																			<p><?php esc_html_e( 'No campaigns available', 'email-subscribers' ); ?><input type="hidden" class="condition-value" disabled value="0" name="<?php echo esc_attr( $input_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $j ); ?>][value]"></p>
																		<?php endif; ?>
																		</div>
																		<?php
																		do_action( 'ig_es_campaigns_extra_filters', $input_name, $value_arr, $value, $i, $j, $select_list_class, $select_list_attr );
																		?>
																	</div>
																	<div class="clear"></div>
																	</div><?php endforeach; ?>
																	
																	</div><?php endforeach; ?>
																</div>
																<div class="mt-5">
																<a class="ig-es-primary-button py-1 px-2 cursor-pointer add-condition">
																	<svg class="w-4 h-4 mt-0.5 inline text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
																	<?php esc_html_e( 'Add Condition', 'email-subscribers' ); ?></a>
																</div>
																<div class="ig-es-condition-empty">
																</div>
																
															</div>
														</div>
														<!-- /End replace -->
													</div>

												</div>
												<?php do_action( 'ig_es_upsell_campaign_rules' ); ?>
											</div>
										</section>
									</div>
								</div>
							</div>
			<?php
		}

		/**
		 * Show selected conditions
		 */
		public function show_conditions( $conditions ) {
			$allowedtags = ig_es_allowed_html_tags_in_esc();
			?>
				<?php
				if ( ! empty( $conditions ) ) :
					?>
					<div class="ig-es-conditions-render">
					<?php
					foreach ( $conditions as $i => $condition_group ) :
						if ( ! empty( $condition_group ) ) :
							?>
						<div class="ig-es-condition-render-group">
							<?php
							if ( $i ) {
								echo '<span class="clear float-left pr-1 ig-es-condition-operators text-xs font-medium text-gray-400 tracking-wide uppercase mt-1 mr-1">' . esc_html__( 'and', 'email-subscribers' );
								if ( count( $condition_group ) > 1 ) {
									echo esc_html( ' ( ' );
								}
								echo wp_kses( '</span>', $allowedtags );

							}
							foreach ( $condition_group as $j => $condition ) :
								$condition_html = $this->get_condition_html( $condition );
								?>
									<div class="ig-es-condition-render ig-es-condition-render-<?php echo esc_attr( $condition['field'] ); ?>" title="<?php echo esc_attr( strip_tags( sprintf( '%s %s %s', $condition_html['field'], $condition_html['operator'], $condition_html['value'] ) ) ); ?>">
									<?php
									if ( $j ) {
										echo '<span class="ig-es-condition-type ig-es-condition-operators text-xs font-medium text-gray-400 tracking-wide uppercase mt-1 mr-1">' . esc_html__( ' or', 'email-subscribers' ) . '</span>';
									}
									?>
										<span class="ig-es-condition-type ig-es-condition-field mt-1"><?php echo wp_kses( $condition_html['field'], $allowedtags ); ?></span>
										<span class="ig-es-condition-type ig-es-condition-operator mt-1"><?php echo wp_kses( $condition_html['operator'], $allowedtags ); ?></span>
										<span class="ig-es-condition-type ig-es-condition-value mt-1 pl-2"><?php echo wp_kses( $condition_html['value'], $allowedtags ); ?></span>
									</div>
								<?php
							endforeach;
							if ( $i && count( $condition_group ) > 1 ) {
								echo '<span class="float-left pr-1 text-xs font-medium text-gray-400 tracking-wide uppercase mt-1">' . esc_html__( ') ', 'email-subscribers' ) . '</span>';
							}
							?>
						</div>
							<?php
						endif;
					endforeach;
					?>
					</div>
					<?php
				endif;
				?>
			<?php
		}

		/**
		 * Get field operator
		 *
		 * @return string $operator Field operator
		 */
		public function get_field_operator( $operator ) {
			$operator = esc_sql( stripslashes( $operator ) );

			switch ( $operator ) {
				case '=':
					return 'is';
				case '!=':
					return 'is_not';
				case '<>':
					return 'contains';
				case '!<>':
					return 'contains_not';
				case '^':
					return 'begin_with';
				case '$':
					return 'end_with';
				case '>=':
					return 'is_greater_equal';
				case '<=':
					return 'is_smaller_equal';
				case '>':
					return 'is_greater';
				case '<':
					return 'is_smaller';
				case '%':
					return 'pattern';
				case '!%':
					return 'not_pattern';
			}

			return $operator;

		}

		/**
		 * Get condition HTML
		 *
		 * @return string Get condition HTML
		 */
		private function get_condition_html( $condition, $formated = true ) {

			$field    = isset( $condition['field'] ) ? $condition['field'] : ( isset( $condition[0] ) ? $condition[0] : '' );
			$operator = isset( $condition['operator'] ) ? $condition['operator'] : ( isset( $condition[1] ) ? $condition[1] : '' );
			$value    = stripslashes_deep( isset( $condition['value'] ) ? $condition['value'] : ( isset( $condition[2] ) ? $condition[2] : '' ) );

			$return        = array(
				'field'    => '<span class="leading-5 text-gray-700 text-sm tracking-wide mr-1">' . $this->nice_name( $field, 'field', $field ) . '</span>',
				'operator' => '',
				'value'    => '',
			);
			$opening_quote = esc_html_x( '&#8220;', 'opening curly double quote', 'email-subscribers' );
			$closing_quote = esc_html_x( '&#8221;', 'closing curly double quote', 'email-subscribers' );

			if ( isset( $this->campaign_rules['Campaign'][ $field ] ) ) {
				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}
				$return['value'] = '<span class="font-medium text-gray-500 tracking-wide mr-1">' . $opening_quote . implode( $closing_quote . ' </span><span class="uppercase text-gray-400 pr-1 text-xs font-medium tracking-wide mt-1 mr-1">' . esc_html__( 'or', 'email-subscribers' ) . ' </span><span class="font-medium text-gray-500 tracking-wide mr-1"> ' . $opening_quote, array_map( array( $this, 'get_campaign_name' ), $value ) ) . $closing_quote . '</span>';
			} elseif ( isset( $this->campaign_rules['List'][ $field ] ) ) {
				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}
				$return['value'] = '<span class="font-medium text-gray-500 tracking-wide mr-1">' . $opening_quote . implode( $closing_quote . ' </span><span class="uppercase text-gray-400 pr-1 text-xs font-medium tracking-wide mt-1 mr-1">' . esc_html__( 'or', 'email-subscribers' ) . ' </span><span class="font-medium text-gray-500 tracking-wide mr-1"> ' . $opening_quote, array_map( array( $this, 'get_list_name' ), $value ) ) . $closing_quote . '</span>';
			} elseif ( 'country_code' === $field ) {
				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}
				$return['operator'] = '<em>' . $this->nice_name( $operator, 'operator', $field ) . '</em>';
				$return['value']    = $opening_quote . implode( $closing_quote . ' ' . esc_html__( 'or', 'email-subscribers' ) . ' ' . $opening_quote, array_map( array( $this, 'get_country_name' ), $value ) ) . $closing_quote;
			} elseif ( 'bounce_status' === $field ) {
				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}
				$return['operator'] = '<em>' . $this->nice_name( $operator, 'operator', $field ) . '</em>';
				$return['value']    = $opening_quote . implode( $closing_quote . ' ' . esc_html__( 'or', 'email-subscribers' ) . ' ' . $opening_quote, array_map( array( $this, 'get_bounce_status_name' ), $value ) ) . $closing_quote;
			} elseif ( false !== strpos( $field, 'cf_' ) ) {
				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}
				$return['operator'] = '<em>' . $this->nice_name( $operator, 'operator', $field ) . '</em>';
				$return['value']    = $opening_quote . implode( $closing_quote . ' ' . esc_html__( 'or', 'email-subscribers' ) . ' ' . $opening_quote, $value ) . $closing_quote;
			} else {
				$return['operator'] = '<em>' . $this->nice_name( $operator, 'operator', $field ) . '</em>';
				$return['value']    = $opening_quote . '<span class="font-medium text-gray-500 tracking-wide mr-1">' . $this->nice_name( $value, 'value', $field ) . '</span>' . $closing_quote;
			}

			return $formated ? $return : strip_tags( $return );
		}

		/**
		 * Get names for field, operator and value
		 *
		 * @return string Formatted string
		 */
		private function nice_name( $string, $type = null, $field = null ) {

			switch ( $type ) {
				case 'field':
					foreach ( $this->campaign_rules as $rule_group => $rules ) {
						foreach ( $rules as $rule_slug => $rule ) {
							if ( $string === $rule_slug ) {
								return $rule['name'];
							}
						}
					}
					break;
				case 'operator':
					if ( isset( $this->operators[ $string ] ) ) {
						return $this->operators[ $string ];
					}
					if ( 'AND' == $string ) {
						return esc_html__( 'and', 'email-subscribers' );
					}
					if ( 'OR' == $string ) {
						return esc_html__( 'or', 'email-subscribers' );
					}
					break;
			}

			return $string;
		}

		/**
		 * Get list of campaign rules
		 *
		 * @return array List of campaign rules
		 */
		public static function get_campaign_rules() {

			$campaign_rules = array(
				'List' => array(
					'_lists__in' => array(
						'name' => esc_html__( 'is in List', 'email-subscribers' ),
					),
				),
			);

			$campaign_rules = apply_filters( 'ig_es_campaign_rules', $campaign_rules );

			return $campaign_rules;
		}

		/**
		 * Get list of subscribers data based rules
		 *
		 * @return array List of subscribers data based rules
		 */
		private function get_fields() {
			$fields = array(
				'email'        => esc_html__( 'Email', 'email-subscribers' ),
				'country_code' => esc_html__( 'Country', 'email-subscribers' ),
			);

			return $fields;
		}

		/**
		 * Get list of campaign related rules
		 *
		 * @return array List of aggregate campaigns related rules
		 */
		private function get_campaign_related() {
			return array(
				'_sent'          => esc_html__( 'has received', 'email-subscribers' ),
				'_sent__not_in'  => esc_html__( 'has not received', 'email-subscribers' ),
				'_open'          => esc_html__( 'has received and opened', 'email-subscribers' ),
				'_open__not_in'  => esc_html__( 'has received but not opened', 'email-subscribers' ),
				'_click'         => esc_html__( 'has received and clicked', 'email-subscribers' ),
				'_click__not_in' => esc_html__( 'has received and not clicked', 'email-subscribers' ),
			);

		}

		/**
		 * Get list of aggregate campaigns related rules
		 *
		 * @return array List of aggregate campaigns related rules
		 */
		private function get_aggregate_campaigns() {
			return array(
				'_last_5'         => esc_html__( 'Any of the Last 5 Campaigns', 'email-subscribers' ),
				'_last_7_day'     => esc_html__( 'Any Campaigns within the last 7 days', 'email-subscribers' ),
				'_last_1_month'   => esc_html__( 'Any Campaigns within the last 1 month', 'email-subscribers' ),
				'_last_3_months'  => esc_html__( 'Any Campaigns within the last 3 months', 'email-subscribers' ),
				'_last_6_months'  => esc_html__( 'Any Campaigns within the last 6 months', 'email-subscribers' ),
				'_last_12_months' => esc_html__( 'Any Campaigns within the last 12 months', 'email-subscribers' ),
			);

		}

		/**
		 * Get list of comparison operators
		 *
		 * @return array List of comparison operators
		 */
		public function get_operators() {
			return array(
				'is'               => esc_html__( 'is', 'email-subscribers' ),
				'is_not'           => esc_html__( 'is not', 'email-subscribers' ),
				'contains'         => esc_html__( 'contains', 'email-subscribers' ),
				'contains_not'     => esc_html__( 'contains not', 'email-subscribers' ),
				'begin_with'       => esc_html__( 'begins with', 'email-subscribers' ),
				'end_with'         => esc_html__( 'ends with', 'email-subscribers' ),
				'is_greater'       => esc_html__( 'is greater than', 'email-subscribers' ),
				'is_smaller'       => esc_html__( 'is smaller than', 'email-subscribers' ),
				'is_greater_equal' => esc_html__( 'is greater or equal', 'email-subscribers' ),
				'is_smaller_equal' => esc_html__( 'is smaller or equal', 'email-subscribers' ),
				'pattern'          => esc_html__( 'match regex pattern', 'email-subscribers' ),
				'not_pattern'      => esc_html__( 'does not match regex pattern', 'email-subscribers' ),
			);

		}

		/**
		 * Get list of simple operators
		 *
		 * @return array Simple operators
		 */
		public function get_simple_operators() {
			return array(
				'is'               => esc_html__( 'is', 'email-subscribers' ),
				'is_not'           => esc_html__( 'is not', 'email-subscribers' ),
				'is_greater'       => esc_html__( 'is greater than', 'email-subscribers' ),
				'is_smaller'       => esc_html__( 'is smaller than', 'email-subscribers' ),
				'is_greater_equal' => esc_html__( 'is greater or equal', 'email-subscribers' ),
				'is_smaller_equal' => esc_html__( 'is smaller or equal', 'email-subscribers' ),
			);

		}

		/**
		 * Get list of string operators
		 *
		 * @return array String operators
		 */
		public function get_string_operators() {
			return array(
				'is'           => esc_html__( 'is', 'email-subscribers' ),
				'is_not'       => esc_html__( 'is not', 'email-subscribers' ),
				'contains'     => esc_html__( 'contains', 'email-subscribers' ),
				'contains_not' => esc_html__( 'contains not', 'email-subscribers' ),
				'begin_with'   => esc_html__( 'begins with', 'email-subscribers' ),
				'end_with'     => esc_html__( 'ends with', 'email-subscribers' ),
				'pattern'      => esc_html__( 'match regex pattern', 'email-subscribers' ),
				'not_pattern'  => esc_html__( 'does not match regex pattern', 'email-subscribers' ),
			);

		}

		/**
		 * Get list of boolean operators
		 *
		 * @return array Boolean operator
		 */
		public function get_bool_operators() {
			return array(
				'is'     => esc_html__( 'is', 'email-subscribers' ),
				'is_not' => esc_html__( 'is not', 'email-subscribers' ),
			);

		}

		/**
		 * Get campaign name
		 *
		 * @param int $campaign_id Campaign ID
		 *
		 * @return string $name Campaign name
		 */
		public function get_campaign_name( $campaign_id ) {

			if ( ! $campaign_id ) {
				return esc_html__( 'Any campaign', 'email-subscribers' );
			}

			if ( isset( $this->aggregate_campaigns[ $campaign_id ] ) ) {
				return $this->aggregate_campaigns[ $campaign_id ];
			}

			$campaign = ES()->campaigns_db->get( $campaign_id );
			if ( empty( $campaign['name'] ) ) {
				$name = '#' . $campaign_id;
			} else {
				$name = $campaign['name'];
			}
			return '<span class="campaign-name" data-campaign-id="' . esc_attr( $campaign_id ) . '">' . $name . '</span>';
		}

		/**
		 * Get list name
		 *
		 * @param int $list_id list ID
		 *
		 * @return string $list_name list name
		 */
		public function get_list_name( $list_id ) {

			if ( ! $list_id ) {
				return esc_html__( 'Any list', 'email-subscribers' );
			}

			$lists     = ES()->lists_db->get_list_id_name_map();
			$list_name = isset( $lists[ $list_id ] ) ? $lists[ $list_id ] : $list_id;
			return $list_name;
		}

		/**
		 * Get bounce status
		 *
		 * @param string $bounce_status bounce status code
		 *
		 * @return string bounce status
		 */
		public function get_bounce_status_name( $bounce_status ) {
			switch ( $bounce_status ) {
				case '2':
					return esc_html__( 'Hard bounced', 'email-subscribers' );
				case '1':
					return esc_html__( 'Soft bounced', 'email-subscribers' );
				case '0':
					return esc_html__( 'Un-bounced', 'email-subscribers' );
				default:
					return esc_html__( 'Any status', 'email-subscribers' );
			}
		}

		/**
		 * Get country name
		 *
		 * @param string $code country code
		 *
		 * @return string $country_name country name
		 */
		public function get_country_name( $code ) {

			$country_name = ES_Geolocation::get_countries_iso_code_name_map( $code );
			return $country_name;
		}

		/**
		 * Remove empty conditions from campaign data
		 *
		 * @param array $conditions_data
		 *
		 * @return array $conditions_data
		 */
		public static function remove_empty_conditions( $conditions_data = array() ) {

			if ( ! empty( $conditions_data ) ) {
				$list_conditions = $conditions_data;
				foreach ( $list_conditions as $i => $and_cond ) {
					foreach ( $and_cond as $j => $cond ) {
						if ( ! isset( $list_conditions[ $i ][ $j ]['field'] ) ) {
							unset( $list_conditions[ $i ][ $j ] );
						} elseif ( isset( $list_conditions[ $i ][ $j ]['value'] ) && is_array( $list_conditions[ $i ][ $j ]['value'] ) ) {
							$list_conditions[ $i ][ $j ]['value'] = array_values( array_unique( $list_conditions[ $i ][ $j ]['value'] ) );
						}
					}
				}
				// Remove any empty value array.
				$conditions_data = array_values( array_filter( $list_conditions ) );
			}

			return $conditions_data;
		}
	}
}

new IG_ES_Campaign_Rules();
