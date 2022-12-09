<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Workflow_Action_Preview {

	/**
	 * Get email preview
	 * 
	 * @param $trigger_name
	 * @param $action
	 *
	 * @return string|null
	 */
	public static function get_preview( $trigger_name, $action ) {
		try {
			$workflow = self::load_preview_workflow( $trigger_name, $action );
			$trigger  = ES_Workflow_Triggers::get( $trigger_name );

			$required_items = $trigger->get_supplied_data_items();
			$workflow->set_data_layer( self::get_preview_data_layer( $required_items ), false );

			$workflow_action           = $workflow->get_action( 1 );
			$workflow_action->workflow = $workflow;

			return $workflow_action->load_preview();
		} catch ( Exception $exception ) {
			return $exception->getMessage();
		}
	}

	/**
	 * Initialize Workflow dynamically for preview purpose
	 *
	 * @param $trigger_name
	 * @param $action
	 *
	 * @return ES_Workflow
	 */
	public static function load_preview_workflow( $trigger_name, $action ) {
		$workflow_object                  = new stdClass();
		$workflow_object->id              = 0;
		$workflow_object->name            = 'action-preview';
		$workflow_object->title           = __( 'Action Preview', 'email-subscribers' );
		$workflow_object->trigger_name    = $trigger_name;
		$workflow_object->trigger_options = array();
		$workflow_object->rules           = array();
		$workflow_object->actions         = array( $action );
		$workflow_object->meta            = array();
		$workflow_object->status          = 'draft';
		$workflow_object->priority        = 0;
		$workflow_object->created_at      = current_time( 'mysql', true );
		$workflow_object->updated_at      = current_time( 'mysql', true );

		$workflow               = new ES_Workflow( $workflow_object );
		$workflow->preview_mode = true;

		return $workflow;
	}

	/**
	 * Get data layer for preview
	 * 
	 * @param array $required_items
	 * @return array $data_layer
	 * 
	 * @throws Exception
	 */
	public static function get_preview_data_layer( $required_items = [] ) {
		$data_layer = [];

		if ( in_array( 'user', $required_items ) ) {
			$data_layer['user'] = wp_get_current_user();
		}

		if ( in_array( 'customer', $required_items ) ) {
			$data_layer['customer'] = IG_ES_Customer_Factory::get_by_user_id( get_current_user_id() );
		}

		/**
		 * Order and order item
		 */
		if ( in_array( 'wc_order', $required_items ) || in_array( 'order_item', $required_items ) ) {
			$order       = self::get_preview_order();
			$order_items = $order->get_items();

			if ( empty( $order_items ) ) {
				throw new Exception( __( 'A valid "Order items" must exist to generate the preview.', 'email-subscribers' ) );
			}

			$data_layer['wc_order']   = $order;
			$data_layer['order_item'] = current( $order_items );
		}

		/**
		 * Product
		 */
		if ( in_array( 'product', $required_items ) ) {
			$product_ids           = self::get_preview_product_ids();
			$data_layer['product'] = wc_get_product( $product_ids[0] );
		}

		/**
		 * Cart
		 */
		if ( in_array( 'cart', $required_items ) ) {
			$cart = new IG_ES_Cart();
			$cart->set_id( 1 );
			$cart->set_total( 100 );
			$cart->set_user_id( get_current_user_id() );
			$cart->set_token();
			$cart->set_date_last_modified( new DateTime() );

			$items = [];

			foreach ( self::get_preview_product_ids() as $product_id ) {
				$product = wc_get_product( $product_id );

				// Reject products that can't be purchased
				if ( ! $product->is_purchasable() ) {
					continue;
				}

				$variation_id = 0;
				$variation    = [];

				if ( $product->is_type( 'variable' ) ) {
					$variations = $product->get_available_variations();
					if ( $variations ) {
						$variation_id = $variations[0]['variation_id'];
						$variation    = $variations[0]['attributes'];
					}
				}

				$items[ uniqid() ] = [
					'product_id'        => $product_id,
					'variation_id'      => $variation_id,
					'variation'         => $variation,
					'quantity'          => 1,
					'line_subtotal'     => (float) $product->get_price(),
					'line_subtotal_tax' => (float) wc_get_price_including_tax( $product ) - (float) $product->get_price(),
				];

			}

			$cart->set_items( $items );

			$cart->set_coupons( [
				'10off' => [
					'discount_incl_tax' => '10',
					'discount_excl_tax' => '9',
					'discount_tax'      => '1'
				]
			] );

			$data_layer['cart'] = $cart;
		}

		/**
		 * Guest
		 */
		if ( in_array( 'guest', $required_items ) ) {
			$guest = new IG_ES_Guest();
			$guest->set_email( 'guest@example.com' );
			$data_layer['guest'] = $guest;
		}

		return apply_filters( 'ig_es_get_preview_data_layer', $data_layer, $required_items );
	}

	/**
	 * Get preview products.
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	protected static function get_preview_product_ids() {
		// Cache for request since this may be called multiple times
		static $products = null;
		if ( null === $products ) {
			$product_query = new \WP_Query(
				[
					'post_type'      => 'product',
					'posts_per_page' => 4,
					'fields'         => 'ids'
				]
			);

			$products = $product_query->posts;
			if ( empty( $products ) ) {
				throw new Exception( __( 'A valid "Product" must exist to generate the preview.', 'email-subscribers' ) );
			}
		}

		return $products;
	}

	/**
	 * Get an order for preview.
	 *
	 * @param int $offset used to do multiple attempts to get a valid order
	 *
	 * @return WC_Order
	 * @throws Exception
	 */
	protected static function get_preview_order( $offset = 0 ) {

		$orders = wc_get_orders(
			[
				'type'   => 'shop_order',
				'limit'  => 1,
				'offset' => $offset,
				'return' => 'ids',
			]
		);

		if ( ! $orders ) {
			throw new Exception( __( 'A valid "Order" must exist to generate the preview.', 'email-subscribers' ) );
		}

		$order = wc_get_order( $orders[0] );

		// if the order has a blank email, it will cause issues
		if ( $order && $order->get_billing_email() ) {
			return $order;
		}

		return self::get_preview_order( $offset + 1 );
	}
}
