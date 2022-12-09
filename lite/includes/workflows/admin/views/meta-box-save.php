<?php
/**
 * Admin workflow save metabox
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

?>
<div class="submitbox" id="submitpost">
	<table class="ig-es-table">
		<tr class="ig-es-table__row">
			<td class="ig-es-table__col">
				<div class="ig-es-input-group__input">
				<?php
				if ( $workflow ) {
					$workflow_status = $workflow->is_active() ? 1 : 0;
				} else {
					$workflow_status = 1;
				}

				$workflow_status_field = new ES_Select( false );
				$workflow_status_field->set_name( 'ig_es_workflow_data[status]' );
				$workflow_status_field->set_options(
					array(
						0 => __( 'Inactive', 'email-subscribers' ),
						1 => __( 'Active', 'email-subscribers' ),
					)
				);
				$workflow_status_field->render( $workflow_status );
				?>
				</div>
			</td>
		</tr>
	</table>
	<div id="major-publishing-actions">
		<?php
		if ( $workflow ) :
			$workflow_id = $workflow->get_id();
			$nonce       = wp_create_nonce( 'es_post_workflow' );
			?>
		<div id="delete-action">
			<?php
				echo sprintf( '<a class="submitdelete deletion" href="?page=%s&action=%s&id=%s&_wpnonce=%s" onclick="return checkDelete()">%s</a>', esc_attr( 'es_workflows' ), 'delete', esc_attr( $workflow_id ), esc_attr( $nonce ), esc_html__( 'Delete', 'email-subscribers' ) );
			?>
		</div>
			<?php
			endif;
		?>
		<div id="publishing-action">
			<?php
			$is_runnable = false;
			if ( $workflow ) :
				$is_runnable = $workflow->is_runnable();
			endif;

			$tooltip_html = ES_Common::get_tooltip_html( __( 'Performs add to list action on existing orders that match trigger conditions.', 'email-subscribers' ) );
			$allowed_tags = ig_es_allowed_html_tags_in_esc();
			?>
			<label id="run-workflow-checkbox-wrapper" class="<?php echo esc_attr( ! $is_runnable ? 'hidden' : '' ); ?>">
				<input type="checkbox" class="form-checkbox " name="run_workflow" value="yes">
				<span class="pr-3 text-gray-500 text-sm font-normal text-left">
					<?php echo esc_html__( 'Run now', 'email-subscribers' ); ?>
					<?php echo wp_kses( $tooltip_html, $allowed_tags ); ?>
				</span>
			</label>
			<button type="submit" id="publish" name="save_workflow" value="save" class="inline-flex justify-center rounded-md border border-transparent px-4 py-1 bg-white text-sm leading-5 font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:shadow-outline-blue transition ease-in-out duration-150"><?php echo esc_html__( 'Save', 'email-subscribers' ); ?></button>
		</div>
		<div class="clear"></div>
	</div>
</div>
