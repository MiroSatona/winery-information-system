<?php

declare(strict_types=1);

namespace App\Model\Image;

use Nette\Http\FileUpload;


final class ImageHandler
{
    public function __construct(private string $uploadsDir)
    {
        
    }

    public function getUploadsDir(): string
    {
        return $this->uploadsDir;
    }

    public function uploadImage(FileUpload $file): string
    {
        // verify upload
        try {
            if ($file->isOk() && $file->isImage()) {
            // generate a safe filename (e.g., unique hash + original extension)
            $extension = strtolower(pathinfo($file->getSanitizedName(), PATHINFO_EXTENSION));
            $timestamp = (new \DateTime())->format('YmdHis');
            $name = "img_" . $timestamp;
            $name = uniqid($name, true) . '.' . $extension;

            // move the uploaded file
            $file->move($this->uploadsDir . '/' . $name);
            return $name;
        }
        return '';
        } catch (\Exception $e) {
            // log error or handle it as needed
            return '';
        }
        
    }

    public function checkImageExists(string $subPath): bool
    {
        $filePath = $this->uploadsDir . '/' . $subPath;
        return file_exists($filePath) && is_file($filePath);
    }

    public function deleteImage(string $subPath): bool
    {
        $filePath = $this->uploadsDir . '/' . $subPath;
        if ($this->checkImageExists($subPath)) {
            return unlink($filePath);
        }
        return false;
    }

}