<?php

namespace App\Controller\Service;

use App\Core\Controller;
use App\Model\ServiceRequest;

class Copy extends Controller
{

    /**
     * Handle the service request form submission.
     *
     * @return void
     */
    public function handle(): void
    {
        try {

            if (!$this->getRequest()->request('id')) {
                $this->redirectReferer();
                return;
            }

            $id = (int)$this->getRequest()->request('id');

            $copy = ServiceRequest::createCopy($id);

            if (!$copy) {
                $this->getRequest()->addError('Failed to create a copy of the service request.');
                $this->redirectReferer();
                return;
            }
            $this->getRequest()->addInfo('Service request copied successfully: ' . $copy->getId());
            // Redirect to the edit page of the copied service request
            $this->redirect('/service/request?id=' . $copy->getId());

            return;

        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
        }

        $this->redirectReferer();
    }
}
