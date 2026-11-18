<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\Image\ImageStorage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ImageExtension extends AbstractExtension
{
    public function __construct(private readonly ImageStorage $storage)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('article_image_url', $this->storage->url(...))];
    }
}