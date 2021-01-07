<?php
// phpcs:ignoreFile
/**
 * Can be loaded by ajax
 *
 * @var $workflow Workflow
 * @var $trigger Trigger
 * @var $fill_fields (optional)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// default to false
if ( ! isset( $fill_fields ) ) {
	$fill_fields = false;
}

if ( ! $trigger ) {
	return;
}


// if we're populating field values, get the trigger object from the workflow
// Otherwise just use the unattached trigger object

if ( $fill_fields ) {
	$trigger = $workflow->get_trigger();
}

$fields = $trigger->get_fields();
?>

	<?php 
	foreach ( $fields as $field ) :

		if ( $fill_fields ) {
			$value = $workflow->get_trigger_option( $field->get_name() );
		} else {
			$value = null;
		}
		

		?>

		<tr class="ig-es-table__row ig-es-trigger-option"
			data-name="name"
			data-type="<?php echo esc_html( $field->get_type() ); ?>"
			data-required="<?php echo (int) $field->get_required(); ?> ">

			<td class="ig-es-table__col ig-es-table__col--label">

				<?php echo esc_html( $field->get_title() ); ?>
				<?php if ( $field->get_required() ) : ?>
					<span class="required">*</span>
				<?php endif; ?>

			</td>

			<td class="ig-es-table__col ig-es-table__col--field">
				<?php $field->render( $value ); ?>
				
				<?php if ( $field->get_description() ) : ?>
					<p class="ig-es-field-description">
					<?php echo esc_html( $field->get_description() ); ?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
