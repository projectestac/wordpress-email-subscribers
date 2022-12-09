<?php
/**
 * Admin workflow rules metabox
 *
 * @since       5.5.0
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Worfklow object
 *
 * @var ES_Workflow $workflow
 *
 * All available rules
 * @var array $all_rules
 *
 * Workflow rules
 * @var array $workflow_rules
 *
 * Workflow trigger
 * @var ES_Workflow_Trigger | false $selected_trigger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$supplied_data_items = array();
if ( is_callable( array( $selected_trigger, 'get_supplied_data_items' ) ) ) {
	$supplied_data_items = $selected_trigger->get_supplied_data_items();
}

$trigger_name = array();
if ( is_callable( array( $selected_trigger, 'get_name' ) ) ) {
	$trigger_name = $selected_trigger->get_name();
}

?>
<div id="ig-es-rules-container"></div>

<script>
	let igEsWorkflowRules = 
	<?php 
	echo wp_json_encode( array(
		'all_rules'           => $all_rules,
		'workflow_rules'      => $workflow_rules,
		'supplied_data_items' => $supplied_data_items,
		'trigger_name'        => $trigger_name
	) ); 
	?>
	;
</script>
<div class="ig-es-metabox-footer rules-metabox-footer hidden">
	<button type="button"
			class="ig-es-add-rule-group inline-flex justify-center rounded-md border border-transparent px-4 py-1.5 bg-white text-sm leading-5 font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:shadow-outline-blue transition ease-in-out duration-150"><?php echo esc_html__( '+ Add Rule Group', 'email-subscribers' ); ?></button>
</div>

<div class="hidden" id="rule-template-container">
	<p class="ig-es-rules-empty-message px-4 py-1.5">
	<?php 
	printf(
			/* translators: 1: HTML Tag 2: HTML Tag */
			esc_attr__( 'Rules can be used to add conditional logic to workflows. Click the %1$s+ Add Rule Group%2$s button to create a rule.', 'email-subscribers' ), '<strong>', '</strong>'
		); 
	?>
		</p>
	<p class="ig-es-no-rules-message px-4 py-1.5"><?php printf( esc_attr__( 'Selected triggers has no rules.. Please select different triggers', 'email-subscribers' ), '<strong>', '</strong>' ); ?></p>
	<input type="text" disabled class="ig-es-field rule-value-text-field border-gray-400">
	<select class="ig-es-field rule-value-object-field" data-placeholder="" data-action=""></select>
	<select class="ig-es-field rule-value-select-field ig-es-field--type-select"></select>

	

	<div class="ig-es-rule-group px-4 py-1.5">
	</div>

	<div class="ig-es-rule-container inline-flex mt-3 mb-3">
		<div class="ig-es-rule__fields inline-flex">
			<div class="ig-es-rule-select-container ig-es-rule__field-container pr-3">
				<select name="" class="rule-select-field ig-es-field" disabled>
					<option value=""><?php esc_attr_e( '[Select Rule]', 'email-subscribers' ); ?></option>
				</select>
			</div>
			<div class="ig-es-rule-field-compare ig-es-rule__field-container pr-3">
				<select name="" class="ig-es-field rule-compare-field" disabled>
				</select>
			</div>
			<div class="ig-es-rule-field-value ig-es-rule__field-container pr-3">
				<input type="text" disabled class="ig-es-field rule-value-field border-gray-400">
				<input type="number" disabled class="ig-es-field rule-value-number-field border-gray-400">
			</div>
		</div>

		<div class="ig-es-rule__buttons inline-flex">
			<button type="button"
					class="add-rule ig-es-rule__add button h-5"><?php esc_html_e( 'and', 'email-subscribers' ); ?></button>
			<button type="button"
					class="remove-rule ig-es-rule__remove text-red-600 mx-3 h-5 py-1"><span class="dashicons dashicons-remove"></span></button>
		</div>
	</div>
</div>
