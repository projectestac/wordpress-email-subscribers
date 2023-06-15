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
				<?php
				$field = new ES_Select( false );
				$field
				->set_name_base( $option_base )
				->set_name( 'when_to_run' )
				->set_options(
					array(
						'immediately' => __( 'Run immediately', 'email-subscribers' ),
						'delayed'     => __( 'Delayed', 'email-subscribers' ),						
					)
				)
				->add_data_attr( 'ig-es-bind', 'timing' )
				->render( $workflow ? $workflow->get_timing_type() : '' );
				?>
		</td>
	</tr>
	
	<tr class="ig-es-table__row" data-ig-es-show="timing=delayed">
		<td class="ig-es-table__col">
			<div class="field-cols">
				<label class="ig-es-label"><?php esc_html_e( 'Length of the delay', 'email-subscribers' ); ?>
				</label>
				<div class="col-1">
						<?php
						$run_delay_value = new ES_Number();
						$run_delay_value
						->set_name_base( $option_base )
						->set_name( 'run_delay_value' )
						->set_min( '0' )
						->add_extra_attr( 'step', 'any' )
						->render( $workflow ? $workflow->get_option( 'run_delay_value' ) : '' );
						?>
				</div>
				<div class="col-2">
						<?php
						$run_delay_unit = new ES_Select();
						$run_delay_unit
						->set_name_base( $option_base )
						->set_name( 'run_delay_unit' )
						->set_options(
							[
								'h'     => __( 'Hours', 'email-subscribers' ),
								'm'     => __( 'Minutes', 'email-subscribers' ),
								'd'     => __( 'Days', 'email-subscribers' ),
								'w'     => __( 'Weeks', 'email-subscribers' ),
							]
						)
						->render( $workflow ? $workflow->get_option( 'run_delay_unit' ) : '' );
						?>
				</div>
			</div>
		</td>
	</tr>		
</table>
