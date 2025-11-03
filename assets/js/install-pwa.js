// Biến để lưu trữ sự kiện beforeinstallprompt (chỉ kích hoạt trên các trình duyệt hỗ trợ)
let deferredPrompt;
const installButton = document.getElementById('pwa-install-button');
const bannerWrapper = document.querySelector('.pwa-install-banner-wrapper');

// 1. Lắng nghe sự kiện trước khi cài đặt (Before Install Prompt)
window.addEventListener('beforeinstallprompt', (e) => {
    // Ngăn lời nhắc mặc định của trình duyệt hiện ra
    e.preventDefault();
    // Lưu sự kiện để kích hoạt sau này
    deferredPrompt = e;
    
    // Chỉ hiển thị banner nếu nó chưa bị người dùng tắt (Kiểm tra amp-state)
    const ampState = JSON.parse(localStorage.getItem('amp-store:pwaStatus') || '{"bannerDismissed": false}');

    if (bannerWrapper && !ampState.bannerDismissed) {
        // Hiện banner bằng cách xóa thuộc tính 'hidden' (amp-bind sẽ xử lý)
        bannerWrapper.removeAttribute('hidden');
    }
});

// 2. Xử lý khi nhấn nút cài đặt tùy chỉnh
if (installButton) {
    installButton.addEventListener('click', (e) => {
        e.preventDefault();

        if (deferredPrompt) {
            // Hiện lời nhắc cài đặt của trình duyệt (Android)
            deferredPrompt.prompt();
            // Ẩn banner ngay lập tức
            if (bannerWrapper) bannerWrapper.setAttribute('hidden', '');
            
            // Xóa trạng thái để nó không hiện lại
            localStorage.setItem('amp-store:pwaStatus', JSON.stringify({ bannerDismissed: true }));
            
            deferredPrompt.userChoice.then((choiceResult) => {
                console.log('User choice outcome:', choiceResult.outcome);
                deferredPrompt = null;
            });
        } else {
            // Fallback cho iOS hoặc các trình duyệt không hỗ trợ: Hướng dẫn thủ công
            // Ẩn banner (bắt buộc)
            if (bannerWrapper) bannerWrapper.setAttribute('hidden', '');
            localStorage.setItem('amp-store:pwaStatus', JSON.stringify({ bannerDismissed: true }));
            
            // Hướng dẫn iOS
            alert("Để cài đặt ứng dụng, vui lòng sử dụng trình duyệt Safari và nhấn biểu tượng Chia sẻ (Share) ở dưới cùng, sau đó chọn 'Thêm vào Màn hình chính'.");
        }
    });
}

// 3. Ẩn banner nếu ứng dụng đã được cài đặt (Kiểm tra khi trang tải)
// Chúng ta sẽ dựa vào Service Worker để xác định trạng thái cài đặt, nhưng đơn giản nhất là dựa vào sự kiện appinstalled và localStorage
window.addEventListener('appinstalled', () => {
    if (bannerWrapper) {
        bannerWrapper.setAttribute('hidden', '');
        // Lưu trạng thái đã tắt vĩnh viễn
        localStorage.setItem('amp-store:pwaStatus', JSON.stringify({ bannerDismissed: true }));
    }
});