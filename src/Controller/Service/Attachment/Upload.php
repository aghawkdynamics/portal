<?php

namespace App\Controller\Service\Attachment;

use App\Core\Config;
use App\Core\Controller;
use App\Model\ServiceRequest;
use App\Model\Account\User;

class Upload extends Controller
{

    /**
     * Handle the service request form submission.
     *
     * @return void
     */
    public function handle(): void
    {
        try {

            if (!User::isAdmin()) {
                throw new \RuntimeException('Access denied. Only admins can upload attachments.');
            }

            if (!$this->getRequest()->isPost()) {
                throw new \RuntimeException('Invalid request method. Please use POST to upload files.');
            }

            $serviceId = (int)$this->getRequest()->request('service_id', 0);
            $service = (new ServiceRequest())->load($serviceId);

            $comment = $this->getRequest()->request('comment', '');

            if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException('File upload error: ' . $_FILES['attachment']['error']);
            }

            $file = $_FILES['attachment'];

            if (!$service->getId()) {
                throw new \RuntimeException('Service not found');
            }

            
            
            if (empty($file['name'])) {
                throw new \RuntimeException('No file uploaded');
            }

            // $allowedTypes = Config::get('upload_types');
            // if (!in_array($file['type'], $allowedTypes)) {
            //     throw new \RuntimeException('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
            // }

            if ($file['size'] > Config::get('max_upload_size')) {
                throw new \RuntimeException('File size exceeds the maximum limit');
            }

            $uploadRelDir = 
                 Config::get('upload_dir') 
                . DIRECTORY_SEPARATOR
                .'attachments' 
                . DIRECTORY_SEPARATOR 
                . 'service_' . $service->getId();

            if (!is_dir($uploadRelDir) && !mkdir($uploadRelDir, 0755, true)) {
                throw new \RuntimeException('Failed to create upload directory: ' . $uploadRelDir);
            }

            // $baseFileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
            // $baseFileName = 'attatchment_' . time() . '_' . uniqid() . '.' . $baseFileExt;
            $baseFileName = basename($file['name']);

            $filePath = 
                getcwd() . DIRECTORY_SEPARATOR . $uploadRelDir . DIRECTORY_SEPARATOR . $baseFileName;

            $relFilePath = 
                $uploadRelDir . DIRECTORY_SEPARATOR . $baseFileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new \RuntimeException('Failed to move uploaded file to ' . $filePath);
            }
            // Save the attachment information to the block
            $attachmentData = [
                'basename' => $baseFileName,
                'path' => $relFilePath,
                'comment' => $comment
            ];


            $service->addAttachment($attachmentData);

            $this->getRequest()->addMessage(
                'File uploaded successfully: ' . basename($file['name'])
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => $attachmentData
            ]);
            
        } catch (\Throwable $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
            return;
        }
    }
}
