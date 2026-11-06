<?php

namespace App\Services;

use Spatie\MediaLibrary\Downloaders\Downloader;
use Spatie\MediaLibrary\MediaCollections\Exceptions\UnreachableUrl;
use Illuminate\Support\Facades\Log;

class CustomDownloader implements Downloader
{
    public function getTempFile(string $url): string
    {
        // Tạo tên file tạm với tên gốc
        mb_internal_encoding('UTF-8');

        $originalFilename = basename(parse_url($url, PHP_URL_PATH));
        $originalFilename = urldecode($originalFilename);
        $originalFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/u', '_', $originalFilename);

        $temporaryFile = storage_path('app/tmp/' . uniqid('media-library-', true) . '-' . $originalFilename);
        if (!file_exists(dirname($temporaryFile))) {
            mkdir(dirname($temporaryFile), 0755, true);
        }

        $fh = fopen($temporaryFile, 'w');
        if ($fh === false) {
            throw new \RuntimeException("Không thể tạo file tạm: $temporaryFile");
        }

        // Encode phần path của URL để tránh lỗi ký tự Unicode
        $urlParts = parse_url($url);
        $encodedPath = preg_replace_callback('/[^\/]+/', fn($m) => rawurlencode($m[0]), $urlParts['path'] ?? '');

        $encodedUrl =
            (isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '') .
            ($urlParts['host'] ?? '') .
            (isset($urlParts['port']) ? ':' . $urlParts['port'] : '') .
            $encodedPath .
            (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');
        $curl = curl_init($encodedUrl);
        $options = [
            CURLOPT_FAILONERROR     => true,
            CURLOPT_FILE            => $fh,
            CURLOPT_TIMEOUT         => 35,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => 0,
        ];
        $headers = [
            'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',
        ];

        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if (false === curl_exec($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            fclose($fh);
            unlink($temporaryFile); // Xóa file nếu lỗi
            Log::error("CURL Error while downloading from $url: $error");
            throw new UnreachableUrl("CURL Error: {$error} - URL: {$url}");
        }

        curl_close($curl);
        fclose($fh);

        return $temporaryFile;
    }
}
