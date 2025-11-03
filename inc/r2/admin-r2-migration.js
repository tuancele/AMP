// File: /assets/js/admin-r2-migration.js

jQuery(document).ready(function($) {
    'use strict';

    const tool = $('#r2-migration-tool');
    if (tool.length === 0) {
        return; // Thoát nếu không tìm thấy công cụ
    }

    // tuanceleR2Data được truyền từ PHP qua wp_localize_script
    if (typeof tuanceleR2Data === 'undefined' || !tuanceleR2Data.nonce) {
        console.error('Lỗi: Dữ liệu nonce không được truyền từ PHP.');
        $('#r2-migration-status').text('Lỗi cấu hình script. Vui lòng kiểm tra Console.');
        return;
    }
    
    const nonce = tuanceleR2Data.nonce;
    const ajaxurl = tuanceleR2Data.ajax_url;
    let statusInterval;

    console.log('R2 Migration Script Loaded. Nonce:', nonce);

    function updateStatus() {
        $.post(ajaxurl, { 
            action: 'tuancele_r2_get_migration_status', 
            _wpnonce: nonce
        })
        .done(function(response) {
            if (!response.success) {
                clearInterval(statusInterval);
                let errorMsg = response.data && response.data.message ? response.data.message : 'Lỗi không xác định.';
                $('#r2-migration-status').html('<span style="color:red;">Lỗi lấy trạng thái: ' + errorMsg + '</span>');
                return;
            }
            const status = response.data;
            const progressBar = $('#r2-progress-bar');
            const statusBar = $('#r2-migration-status');
            const startBtn = $('#start-r2-migration');
            const cancelBtn = $('#cancel-r2-migration');

            if (status.running) {
                startBtn.prop('disabled', true);
                cancelBtn.prop('disabled', false);
                let percentage = status.total > 0 ? Math.round((status.processed / status.total) * 100) : 0;
                statusBar.text('Đang xử lý... (' + status.processed + ' / ' + status.total + ' tệp)');
                progressBar.css('width', percentage + '%').text(percentage + '%');
            } else {
                cancelBtn.prop('disabled', true);
                clearInterval(statusInterval);
                 if (status.total > 0 && status.processed >= status.total && status.total === status.processed) {
                     statusBar.text('🎉 Hoàn tất! Đã di chuyển ' + status.total + ' tệp.');
                     progressBar.css('width', '100%').text('100%');
                     startBtn.hide();
                 } else {
                    statusBar.text('Sẵn sàng để di chuyển hoặc đã bị hủy.');
                    if (parseInt(startBtn.text().replace(/[^0-9]/g, '')) > 0) {
                        startBtn.prop('disabled', false);
                    }
                 }
            }
        })
        .fail(function(jqXHR) {
            clearInterval(statusInterval);
            $('#r2-migration-status').html('<span style="color:red;">Lỗi ' + jqXHR.status + '! Yêu cầu bị máy chủ từ chối.</span>');
        });
    }

    $('#start-r2-migration').on('click', function() {
        $(this).prop('disabled', true).text('Đang khởi tạo...');
        $.post(ajaxurl, { 
            action: 'tuancele_r2_start_migration', 
            _wpnonce: nonce
        })
        .done(function(response) {
            if(response.success) {
                updateStatus();
                statusInterval = setInterval(updateStatus, 5000);
            } else {
                let errorMsg = response.data && response.data.message ? response.data.message : 'Không rõ nguyên nhân.';
                alert('Lỗi khởi tạo: ' + errorMsg);
                $('#start-r2-migration').prop('disabled', false).text('Bắt đầu Di chuyển');
            }
        });
    });

    $('#cancel-r2-migration').on('click', function() {
        if (!confirm('Bạn có chắc muốn hủy bỏ quá trình di chuyển?')) return;
        $(this).prop('disabled', true).text('Đang hủy...');
        $.post(ajaxurl, { 
            action: 'tuancele_r2_cancel_migration', 
            _wpnonce: nonce
        })
        .done(function() {
            updateStatus();
        });
    });
    
    updateStatus();
    if ($('#cancel-r2-migration').is(':enabled')) {
        statusInterval = setInterval(updateStatus, 5000);
    }
});