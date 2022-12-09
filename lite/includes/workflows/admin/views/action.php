<?php
/**
 * Workflow single action
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/**
 * View args:
 *
 * @var int $action_number
 * @var ES_Workflow_Action $action
 * @var array $action_select_box_values
 * @var ES_Workflow $workflow
 */

?>

<div class="ig-es-action"
	data-action-number="<?php echo $action ? esc_attr( $action_number ) : ''; ?>">

	<div class="ig-es-action__header">
		<div class="row-options">
			<button class="js-preview-action text-indigo-600 hidden" title="<?php esc_attr_e( 'Preview', 'email-subscribers' ); ?>"><span class="dashicons dashicons-welcome-view-site"></span></button>
			<a class="js-edit-action text-indigo-600" href="#" title="<?php esc_attr_e( 'Edit', 'email-subscribers' ); ?>"><span class="dashicons dashicons-edit"></span></a>
			<a class="js-delete-action text-indigo-600" href="#" title="<?php esc_attr_e( 'Delete', 'email-subscribers' ); ?>"><span class="dashicons dashicons-trash"></span></a>
		</div>
		<h4 class="action-title"><?php echo esc_html( $action ? $action->get_title( true ) : __( 'New Action', 'email-subscribers' ) ); ?></h4>
	</div>
	<div class="ig-es-action__fields">
		<table class="ig-es-table">

			<tr class="ig-es-table__row" data-name="action_name" data-type="select" data-required="1">
				<td class="ig-es-table__col ig-es-table__col--label">
					<label><?php esc_attr_e( 'Action', 'email-subscribers' ); ?> <span class="required">*</span></label>
				</td>
				<td class="ig-es-table__col ig-es-table__col--field">

					<?php

					$action_field = new ES_Select();

					if ( $action ) {
						$action_field->set_name_base( "ig_es_workflow_data[actions][{$action_number}]" );
						$action_field->set_name( 'action_name' );
					} else {
						$action_field->set_name( '' );
					}

					$action_field->set_options( $action_select_box_values );
					$action_field->add_classes( 'ig-es-field js-action-select' );
					$action_field->render( $action ? $action->get_name() : false );

					?>

					<?php if ( $action && $action->get_description() ) : ?>
						<div class="js-action-description"><?php echo wp_kses_post( $action->get_description_html() ); ?></div>
					<?php else : ?>
						<div class="js-action-description"></div>
					<?php endif; ?>

				</td>
			</tr>
			<?php
			if ( $action ) {
				ES_Workflow_Admin::get_view(
					'action-fields',
					array(
						'workflow_action' => $action,
						'action_number'   => $action_number,
						'workflow'        => $workflow,
						'fill_fields'     => true,
					)
				);
			}
			?>
		</table>
	</div>
</div>
