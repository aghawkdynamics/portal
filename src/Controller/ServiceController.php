<?php
namespace App\Controller;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Model\Collection;
use App\Core\Registry;
use App\Model\Account;
use App\Model\Account\User;
use App\Model\Block;
use App\Model\Parcel;
use App\Model\ServiceRequest;

class ServiceController extends Controller
{

    /**
     * ServiceController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        //$this->setTitle('Service Requests');
    }

    /**
     * View a specific service request in read-only mode.
     *
     * @return void
     */
    public function view()
    {
        Registry::set('service_request_readonly', true);

        $this->forward('service','request');
            
    }

    /**
     * Display the edit form for a service request.
     *
     * @return void
     */
    protected function editForm()
    {
        try {
            $requestId = $this->getRequest()->request('id') ?? 0;
            $serviceModel = (new ServiceRequest())
                ->load((int)$requestId);

            if (!$serviceModel->getId()) {
                throw new \InvalidArgumentException('Service request not found.');
            }

            if (!User::isAdmin() && $serviceModel->getAccountId() !== User::uid()) {
                throw new \InvalidArgumentException('You do not have permission to access this service request.');
            }

            $this->render(
                'service/request', 
                [
                    'serviceModel' => $serviceModel
                ]
            );
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
            
        } finally {
            $this->redirectReferer();
        }
    }

    

    /**
     * View details of a specific service request.
     *
     * @return void
     */
    public function details()
    {
        $requestId = (int)$this->getRequest()->request('id', 0);
        if (!$requestId) {
            $this->getRequest()->addError('Invalid service request ID.');
            $this->redirectReferer();
            return;
        }

        $serviceRequest = (new ServiceRequest())->load($requestId);
        if (!$serviceRequest->getId()) {
            $this->getRequest()->addError('Service request not found.');
            $this->redirectReferer();
            return;
        }

        if (!User::isAdmin() && $serviceRequest->getAccount()->getId() !== User::getInstance()->getId()) {
            $this->getRequest()->addError('You do not have permission to view this service request.');
            $this->redirectReferer();
            return;
        }

        $this->render('service/details', ['serviceModel' => $serviceRequest]);
        //$this->render('service/request', ['serviceModel' => $serviceRequest]);
    }

    /*
     * @return void
     */
    public function complete_details()
    {
        $requestId = (int)$this->getRequest()->request('id', 0);

        try {
            $serviceRequest = (new ServiceRequest())->load($requestId);

            if (!$serviceRequest->getId()) {
                throw new \InvalidArgumentException('Service request not found.');
            }

            if (!User::isAdmin() && $serviceRequest->getAccount()->getId() !== User::getInstance()->getId()) {
                throw new \InvalidArgumentException('You do not have permission to view this service request.');
            }

            $this->render('service/complete_details', ['serviceModel' => $serviceRequest]);

        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
        }

        $this->redirectReferer();
    }

    /**
     * View details of a specific service request.
     *
     * @return void
     */
    public function cancel(): void
    {
        $requestId = (int)$this->getRequest()->request('id', 0);        

        if (!$requestId) {
            $this->getRequest()->addError('Invalid service request ID.');
            $this->redirectReferer();
            return;
        }

        $serviceRequest = (new ServiceRequest())->load($requestId);
        if (!$serviceRequest->getId()) {
            $this->getRequest()->addError('Service request not found.');
            $this->redirectReferer();
            return;
        }

        if (!User::isAdmin() && $serviceRequest->getAccount()->getId() !== User::getInstance()->getId()) {
            $this->getRequest()->addError('You do not have permission to cancel this service request.');
            $this->redirectReferer();
            return;
        }

        if ($serviceRequest->canCancel()) {
            $serviceRequest->setStatus(ServiceRequest::STATUS_CANCELLED);

            $serviceRequest->save();
            $this->getRequest()->addMessage('Service request has been cancelled successfully.');
        } else {
            $this->getRequest()->addError('This service request cannot be cancelled.');
        }

        $this->redirectReferer();
    }

