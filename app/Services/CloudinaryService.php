<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudinaryService
{
    public function uploadSingleFile(UploadedFile $file, string $preset): string
{
    try {
        // Log để kiểm tra file có thực sự tồn tại không
        if (!$file->isValid()) {
            throw new \Exception("File tạm trên server không hợp lệ.");
        }

        $result = Cloudinary::uploadApi()->upload($file->getRealPath(), [ 'upload_preset' => $preset, 'resource_type' => 'auto', ]);



        // KIỂM TRA: Nếu $result không phải là object, nghĩa là upload thất bại
        if (!is_object($result)) {
            Log::error("Cloudinary trả về giá trị không hợp lệ (null hoặc array).");
            throw new \Exception("Kết quả từ Cloudinary không hợp lệ. Kiểm tra cấu hình .env");
        }

        

        // Nếu vẫn null, chúng ta sẽ bắt lỗi thủ công ở đây
        if (!$result) {
            throw new \Exception("Cloudinary trả về NULL. Khả năng cao do lỗi kết nối mạng hoặc SSL (CURL error).");
        }

        return $result['public_id'];

    } catch (\Exception $e) {
        // Ghi lại lỗi thực sự vào Log để soi
        Log::error("LỖI THỰC TẾ: " . $e->getMessage());
        throw new \Exception("Lỗi: " . $e->getMessage());
    }
}

    /**
     * Tải lên nhiều file và trả về danh sách public_id.
     * Tương đương: storeMultipleFiles
     * * @param array $files Mảng các file (UploadedFile[])
     * @param string $preset
     * @return array List<String> public_ids
     */
    public function uploadMultipleFiles(array $files, string $preset): array
    {
        $publicIds = [];

        foreach ($files as $file) {
            // Kiểm tra instance để đảm bảo là file upload hợp lệ
            if ($file instanceof UploadedFile) {
                $publicIds[] = $this->uploadSingleFile($file, $preset);
            }
        }

        return $publicIds;
    }

    /**
     * Tải file lên với tên tùy chỉnh.
     * Tương đương: storeFileWithCustomName
     * * @param UploadedFile $file
     * @param string $folder Tương đương fileType.getPath() dùng làm folder
     * @param string $customFileName Tên file mong muốn
     * @return string Public ID đầy đủ
     */
    public function uploadFileWithCustomName(UploadedFile $file, string $folder, string $customFileName): string
    {
        try {
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder'        => $folder,
                'public_id'     => $customFileName,
                'resource_type' => 'auto'
            ]);

            return $result->getPublicId();
        } catch (Exception $e) {
            throw new Exception("Could not upload file to Cloudinary", 0, $e);
        }
    }

    public function deleteFile(string $publicId): void
    {
        try {
            $this->performDelete($publicId, []);
        } catch (Exception $e1) {
            try {
                $this->performDelete($publicId, ['resource_type' => 'video']);
            } catch (Exception $e2) {
                try {
                    $this->performDelete($publicId, ['resource_type' => 'raw']);
                } catch (Exception $e3) {
                    Log::error("Failed to delete file $publicId: " . $e3->getMessage());
                    throw new Exception("Could not delete file from Cloudinary: " . $publicId);
                }
            }
        }
    }

    private function performDelete(string $publicId, array $options)
    {

        $result = Cloudinary::uploadApi()->destroy($publicId, $options);
        if (isset($result['result']) && $result['result'] !== 'ok') {
            throw new Exception("Cloudinary result: " . $result['result']);
        }
    }
}
