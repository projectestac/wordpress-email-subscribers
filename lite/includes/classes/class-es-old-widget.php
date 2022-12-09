<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Old_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_text elp-widget',
			'description' => __( 'Icegram Express', 'email-subscribers' ),
		);
		parent::__construct( 'email-subscribers', __( 'Icegram Express ', 'email-subscribers' ), $widget_ops );
	}

	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['es_title'] );

		echo wp_kses_post( $args['before_widget'] );

		if ( ! empty( $title ) ) {
			echo wp_kses_post( sprintf( '%s %s %s', $args['before_title'], $title, $args['after_title'] ) );
		}

		$display_name      = isset( $instance['es_name'] ) ? esc_attr( $instance['es_name'] ) : '';
		$subscribers_group = isset( $instance['es_group'] ) ? esc_attr( $instance['es_group'] ) : '';
		$desc              = isset( $instance['es_desc'] ) ? esc_attr( $instance['es_desc'] ) : '';

		$name = strtolower( $display_name ) != 'no' ? 'yes' : '';

		$list = ES()->lists_db->get_list_by_name( $subscribers_group );
		if ( ! empty( $list ) ) {
			$list_id = $list['id'];
		}

		$data['name_visible'] = $name;
		$data['list_visible'] = 'no';
		$data['lists']        = array();
		$data['form_id']      = 0;
		$data['list']         = $list_id;
		$data['desc']         = $desc;

		ES_Shortcode::render_form( $data );

		echo wp_kses_post( $args['after_widget'] );
	}

	public function update( $new_instance, $old_instance ) {
		$instance             = $old_instance;
		$instance['es_title'] = ( ! empty( $new_instance['es_title'] ) ) ? strip_tags( $new_instance['es_title'] ) : '';
		$instance['es_desc']  = ( ! empty( $new_instance['es_desc'] ) ) ? strip_tags( $new_instance['es_desc'] ) : '';
		$instance['es_name']  = ( ! empty( $new_instance['es_name'] ) ) ? strip_tags( $new_instance['es_name'] ) : '';
		$instance['es_group'] = ( ! empty( $new_instance['es_group'] ) ) ? strip_tags( $new_instance['es_group'] ) : '';

		return $instance;
	}

	public function form( $instance ) {
		$defaults = array(
			'es_title' => '',
			'es_desc'  => '',
			'es_name'  => '',
			'es_group' => '',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		$es_title = $instance['es_title'];
		$es_desc  = $instance['es_desc'];
		$es_name  = $instance['es_name'];
		$es_group = $instance['es_group'];
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'es_title' ) ); ?>"><?php echo esc_html__( 'Widget Title', 'email-subscribers' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'es_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'es_title' ) ); ?>" type="text" value="<?php echo esc_html( $es_title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'es_desc' ) ); ?>"><?php echo esc_html__( 'Short description about subscription form', 'email-subscribers' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'es_desc' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'es_desc' ) ); ?>" type="text" value="<?php echo esc_html( $es_desc ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'es_name' ) ); ?>"><?php echo esc_html__( 'Display Name Field', 'email-subscribers' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $$this->get_field_id( 'es_name' ) ); ?>" name="<?php echo esc_attr( $$this->get_field_name( 'es_name' ) ); ?>">
				<option value="YES" <?php $this->es_selected( 'YES' == $es_name ); ?>><?php echo esc_html__( 'YES', 'email-subscribers' ); ?></option>
				<option value="NO" <?php $this->es_selected( 'NO' == $es_name ); ?>><?php echo esc_html__( 'NO', 'email-subscribers' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $$this->get_field_id( 'es_group' ) ); ?>"><?php echo esc_html__( 'Subscriber Group', 'email-subscribers' ); ?></label>
			<select class="widefat" name="<?php echo esc_attr( $$this->get_field_name( 'es_group' ) ); ?>" id="<?php echo esc_attr( $$this->get_field_id( 'es_group' ) ); ?>">
				<?php
				$groups = ES()->lists_db->get_list_id_name_map();
				if ( count( $groups ) > 0 ) {
					$i = 1;
					foreach ( $groups as $group ) {
						?>
						<option value="<?php echo esc_attr( stripslashes( $group ) ); ?>" 
												  <?php
													if ( stripslashes( $es_group ) == $group ) {
														echo 'selected="selected"';
													}
													?>
						>
							<?php echo esc_html( stripslashes( $group ) ); ?>
						</option>
						<?php
					}
				}
				?>
			</select>
		</p>
		<?php
	}

	public function es_selected( $var ) {
		if ( 1 == $var || true == $var ) {
			echo 'selected="selected"';
		}
	}
}
