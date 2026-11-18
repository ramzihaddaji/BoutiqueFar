<?php

declare(strict_types=1);

namespace App\Service\Image;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Stocke les images d'articles : Supabase Storage si SUPABASE_URL et
 * SUPABASE_SERVICE_KEY sont définis (production), sinon public/uploads/articles (local).
 */
final class ImageStorage
{
    public function __construct(
        private readonly HttpClientInterface $http,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%env(SUPABASE_URL)%')] private readonly string $supabaseUrl,
        #[Autowire('%env(SUPABASE_SERVICE_KEY)%')] private readonly string $serviceKey,
        #[Autowire('%env(SUPABASE_BUCKET)%')] private readonly string $bucket,
    ) {
    }

    private function isRemote(): bool
    {
        return '' !== $this->supabaseUrl && '' !== $this->serviceKey;
    }

    /** @return string le nom de fichier à enregistrer en base */
    public function store(UploadedFile $file, string $filename): string
    {
        if (!$this->isRemote()) {
            $dir = $this->projectDir . '/public/uploads/articles';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $file->move($dir, $filename);

            return $filename;
        }

        $response = $this->http->request(
            'POST',
            sprintf('%s/storage/v1/object/%s/%s', rtrim($this->supabaseUrl, '/'), $this->bucket, $filename),
            [
                'headers' => $this->headers() + ['Content-Type' => $file->getMimeType() ?? 'application/octet-stream'],
                'body' => file_get_contents($file->getPathname()),
            ]
        );

        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException('Supabase Storage upload failed: HTTP ' . $response->getStatusCode());
        }

        return $filename;
    }

    public function url(?string $filename): ?string
    {
        if (null === $filename || '' === $filename) {
            return null;
        }
        if (!$this->isRemote()) {
            return '/uploads/articles/' . $filename;
        }

        return sprintf('%s/storage/v1/object/public/%s/%s', rtrim($this->supabaseUrl, '/'), $this->bucket, $filename);
    }

    public function delete(?string $filename): void
    {
        if (null === $filename || '' === $filename) {
            return;
        }
        if (!$this->isRemote()) {
            $path = $this->projectDir . '/public/uploads/articles/' . basename($filename);
            if (is_file($path)) {
                unlink($path);
            }

            return;
        }

        $this->http->request(
            'DELETE',
            sprintf('%s/storage/v1/object/%s/%s', rtrim($this->supabaseUrl, '/'), $this->bucket, $filename),
            ['headers' => $this->headers()]
        )->getStatusCode();
    }

    /** @return array<string,string> */
    private function headers(): array
    {
        return [
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
        ];
    }
}