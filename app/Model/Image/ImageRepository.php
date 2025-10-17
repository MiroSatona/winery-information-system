<?php

namespace App\Model\Image;
use Nette\Database\Explorer;

final class ImageRepository 
{
    public function __construct(private Explorer $db)
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

}