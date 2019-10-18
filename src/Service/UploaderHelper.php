<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const ARTICLE_IMAGE = 'article_image';
    const ARTICLE_REFERENCE = 'article_reference';

    private $fileSystem;

    private $privateFileSystem;

    private $requestStackContext;

    private $logger;

    private $publicAssetBaseUrl;

    public function __construct(FilesystemInterface $publicUploadsFileSystem, FilesystemInterface $privateUploadsFileSystem, RequestStackContext $requestStackContext, LoggerInterface $logger, string $uploadedAssetsBaseUrl)
    {
        $this->fileSystem = $publicUploadsFileSystem;
        $this->privateFileSystem = $privateUploadsFileSystem;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
    }

    public function uploadArticleImage(File $file, ?string $existingFileName): string
    {
        $newFileName = $this->uploadFile($file, self::ARTICLE_IMAGE, true);

        if ($existingFileName) {
            try {
                $result = $this->fileSystem->delete(self::ARTICLE_IMAGE.'/'.$existingFileName);

                if ($result === false) {
                    throw new \Exception(sprintf('Could not delete old uploaded file "%s"', $existingFileName));
                }
            } catch (FileNotFoundException $exception) {
                $this->logger->alert(sprintf('Old uploaded file "%s" was missing when trying to delete', $existingFileName));
            }
        }

        return $newFileName;
    }

    public function uploadArticleReference(File $file): string
    {
        return $this->uploadFile($file, self::ARTICLE_REFERENCE, false);
    }

    public function getPublicPath(string $path): string
    {
        return $this->requestStackContext
                ->getBasePath().$this->publicAssetBaseUrl.'/'.$path;
    }

    private function uploadFile(File $file, string $directory, bool $isPublic): string
    {
        if ($file instanceof UploadedFile) {
            $originalFileName = $file->getClientOriginalName();
        } else {
            $originalFileName = $file->getFilename();
        }

        $newFileName = Urlizer::urlize(pathinfo($originalFileName, PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();

        $fileSystem = $isPublic ? $this->fileSystem : $this->privateFileSystem;

        $stream = fopen($file->getPathname(), 'r');
        $result = $fileSystem->writeStream(
            $directory.'/'.$newFileName,
            $stream
        );

        if ($result === false) {
            throw new \Exception(sprintf('Could not write uploaded file "%s"', $newFileName));
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $newFileName;
    }
}
