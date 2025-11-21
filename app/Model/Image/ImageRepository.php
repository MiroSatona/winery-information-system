<?php
//FILE:     ImageRepository.php
//AUTHORS:  Martin Zůbek [C]
//          Otakar Kočí
//          Martin Mendl
//YEAR:     2025
//DESC:     Repository for managing operations with images in the system.

declare(strict_types=1);

namespace App\Model\Image;

use Nette\Database\Explorer;
use Nette\Http\FileUpload;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Handles DB operations related to Images
 */
final class ImageRepository 
{
    /**
     * Repository constructor
     *
     * @param string $uploadsDir
     * @param string $uploadsDirWeb
     * @param string $tmpImage
     * @param Explorer $db
     */
    public function __construct(
        private string $uploadsDir,
        private string $uploadsDirWeb,
        private string $tmpImage,
        private Explorer $db
    ) {
    }
    
    /**
     * Fetch image by its ID
     *
     * @param int $imgId
     * @return Image|null
     */
    public function fetchImageById(int $imgId)
    {
        $imgRow = $this->db->table("PICTURE")->where("ID", $imgId)->fetch();
        return $this->createImageFromRow($imgRow);
    }

    /**
     * Create Image object from DB row
     *
     * @param \Nette\Database\Table\ActiveRow $imgRow
     * @return Image
     */
    private function createImageFromRow($imgRow) 
    {
        return new Image(
            $imgRow->ID,
            $imgRow->LINK,
            $imgRow->IMAGE_NAME,
            $imgRow->DESCRIPTION
        ); 
    }

    /**
     * Remove image by its ID
     *
     * @param int $imageId
     * @return bool True on success, false on failure
     */
    public function removeImage(int $imageId): bool
    {
        $image = $this->fetchImageById($imageId);
        if ($image === null) {
            return false;
        }

        $resultDB = $this->db->table("PICTURE")->where("ID", $image->id)->delete();
        $resultFS = $this->deleteImageFromUploads($image->src);
        return $resultDB && $resultFS;
    }

    /**
     * Upload image file to the uploads directory
     *
     * @param FileUpload $file
     * @return string Uploaded file name or empty string on failure
     */
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
            Debugger::log("Image upload failed: " . $e->getMessage(), ILogger::ERROR);
            return '';
        }
    }

    /**
     * Get the web path for an image
     *
     * @param Image $image
     * @return string
     */
    public function getPathForImage(Image $image): string
    {
        if ($this->checkImageExists($image->src)) {
            return $this->uploadsDirWeb . '/' . $image->src;
        } else {
            return $this->tmpImage . "/" . "tempImage.png";
        }
    }
    
    /**
     * Get the temporary wine image path
     *
     * @return string
     */
    public function getTmpWineImage(): string
    {
        return $this->tmpImage . "/" . "tempWine.jpg";
    }

    /**
     * Check if image file exists in uploads directory
     *
     * @param string $subPath
     * @return bool
     */
    public function checkImageExists(string $subPath): bool
    {
        $filePath = $this->uploadsDir . '/' . $subPath;
        return file_exists($filePath) && is_file($filePath);
    }

    /**
     * Delete image file from uploads directory
     *
     * @param string $subPath
     * @return bool True on success, false on failure
     */
    public function deleteImageFromUploads(string $subPath): bool
    {
        $filePath = $this->uploadsDir . '/' . $subPath;
        if ($this->checkImageExists($subPath)) {
            return unlink($filePath);
        }
        return false;
    }
}
