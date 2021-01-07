<?php
/**
 * Workflow single action fields
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

/**
 * View args:
 *
 * @var ES_action $workflow_action
 * @var ES_Workflow $workflow
 * @var $fill_fields (optional)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! $workflow_action || ! $action_number ) {
	return;
}

// Default to false.
if ( ! isset( $fill_fields ) ) {
	$fill_fields = false;
}

if ( $fill_fields ) {
	$workflow_action = $workflow->get_action( $action_number );
}

$fields = $workflow_action->get_fields();
?>

<?php
foreach ( $fields as $field ) :

	// add action number to name base.
	$field->set_name_base( "ig_es_workflow_data[actions][$action_number]" );

	if ( $fill_fields ) {
		$value = $workflow_action->get_option_raw( $field->get_name() );
	} else {
		$value = '';
	}
	?>

	<tr class="ig-es-table__row"
		data-name="<?php echo esc_attr( $field->get_name() ); ?>"
		data-type="<?php echo esc_attr( $field->get_type() ); ?>"
		data-required="<?php echo (int) $field->get_required(); ?> ">

		<td class="ig-es-table__col ig-es-table__col--label">
			<?php
			if ( 'checkbox' !== $field->get_type() ) :
				?>
			<label><?php echo esc_html( $field->get_title() ); ?>
				<?php if ( $field->get_required() ) : ?>
					<span class="required">*</span>
				<?php endif; ?>
			</label>
				<?php
			endif;
			?>
		</td>

		<td class="ig-es-table__col ig-es-table__col--field ig-es-field-wrap">
			<?php $field->render( $value ); ?>
			<?php if ( $field->get_description() ) : ?>
				<p class="ig-es-field-description">
				<?php echo wp_kses_post( $field->get_description() ); ?>
				</p>
			<?php endif; ?>
		</td>
	</tr>

<?php endforeach; ?>
