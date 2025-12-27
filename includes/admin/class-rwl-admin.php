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

	public function ajax_delete_log()
	{
		check_ajax_referer('rwl_admin_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
		}

		$log_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		if ($log_id <= 0) {
			wp_send_json_error(array('message' => 'شناسه نامعتبر'));
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rwl_logs';

		$deleted = $wpdb->delete($table_name, array('id' => $log_id));

		if ($deleted) {
			wp_send_json_success(array('message' => 'رکورد حذف شد.'));
		} else {
			wp_send_json_error(array('message' => 'خطا در حذف رکورد.'));
		}
	}

	public function action_export_csv()
	{
		if (!current_user_can('manage_options')) {
			wp_die('دسترسی غیرمجاز');
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rwl_logs';
		$results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

		if (empty($results)) {
			wp_die('داده‌ای برای خروجی وجود ندارد.');
		}

		$filename = 'rwl-reports-' . date('Y-m-d') . '.csv';

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);

		$output = fopen('php://output', 'w');

		// UTF-8 BOM for Excel
		fputs($output, "\xEF\xBB\xBF");

		// Headers
		fputcsv($output, array('ID', 'شماره موبایل', 'آیتم برنده شده', 'کد تخفیف', 'آی‌پی کاربر', 'تاریخ و ساعت'));

		foreach ($results as $row) {
			fputcsv($output, array(
				$row['id'],
				$row['mobile'],
				$row['won_item'],
				$row['won_code'],
				$row['user_ip'],
				$row['created_at']
			));
		}

		fclose($output);
		exit;
	}

	public function ajax_reset_settings()
	{
		check_ajax_referer('rwl_admin_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
		}

		$default_items = array(
			array(
				'title' => '۱۰٪ تخفیف',
				'code' => 'OFF10',
				'chance' => '20',
				'color' => '#36a2eb'
			),
			array(
				'title' => 'پوچ',
				'code' => '',
				'chance' => '20',
				'color' => '#ff6384'
			),
			array(
				'title' => '۲۰٪ تخفیف',
				'code' => 'OFF20',
				'chance' => '10',
				'color' => '#ff9f40'
			),
			array(
				'title' => 'ارسال رایگان',
				'code' => 'FREESHIP',
				'chance' => '15',
				'color' => '#4bc0c0'
			),
			array(
				'title' => '۵٪ تخفیف',
				'code' => 'OFF5',
				'chance' => '25',
				'color' => '#9966ff'
			),
			array(
				'title' => 'شانس مجدد',
				'code' => 'AGAIN',
				'chance' => '10',
				'color' => '#ffcd56'
			)
		);

		$default_settings = array(
			'limit_duration' => 24,
			'global_win_chance' => 70,
			'test_mode' => 0,
			'items' => $default_items
		);

		update_option('rwl_settings', $default_settings);

		wp_send_json_success(array('message' => 'تنظیمات با موفقیت به حالت پیش‌فرض بازگشت.'));
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
		$test_mode = isset($options['test_mode']) ? $options['test_mode'] : 0;
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
