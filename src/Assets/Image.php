<?php

declare(strict_types=1);

namespace Conia\Core\Assets;

use Conia\Chuck\Request;
use Conia\Core\Exception\RuntimeException;
use Conia\Core\Util\Path;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;

class Image
{
    public readonly string $relativeFile;
    public readonly string $file;
    protected ?string $cacheFile = null;

    public function __construct(
        protected readonly Request $request,
        protected readonly Assets $assets,
        string $file,
    ) {
        $this->file = Path::inside($assets->assetsDir, $file, checkIsFile: true);
        $this->relativeFile = substr($this->file, strlen($assets->assetsDir));
    }

    public function path(bool $bust = false): string
    {
        $path = str_replace('\\', '/', $this->cacheFile ?: $this->file);

        if ($bust) {
            $buster = hash('xxh32', (string)filemtime($this->file));
            $path .= '?v=' . $buster;
        }

        return substr($path, strlen($this->assets->publicDir));
    }

    public function url(bool $bust = false): string
    {
        return $this->request->origin() . $this->path($bust);
    }

    public function resize(Size $size, ResizeMode $mode, bool $enlarge, ?int $quality): static
    {
        $this->cacheFile = $this->getCacheFilePath($size, $mode, $enlarge);

        if (is_file($this->cacheFile)) {
            $fileMtime = filemtime($this->file);
            $cacheMtime = filemtime($this->cacheFile);

            if ($fileMtime > $cacheMtime) {
                $this->createCacheFile($size, $mode, $enlarge, $quality);
            }
        } else {
            $this->createCacheFile($size, $mode, $enlarge, $quality);
        }

        return $this;
    }

    public function delete(): bool
    {
        return unlink($this->file);
    }

    public function get(): ImageResize
    {
        return new ImageResize($this->file);
    }

    protected function createCacheFile(Size $size, ResizeMode $mode, bool $enlarge, ?int $quality): void
    {
        try {
            $image = match ($mode) {
                ResizeMode::Width => $this->get()->resizeToWidth($size->firstDimension, $enlarge),
                ResizeMode::Fit => $this->get()->resizeToBestFit(
                    $size->firstDimension,
                    $size->secondDimension,
                    $enlarge
                ),
                ResizeMode::Crop => $this->get()->crop(
                    $size->firstDimension,
                    $size->secondDimension,
                    $size->cropMode
                ),
                ResizeMode::Height => $this->get()->resizeToHeight($size->firstDimension, $enlarge),
                ResizeMode::LongSide => $this->get()->resizeToLongSide($size->firstDimension, $enlarge),
                ResizeMode::ShortSide => $this->get()->resizeToShortSide($size->firstDimension, $enlarge),
                ResizeMode::FreeCrop => $this->get()->freecrop(
                    $size->firstDimension,
                    $size->secondDimension,
                    x: $size->cropMode['x'],
                    y: $size->cropMode['y'],
                ),
                ResizeMode::Resize => $this->get()->resize(
                    $size->firstDimension,
                    $size->secondDimension,
                    $enlarge
                ),
            };

            $image->save($this->cacheFile, quality: $quality);
        } catch (ImageResizeException $e) {
            throw new RuntimeException('Assets error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function getCacheFilePath(Size $size, ResizeMode $mode, bool $enlarge): string
    {
        $info = pathinfo($this->relativeFile);
        $relativeDir = $info['dirname'] ?? null;
        // pathinfo does not handle multiple dots like .tar.gz well
        $filenameSegments = explode('.', $info['basename']);
        $cacheDir = $this->assets->cacheDir;

        if ($relativeDir !== '/') {
            $cacheDir .= $relativeDir;

            // create cache sub directory if it does not exist
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
        }

        $suffix = '-' . match ($mode) {
            ResizeMode::Width => 'w' . $size->firstDimension,
            ResizeMode::Fit => $size->firstDimension . 'x' . $size->secondDimension . '-fit',
            ResizeMode::Crop => $size->firstDimension . 'x' . $size->secondDimension . '-crop' . $size->cropMode,
            ResizeMode::FreeCrop => $size->firstDimension . 'x' .
                $size->secondDimension . '-crop-x' .
                $size->cropMode['x'] .
                'y' . $size->cropMode['y'],
            ResizeMode::Height => 'h' . $size->firstDimension,
            ResizeMode::LongSide => 'l' . $size->firstDimension,
            ResizeMode::ShortSide => 's' . $size->firstDimension,
            ResizeMode::Resize => $size->firstDimension . 'x' . $size->secondDimension . '-resize',
            default => throw new RuntimeException('Assets error: resize mode not supported'),
        };

        if ($enlarge) {
            $suffix .= '-enl';
        }

        $cacheFile = $cacheDir . '/' . $filenameSegments[0] . $suffix;

        // Add extension
        return $cacheFile . '.' . implode('.', array_slice($filenameSegments, 1));
    }
}