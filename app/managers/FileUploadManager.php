<?php

namespace App\Managers;

use App\Core\FileManager;
use App\Core\HashManager;
use App\Entities\PostImageFileEntity;
use App\Exceptions\FileUploadException;
use App\Logger\Logger;
use App\Repositories\FileUploadRepository;
use Exception;

class FileUploadManager extends AManager {
    private const LOG = true;

    private FileUploadRepository $fur;
    private array $cfg;

    public function __construct(Logger $logger, FileUploadRepository $fur, array $cfg) {
        parent::__construct($logger);

        $this->fur = $fur;
        $this->cfg = $cfg;
    }

    private function createUploadId() {
        return HashManager::createHash(16, false);
    }

    private function createPostImageFileUploadPath(int $userId, int $postId, int $topicId, string $filename, string $extension, string $uploadId) {
        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['UPLOAD_DIR'] . $topicId . '\\' . $postId . '\\' . $userId . '\\' . $uploadId . '\\';

        FileManager::createFolder($path, true);

        $filename = md5($filename);

        if(self::LOG) {
            $this->logger->info('Created file upload path: ' . $path . $filename . $extension, __METHOD__);
        }

        return $path . $filename . '.' . $extension;
    }

    public function uploadPostImage(int $userId, int $postId, int $topicId, string $filename, string $filepath, array $fileData) {
        if(!getimagesize($filepath)) {
            throw new FileUploadException('Uploaded file is not an image.');
        }

        $uploadId = $this->createUploadId();

        if(self::LOG) {
            $this->logger->info('Created file upload ID: ' . $uploadId, __METHOD__);
        }

        $fileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(self::LOG) {
            $this->logger->info('File type of the uploaded file is: ' . $fileType, __METHOD__);
        }

        $newFilePath = $this->createPostImageFileUploadPath($userId, $postId, $topicId, $filename, $fileType, $uploadId);

        if(FileManager::fileExists($newFilePath)) {
            throw new FileUploadException('File already exists in path ' . $newFilePath . '.');
        }

        if($fileData['size'] > 500000) {
            throw new FileUploadException('File exceeds the maximum file size limit ' . $newFilePath . ' of 500,000 bytes.');
        }

        if(!in_array($fileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            throw new FileUploadException('File format is not supported. Only jpg, png, jpeg and gif formats are now supported.');
        }

        if(!move_uploaded_file($filepath, $newFilePath)) {
            throw new FileUploadException('File could not be uploaded.');
        }

        try {
            $newFilePath = str_replace('\\', '\\\\', $newFilePath);
            $this->fur->createNewEntry($uploadId, $filename, $newFilePath, $userId, $postId);
        } catch(Exception $e) {
            throw new FileUploadException('Could not create a database entry. Reason: ' . $e->getMessage() . ' Please contact an administrator and provide this file upload ID: ' . $uploadId . '.', $e);
        }
    }

    public function createPostImageSourceLink(PostImageFileEntity $pife) {
        $split = explode('\\', $pife->getFilepath());

        $parts = [];
        for($i = 3; $i < count($split); $i++) {
            $parts[] = $split[$i];
        }

        $src = implode('/', $parts);

        return '/' . $src;
    }
}

?>