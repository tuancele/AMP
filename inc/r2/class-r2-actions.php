<?php
// File: inc/r2/class-r2-actions.php
// ĐÃ SỬA LỖI: 
// 1. Xóa tham số 'ACL' không tương thích với R2.
// 2. Thêm 'use Aws\S3\Exception\S3Exception' để bắt lỗi chính xác.

if ( ! defined( 'ABSPATH' ) ) { exit; }

use Aws\S3\Exception\S3Exception; // [THÊM MỚI]

final class Tuancele_R2_Actions {

    private $client;
    private $options;

    public function __construct() {
        $this->client = Tuancele_R2_Client::get_instance();
        $this->options = $this->client->get_options();
    }
    
    /**
     * Hook vào filter của WordPress khi có file mới được upload.
     * Hàm này đóng vai trò là một trình bao (wrapper), gọi đến logic offload chính.
     */
    public function handle_upload($metadata, $attachment_id) {
        return $this->offload_attachment($attachment_id, $metadata);
    }

    /**
     * [PHƯƠNG THỨC LÕI] Xử lý việc offload một attachment lên R2.
     * Phương thức này được tái sử dụng bởi cả quá trình upload mới và công cụ di chuyển dữ liệu cũ.
     *
     * @param int $attachment_id ID của attachment.
     * @param array|null $metadata Metadata hiện có (nếu có).
     * @return array Metadata đã được cập nhật.
     */
    public function offload_attachment($attachment_id, $metadata = null) {
        // Nếu metadata chưa được cung cấp (ví dụ: khi gọi từ công cụ di chuyển), hãy lấy nó từ database.
        if ($metadata === null) {
            $metadata = wp_get_attachment_metadata($attachment_id);
        }

        // Lấy danh sách tất cả các file cần xử lý (gốc, các size, và webp) cùng với metadata đã được cập nhật
        list($files_to_process, $updated_metadata) = $this->get_files_for_attachment($metadata, $attachment_id);

        if (empty($files_to_process)) {
            return $metadata; // Trả về metadata gốc nếu không có file nào để xử lý.
        }

        $s3 = $this->client->get_s3_client();
        if (!$s3) {
            return $updated_metadata; // Trả về metadata đã cập nhật ngay cả khi client lỗi.
        }
        
        $upload_dir = wp_upload_dir();
        $success = true;
        // --- BẮT ĐẦU ĐOẠN CODE MỚI ---
        // Lấy cài đặt Cache-Control từ options
        $cache_control = $this->options['cache_control'] ?? '';
        if ( empty( $cache_control ) ) {
            // Sử dụng giá trị mặc định (1 năm) nếu trường này bị để trống
            $cache_control = 'public, max-age=31536000';
        }
        // --- KẾT THÚC ĐOẠN CODE MỚI ---

        foreach ($files_to_process as $file) {
            $local_path = $file['path'];
            if (!file_exists($local_path)) {
                continue;
            }

            try {
                $s3_key = str_replace($upload_dir['basedir'] . '/', '', $local_path);
                $s3->putObject([
                    'Bucket'      => $this->options['bucket'],
                    'Key'         => $s3_key,
                    'SourceFile'  => $local_path,
                    // 'ACL'         => 'public-read', // [SỬA LỖI] ĐÃ XÓA DÒNG NÀY
                    'ContentType' => $file['mime'],
                ]);
            } catch (S3Exception $e) {
                // [SỬA LỖI] Bắt lỗi S3Exception cụ thể
                $success = false;
                error_log("R2 Offload S3Exception for attachment {$attachment_id}: " . $e->getAwsErrorMessage());
                break;
            } catch (Exception $e) {
                // Bắt các lỗi chung khác
                $success = false;
                error_log("R2 Offload Generic Error for attachment {$attachment_id}: " . $e->getMessage());
                break;
            }
        }

        if ($success) {
            update_post_meta($attachment_id, '_tuancele_r2_offloaded', true);
            
            // Chỉ xóa file cục bộ nếu tùy chọn được bật VÀ đây là lần upload file mới.
            // Công cụ di chuyển sẽ có cơ chế xóa riêng (nếu cần) để an toàn hơn.
            if ($this->client->should_delete_local_files() && did_action('add_attachment')) {
                foreach ($files_to_process as $file) {
                    if (file_exists($file['path'])) {
                        @unlink($file['path']);
                    }
                }
            }
        }
        
        // Cập nhật lại metadata của WordPress để lưu các thông tin WebP mới.
        wp_update_attachment_metadata($attachment_id, $updated_metadata);
        
        return $updated_metadata;
    }