    /**
     * Uncancel a cancelled service request.
     *
     * @return void
     */
    public function uncancel(): void
    {
        try {
            $requestId = (int)$this->getRequest()->request('id', 0);
            if (!$requestId) {
                throw new \InvalidArgumentException('Invalid service request ID.');
            }

            $serviceRequest = (new ServiceRequest())->load($requestId);
            if (!$serviceRequest->getId()) {
                throw new \InvalidArgumentException('Service request not found.');
            }

            if (!User::isAdmin() && $serviceRequest->getAccount()->getId() !== User::getInstance()->getId()) {
                throw new \InvalidArgumentException('You do not have permission to uncancel this service request.');
            }

            if ($serviceRequest->isCancelled()) {

                $serviceRequest->setStatus(ServiceRequest::STATUS_PENDING);
                $serviceRequest->save();
                $this->getRequest()->addMessage('Service request has been restored successfully.');

            } else {
                throw new \InvalidArgumentException('This service request is not cancelled.');
            }
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
        }

        $this->redirectReferer();
    }

    

    /**
     * Complete a service request.
     *
     * @return void
     */
    public function complete(): void
    {
        try {
            $requestId = (int)$this->getRequest()->request('id', 0);
            $completeData = $this->getRequest()->post('complete', []);
            if (!$requestId) {
                throw new \InvalidArgumentException('Invalid service request ID.');
            }

            $serviceRequest = (new ServiceRequest())->load($requestId);
            if (!$serviceRequest->getId()) {
                throw new \InvalidArgumentException('Service request not found.');
            }

            if (!User::isAdmin() && $serviceRequest->getAccount()->getId() !== User::getInstance()->getId()) {
                throw new \InvalidArgumentException('You do not have permission to complete this service request.');
            }

            if ($serviceRequest->canComplete()) {
                $serviceRequest->complete($completeData);

                $this->getRequest()->addMessage('Service request has been marked as completed successfully.');
            } else {
                throw new \InvalidArgumentException('This service request cannot be completed at this time.');
            }
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
            $this->redirectReferer();
            return;
        }

        $this->redirectReferer();
    }

    /**
     * Uncomplete a completed service request.
     *
     * @return void
     */
    public function uncomplete(): void
    {
        try {
            if (!User::isAdmin()) {
                throw new \InvalidArgumentException('You do not have permission to uncomplete this service request.');
            }

            $requestId = (int)$this->getRequest()->request('id', 0);

            if (!$requestId) {
                throw new \InvalidArgumentException('Invalid service request ID.');
            }

            $serviceRequest = (new ServiceRequest())->load($requestId);

            if (!$serviceRequest->getId()) {
                throw new \InvalidArgumentException('Service request not found.');
            }

            if ($serviceRequest->isCompleted()) {
                $serviceRequest->setStatus(ServiceRequest::STATUS_PENDING);
                $serviceRequest->setCompleteData([]);
                $serviceRequest->save();

                $this->getRequest()->addMessage('Service request has been restored to pending status successfully.');
            } else {
                throw new \InvalidArgumentException('This service request is not completed.');
            }
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
        }

        $this->redirectReferer();
    }

    /**
     * Upload an attachment to a service request.
     *
     * @return void
     */
    public function uploadAttachment(): void
    {
        try {
            // if (!User::isAdmin() ){
            //     throw new \RuntimeException('Access denied. Only admins can upload attachments.');
            // }

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

            if (!$this->getRequest()->isPost()) {
                throw new \RuntimeException('Invalid request method. Please use POST to upload files.');
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

            $filePath = 
                getcwd() . DIRECTORY_SEPARATOR . $uploadRelDir . DIRECTORY_SEPARATOR . basename($file['name']);

            $relFilePath = 
                $uploadRelDir . DIRECTORY_SEPARATOR . basename($file['name']);

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new \RuntimeException('Failed to move uploaded file to ' . $filePath);
            }
            // Save the attachment information to the block
            $attachmentData = [
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

    /**
     * Delete an attachment from a service request.
     *
     * @return void
     */
    public function deleteAttachment(): void
    {
        if (!User::isAdmin()) {
            $this->getRequest()->addError('Access denied. Only admins can delete attachments.');
            $this->redirectReferer();
            return;
        }

        try {
            $serviceId = (int)$this->getRequest()->request('service_id', 0);
            $attachmentId = (int)$this->getRequest()->request('attachment_id', 0);
            if (!$attachmentId) {
                throw new \InvalidArgumentException('Invalid attachment ID.');
            }

            $service = (new ServiceRequest())->load($serviceId);
            if (!$service->getId()) {
                throw new \InvalidArgumentException('Service not found.');
            }

            $service->deleteAttachment($attachmentId);

            $this->getRequest()->addMessage('Attachment deleted successfully.');
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
        }

        $this->redirectReferer();
    }

    
}
