<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IG_ES_Variable object
 *
 * @var IG_ES_Variable $variable
 */

?>

	<div class="ig-es-modal__header text-center">
		<h1><?php echo esc_html( $variable->get_name() ); ?></h1>
	</div>

	<div class="ig-es-modal__body">
		<div class="ig-es-modal__body-inner pt-2">

			<?php if ( $variable->get_description() ) : ?>
				<p class="mb-2"><?php echo esc_html( $variable->get_description() ); ?></p>
			<?php endif; ?>

			<table class="ig-es-table ig-es-table--bordered ig-es-workflow-variable-parameters-table">

				<?php foreach ( $variable->get_parameter_fields() as $field ) : ?>

					<tr class="ig-es-table__row ig-es-workflow-variables-parameter-row"
						data-parameter-name="<?php echo esc_attr( $field->get_name() ); ?>"
						<?php
						if ( isset( $field->meta['show'] ) ) :
							?>
							data-parameter-show="<?php echo esc_attr( $field->meta['show'] ); ?>"<?php endif; ?>
						<?php echo ( $field->get_required() ? 'data-is-required="true"' : '' ); ?>
					>

						<td class="ig-es-table__col ig-es-table__col--label">
							<strong><?php echo esc_html( ucfirst( $field->get_name() ) ); ?></strong>
							<?php
							if ( $field->get_required() ) :
								?>
								<span class="required">*</span><?php endif; ?>
						</td>
						<td class="ig-es-table__col ig-es-table__col--field">
							<?php $field->add_classes( 'ig-es-workflow-variable-parameter' ); ?>
							<?php $field->render( '' ); ?>
							<p class="field-desciption mb-2 text-xs italic font-normal leading-snug text-gray-500 helper">
								<?php echo $field->get_description() ? esc_html( $field->get_description() ) : ''; ?>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>

				<?php if ( $variable->use_fallback ) : ?>
					<tr class="ig-es-table__row">
						<td class="ig-es-table__col ig-es-table__col--label">
							<strong><?php echo esc_html__( 'Fallback', 'email-subscribers' ); ?></strong>
						</td>
						<td class="ig-es-table__col ig-es-table__col--field">
							<input type="text" name="fallback" class="ig-es-field ig-es-field--type-text ig-es-workflow-variable-parameter">
							<p class="field-desciption mb-2 text-xs italic font-normal leading-snug text-gray-500 helper">
								<?php echo esc_html__( 'Entered text is displayed when there is no value found.', 'email-subscribers' ); ?>
							</p>
						</td>
					</tr>
				<?php endif; ?>

			</table>

			<div class="ig-es-workflow-variable-clipboard-form">
				<div id="ig_es_workflow_variable_preview_field" class="ig-es-workflow-variable-preview-field w-full p-3 text-center mt-2 bg-gray-100 ig-es-workflow-variable-preview-field hidden" data-variable="<?php echo esc_attr( $variable->get_name() ); ?>">
				</div>
				<button type="button" class="mt-2 ig-es-clipboard-btn w-full inline-flex justify-center rounded-md border border-transparent px-4 py-1 bg-white text-sm leading-5 font-medium text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:shadow-outline-blue transition ease-in-out duration-150"><?php echo esc_html__( 'Copy to clipboard', 'email-subscribers' ); ?></button>
			</div>

		</div>
	</div>
