<?php

/**
 * The public-facing functionality of the plugin.
 */
class RWL_Public
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
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . '../../assets/css/public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . '../../assets/js/public.js', array('jquery'), $this->version, false);

        wp_localize_script($this->plugin_name, 'rwl_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('rwl_nonce'),
        ));
    }

    public function render_shortcode($atts)
    {
        // Get settings to pass slices to JS
        $options = get_option('rwl_settings');
        $items = isset($options['items']) ? $options['items'] : array();

        // Ensure items is a sequential array (list) for JSON
        if (is_array($items)) {
            $items = array_values($items);
        }

        // Pass items to JS via a global variable or data attribute
        // We'll use a data attribute on the container
        $items_json = json_encode($items);

        ob_start();
        include plugin_dir_path(__FILE__) . 'views/html-rwl-wheel.php';
        return ob_get_clean();
    }

    // AJAX: Send OTP
    public function ajax_send_otp()
    {
        check_ajax_referer('rwl_nonce', 'nonce');

        $mobile = sanitize_text_field($_POST['mobile']);

        // Validation
        if (! preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error(array('message' => 'شماره موبایل نامعتبر است.'));
        }

        // Check Rate Limit (User shouldn't request OTP too often - optional but good)
        // Check Game Limit (User shouldn't play if they are blocked by time limit)
        if ($this->is_user_limited($mobile)) {
            wp_send_json_error(array('message' => 'شما اخیرا شانس خود را امتحان کرده‌اید. لطفا بعدا تلاش کنید.'));
        }

        // Get SMS Settings
        $sms_options = get_option('rwl_sms_settings');
        $otp_length = isset($sms_options['otp_length']) ? intval($sms_options['otp_length']) : 5;
        $otp_expiry = isset($sms_options['otp_expiry']) ? intval($sms_options['otp_expiry']) : 2; // minutes

        // Generate OTP
        // Ensure length
        $min = pow(10, $otp_length - 1);
        $max = pow(10, $otp_length) - 1;
        $otp = rand($min, $max);

        // Store OTP in transient (valid for X minutes)
        set_transient('rwl_otp_' . $mobile, $otp, $otp_expiry * 60);

        // Send SMS
        $sent = $this->send_sms_melipayamak($mobile, $otp);

        if ($sent) {
            wp_send_json_success(array('message' => 'کد تایید ارسال شد.'));
        } else {
            // For development/demo purposes if SMS fails or no API key, we might want to log it.
            // wp_send_json_error( array( 'message' => 'خطا در ارسال پیامک. (Dev: Code is ' . $otp . ')' ) );
            wp_send_json_error(array('message' => 'خطا در ارسال پیامک. لطفا با پشتیبانی تماس بگیرید.'));
        }
    }

    // AJAX: Verify and Spin
    public function ajax_verify_spin()
    {
        check_ajax_referer('rwl_nonce', 'nonce');

        $mobile = sanitize_text_field($_POST['mobile']);
        $otp = sanitize_text_field($_POST['otp']);

        // Verify OTP
        $saved_otp = get_transient('rwl_otp_' . $mobile);
        if (! $saved_otp || $saved_otp != $otp) {
            wp_send_json_error(array('message' => 'کد تایید اشتباه یا منقضی شده است.'));
        }

        // Delete OTP
        delete_transient('rwl_otp_' . $mobile);

        // Double check limit
        if ($this->is_user_limited($mobile)) {
            wp_send_json_error(array('message' => 'محدودیت زمانی شما هنوز تمام نشده است.'));
        }

        // Calculate Result
        $result = $this->calculate_spin_result();

        // Log Result
        $this->log_spin($mobile, $result);

        // Set cookie for floating bar (valid for 24 hours or custom)
        // We will return the data and let JS handle the UI, but maybe set a cookie for persistence?
        // Let's rely on JS localStorage for the floating bar persistence to keep it simple and fast.

        wp_send_json_success(array(
            'result_index' => $result['index'],
            'item' => $result['item'],
            'message' => 'تبریک! شما برنده شدید.'
        ));
    }

    private function is_user_limited($mobile)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rwl_logs';

        $options = get_option('rwl_settings');
        $limit_hours = isset($options['limit_duration']) ? intval($options['limit_duration']) : 24;

        $last_spin = $wpdb->get_var($wpdb->prepare(
            "SELECT created_at FROM $table_name WHERE mobile = %s ORDER BY created_at DESC LIMIT 1",
            $mobile
        ));

        if ($last_spin) {
            $last_time = strtotime($last_spin);
            $diff = (time() - $last_time) / 3600;
            if ($diff < $limit_hours) {
                return true;
            }
        }
        return false;
    }

    private function calculate_spin_result()
    {
        $options = get_option('rwl_settings');
        $global_chance = isset($options['global_win_chance']) ? intval($options['global_win_chance']) : 70;
        $items = isset($options['items']) ? $options['items'] : array();

        if (empty($items)) {
            return array('index' => -1, 'item' => array('title' => 'خطا', 'code' => ''));
        }

        // 1. Global Win Check
        $rand_global = rand(1, 100);
        if ($rand_global > $global_chance) {
            // Lose
            // We need a "Try Again" item or we force a losing item if one exists.
            // If the user didn't define a "Try Again" item, we might have an issue.
            // Assumption: The wheel *has* losing slices defined by the user? 
            // Or we just pick a random item but tell the frontend it's a loss?
            // The user said: "lower chance = more for empty items".
            // So we need to pick a "Pooch" (Empty) item.
            // If no empty item is explicitly defined in the list, we might have a problem.
            // Strategy: We will pick an item that has "0" value or just pick a random one 
            // but usually we should pick based on the weights provided.

            // Wait, the user said: "number of chance for each discount code... total 100%".
            // AND "Global Win Chance".
            // If Global Win Chance fails, we should land on a "Lose" sector.
            // Does the wheel have a "Lose" sector?
            // If the items list sums to 100%, there is no "gap" for a lose sector unless one item is "Try Again".
            // Let's assume the user MUST add a "Try Again" item in the list if they want it to be visual.
            // OR: We override the probability.

            // Let's stick to the user's "Global Win Chance" instruction.
            // If (Global Win Chance Fail) -> Find an item with "no code" or title "Pooch".
            // If not found, just pick the one with the lowest value or highest probability?
            // Actually, usually "Global Win Chance" implies that even if I hit a 10% slice, the system might force a loss.
            // But visually the wheel must stop somewhere.
            // Let's assume standard weighted random based on the items provided.
            // If the user wants "Pooch", they should add a "Pooch" item with XX% chance.
            // But the user *specifically* asked for a separate "Global Win Chance".
            // "تعداد کلی شانس برنده شدن هم رو بتوان مشخص کرد که هر چی کم باشه برای ایتم های پوچ میافته"
            // This implies we force the result to be a "Pooch" item.
            // So I will look for an item with empty code.

            $pooch_items = array();
            foreach ($items as $key => $item) {
                if (empty($item['code'])) {
                    $pooch_items[$key] = $item;
                }
            }

            if (! empty($pooch_items)) {
                $random_key = array_rand($pooch_items);
                return array('index' => $random_key, 'item' => $items[$random_key]);
            }
        }

        // 2. Weighted Random
        $rand = rand(1, 100);
        $current = 0;
        foreach ($items as $key => $item) {
            $chance = intval($item['chance']);
            $current += $chance;
            if ($rand <= $current) {
                return array('index' => $key, 'item' => $item);
            }
        }

        // Fallback
        return array('index' => 0, 'item' => $items[0]);
    }

    private function log_spin($mobile, $result)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rwl_logs';

        $wpdb->insert(
            $table_name,
            array(
                'mobile' => $mobile,
                'won_item' => $result['item']['title'],
                'won_code' => $result['item']['code'],
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'created_at' => current_time('mysql')
            )
        );
    }

    private function send_sms_melipayamak($mobile, $otp)
    {
        // Get settings from new SMS options
        $sms_options = get_option('rwl_sms_settings');
        $username = isset($sms_options['username']) ? $sms_options['username'] : '';
        $password = isset($sms_options['password']) ? $sms_options['password'] : '';
        $pattern_code = isset($sms_options['pattern_code']) ? $sms_options['pattern_code'] : '';
        $sender = isset($sms_options['sender']) ? $sms_options['sender'] : '';

        if (empty($username) || empty($password)) {
            return true; // Dev mode if no credentials
        }

        require_once plugin_dir_path(dirname(__FILE__)) . 'class-rwl-melipayamak.php';
        $api = new RWL_Melipayamak($username, $password);

        // If pattern code is set, use it (Shared Service)
        if (! empty($pattern_code)) {
            $response = $api->send_by_base_number($mobile, strval($otp), $pattern_code);
            return $response['status'];
        }

        // Fallback to normal SMS if Sender is set
        if (! empty($sender)) {
            $text = "کد تایید شما: $otp\n" . get_bloginfo('name');
            $response = $api->send_sms($mobile, $sender, $text);
            return $response['status'];
        }

        return false;
    }
}
