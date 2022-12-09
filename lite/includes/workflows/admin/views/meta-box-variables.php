<?php
/**
 * ES_Workflow object
 *
 * @var ES_Workflow $workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_page         = ( isset( $_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : '';
$workflow_action     = ( isset( $_GET['action'] ) ) ? sanitize_text_field( $_GET['action'] ) : '';
$show_variables_list = ( 'es_workflows' === $active_page && 'new' !== $workflow_action );

?>
<div id="ig-es-variable-info-popup" style="display:none">
	<div class="fixed flex inset-0 overflow-x-hidden overflow-y-auto z-50 flex justify-center w-full h-full" style="background-color: rgba(0,0,0,.5);">
		<section class="absolute flex justify-center mx-auto md:mx-auto lg:mx-auto my-12 sm:my-12 lg:my-24">
			<div
			class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
			role="dialog"
			aria-modal="true"
			aria-labelledby="modal-headline"
			>
			<span class="ig-es-close-variable-info-popup cursor-pointer"><svg class="mt-1 w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
			<div id="ig-es-workflow-variable-info-body" class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4 ig-es-loading" data-loader="<?php echo esc_url( ES_PLUGIN_URL ); ?>lite/admin/images/spinner-2x.gif">
			</div>
			</div>
		</section>
	</div>
</div>
<table class="ig-es-table">
	<tr class="ig-es-table__row">
		<td class="ig-es-table__col">
			<div id="ig-es-workflow-variables-container" class="ig-es-workflow-variables-container">
				<?php foreach ( IG_ES_Variables::get_list() as $data_type => $vars ) : ?>
					<div class="ig-es-variables-group py-1 <?php echo esc_attr( $show_variables_list ? '' : 'hidden' ); ?>" data-ig-es-variable-group="<?php echo esc_attr( $data_type ); ?>">
						<?php foreach ( $vars as $variable => $file_path ) : ?>
							<span class="ig-es-workflow-variable-outer inline-block items-center justify-center px-2 py-2 mr-2 mb-2 text-xs font-bold leading-none bg-gray-100 hover:bg-gray-300 rounded-full">
								<span class="ig-es-workflow-variable cursor-pointer" data-ig-es-variable-slug="<?php echo esc_attr( $data_type . '.' . $variable ); ?>">
								<?php echo esc_html( $data_type . '.' . $variable ); ?>
								</span>
							</span>
						<?php endforeach; ?>
						<hr>
					</div>
				<?php endforeach; ?>
				<p class="js-ig-es-no-variables-message" style="display:<?php echo esc_attr( $show_variables_list ? 'none' : 'block' ); ?>;">
					<?php echo esc_html__( 'Sorry, no placeholder tags are available for this trigger', 'email-subscribers' ); ?>
				</p>
			</div>
		</td>
	</tr>
</table>
