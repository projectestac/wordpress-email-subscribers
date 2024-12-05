<?php
if (empty($dashboard_kpi) || !is_array($dashboard_kpi)) {
	return;
}

$allowed_html_tags = ig_es_allowed_html_tags_in_esc();

function render_kpi( $value, $kpi_percentage, $label, $allowed_html_tags) {
	$text_color_class = $kpi_percentage < 0 ? 'text-red-600' : 'text-green-600';
	$arrow_direction = $kpi_percentage < 0 
		? 'M16.707 10.293a1 1 0 00-1.414 0L11 14.586V3a1 1 0 00-2 0v11.586L4.707 10.293a1 1 0 00-1.414 1.414l6 6a1 1 0 001.414 0l6-6a1 1 0 000-1.414z' 
		: 'M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z';
	$arrow_html = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="inline-block" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="' . esc_attr($arrow_direction) . '"></path></svg>';
	?>
	<div class="p-1 mr-6 kpi-div">
		<span class="text-2xl font-bold leading-none text-indigo-600"><?php echo esc_html($value, 'email-subscribers'); ?></span>
		<div class="inline-block es-new-subscriber-growth-percentage es-tooltip relative align-middle cursor-pointer text-left">
			<?php if (0 !== $kpi_percentage) { ?>
				<span class="text-sm mr-0.5 <?php echo esc_attr($text_color_class); ?>">
					<?php echo esc_html(number_format_i18n((float) $kpi_percentage, 2)); ?>%
					<?php echo wp_kses($arrow_html, $allowed_html_tags); ?>
				</span>
			<?php } ?>
		</div>
		<p class="mt-1 font-medium leading-6 text-gray-500"><?php echo esc_html__($label, 'email-subscribers'); ?></p>
	</div>
	<?php
}
?>

<section class="flex items-center justify-between"> 
	
<?php
$args = array('days' => 30);
$subscribed_contact_growth_percentage = ES_Reports_Data::get_contacts_growth_percentage($args);
$total_active_contact_growth_percentage = ES_Reports_Data::get_total_contacts_growth_percentage($args);

render_kpi($dashboard_kpi['total_subscribed'] ?? 0, $subscribed_contact_growth_percentage, 'New Subscribers', $allowed_html_tags);
render_kpi($dashboard_kpi['total_unsubscribed'] ?? 0, $dashboard_kpi['unsubscribe_percentage_growth'] ?? 0, 'Unsubscribed', $allowed_html_tags);
render_kpi( $dashboard_kpi['total_active_contact'] ?? 0, $total_active_contact_growth_percentage ?? 0, 'Total Active', $allowed_html_tags);
render_kpi($dashboard_kpi['total_message_sent'] ?? 0, $dashboard_kpi['sent_percentage_growth'] ?? 0, 'Email Sent', $allowed_html_tags);
render_kpi($dashboard_kpi['avg_open_rate'] ?? 0, $dashboard_kpi['open_percentage_growth'] ?? 0, 'Opened', $allowed_html_tags);
render_kpi($dashboard_kpi['avg_click_rate'] ?? 0, $dashboard_kpi['click_percentage_growth'] ?? 0, 'Clicked', $allowed_html_tags);
?>
</section>
