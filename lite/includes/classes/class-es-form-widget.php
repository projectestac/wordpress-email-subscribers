<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Form_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct( 'email-subscribers-form', __( 'Icegram Express', 'email-subscribers' ), array( 'description' => __( 'Icegram Express Form', 'email-subscribers' ) ) );
	}

	public function widget( $args, $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';

		$title = apply_filters( 'widget_title', $title );

		echo wp_kses_post( $args['before_widget'] );

		if ( ! empty( $title ) ) {
			echo wp_kses_post( sprintf( '%s %s %s', $args['before_title'], $title, $args['after_title'] ) );
		}

		$form_id = isset( $instance['form_id'] ) ? esc_attr( $instance['form_id'] ) : 0;

		$form_data = array();
		if ( ! empty( $form_id ) ) {

			$form = ES()->forms_db->get_form_by_id( $form_id );
			if ( $form ) {
				$form_data = ES_Forms_Table::get_form_data_from_body( $form );
			}
		}

		$data = array();

		$data['form_id']            = $form_id;
		$data['list']               = '';
		$data['name_visible']       = ( ! empty( $form_data['name_visible'] ) && 'yes' === $form_data['name_visible'] ) ? 'yes' : '';
		$data['name_required']      = ( ! empty( $form_data['name_required'] ) && 'yes' === $form_data['name_required'] ) ? 'yes' : '';
		$data['list_visible']       = ( ! empty( $form_data['list_visible'] ) && 'yes' === $form_data['list_visible'] ) ? 'yes' : '';
		$data['list_label']       = ! empty( $form_data['list_label'] ) ? $form_data['list_label'] : __( 'Select list(s)', 'email-subscribers' );
;
		$data['lists']              = ( ! empty( $form_data['lists'] ) ) ? $form_data['lists'] : array();
		$data['desc']               = ( ! empty( $form_data['desc'] ) ) ? $form_data['desc'] : '';
		$data['name_label']         = ( ! empty( $form_data['name_label'] ) ) ? $form_data['name_label'] : '';
		$data['name_place_holder']  = ( ! empty( $form_data['name_place_holder'] ) ) ? $form_data['name_place_holder'] : '';
		$data['email_label']        = ( ! empty( $form_data['email_label'] ) ) ? $form_data['email_label'] : '';
		$data['email_place_holder'] = ( ! empty( $form_data['email_place_holder'] ) ) ? $form_data['email_place_holder'] : '';
		$data['button_label']       = ( ! empty( $form_data['button_label'] ) ) ? $form_data['button_label'] : '';
		$data['form_version']       = ( ! empty( $form_data['form_version'] ) ) ? $form_data['form_version'] : '';
		$data['gdpr_consent']       = ( ! empty( $form_data['gdpr_consent'] ) ) ? $form_data['gdpr_consent'] : 'no';
		$data['gdpr_consent_text']  = ( ! empty( $form_data['gdpr_consent_text'] ) ) ? $form_data['gdpr_consent_text'] : '';
		$data['captcha']            = ( ! empty( $form_data['captcha'] ) ) ? $form_data['captcha'] : 'no';

		if ( ! empty ( $form_data['custom_fields'] ) ) {
			$data['custom_fields'] 		= ( ! empty( $form_data['custom_fields' ] ) ) ? $form_data['custom_fields' ] : '';
		}

		if ( ! empty( $form_data['settings'] ) ) {
			$data['settings']['editor_type']    = ! empty( $form_data['settings']['editor_type'] ) ? $form_data['settings']['editor_type'] : array();
			$data['settings']['dnd_editor_css'] = ! empty( $form_data['settings']['dnd_editor_css'] ) ? $form_data['settings']['dnd_editor_css'] : array();
			$data['settings']['lists']          = ! empty( $form_data['settings']['lists'] ) ? $form_data['settings']['lists'] : array();
		}

		if ( ! empty( $form_data['body'] ) ) {
			$data['body'] = ! empty( $form_data['body'] ) ? $form_data['body'] : '';
		}

		ES_Shortcode::render_form( $data );

		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$selected_form_id = isset( $instance['form_id'] ) ? esc_attr( $instance['form_id'] ) : '';
		$title            = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Widget Title:', 'email-subscribers' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="widget-email-subscribers-2-es_group"><?php esc_html_e( 'Forms', 'email-subscribers' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'form_id' ) ); ?>" class="widefat" style="width:100%;">
				<?php
				$form_dropdown = ES_Common::prepare_form_dropdown_options( $selected_form_id, null );
				$allowedtags   = ig_es_allowed_html_tags_in_esc();
				echo wp_kses( $form_dropdown, $allowedtags );
				?>
			</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance            = array();
		$instance['title']   = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['form_id'] = ( ! empty( $new_instance['form_id'] ) ) ? strip_tags( $new_instance['form_id'] ) : '';

		return $instance;
	}
}
