<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ES_List_Table extends WP_List_Table {

	/**
	 * Perpage items
	 *
	 * @since 4.6.6
	 * @var int
	 */
	public $per_page = 10;

	/**
	 * Prepare Items
	 *
	 * @since 4.6.6
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$search_str = ig_es_get_request_data( 's' );
		$this->search_box( $search_str, 'form-search-input' );

		$per_page = $this->get_items_per_page( static::$option_per_page, 25 );
		// $per_page = $this->per_page; // Show Max 10 records per page

		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items
				'per_page'    => $per_page, // WE have to determine how many items to show on a page
			)
		);

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	/**
	 * Get Lists
	 *
	 * @param int   $per_page
	 * @param int   $current_page
	 * @param false $do_count_only
	 *
	 * @since 4.6.6
	 */
	public function get_lists( $per_page = 10, $current_page = 1, $do_count_only = false ) {

	}

	/**
	 * For Bulk actions
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_action() {

	}

	/**
	 * Hide default search box
	 *
	 * @param string $text
	 * @param string $input_id
	 *
	 * @since 4.6.6
	 */
	public function search_box( $text, $input_id ) {
	}


	/**
	 * Hide top pagination
	 *
	 * @param string $which
	 *
	 * @since 4.6.6
	 */
	public function pagination( $which ) {

		if ( 'bottom' == $which ) {
			parent::pagination( $which );
		}
	}

	/**
	 * Add Row action
	 *
	 * @param string[] $actions
	 * @param bool     $always_visible
	 * @param string   $class
	 *
	 * @return string
	 *
	 * @since 4.6.6
	 */
	protected function row_actions( $actions, $always_visible = false, $class = '' ) {
		$action_count = count( $actions );
		$i            = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions ' . $class ) . '">';
		foreach ( $actions as $action => $link ) {
			++ $i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out                          .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'email-subscribers' ) . '</span></button>';

		return $out;
	}

}
