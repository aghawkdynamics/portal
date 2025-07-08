<?php

namespace App\Controller\Service\Attachment;

use App\Core\Controller;
use App\Model\ServiceRequestAttachment;

class Download extends Controller
{

    /**
     * Handle the service request form submission.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $id = (int)$this->getRequest()->request('id');
            if (!$id) {
                throw new \InvalidArgumentException('Attachment ID is required.');
            }

            $attachment = (new ServiceRequestAttachment())->load($id);

            if (!$attachment->getId()) {
                throw new \InvalidArgumentException('Attachment not found.');
            }

            $filePath = getcwd() . DIRECTORY_SEPARATOR . $attachment->get('path');
            if (!file_exists($filePath)) {
                throw new \RuntimeException('File does not exist: ' . $filePath);
            }

            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));

            // Read the file and output it to the response
            readfile($filePath);
            exit;
            
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
        }

        $this->redirectReferer();
    }
}
