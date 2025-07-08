<?php

namespace App\Controller\Service\Attachment;

use App\Core\Controller;
use App\Model\Account\User;
use App\Model\ServiceRequest;
use App\Model\ServiceRequestAttachment;

class Delete extends Controller
{

    /**
     * Handle the service request form submission.
     *
     * @return void
     */
    public function handle(): void
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