    /**
     * Xử lý việc xóa các file trên R2 khi một attachment bị xóa khỏi WordPress.
     */
    public function handle_delete($attachment_id) {
        if (!get_post_meta($attachment_id, '_tuancele_r2_offloaded', true)) {
            return;
        }

        $s3 = $this->client->get_s3_client();
        if (!$s3) {
            return;
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        $keys_to_delete = [];
        $upload_dir = wp_upload_dir();
        $original_file_path = get_attached_file($attachment_id);

        if ($original_file_path) {
            $base_dir = dirname($original_file_path);
            
            // Thêm file gốc vào danh sách xóa
            $keys_to_delete[] = ['Key' => str_replace($upload_dir['basedir'] . '/', '', $original_file_path)];
            
            // Thêm file WebP gốc (nếu có)
            if (isset($metadata['webp_original'])) {
                $keys_to_delete[] = ['Key' => str_replace($upload_dir['basedir'] . '/', '', $base_dir . '/' . $metadata['webp_original'])];
            }

            // Thêm các size con và các phiên bản WebP của chúng
            if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size) {
                    $keys_to_delete[] = ['Key' => str_replace($upload_dir['basedir'] . '/', '', $base_dir . '/' . $size['file'])];
                    if (isset($size['file_webp'])) {
                        $keys_to_delete[] = ['Key' => str_replace($upload_dir['basedir'] . '/', '', $base_dir . '/' . $size['file_webp'])];
                    }
                }
            }
        }

        if (!empty($keys_to_delete)) {
            try {
                $s3->deleteObjects([
                    'Bucket' => $this->options['bucket'],
                    'Delete' => ['Objects' => $keys_to_delete]
                ]);
            } catch (S3Exception $e) { // [SỬA LỖI] Bắt S3Exception
                error_log('R2 Delete S3Exception: ' . $e->getAwsErrorMessage());
            } catch (Exception $e) {
                error_log('R2 Delete Generic Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Lấy danh sách tất cả các file liên quan đến một attachment (bao gồm cả WebP).
     * @return array Mảng gồm 2 phần tử: [danh_sách_file, metadata_đã_cập_nhật].
     */
    private function get_files_for_attachment($metadata, $attachment_id, $generate_webp = true) {
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return [[], $metadata];
        }

        $files = [];
        $is_webp_enabled = $this->client->is_webp_enabled() && $generate_webp;

        $files[] = ['path' => $file_path, 'mime' => mime_content_type($file_path)];

        if ($is_webp_enabled && strpos(mime_content_type($file_path), 'image') !== false) {
            $webp_path = Tuancele_R2_WebP::convert($file_path);
            if ($webp_path) {
                $files[] = ['path' => $webp_path, 'mime' => 'image/webp'];
                $metadata['webp_original'] = basename($webp_path);
            }
        }

        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $base_dir = dirname($file_path);
            foreach ($metadata['sizes'] as $size_name => $size_info) {
                $thumb_path = $base_dir . '/' . $size_info['file'];
                if (file_exists($thumb_path)) {
                    $files[] = ['path' => $thumb_path, 'mime' => $size_info['mime-type']];

                    if ($is_webp_enabled) {
                        $webp_thumb_path = Tuancele_R2_WebP::convert($thumb_path);
                        if ($webp_thumb_path) {
                            $files[] = ['path' => $webp_thumb_path, 'mime' => 'image/webp'];
                            $metadata['sizes'][$size_name]['file_webp'] = basename($webp_thumb_path);
                        }
                    }
                }
            }
        }
        
        return [$files, $metadata];
    }
}