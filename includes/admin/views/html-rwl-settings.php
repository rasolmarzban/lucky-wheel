<div class="wrap rwl-wrap">
    <h1>تنظیمات گردونه شانس RSD</h1>
    <form method="post" action="options.php">
        <?php settings_fields('rwl_settings_group'); ?>

        <div class="rwl-card">
            <h2>تنظیمات عمومی</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">حالت تست (Test Mode)</th>
                    <td>
                        <label>
                            <input type="checkbox" name="rwl_settings[test_mode]" value="1" <?php checked(1, isset($test_mode) ? $test_mode : 0); ?> />
                            فعال‌سازی حالت تست (نمایش گردونه بدون دریافت شماره و کد تایید)
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">محدودیت زمانی (ساعت)</th>
                    <td>
                        <input type="number" name="rwl_settings[limit_duration]" value="<?php echo esc_attr($limit_duration); ?>" class="small-text" />
                        <p class="description">مدت زمانی که کاربر باید صبر کند تا دوباره بتواند شانس خود را امتحان کند.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">شانس کلی برنده شدن (درصد)</th>
                    <td>
                        <input type="number" name="rwl_settings[global_win_chance]" value="<?php echo esc_attr($global_win_chance); ?>" max="100" min="0" class="small-text" /> %
                        <p class="description">اگر روی ۷۰ باشد، یعنی ۳۰ درصد احتمال دارد که کاربر کلا پوچ بیاورد (بدون توجه به آیتم‌ها).</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="rwl-card">
            <h2>آیتم‌های گردونه</h2>
            <p class="description">مجموع شانس‌ها باید دقیقا ۱۰۰ باشد. <strong>برای تعریف آیتم «پوچ»، فیلد «کد تخفیف» را خالی بگذارید.</strong></p>
            <div id="rwl-items-wrapper">
                <?php
                if (! empty($items)) {
                    foreach ($items as $index => $item) {
                ?>
                        <div class="rwl-item-row">
                            <input type="text" name="rwl_settings[items][<?php echo $index; ?>][title]" placeholder="عنوان (مثلا ۱۰٪ تخفیف)" value="<?php echo esc_attr($item['title']); ?>" />
                            <input type="text" name="rwl_settings[items][<?php echo $index; ?>][code]" placeholder="کد تخفیف (مثلا OFF10)" value="<?php echo esc_attr($item['code']); ?>" />
                            <input type="number" class="rwl-chance-input" name="rwl_settings[items][<?php echo $index; ?>][chance]" placeholder="شانس (%)" value="<?php echo esc_attr($item['chance']); ?>" />
                            <input type="color" name="rwl_settings[items][<?php echo $index; ?>][color]" value="<?php echo esc_attr($item['color']); ?>" />
                            <button type="button" class="button rwl-remove-item">حذف</button>
                        </div>
                <?php
                    }
                }
                ?>
            </div>
            <button type="button" id="rwl-add-item" class="button button-secondary">افزودن آیتم جدید</button>
            <p>مجموع شانس فعلی: <span id="rwl-total-chance">0</span>%</p>
        </div>

        <?php submit_button(); ?>
        <button type="button" id="rwl-reset-settings" class="button button-link-delete" style="margin-top: -45px; margin-left: 10px; float: left;">بازنشانی به تنظیمات پیش‌فرض</button>
    </form>
</div>

<script type="text/template" id="rwl-item-template">
    <div class="rwl-item-row">
        <input type="text" name="rwl_settings[items][INDEX][title]" placeholder="عنوان (مثلا ۱۰٪ تخفیف)" />
        <input type="text" name="rwl_settings[items][INDEX][code]" placeholder="کد تخفیف (مثلا OFF10)" />
        <input type="number" class="rwl-chance-input" name="rwl_settings[items][INDEX][chance]" placeholder="شانس (%)" value="10" />
        <input type="color" name="rwl_settings[items][INDEX][color]" value="#ff0000" />
        <button type="button" class="button rwl-remove-item">حذف</button>
    </div>
</script>