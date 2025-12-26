<?php

/**
 * Fired during plugin activation
 */
class RWL_Activator {

	/**
	 * Create the database table for logs.
	 */
	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rwl_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			mobile varchar(20) NOT NULL,
			won_item varchar(255) NOT NULL,
			won_code varchar(100) NOT NULL,
			user_ip varchar(100) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Set default options if they don't exist
		if ( ! get_option( 'rwl_settings' ) ) {
			$default_settings = array(
				'sms_api_key' => '',
				'sms_sender' => '',
				'limit_duration' => 24, // hours
				'global_win_chance' => 70, // percent
			);
			update_option( 'rwl_settings', $default_settings );
		}
	}
}
