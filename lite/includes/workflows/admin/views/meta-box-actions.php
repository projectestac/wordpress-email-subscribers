<?php
/**
 * Admin workflow actions metabox
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * Worfklow object
 *
 * @var ES_Workflow $workflow
 *
 * Workflow Action objects
 * @var ES_Workflow_Action[] $workflow_actions
 *
 * Action select box value
 * @var array $action_select_box_values
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="ig-es-actions-container">

	<?php if ( is_array( $workflow_actions ) ) : ?>
		<?php $n = 1; ?>
		<?php
		foreach ( $workflow_actions as $workflow_action ) :
			ES_Workflow_Admin::get_view(
				'action',
				array(
					'workflow'                 => $workflow,
					'action'                   => $workflow_action,
					'action_number'            => $n,
					'action_select_box_values' => $action_select_box_values,
				)
			);
			?>
			<?php $n++; ?>
		<?php endforeach; ?>
	<?php endif; ?>
	<div class="ig-es-action-template">
		<?php
		// Render blank action template.
		ES_Workflow_Admin::get_view(
			'action',
			array(
				'workflow'                 => $workflow,
				'action'                   => false,
				'action_number'            => false,
				'action_select_box_values' => $action_select_box_values,
			)
		);
		?>
	</div>

	<?php if ( empty( $workflow_actions ) ) : ?>

		<div class="js-ig-es-no-actions-message">
			<p>
				<?php
					/* translators: 1: Starting strong tag 2: Closing strong tag */
					echo sprintf( esc_html__( 'No actions found. Click the %1$s+ Add action%2$s to create an action.', 'email-subscribers' ), '<strong>', '</strong>' );
				?>
			</p>
		</div>

	<?php endif; ?>
</div>

<div class="ig-es-metabox-footer">
	<button type="button" class="js-ig-es-add-action inline-flex justify-center rounded-md border border-transparent px-4 py-1.5 bg-white text-sm leading-5 font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:shadow-outline-blue transition ease-in-out duration-150"><?php echo esc_html__( '+ Add action', 'email-subscribers' ); ?></button>
</div>

<!--Email Action Preview-->
<div class="hidden" id="workflow-email-preview-popup">
	<div class="fixed top-0 left-0 flex items-center justify-center w-full h-full" style="background-color: rgba(0,0,0,.5); z-index: 999;">
		<div id="campaign-preview-main-container" class="absolute h-auto pt-2 ml-16 mr-4 text-left bg-white rounded shadow-xl z-80 w-1/2 md:max-w-5xl lg:max-w-7xl md:pt-3 lg:pt-2">
			<div class="py-2 px-4">
					<div class="flex">
						<button id="close-workflow-email-preview-popup" class="text-sm font-medium tracking-wide text-gray-700 select-none no-outline focus:outline-none focus:shadow-outline-red hover:border-red-400 active:shadow-lg">
							<svg class="h-5 w-5 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
							</svg>
						</button>
					</div>
			</div>
			<div id="workflow-email-preview-container" class="hidden">
				<div id="campaign-browser-preview-container">
					<?php
					do_action( 'ig_es_campaign_preview_options_content', array( 'type' => 'workflow' ) );
					?>

					<div id="workflow-preview-iframe-container" class="py-4 list-decimal popup-preview">
					</div>
				</div>

			</div>
			<div id="workflow-email-preview-loader" class="p-13 text-center" style="min-width: 600px">
				<img src="<?php echo esc_attr( ES_PLUGIN_URL . 'lite/admin/images/spinner-2x.gif' ); ?>" class="inline-block es-loader pl-2 h-5 w-7"
					 alt="<?php echo esc_attr__( 'Loading...', 'email-subscribers' ); ?>"/>
			</div>
		</div>
	</div>
</div>
