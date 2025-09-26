<?php
declare(strict_types=1);

namespace App\Service;

final class ProfilePhotoStorage extends BasePhotoStorage
{
    public function publicUrl(string $key): ?string
    {
        return $this->urlFor('profiles', $key);
    }
}
