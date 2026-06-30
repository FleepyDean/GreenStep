<?php
declare(strict_types=1);

namespace App\Services;

class CloudinaryUploader
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? '';
        $this->apiKey = $_ENV['CLOUDINARY_API_KEY'] ?? '';
        $this->apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? '';
    }

    /**
     * Upload image to Cloudinary
     * @param string $filePath - Temporary file path
     * @param string $folder - Cloudinary folder (e.g., 'greenstep/activities')
     * @return string|null - Cloudinary URL or null on failure
     */
    public function upload(string $filePath, string $folder = 'greenstep/activities'): ?string
    {
        if (empty($this->cloudName) || empty($this->apiKey) || empty($this->apiSecret)) {
            error_log('Cloudinary credentials not configured');
            return null;
        }

        $timestamp = time();
        $publicId = $folder . '/' . uniqid();
        
        // Build signature
        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];
        
        ksort($paramsToSign);
        $signatureString = '';
        foreach ($paramsToSign as $key => $value) {
            $signatureString .= $key . '=' . $value . '&';
        }
        $signatureString = rtrim($signatureString, '&') . $this->apiSecret;
        $signature = sha1($signatureString);

        // Prepare multipart form data
        $postFields = [
            'file' => new \CURLFile($filePath),
            'folder' => $folder,
            'timestamp' => $timestamp,
            'api_key' => $this->apiKey,
            'signature' => $signature,
        ];

        // Upload to Cloudinary
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            return $result['secure_url'] ?? null;
        }

        error_log("Cloudinary upload failed: HTTP $httpCode - $response");
        return null;
    }
}
