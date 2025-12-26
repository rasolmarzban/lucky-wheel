<?php

/**
 * Plugin Name:       RSD Lucky Wheel
 * Plugin URI:        https://example.com/rsd-lucky-wheel
 * Description:       یک پلاگین پیشرفته گردونه شانس با قابلیت احراز هویت پیامکی و گزارش‌گیری دقیق.
 * Version:           1.0.0
 * Author:            rasolmarzban
 * Author URI:        https://example.com/
 * Text Domain:       rsd-lucky-wheel
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('RSD_LUCKY_WHEEL_VERSION', '1.0.0');

/**
 * Define plugin paths
 */
define('RWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RWL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_rsd_lucky_wheel()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-rwl-activator.php';
    RWL_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_rsd_lucky_wheel()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-rwl-deactivator.php';
    RWL_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_rsd_lucky_wheel');
register_deactivation_hook(__FILE__, 'deactivate_rsd_lucky_wheel');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-rwl-main.php';

/**
 * Begins execution of the plugin.
 */
function run_rsd_lucky_wheel()
{
    $plugin = new RWL_Main();
    $plugin->run();
}
run_rsd_lucky_wheel();
