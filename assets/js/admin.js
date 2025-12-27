jQuery(document).ready(function($) {
    
    function calculateTotalChance() {
        var total = 0;
        $('.rwl-chance-input').each(function() {
            var val = parseInt($(this).val()) || 0;
            total += val;
        });
        
        $('#rwl-total-chance').text(total);
        if (total > 100) {
            $('#rwl-total-chance').addClass('error');
            alert('مجموع شانس‌ها نباید بیشتر از ۱۰۰ باشد!');
        } else {
            $('#rwl-total-chance').removeClass('error');
        }
    }

    // Add Item
    $('#rwl-add-item').on('click', function() {
        var template = $('#rwl-item-template').html();
        var index = $('.rwl-item-row').length; // simple index
        // In a real repeater, we might use a timestamp or a more robust index
        // But for this simple implementation, let's just append. 
        // Note: Using length might cause index conflict if we delete items in middle. 
        // Better to use Date.now()
        
        var newIndex = Date.now();
        template = template.replace(/INDEX/g, newIndex);
        
        $('#rwl-items-wrapper').append(template);
        calculateTotalChance();
    });

    // Remove Item
    $(document).on('click', '.rwl-remove-item', function() {
        $(this).closest('.rwl-item-row').remove();
        calculateTotalChance();
    });

    // Recalculate on change
    $(document).on('change keyup', '.rwl-chance-input', function() {
        calculateTotalChance();
    });

    // Initial calculation
    calculateTotalChance();

    // Test SMS Button
    $('#rwl-send-test-sms').on('click', function() {
        var mobile = $('#rwl-test-mobile').val();
        var $btn = $(this);
        var $result = $('#rwl-test-result');
        
        if (!mobile) {
            alert('لطفا شماره موبایل را وارد کنید');
            return;
        }
        
        $btn.prop('disabled', true).text('در حال ارسال...');
        $result.text('').removeClass('rwl-success rwl-error');
        
        $.post(rwl_admin_obj.ajax_url, {
            action: 'rwl_test_sms',
            nonce: rwl_admin_obj.nonce,
            mobile: mobile
        }, function(response) {
            $btn.prop('disabled', false).text('ارسال تست');
            if (response.success) {
                $result.text(response.data.message).css('color', 'green');
                console.log('Debug:', response.data.debug);
                alert('موفق: ' + response.data.message);
            } else {
                $result.text('خطا!').css('color', 'red');
                console.error('Error:', response.data.message);
                alert(response.data.message);
            }
        });
    });

    // Handle Reset Settings
    $('#rwl-reset-settings').on('click', function() {
        if (!confirm('آیا مطمئن هستید که می‌خواهید تمام تنظیمات (رنگ‌ها، متن‌ها و شانس‌ها) را به حالت پیش‌فرض بازگردانید؟ این عملیات غیرقابل بازگشت است.')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('در حال بازنشانی...');

        $.post(rwl_admin_obj.ajax_url, {
            action: 'rwl_reset_settings',
            nonce: rwl_admin_obj.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
                $btn.prop('disabled', false).text('بازنشانی به تنظیمات پیش‌فرض');
            }
        });
    });
});
