<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    private $uploadsPath;

    public function __construct(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }

    public function uploadArticleImage(UploadedFile $uploadedFile): string
    {
        $destination = $this->uploadsPath.'/article_image';

        $originalFileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFileName = Urlizer::urlize($originalFileName).'-'.uniqid().'.'.$uploadedFile->guessExtension();

        $uploadedFile->move($destination, $newFileName);

        return $newFileName;
    }
}
