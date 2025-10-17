<?php

namespace App\Model\Image;
use Nette\Database\Explorer;
use Nette\Http\FileUpload;

final class ImageRepository 
{
    public function __construct(private string $uploadsDir, private string $uploadsDirWeb, private Explorer $db)
    {
    }
    
    public function fetchImageById(int $imgId)
    {
        $imgRow = $this->db->table("PICTURE")->where("ID", $imgId)->fetch();
        return $this->createImageFromRow($imgRow);
    }
    
    public function fetchAllImages(): array
    {
        $rows = $this->db->table("PICTURE");
        $return = [];
        foreach ($rows as $row) {
           $return[] = $this->createImageFromRow($row);
        }
        return $return;
    }

    private function createImageFromRow($imgRow) 
    {
        return new Image(
            $imgRow->ID,
            $imgRow->LINK,
            $imgRow->IMAGE_NAME,
            $imgRow->DESCRIPTION
        ); 
    }

    public function removeImage(int $imageId): bool
    {
        $image = $this->fetchImageById($imageId);
        if ($image === null) {
            return false;
        }

        $resultDB = $this->db->table("PICTURE")->where("ID", $image->id)->delete();
        $resultFS = $this->deleteImageFromUploads($image->src); // unify
        return $resultDB && $resultFS;
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

    public function getPathForImage(Image $image): string
    {
        return $this->uploadsDirWeb . '/' . $image->src;
    }

    public function checkImageExists(string $subPath): bool
    {
        $filePath = $this->uploadsDir . '/' . $subPath;
        return file_exists($filePath) && is_file($filePath);
    }

    public function deleteImageFromUploads(string $subPath): bool
    {
        $filePath = $this->uploadsDir . '/' . $subPath;
        if ($this->checkImageExists($subPath)) {
            return unlink($filePath);
        }
        return false;
    }

}