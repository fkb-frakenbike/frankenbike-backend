<?php
declare(strict_types=1);

namespace App\Service;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class BasePhotoStorage
{
    public function __construct(
        protected FilesystemOperator $storage,
        protected ?string $publicBase = null
    ) {}

    public function upload(UploadedFile $file, string $key): void
    {
        $stream = fopen($file->getRealPath(), 'rb');
        $this->storage->writeStream($key, $stream, [
            'mimetype' => $file->getMimeType(),
            // Optional caching:
            // 'CacheControl' => 'public, max-age=31536000, immutable',
        ]);
        is_resource($stream) && fclose($stream);
    }

    public function delete(string $key): void
    {
        if ($this->storage->fileExists($key)) {
            $this->storage->delete($key);
        }
    }

    protected function urlFor(string $folder, string $key): ?string
    {
        if (!$this->publicBase) return null;
        return rtrim($this->publicBase, '/') . '/' . trim($folder, '/') . '/' . ltrim($key, '/');
    }
}
