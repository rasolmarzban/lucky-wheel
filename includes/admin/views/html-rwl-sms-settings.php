<div class="wrap rwl-wrap">
    <h1>تنظیمات پیامک (ملی پیامک)</h1>
    <p>برای ارسال کد تایید (OTP) از طریق پترن، لطفاً اطلاعات زیر را تکمیل کنید.</p>
    <p>آدرس پنل: <a href="https://www.melipayamak.com/" target="_blank">https://www.melipayamak.com/</a></p>
    
    <form method="post" action="options.php">
        <?php settings_fields( 'rwl_sms_settings_group' ); ?>
        
        <div class="rwl-card">
            <h2>اطلاعات احراز هویت</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">نام کاربری</th>
                    <td><input type="text" name="rwl_sms_settings[username]" value="<?php echo esc_attr( $username ); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">رمز عبور</th>
                    <td><input type="password" name="rwl_sms_settings[password]" value="<?php echo esc_attr( $password ); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">شماره ارسال کننده (Sender)</th>
                    <td>
                        <input type="text" name="rwl_sms_settings[sender]" value="<?php echo esc_attr( $sender ); ?>" class="regular-text" placeholder="مثلا 5000..." />
                        <p class="description">برای ارسال پیامک‌های معمولی (غیر پترن) استفاده می‌شود. اگر از پترن استفاده می‌کنید، ممکن است نیازی نباشد.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="rwl-card">
            <h2>تنظیمات OTP (رمز یکبار مصرف)</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">کد پترن (Body ID)</th>
                    <td>
                        <input type="text" name="rwl_sms_settings[pattern_code]" value="<?php echo esc_attr( $pattern_code ); ?>" class="regular-text" placeholder="مثلا 12345" />
                        <p class="description">شناسه پترن که از ملی پیامک دریافت کرده‌اید. متن پیشنهادی: <code>کد تایید شما: {0}</code></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">طول کد تایید</th>
                    <td>
                        <input type="number" name="rwl_sms_settings[otp_length]" value="<?php echo esc_attr( $otp_length ); ?>" class="small-text" min="4" max="8" />
                        <p class="description">تعداد ارقام کد تایید (پیش‌فرض: 5)</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">مدت زمان اعتبار (دقیقه)</th>
                    <td>
                        <input type="number" name="rwl_sms_settings[otp_expiry]" value="<?php echo esc_attr( $otp_expiry ); ?>" class="small-text" min="1" max="60" />
                        <p class="description">مدت زمانی که کد تایید معتبر است (پیش‌فرض: 2 دقیقه)</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="rwl-card" style="border-color: #0073aa;">
            <h2>تست ارسال پیامک</h2>
            <p>پس از ذخیره تنظیمات بالا، می‌توانید یک پیامک تست ارسال کنید تا از صحت عملکرد مطمئن شوید.</p>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">شماره موبایل تست</th>
                    <td>
                        <input type="text" id="rwl-test-mobile" class="regular-text" placeholder="09xxxxxxxxx" />
                        <button type="button" id="rwl-send-test-sms" class="button button-secondary">ارسال تست</button>
                        <span id="rwl-test-result" style="margin-right: 10px; font-weight: bold;"></span>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>
</div>
