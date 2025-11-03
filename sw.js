const CACHE_NAME = 'tuancele-amp-v2'; // Thay đổi phiên bản khi cập nhật nội dung
const urlsToCache = [
    '/',
    '/site.webmanifest',
    // Thêm các file CSS, JS tĩnh, và ảnh logo quan trọng
    '<?php echo esc_url(get_template_directory_uri() . '/css/amp-custom.min.css'); ?>',
    '<?php echo esc_url(get_template_directory_uri() . '/assets/icons/android-chrome-192x192.png'); ?>'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Service Worker: Cache opened.');
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Trả về từ cache nếu có
                if (response) {
                    return response;
                }
                // Nếu không có, fetch từ mạng
                return fetch(event.request);
            })
    );
});

self.addEventListener('activate', (event) => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // Xóa các cache cũ
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});