<?php

/**
 * The admin-specific functionality of the plugin.
 */
class RWL_Admin
{

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . '../../assets/css/admin.css', array(), $this->version, 'all');
	}

	public function enqueue_scripts()
	{
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . '../../assets/js/admin.js', array('jquery'), $this->version, false);

		wp_localize_script($this->plugin_name, 'rwl_admin_obj', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('rwl_admin_nonce'),
		));
	}

	public function ajax_test_sms()
	{
		check_ajax_referer('rwl_admin_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
		}

		$mobile = sanitize_text_field($_POST['mobile']);
		if (empty($mobile)) {
			wp_send_json_error(array('message' => 'شماره موبایل را وارد کنید.'));
		}

		// Get settings
		$sms_options = get_option('rwl_sms_settings');
		$username = isset($sms_options['username']) ? $sms_options['username'] : '';
		$password = isset($sms_options['password']) ? $sms_options['password'] : '';
		$pattern_code = isset($sms_options['pattern_code']) ? $sms_options['pattern_code'] : '';
		$sender = isset($sms_options['sender']) ? $sms_options['sender'] : '';

		if (empty($username) || empty($password)) {
			wp_send_json_error(array('message' => 'نام کاربری یا رمز عبور تنظیم نشده است.'));
		}

		require_once plugin_dir_path(dirname(__FILE__)) . 'class-rwl-melipayamak.php';
		$api = new RWL_Melipayamak($username, $password);

		$result = array();

		// Try Pattern
		if (!empty($pattern_code)) {
			$result = $api->send_by_base_number($mobile, '12345', $pattern_code);
		} elseif (!empty($sender)) {
			// Try Normal
			$text = "تست پلاگین گردونه شانس";
			$result = $api->send_sms($mobile, $sender, $text);
		} else {
			wp_send_json_error(array('message' => 'کد پترن یا شماره ارسال کننده تنظیم نشده است.'));
		}

		if ($result['status']) {
			wp_send_json_success(array(
				'message' => 'پیامک با موفقیت ارسال شد.',
				'debug' => $result['response']
			));
		} else {
			// Encode the full response to show user
			$debug_msg = isset($result['response']) ? json_encode($result['response'], JSON_UNESCAPED_UNICODE) : 'Unknown Error';
			if (isset($result['message'])) {
				$debug_msg .= ' - ' . $result['message'];
			}

			wp_send_json_error(array(
				'message' => 'خطا در ارسال: ' . $debug_msg
			));
		}
	}

	public function add_plugin_admin_menu()
	{
		add_menu_page(
			'تنظیمات گردونه شانس',
			'گردونه شانس RSD',
			'manage_options',
			'rsd-lucky-wheel',
			array($this, 'display_plugin_setup_page'),
			'dashicons-chart-pie',
			26
		);

		add_submenu_page(
			'rsd-lucky-wheel',
			'تنظیمات عمومی',
			'تنظیمات عمومی',
			'manage_options',
			'rsd-lucky-wheel',
			array($this, 'display_plugin_setup_page')
		);

		add_submenu_page(
			'rsd-lucky-wheel',
			'تنظیمات پیامک (OTP)',
			'تنظیمات پیامک',
			'manage_options',
			'rsd-lucky-wheel-sms',
			array($this, 'display_plugin_sms_page')
		);

		add_submenu_page(
			'rsd-lucky-wheel',
			'گزارشات',
			'گزارشات',
			'manage_options',
			'rsd-lucky-wheel-reports',
			array($this, 'display_plugin_reports_page')
		);
	}

	public function register_settings()
	{
		register_setting('rwl_settings_group', 'rwl_settings');
		register_setting('rwl_sms_settings_group', 'rwl_sms_settings');
	}

	public function display_plugin_setup_page()
	{
		// Get existing settings
		$options = get_option('rwl_settings');

		// Defaults
		$limit_duration = isset($options['limit_duration']) ? $options['limit_duration'] : 24;
		$global_win_chance = isset($options['global_win_chance']) ? $options['global_win_chance'] : 70;
		$items = isset($options['items']) ? $options['items'] : array();

		require_once plugin_dir_path(__FILE__) . 'views/html-rwl-settings.php';
	}

	public function display_plugin_sms_page()
	{
		// Get SMS settings
		$sms_options = get_option('rwl_sms_settings');

		$username = isset($sms_options['username']) ? $sms_options['username'] : '';
		$password = isset($sms_options['password']) ? $sms_options['password'] : '';
		$sender = isset($sms_options['sender']) ? $sms_options['sender'] : '';
		$pattern_code = isset($sms_options['pattern_code']) ? $sms_options['pattern_code'] : '';

		$otp_length = isset($sms_options['otp_length']) ? $sms_options['otp_length'] : 5;
		$otp_expiry = isset($sms_options['otp_expiry']) ? $sms_options['otp_expiry'] : 2;

		require_once plugin_dir_path(__FILE__) . 'views/html-rwl-sms-settings.php';
	}

	public function display_plugin_reports_page()
	{
		require_once plugin_dir_path(__FILE__) . 'views/html-rwl-reports.php';
	}
}
