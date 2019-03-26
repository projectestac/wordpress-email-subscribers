<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Exporter bootstrap file
 */
class Export_Subscribers {

	/**
	 * Constructor
	 */
	public function __construct() {

		if ( isset( $_GET['report'] ) && isset( $_GET['status'] ) ) {

			$status = trim( $_GET['status'] );

			$csv = $this->generate_csv( $status );

			$file_name = strtolower( $status ) . '-' . 'contacts.csv';

			if ( empty( $csv ) ) {
				$message = __( "No data available", 'email-subscribers' );
				$this->show_message( $message, 'error' );
				exit();
			} else {
				header( "Pragma: public" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Cache-Control: private", false );
				header( "Content-Type: application/octet-stream" );
				header( "Content-Disposition: attachment; filename={$file_name};" );
				header( "Content-Transfer-Encoding: binary" );

				echo $csv;
				exit;
			}
		}

		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
	}

	public function plugin_menu() {
		add_submenu_page( null, 'Export Contacts', __( 'Export Contacts', 'email-subscribers' ), get_option( 'es_roles_subscriber', true ), 'es_export_subscribers', array( $this, 'export_subscribers_page' ) );
	}

	public function prepare_header_footer_row() {

		?>

        <tr>
            <th scope="col"><?php _e( 'No', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Contacts', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Total Contacts', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Export', 'email-subscribers' ); ?></th>
        </tr>

		<?php
	}

	public function prepare_body() {

		$export_lists = array(
			'all'      => __( 'All Contacts', 'email-subscribers' ),
			'active'   => __( 'Subscribed Contacts', 'email-subscribers' ),
			'inactive' => __( 'Unsubscribed Contacts', 'email-subscribers' )
		);

		$i = 1;
		foreach ( $export_lists as $key => $export_list ) {
			$class = '';
			if ( $i % 2 === 0 ) {
				$class = 'alternate';
			}
			$url = "admin.php?page=download_report&report=users&status={$key}";

			?>

            <tr class="<?php echo $class; ?>">
                <td><?php echo $i; ?></td>
                <td><?php _e( $export_list, 'email-subscribers' ); ?></td>
                <td><?php echo $this->count_subscribers( $key ); ?></td>
                <td><a href="<?php echo $url; ?>"><?php _e( 'Download', 'email-subscribers' ); ?></a></td>
            </tr>

			<?php
			$i ++;
		}

	}

	public function export_subscribers_page() {
		?>
        <div class="wrap">
            <h2 style="margin-bottom:1em;">
				<?php _e( 'Audience > Export Contacts', 'email-subscribers' ); ?>
                <a class="add-new-h2" href="admin.php?page=es_subscribers&action=new"><?php _e( 'Add New Contact', 'email-subscribers' ); ?></a>
                <a href="admin.php?page=es_subscribers&action=import" class="page-title-action"><?php _e( 'Import Contacts', 'email-subscribers' ); ?></a>
                <a href="admin.php?page=es_lists" class="page-title-action es-imp-button"><?php _e( 'Manage Lists', 'email-subscribers' ); ?></a>
            </h2>
            <div class="tool-box">
                <form name="frm_es_subscriberexport" method="post">
                    <table width="100%" class="widefat" id="straymanage">
                        <thead>
						<?php $this->prepare_header_footer_row(); ?>
                        </thead>
                        <tbody>
						<?php $this->prepare_body(); ?>
                        </tbody>
                        <tfoot>
						<?php $this->prepare_header_footer_row(); ?>
                        </tfoot>
                    </table>
                </form>
            </div>
        </div>
	<?php }


	/**
	 * @param string $status
	 *
	 * @return string|null
	 */
	public function count_subscribers( $status = 'all' ) {

		global $wpdb;

		switch ( $status ) {
			case 'all':
				// All Subscribers
				$sql = "SELECT COUNT(email) FROM " . IG_CONTACTS_TABLE;
				break;

			case 'active':
				// Active Subscribers
				$sql = "SELECT COUNT(email) FROM " . IG_CONTACTS_TABLE . " WHERE unsubscribed = 0 OR unsubscribed IS NULL";
				break;

			case 'inactive':
				// InActive Subscribers
				$sql = "SELECT COUNT(email) FROM " . IG_CONTACTS_TABLE . " WHERE unsubscribed = 1 ";
				break;

			case 'registered':
			case 'commented':
				// Registered/ Commented Subscribers
				$sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . "users";
				break;
		}

		return $wpdb->get_var( $sql );

	}


	/**
	 * Allow for custom query variables
	 */
	public function query_vars( $query_vars ) {
		$query_vars[] = 'download_report';

		return $query_vars;
	}

	/**
	 * Parse the request
	 */
	public function parse_request( &$wp ) {
		if ( array_key_exists( 'download_report', $wp->query_vars ) ) {
			$this->download_report();
			exit;
		}
	}

	/**
	 * Download report
	 */
	public function download_report() {
		?>

        <div class="wrap">
        <div id="icon-tools" class="icon32"></div>
        <h2>Download Report</h2>
        <p>
            <a href="?page=download_report&report=users"><?php _e( 'Export the Subscribers', 'email-subscribers' ); ?></a>
        </p>

		<?php

	}


	public function generate_csv( $status = 'all' ) {

		global $wpdb;

		ini_set( 'memory_limit', IG_MAX_MEMORY_LIMIT );
		set_time_limit( IG_SET_TIME_LIMIT );

		$email_subscribe_table = IG_CONTACTS_TABLE;
		if ( 'active' === $status ) {
			$query = "SELECT `first_name`, `last_name`, `email`, `status`, `unsubscribed`, `created_at` FROM  $email_subscribe_table WHERE unsubscribed = 0 OR unsubscribed IS NULL";
		} elseif ( 'inactive' === $status ) {
			$query = "SELECT `first_name`, `last_name`, `email`, `status`, `unsubscribed`, `created_at` FROM  $email_subscribe_table WHERE unsubscribed = 1 ";
		} else {
			$query = "SELECT `first_name`, `last_name`, `email`, `status`, `unsubscribed`, `created_at` FROM  $email_subscribe_table";
		}

		$subscribers = $wpdb->get_results( $query, ARRAY_A );

		$csv_output = '';
		if ( count( $subscribers ) > 0 ) {

			$headers = array(
				__( 'Name', 'email-subscribers' ),
				__( 'Email', 'email-subscribers' ),
				__( 'Status', 'email-subscribers' ),
				__( 'Created On', 'email-subscribers' )
			);

			$csv_output .= implode( ',', $headers );
			$csv_output .= "\n";

			foreach ( $subscribers as $key => $subscriber ) {
				$data['name']       = trim( $subscriber['first_name'] . ' ' . $subscriber['last_name'] );
				$data['email']      = trim( $subscriber['email'] );
				$data['status']     = ( $subscriber['unsubscribed'] == 1 ) ? __( 'Unsubscribed', 'email-subscribers' ) : __( 'Subscribed', 'email-subscribers' );
				$data['created_at'] = $subscriber['created_at'];

				$csv_output .= implode( ',', $data );
				$csv_output .= "\n";
			}

		}

		return $csv_output;
	}

	public function show_message( $message = '', $status = 'success' ) {

		$class = 'notice notice-success is-dismissible';
		if ( 'error' === $status ) {
			$class = 'notice notice-error is-dismissible';
		}
		echo "<div class='{$class}'><p>{$message}</p></div>";
	}


}

add_action( 'plugins_loaded', function () {
	new Export_Subscribers();
} );
// Instantiate a singleton of this plugin
