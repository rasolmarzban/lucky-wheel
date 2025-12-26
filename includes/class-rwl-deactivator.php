<?php

/**
 * Fired during plugin deactivation
 */
class RWL_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clean up rules or keep data? Usually we keep data on deactivation.
		// Flushing rewrite rules if we had custom post types (not needed here but good practice).
		flush_rewrite_rules();
	}

}
