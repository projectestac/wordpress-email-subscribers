<?php
/**
 * Admin workflow options metabox
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
?>
<table class="ig-es-table">
	<tr class="ig-es-table__row">
		<td class="ig-es-table__col">
			<label class="ig-es-label"><?php echo esc_html__( 'Workflow priority', 'email-subscribers' ); ?></label>
			<?php
				$field = new ES_Number();
				$field->set_name( 'ig_es_workflow_data[priority]' );
				$field->render( $workflow ? $workflow->priority : '' );
			?>
		</td>
	</tr>
</table>
