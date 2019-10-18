<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const ARTICLE_IMAGE = 'article_image';

    private $uploadsPath;

    private $requestStackContext;

    public function __construct(string $uploadsPath, RequestStackContext $requestStackContext)
    {
        $this->uploadsPath = $uploadsPath;
        $this->requestStackContext = $requestStackContext;
    }

    public function uploadArticleImage(File $file): string
    {
        $destination = $this->uploadsPath.'/'.self::ARTICLE_IMAGE;

        if ($file instanceof UploadedFile) {
            $originalFileName = $file->getClientOriginalName();
        } else {
            $originalFileName = $file->getFilename();
        }

        $newFileName = Urlizer::urlize(pathinfo($originalFileName, PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();

        $file->move($destination, $newFileName);

        return $newFileName;
    }

    public function getPublicPath(string $path): string
    {
        return $this->requestStackContext
                ->getBasePath().'/uploads/'.$path;
    }
}
