tuancele/amp/
│
├── functions.php               (Chỉ thêm 1 dòng require)
│
├── inc/
│   ├── qapage/                 <-- THƯ MỤC MỚI
│   │   ├── qapage-module.php     (File 1: Trình tải chính của Module)
│   │   ├── class-qapage-cpt.php      (File 2: Đăng ký CPT 'qapage_question')
│   │   ├── class-qapage-templates.php(File 3: Logic ghi đè Template)
│   │   ├── class-qapage-schema.php   (File 4: Logic xuất Schema QAPage)
│   │   ├── class-qapage-security.php (File 5: Logic reCaptcha & Đăng ký)
│   │   ├── class-qapage-ajax.php     (File 6: Xử lý Vote & Accept Answer)
│   │   ├── class-qapage-metabox.php  (File 7: Meta box 'Link nội dung')
│   │   ├── class-qapage-shortcodes.php (File 8: Shortcode [qapage_related_list])
│   │   ├── class-qapage-assets.php   (File 9: Tải CSS/JS riêng)
│   │   ├── class-qapage-walker.php   (File 10: Hiển thị Answer/Comment)
│   │   │
│   │   └── assets/
│   │       ├── css/qapage-style.css  (File 11: CSS riêng cho QAPage)
│   │       └── js/qapage-amp.js      (File 12: JS riêng cho AMP XHR)
│   │
│   ├── seo-module.php            (Không bị ảnh hưởng)
│   ├── comments-module.php       (Không bị ảnh hưởng)
│   └── ... (các file cũ)
│
├── archive-qapage_question.php     (File 13: Template Archive QAPage)
├── single-qapage_question.php      (File 14: Template Single QAPage)
├── qapage-comments.php             (File 15: Template Comment QAPage)
│
└── page-templates/
    └── page-qapage-ask.php         (File 16: Template Trang "Đặt Câu Hỏi")