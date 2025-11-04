<?php
// BẮT BUỘC: Thêm dòng này ở đầu file để trình duyệt hiểu đây là file JavaScript
header('Content-Type: application/javascript');

// Tăng phiên bản CACHE_NAME lên v3 để buộc cập nhật service worker
$CACHE_NAME = 'tuancele-amp-v3'; // [ĐÃ SỬA] Đổi 'const' (JS) thành '$' (PHP)

// Lấy các URL động từ WordPress
$urls_to_cache = [
    '/',
    '/site.webmanifest',
    esc_url(get_template_directory_uri() . '/css/amp-custom.min.css'),
    esc_url(get_template_directory_uri() . '/assets/icons/android-chrome-192x192.png'),
    esc_url(get_template_directory_uri() . '/assets/icons/android-chrome-512x512.png'),
    esc_url(get_template_directory_uri() . '/assets/icons/apple-touch-icon.png')
];
?>

const CACHE_NAME = '<?php echo $CACHE_NAME; ?>'; // Dòng này bây giờ sẽ hoạt động chính xác
// Chuyển mảng PHP thành mảng JavaScript
const PRECACHE_URLS = <?php echo json_encode($urls_to_cache, JSON_UNESCAPED_SLASHES); ?>;

/**
 * 1. Sự kiện Cài đặt (Install)
 * Cache các tài sản quan trọng (app shell) ngay khi cài đặt.
 */
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Service Worker: Đã mở cache và đang cache các file cơ bản.');
                return cache.addAll(PRECACHE_URLS);
            })
            .catch(err => {
                console.error('Service Worker: Không thể cache các file cơ bản', err);
            })
    );
    self.skipWaiting();
});

/**
 * 2. Sự kiện Kích hoạt (Activate)
 * Xóa các cache cũ (phiên bản cũ) để giải phóng dung lượng.
 */
self.addEventListener('activate', (event) => {
    const cacheWhitelist = [CACHE_NAME]; // Giữ lại cache phiên bản v3
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // Nếu cache name không nằm trong whitelist, xóa nó đi
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        console.log('Service Worker: Đã xóa cache cũ:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    return self.clients.claim();
});

/**
 * 3. Sự kiện Tải tài nguyên (Fetch) - ĐÃ TỐI ƯU
 * Sử dụng chiến lược cache phù hợp cho từng loại tài nguyên.
 */
self.addEventListener('fetch', (event) => {
    // Bỏ qua các yêu cầu không phải GET (ví dụ: POST, PUT...)
    if (event.request.method !== 'GET') {
        return;
    }

    // Chiến lược 1: NETWORK FIRST (cho các trang HTML)
    // Ưu tiên tải nội dung mới nhất, nếu rớt mạng thì mới dùng cache.
    // Áp dụng khi người dùng điều hướng trang.
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    // Nếu tải thành công, lưu bản mới vào cache
                    event.waitUntil(
                        caches.open(CACHE_NAME).then((cache) => {
                            if (networkResponse.ok) {
                                cache.put(event.request, networkResponse.clone());
                            }
                        })
                    );
                    return networkResponse;
                })
                .catch(() => {
                    // Nếu tải thất bại (offline), lấy từ cache
                    return caches.match(event.request);
                })
        );
        return;
    }

    // Chiến lược 2: STALE-WHILE-REVALIDATE (cho tài sản tĩnh: CSS, JS, Ảnh, Font)
    // Phản hồi ngay lập tức bằng cache (tốc độ nhanh nhất),
    // đồng thời gửi yêu cầu mạng ở chế độ nền để cập nhật cache cho lần sau.
    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                // 1. Phản hồi ngay bằng cache (nếu có)
                const fetchPromise = fetch(event.request).then((networkResponse) => {
                    // 2. Cập nhật cache ở chế độ nền
                    if(networkResponse.ok) {
                        event.waitUntil(
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(event.request, networkResponse.clone());
                            })
                        );
                    }
                    return networkResponse;
                }).catch(err => {
                    // Nếu mạng lỗi, không làm gì cả (vì đã trả về cache rồi)
                    console.log('Service Worker: Lỗi khi fetch nền:', err.message);
                });

                // Trả về cache ngay lập tức, không chờ fetch nền hoàn tất
                return cachedResponse || fetchPromise;
            })
    );
});