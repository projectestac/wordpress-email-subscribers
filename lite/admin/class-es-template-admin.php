<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Template_Admin extends ES_Admin {

	public static $instance;

	public function __construct() {
		$this->init();
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function show_merge_tags( $template_type ) {
		$subscriber_tags = $this->get_subscriber_tags();
		if ( ! empty( $subscriber_tags ) ) {
			?>
				<div id="ig-es-subscriber-tags">
						<?php
								$this->render_merge_tags( $subscriber_tags );
						?>
				</div>
				<?php
		}
		$site_tags = $this->get_site_tags();
		if ( ! empty( $site_tags ) ) {
			?>
				<div id="ig-es-site-tags">
						<?php
								$this->render_merge_tags( $site_tags );
						?>
				</div>
				<?php
		}
		$template_tags = $this->get_campaign_tags();
		if ( ! empty( $template_tags ) ) {
			?>
			<div id="ig-es-campaign-tags">
			<?php foreach ($template_tags as $type => $tags ) : ?>
					<?php
							$class = $type !== $template_type ? 'hidden' : '';
					?>
					<div class="ig-es-campaign-tags <?php echo esc_attr( $type ); ?> <?php echo esc_attr( $class ); ?>">
									<?php
											
											$this->render_merge_tags( $tags );
									?>
					</div>
			<?php endforeach; ?>
			</div>
			<?php
		}
	}
}

ES_Template_Admin::get_instance();
