<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function header_part() {
	$screen = get_current_screen();
	if ( 'es_dashboard' == $screen->parent_base || 'es_template' == $screen->id || 'admin_page_es_template_preview' == $screen->parent_base ) {
		?>

		<div class="headerpart">
			<div class="esbgheader">
				<h1>Icegram Express V4.0</h1>
			</div>
		</div>
		<?php
	}
}

// add_action( 'admin_notices', 'header_part' );


?>
