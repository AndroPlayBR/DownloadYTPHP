<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use RuntimeException;

final class YouTubeDownloader
{
    private const API_URL = 'https://www.clipto.com/api/youtube';
    private const USER_AGENT = 'YouTubeDownloader/8.4.13 (PHP)';
    private const TIMEOUT = 30;

    private string $videoUrl;
    private array $response = [];
    private string $downloadDirectory;

    public function __construct(string $videoUrl, string $downloadDirectory = __DIR__ . '/downloads')
    {
        if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('URL inválida.');
        }
        $this->videoUrl = $videoUrl;
        $this->downloadDirectory = rtrim($downloadDirectory, '/');

        if (!is_dir($this->downloadDirectory) && !mkdir($this->downloadDirectory, 0775, true) && !is_dir($this->downloadDirectory)) {
            throw new RuntimeException('Falha ao criar diretório de downloads.');
        }
    }

    public function fetchVideoData(): self
    {
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'User-Agent: ' . self::USER_AGENT],
            CURLOPT_POSTFIELDS => json_encode(['url' => $this->videoUrl], JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new RuntimeException('Erro na requisição: ' . curl_error($ch));
        }
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($statusCode !== 200) {
            throw new RuntimeException("Falha ao obter dados. Código HTTP: $statusCode");
        }
        $this->response = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        if (empty($this->response['success']) || !$this->response['success']) {
            throw new RuntimeException('API retornou uma resposta inválida.');
        }
        return $this;
    }

    public function getMetadata(): string
    {
        if (empty($this->response)) {
            throw new RuntimeException('Nenhum dado carregado. Chame fetchVideoData() primeiro.');
        }
        return json_encode([
            'url' => $this->response['url'] ?? '',
            'title' => $this->response['title'] ?? '',
            'author' => $this->response['author'] ?? '',
            'thumbnail' => $this->response['thumbnail'] ?? '',
            'duration' => $this->response['duration'] ?? 0,
            'available_formats' => array_map(fn($m) => [
                'label' => $m['label'] ?? '',
                'extension' => $m['extension'] ?? '',
                'bitrate' => $m['bitrate'] ?? '',
                'mime' => $m['mimeType'] ?? '',
            ], $this->response['medias'] ?? []),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function downloadBestQuality(): string
    {
        if (empty($this->response['medias'])) {
            throw new RuntimeException('Nenhum formato disponível para download.');
        }
        usort($this->response['medias'], fn($a, $b) => ($b['bitrate'] ?? 0) <=> ($a['bitrate'] ?? 0));
        $best = $this->response['medias'][0];
        $filename = $this->sanitizeFileName(($this->response['title'] ?? 'video') . '.' . ($best['ext'] ?? 'mp4'));
        $filePath = $this->downloadDirectory . '/' . $filename;
        $fp = fopen($filePath, 'w+');
        if (!$fp) {
            throw new RuntimeException('Falha ao criar arquivo local.');
        }
        $ch = curl_init($best['url']);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        curl_exec($ch);
        if (curl_errno($ch)) {
            fclose($fp);
            throw new RuntimeException('Erro no download: ' . curl_error($ch));
        }
        curl_close($ch);
        fclose($fp);
        return json_encode([
            'downloaded' => true,
            'file' => realpath($filePath),
            'size_mb' => round(filesize($filePath) / 1048576, 2),
            'format' => $best['label'] ?? 'unknown',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_\-.]/', '_', $name);
    }

    public static function fromUrl(string $url, ?string $path = null): string
    {
        $instance = new self($url, $path ?? __DIR__ . '/downloads');
        $instance->fetchVideoData();
        return $instance->getMetadata();
    }
}
