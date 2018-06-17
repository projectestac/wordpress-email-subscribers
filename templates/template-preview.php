<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

$did = isset($_GET['did']) ? $_GET['did'] : '0';
es_cls_security::es_check_number($did);

$template_type = get_post_meta( $did, 'es_template_type', true );
?>
<style type="text/css">
	.es-sidebar {
		width: 23%;
	    background-color: rgb(230, 230, 230);
	    padding:15px;
	    border-right: 1px solid #bdbdbd;
	}
	.es-preview {
	    float: left;
		padding:15px;
		width: 70%;
	}
</style>

<div class="wrap">
	<div class="tool-box">
		<div class="es-main" style="display:inline-flex;">
			<div class="es-sidebar">
				<h2 style="margin-bottom:1em;">
					<?php echo __( 'Template Preview', ES_TDOMAIN ); ?>
					<a class="add-new-h2" target="_blank" href="<?php echo ES_FAV; ?>"><?php echo __( 'Help', ES_TDOMAIN ); ?></a>
				</h2>
				<p>
					<a class="button-primary" target="_blank" href="<?php echo admin_url(); ?>post.php?post=<?php echo $did; ?>&action=edit"><?php echo __( 'Edit', ES_TDOMAIN ); ?></a>
				</p>
				<p>
					<?php
						echo __( 'This is how your email may look.', ES_TDOMAIN );
						if ( $template_type == 'Post Notification' ) {
							echo __( '<br><br>This Post Notification preview has replaced keywords from your last published blog post.', ES_TDOMAIN );
						}
						echo __( '<br><br>Note: Different email services (like gmail, yahoo etc) display email content differently. So there could be a slight variation on how your customer will view the email content.', ES_TDOMAIN );

					?>
				</p>
			</div>
			<div class="es-preview">

				<?php
					$preview = es_cls_templates::es_template_select($did);
					$es_templ_body = $preview["es_templ_body"];

					if ( $template_type == 'Post Notification' ) {
						//Query recent published post in descending order
						$args = array( 'numberposts' => '1', 'order' => 'DESC','post_status' => 'publish' );
						$recent_posts = wp_get_recent_posts( $args );
						//Now lets do something with these posts
						foreach( $recent_posts as $recent ) {

							$post_id = $recent['ID'];

							$post_date = $recent['post_modified'];
							$es_templ_body = str_replace('{{DATE}}', $post_date, $es_templ_body);

							$post_title = $recent['post_title'];
							$es_templ_body = str_replace('{{POSTTITLE}}', $post_title, $es_templ_body);

							$post_link = get_permalink($post_id);
							$es_templ_body = str_replace('{{POSTLINK}}', $post_link, $es_templ_body);

							// Size of {{POSTIMAGE}}
							$post_thumbnail  = "";
							$post_thumbnail_link  = "";
							if ( (function_exists('has_post_thumbnail')) && (has_post_thumbnail($post_id)) ) {
								$es_post_image_size = get_option( 'ig_es_post_image_size', 'full' );
								switch ( $es_post_image_size ) {
									case 'full':
										$post_thumbnail = get_the_post_thumbnail( $post_id, 'full' );
										break;
									case 'medium':
										$post_thumbnail = get_the_post_thumbnail( $post_id, 'medium' );
										break;
									case 'thumbnail':
										$post_thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );
										break;
								}
							}

							if($post_thumbnail != "") {
								$post_thumbnail_link = "<a href='".$post_link."' target='_blank'>".$post_thumbnail."</a>";
							}
							$es_templ_body = str_replace('{{POSTIMAGE}}', $post_thumbnail_link, $es_templ_body);

							// Get post description
							$post_description_length = 50;
							$post_description = $recent['post_content'];
							$post_description = strip_tags(strip_shortcodes($post_description));
							$words = explode(' ', $post_description, $post_description_length + 1);
							if(count($words) > $post_description_length) {
								array_pop($words);
								array_push($words, '...');
								$post_description = implode(' ', $words);
							}
							$es_templ_body = str_replace('{{POSTDESC}}', $post_description, $es_templ_body);

							// Get post excerpt
							$post_excerpt = $recent['post_excerpt'];
							$es_templ_body = str_replace('{{POSTEXCERPT}}', $post_excerpt, $es_templ_body);

							// get post author
							$post_author_id = $recent['post_author'];
							$post_author = get_the_author_meta( 'display_name' , $post_author_id );
							$es_templ_body = str_replace('{{POSTAUTHOR}}', $post_author, $es_templ_body);

							if($post_link != "") {
								$post_link_with_title = "<a href='".$post_link."' target='_blank'>".$post_title."</a>";
								$es_templ_body = str_replace('{{POSTLINK-WITHTITLE}}', $post_link_with_title, $es_templ_body);

								$post_link = "<a href='".$post_link."' target='_blank'>".$post_link."</a>";
							}
							$es_templ_body = str_replace('{{POSTLINK-ONLY}}', $post_link, $es_templ_body);

							// Get full post
							$post_full = $recent['post_content'];
							$post_full = wpautop($post_full);
							$es_templ_body = str_replace('{{POSTFULL}}', $post_full, $es_templ_body);

						}
					}

					$es_templ_body = es_cls_registerhook::es_process_template_body($es_templ_body, $did);
					echo stripslashes($es_templ_body);
				?>
			</div>
			<div style="clear:both;"></div>
		</div>
	</div>
</div>