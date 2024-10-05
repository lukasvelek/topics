<?php

namespace App\Managers;

use App\Authorizators\ActionAuthorizator;
use App\Core\FileManager;
use App\Entities\PostImageFileEntity;
use App\Exceptions\FileUploadDeleteException;
use App\Exceptions\FileUploadException;
use App\Logger\Logger;
use App\Repositories\FileUploadRepository;
use Exception;

class FileUploadManager extends AManager {
    private const LOG = true;

    private FileUploadRepository $fur;
    private array $cfg;
    private ActionAuthorizator $aa;

    public function __construct(Logger $logger, FileUploadRepository $fur, array $cfg, ActionAuthorizator $aa, EntityManager $entityManager) {
        parent::__construct($logger, $entityManager);

        $this->fur = $fur;
        $this->cfg = $cfg;
        $this->aa = $aa;
    }

    private function createPostImageFileUploadPath(string $userId, string $postId, string $topicId, string $filename, string $extension, string $uploadId) {
        $path = $this->cfg['UPLOAD_DIR'] . 'topic_' . $topicId . '\\post_' . $postId . '\\user_' . $userId . '\\upload_' . $uploadId . '\\';

        FileManager::createFolder($path, true);

        $filename = md5($filename);

        if(self::LOG) {
            $this->logger->info('Created file upload path: ' . $path . $filename . $extension, __METHOD__);
        }

        return $path . $filename . '.' . $extension;
    }

    public function uploadPostImage(string $userId, string $postId, string $topicId, string $filename, string $filepath, array $fileData) {
        if(!getimagesize($filepath)) {
            throw new FileUploadException('Uploaded file is not an image.');
        }

        $uploadId = $this->createId(EntityManager::POST_FILE_UPLOADS);

        if(self::LOG) {
            $this->logger->info('Created file upload ID: ' . $uploadId, __METHOD__);
        }

        $fileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(self::LOG) {
            $this->logger->info('File type of the uploaded file is: ' . $fileType, __METHOD__);
        }

        if($fileData['size'] > 500000) {
            throw new FileUploadException('File exceeds the maximum file size limit ' . $filename . ' of 500,000 bytes.');
        }

        $allowedFormats = ['jpg', 'png', 'jpeg', 'gif', 'webp'];
        if(!in_array($fileType, $allowedFormats)) {
            throw new FileUploadException('File format is not supported. Only ' . implode(', ', $allowedFormats) . ' formats are now supported.');
        }

        $newFilePath = $this->createPostImageFileUploadPath($userId, $postId, $topicId, $filename, $fileType, $uploadId);

        if(FileManager::fileExists($newFilePath)) {
            throw new FileUploadException('File already exists in path ' . $newFilePath . '.');
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

    public function deleteUploadedFile(PostImageFileEntity $pife, string $userId, bool $checkForFileExistance = false) {
        if(!$this->aa->canDeleteFileUpload($userId, $pife)) {
            throw new FileUploadDeleteException('The post the file is related to, still exists and has not been deleted yet.');
        }

        if($checkForFileExistance) {
            if(!FileManager::fileExists($pife->getFilepath())) {
                throw new FileUploadDeleteException('File does not exist.');
            }
        }

        try {
            unlink($pife->getFilepath());
        } catch(Exception $e) {
            throw new FileUploadDeleteException(sprintf('Error while deleting the binary file. Reason: %s', $e->getMessage()), $e);
        }

        if(!$this->fur->deleteFileUploadById($pife->getId())) {
            throw new FileUploadDeleteException('Error while deleting the database entry for the file upload.');
        }
    }
}

?>