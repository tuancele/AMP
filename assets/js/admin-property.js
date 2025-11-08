jQuery(document).ready(function($) {
    'use strict';

    // ==================================================
    // 1. LOGIC TẢI HOTSPOT ĐỘNG (ĐỌC DỮ LIỆU TỪ `wp_localize_script`)
    // ==================================================
    
    // Kiểm tra xem đối tượng `tuancele_hotspot_data` có tồn tại không.
    if (typeof tuancele_hotspot_data === 'undefined') {
        console.error('Lỗi: Dữ liệu tuancele_hotspot_data (chứa Nonce) không được tải. Logic hotspot sẽ không chạy.');
        
        const hotspotWrapper = $('#hotspot-selector-wrapper');
        if(hotspotWrapper.length > 0) {
            hotspotWrapper.show();
            $('#_property_hotspot_name').prop('disabled', true).html('<option value="">— Lỗi tải script (F12) —</option>');
        }
        
    } else {
        // Nếu đối tượng tồn tại, gán các biến
        const savedHotspotName = tuancele_hotspot_data.saved_name;
        const hotspotNonce = tuancele_hotspot_data.nonce;
        const ajaxurl = tuancele_hotspot_data.ajax_url;

        const projectDropdown = $('#_project_id');
        const hotspotWrapper = $('#hotspot-selector-wrapper');
        const hotspotSelect = $('#_property_hotspot_name');

        function loadHotspots(projectId) {
            if (!projectId || projectId === "") {
                hotspotWrapper.hide();
                hotspotSelect.html(''); 
                return;
            }
            
            hotspotSelect.prop('disabled', true).html('<option value="">— Đang tải mặt bằng... —</option>');
            hotspotWrapper.show();

            // ==================================================
            // [SỬA LỖI 400 - FINAL] Sửa lại tên 'action'
            // ==================================================
            $.post(ajaxurl, {
                action: 'load_project_hotspots', // <-- ĐÃ SỬA LỖI (Bỏ tiền tố 'tuancele_ajax_')
                project_id: projectId,
                security: hotspotNonce 
            }, function(response) {
                if (response.success) {
                    if (response.data.hotspots && response.data.hotspots.length > 0) {
                        hotspotSelect.prop('disabled', false).html('<option value="">— Chọn vị trí căn —</option>');
                        response.data.hotspots.forEach(function(name) {
                            hotspotSelect.append($('<option>', {
                                value: name,
                                text: name,
                                selected: (name === savedHotspotName)
                            }));
                        });
                    } else {
                        hotspotSelect.prop('disabled', true).html('<option value="">— Dự án này không có mặt bằng —</option>');
                    }
                } else {
                    hotspotSelect.prop('disabled', true).html('<option value="">— Lỗi: ' + (response.data.message || 'Không tải được') + ' —</option>');
                }
            })
            .fail(function(jqXHR) {
                let errorMsg = 'Lỗi máy chủ ' + jqXHR.status;
                if (jqXHR.status === 400 || jqXHR.status === 403) {
                    errorMsg = 'Lỗi bảo mật (Nonce) hoặc request không hợp lệ. Hãy Hard Refresh (Ctrl+Shift+R).';
                }
                hotspotSelect.prop('disabled', true).html('<option value="">— ' + errorMsg + ' —</option>');
            });
        }
        
        projectDropdown.on('change', function() {
            loadHotspots($(this).val());
        });

        if (projectDropdown.val() && projectDropdown.val() !== "") {
            loadHotspots(projectDropdown.val());
        }
    } // Kết thúc khối logic hotspot

    // ==================================================
    // 2. LOGIC MEDIA UPLOADER (TỪ CODE GỐC CỦA BẠN)
    // ==================================================
    if (typeof wp === 'undefined' || !wp.media) {
        console.error('wp.media không tồn tại.');
        return; 
    }

    // --- Các hàm Helper JS ---
    function js_esc_attr(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/'/g, '&#39;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function js_esc_url(url) {
        if (typeof url !== 'string') return '';
        return url.replace(/'/g, '%27').replace(/"/g, '%22');
    }

    $('.property-image-gallery-uploader').each(function() {
        const uploader = $(this);
        const hiddenInput = uploader.find('input[type=hidden]');
        const previewDiv = uploader.find('.image-gallery-preview');
        const removeBtn = uploader.find('.remove-gallery-button');
        let mediaFrame;

        previewDiv.sortable({
            items: 'img',
            update: function(event, ui) { updateHiddenInput(); }
        });

        function updateHiddenInput() {
            const ids = previewDiv.find('img').map(function() {
                return $(this).data('id');
            }).get();
            hiddenInput.val(ids.join(','));
        }

        uploader.on('click', '.upload-gallery-button', function(e) {
            e.preventDefault();
            const currentIDs = hiddenInput.val().split(',').filter(Number).map(Number);
            if (mediaFrame) { mediaFrame.open(); return; }

            mediaFrame = wp.media({
                title: 'Chọn ảnh cho Slider',
                button: { text: 'Sử dụng các ảnh này' },
                multiple: 'add',
                library: { type: 'image' }
            });

            mediaFrame.on('open', function() {
                const selection = mediaFrame.state().get('selection');
                currentIDs.forEach(function(id) {
                    const attachment = wp.media.attachment(id);
                    attachment.fetch();
                    selection.add(attachment ? [attachment] : []);
                });
            });

            mediaFrame.on('select', function() {
                const selection = mediaFrame.state().get('selection');
                let previewHtml = '';
                let newIds = [];
                selection.map(function(attachment) {
                    attachment = attachment.toJSON();
                    if (attachment.id) {
                        newIds.push(attachment.id);
                        const thumbUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                        previewHtml += '<img src="' + js_esc_url(thumbUrl) + '" style="width: 75px; height: 75px; object-fit: cover; border-radius: 3px;" data-id="' + js_esc_attr(String(attachment.id)) + '">';
                    }
                });
                hiddenInput.val(newIds.join(','));
                previewDiv.html(previewHtml);
                removeBtn.show();
            });
            mediaFrame.open();
        });

        uploader.on('click', '.remove-gallery-button', function(e) {
            e.preventDefault();
            if (confirm('Bạn có chắc muốn xóa tất cả ảnh slider?')) {
                hiddenInput.val('');
                previewDiv.html('');
                removeBtn.hide();
            }
        });
    });
});