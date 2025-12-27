<?php

/**
 * The core plugin class.
 */
class RWL_Main
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct()
	{
		if (defined('RSD_LUCKY_WHEEL_VERSION')) {
			$this->version = RSD_LUCKY_WHEEL_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'rsd-lucky-wheel';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-rwl-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/class-rwl-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/public/class-rwl-public.php';

		$this->loader = new RWL_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new RWL_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
		$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
		$this->loader->add_action('wp_ajax_rwl_test_sms', $plugin_admin, 'ajax_test_sms');
		$this->loader->add_action('wp_ajax_rwl_delete_log', $plugin_admin, 'ajax_delete_log');
		$this->loader->add_action('admin_post_rwl_export_csv', $plugin_admin, 'action_export_csv');
		$this->loader->add_action('wp_ajax_rwl_reset_settings', $plugin_admin, 'ajax_reset_settings');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 */
	private function define_public_hooks()
	{
		$plugin_public = new RWL_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		// Shortcode
		$this->loader->add_shortcode('rsd_lucky_wheel', $plugin_public, 'render_shortcode');

		// AJAX
		$this->loader->add_action('wp_ajax_rwl_send_otp', $plugin_public, 'ajax_send_otp');
		$this->loader->add_action('wp_ajax_nopriv_rwl_send_otp', $plugin_public, 'ajax_send_otp');

		$this->loader->add_action('wp_ajax_rwl_verify_spin', $plugin_public, 'ajax_verify_spin');
		$this->loader->add_action('wp_ajax_nopriv_rwl_verify_spin', $plugin_public, 'ajax_verify_spin');

		$this->loader->add_action('wp_ajax_rwl_test_spin', $plugin_public, 'ajax_test_spin');
		$this->loader->add_action('wp_ajax_nopriv_rwl_test_spin', $plugin_public, 'ajax_test_spin');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run()
	{
		$this->loader->run();
	}

	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	public function get_version()
	{
		return $this->version;
	}
}
