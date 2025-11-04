// File: assets/js/admin-settings.js
jQuery(document).ready(function($) {
    'use strict';

    // --- Logic cho trang Hướng dẫn Shortcode (Accordion) ---
    // Chỉ chạy nếu các box này tồn tại
    if ($('.shortcode-guide-box').length > 0) {
        $('.shortcode-guide-box h2').on('click', function(){
            $(this).next('.details').slideToggle();
        });
    }

    // --- Logic cho các trang SMTP, R2 (Toggle) ---
    // Tìm checkbox "enable"
    const mainCheckbox = $('input[type="checkbox"][name*="[enable_"]');
    if (mainCheckbox.length > 0) {
        // Tìm tất cả các hàng (tr) theo sau hàng của checkbox
        const dependentFields = mainCheckbox.closest('tr').nextAll();
        
        function toggleFields() {
            if (mainCheckbox.is(':checked')) {
                dependentFields.show();
            } else {
                dependentFields.hide();
            }
        }
        
        // Chạy lần đầu khi tải trang
        toggleFields(); 
        
        // Gán sự kiện 'change'
        mainCheckbox.on('change', toggleFields);
    }
});