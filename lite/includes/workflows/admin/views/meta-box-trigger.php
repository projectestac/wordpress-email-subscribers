<?php
/**
 * Admin trigger metabox
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

// Group triggers.
$trigger_list = array();

foreach ( ES_Workflow_Triggers::get_all() as $trigger ) {
	if ( $trigger instanceof ES_Workflow_Trigger ) {
		$trigger_list[ $trigger->get_group() ][ $trigger->get_name() ] = $trigger;
	}
}

if ( ! ES()->is_starter() ) {
	$starter_trigger_list = array(
		'Comment' => array(
			'ig_es_comment_added' => __( 'Comment Added', 'email-subscribers' ),
		),
		'Form'    => array(
			'ig_es_cf7_submitted'              => __( 'Contact Form 7 Submitted', 'email-subscribers' ),
			'ig_es_wpforms_submitted'          => __( 'WP Form Submitted', 'email-subscribers' ),
			'ig_es_ninja_forms_submitted'      => __( 'Ninja Form Submitted', 'email-subscribers' ),
			'ig_es_gravity_forms_submitted'    => __( 'Gravity Form Submitted', 'email-subscribers' ),
			'ig_es_forminator_forms_submitted' => __( 'Forminator Form Submitted', 'email-subscribers' ),
		),
		'Order'   => array(
			'ig_es_wc_order_completed'    => __( 'WooCommerce Order Completed', 'email-subscribers' ),
			'ig_es_edd_complete_purchase' => __( 'EDD Purchase Completed', 'email-subscribers' ),
			'ig_es_give_donation_made'    => __( 'Give Donation Added', 'email-subscribers' ),
		),
	);

	$trigger_list = array_merge_recursive( $trigger_list, $starter_trigger_list );
}

if ( ! ES()->is_pro() ) {
	$pro_trigger_list = array(
		'Comment'   => array(
			'ig_es_wc_product_review_approved' => __( 'New Product Review Posted', 'email-subscribers' ),
		),
		'Order'     => array(
			'ig_es_wc_order_refunded' => __( 'WooCommerce Order Refunded', 'email-subscribers' ),
		),
		'Wishlists' => array(
			'ig_es_yith_wc_wishlist' => __( 'Wishlist Item On Sale (YITH Wishlists)', 'email-subscribers' ),
		),
		'Carts'     => array(
			'ig_es_wc_cart_abandoned'                  => __( 'Cart Abandoned', 'email-subscribers' ),
			'ig_es_wc_cart_abandoned_registered_users' => __( 'Cart Abandoned - Registered Users Only', 'email-subscribers' ),
			'ig_es_wc_cart_abandoned_guests_users'     => __( 'Cart Abandoned - Guests Only', 'email-subscribers' ),
		),
		'User'      => array(
			'ig_es_user_role_changed' => __( 'User Role Changed', 'email-subscribers' ),
		),
		'LearnDash' => array(
			'ig_es_ld_user_enrolled' => __( 'User enrolled in course', 'email-subscribers' ),
			'ig_es_ld_user_removed'  => __( 'User removed from a course', 'email-subscribers' ),
		),
		'Ultimate Member' => array(
			'ig_es_um_membership_approved' => __( 'Membership Approved', 'email-subscribers' ),
			'ig_es_um_membership_deactivated'  => __( 'Membership Deactivated', 'email-subscribers' ),
		),
		'Paid Memberships Pro' => array(
			'ig_es_pmp_membership_purchased' => __( 'Membership Purchased', 'email-subscribers' ),
			'ig_es_pmp_membership_expired'  => __( 'Membership Expired', 'email-subscribers' ),
			'ig_es_pmp_membership_canceled'  => __( 'Membership Canceled', 'email-subscribers' ),
		),
		'MemberPress' => array(
			'ig_es_mp_one_time_product_purchased' => __( 'Product Purchased - One Time', 'email-subscribers' ),
			'ig_es_mp_recurring_product_purchased'  => __( 'Product Purchased - Recurring', 'email-subscribers' ),
			'ig_es_mp_membership_expired'  => __( 'Membership Expired', 'email-subscribers' ),
			'ig_es_mp_membership_canceled'  => __( 'Membership Canceled', 'email-subscribers' ),
		),
		'WooCommerce Memberships' => array(
			'ig_es_wcm_membership_created' => __( 'Membership Created', 'email-subscribers' ),
			'ig_es_wcm_membership_expired' => __( 'Membership Expired', 'email-subscribers' ),
			'ig_es_wcm_membership_canceled'  => __( 'Membership Canceled', 'email-subscribers' ),
		),
	);
	$trigger_list     = array_merge_recursive( $trigger_list, $pro_trigger_list );
}
?>
<table class="ig-es-table">
	<tr class="ig-es-table__row" data-name="trigger_name" data-type="select"
		data-required="1">
		<td class="ig-es-table__col ig-es-table__col--label">
			<label><?php esc_html_e( 'Trigger', 'email-subscribers' ); ?> <span class="required">*</span></label>
		</td>
		<td class="ig-es-table__col ig-es-table__col--field">
			<select name="ig_es_workflow_data[trigger_name]" class="ig-es-field js-trigger-select" required>
				<option value=""><?php esc_html_e( '[Select]', 'email-subscribers' ); ?></option>
				<?php foreach ( $trigger_list as $trigger_group => $triggers ) : ?>
					<optgroup label="<?php echo esc_attr( $trigger_group ); ?>">
						<?php
						foreach ( $triggers as $trigger_name => $_trigger ) :
							if ( $_trigger instanceof ES_Workflow_Trigger ) :
								?>
								<option value="<?php echo esc_attr( $_trigger->get_name() ); ?>" <?php echo esc_attr( $current_trigger && $current_trigger->get_name() === $trigger_name ? 'selected="selected"' : '' ); ?>><?php echo esc_html( $_trigger->get_title() ); ?></option>
								<?php
							elseif ( is_string( $_trigger ) ) :
								?>
								<option value="<?php echo esc_attr( $trigger_name ); ?>" disabled><?php echo esc_html( $_trigger ); ?></option>
								<?php
							endif;
						endforeach;
						?>
					</optgroup>
				<?php endforeach; ?>
			</select>
			<div class="js-trigger-description">
				<?php if ( $current_trigger && $current_trigger->get_description() ) : ?>
					<?php echo wp_kses_post( $current_trigger->get_description_html() ); ?>
				<?php endif; ?>
			</div>
		</td>
	</tr>

	<?php

	if ( $workflow ) {
		ES_Workflow_Admin::get_view(
			'trigger-fields',
			array(
				'trigger'     => $current_trigger,
				'workflow'    => $workflow,
				'fill_fields' => true,
			)
		);
	}

	?>
</table>
