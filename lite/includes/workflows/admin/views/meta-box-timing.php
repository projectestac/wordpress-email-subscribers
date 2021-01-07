<?php
/**
 * Admin workflow timing metabox
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Passed worfklow object
 *
 * @var ES_Workflow $workflow
 */

$option_base = 'ig_es_workflow_data[workflow_options]';
?>

<table class="ig-es-table">
	<tr class="ig-es-table__row">
		<td class="ig-es-table__col">
			<label class="ig-es-label"><?php esc_html_e( 'Timing', 'email-subscribers' ); ?></label>
				<?php
				$field = new ES_Select( false );
				$field
				->set_name_base( $option_base )
				->set_name( 'when_to_run' )
				->set_options(
					array(
						'immediately' => __( 'Run immediately', 'email-subscribers' ),
						'delayed'     => __( 'Delayed', 'email-subscribers' ),
						'scheduled'   => __( 'Scheduled', 'email-subscribers' ),
						'fixed'       => __( 'Fixed', 'email-subscribers' ),
					)
				)
				->add_data_attr( 'ig-es-bind', 'timing' )
				->render( $workflow ? $workflow->get_timing_type() : '' );
				?>
		</td>
	</tr>

	<tr class="ig-es-table__row" data-ig-es-show="timing=scheduled">
		<td class="ig-es-table__col">
			<label class="ig-es-label"><?php esc_html_e( 'Scheduled time', 'email-subscribers' ); ?> <span class="ig-es-label__extra"><?php esc_html_e( '(24hr)', 'email-subscribers' ); ?></span></label>
				<?php
				$field = new ES_Text( false );
				$field->set_name_base( $option_base );
				$field->set_name( 'scheduled_time' );
				$field->add_classes( 'ig-es-time-picker' );
				$field->render( $workflow ? $workflow->get_scheduled_time() : '' );
				?>
		</td>
	</tr>

	<tr class="ig-es-table__row" data-ig-es-show="timing=scheduled">
		<td class="ig-es-table__col">
			<label class="ig-es-label"><?php esc_html_e( 'Scheduled days', 'email-subscribers' ); ?> <span class="ig-es-label__extra"><?php esc_html_e( '(optional)', 'email-subscribers' ); ?></span></label>
				<?php
				$options = array();

				for ( $day = 1; $day <= 7; $day++ ) {
					$options[ $day ] = ES_Format::weekday( $day );
				}

				$field = new ES_Select( false );
				$field->set_name_base( $option_base );
				$field->set_name( 'scheduled_day' );
				$field->set_placeholder( __( '[Any day]', 'email-subscribers' ) );
				$field->set_multiple();
				$field->set_options( $options );
				$field->render( $workflow ? $workflow->get_scheduled_days() : '' );
				?>
		</td>
	</tr>
	<tr class="ig-es-table__row" data-ig-es-show="timing=fixed">
		<td class="ig-es-table__col">
			<div class="field-cols">
				<label class="ig-es-label"><?php esc_html_e( 'Date', 'email-subscribers' ); ?>
					<span class="ig-es-label__extra"><?php esc_html_e( '(24 hour time)', 'email-subscribers' ); ?></span>
				</label>
				<div class="col-1">
						<?php
						$field = new ES_Date();
						$field
						->set_name_base( $option_base )
						->set_name( 'fixed_date' )
						->render( $workflow ? $workflow->get_option( 'fixed_date' ) : '' );
						?>
				</div>
				<div class="col-2">
					<?php
					if ( $workflow && $workflow->get_option( 'fixed_time' ) ) {
						$value = ES_Clean::recursive( (array) $workflow->get_option( 'fixed_time' ) );
					} else {
						$value = array( '', '' );
					}

						$field = new ES_Time();
						$field->set_name_base( $option_base );
						$field->set_name( 'fixed_time' );
						$field->set_show_24hr_note( false );
						$field->render( $value );
					?>
				</div>
			</div>
		</td>
	</tr>
</table>
