<?php

namespace App\Controller\Service;

use App\Core\Controller;
use App\Core\Model\Collection;
use App\Core\Registry;
use App\Model\Account\User;
use App\Model\Parcel;
use App\Model\ServiceRequest;

class Edit extends Controller
{


    /**
     * Handle the service request form submission.
     *
     * @return void
     */
    public function handle(): void
    {
        try {

            if (!$this->getRequest()->isPost()) {
                $this->redirectReferer();
                return;
            }

            $data = $this->getRequest()->getPost('service');

            /**
             @todo: centalize this logic (see create)
             */
            $data['parcels']  = array_filter(
                $data['parcels'] ?? [],
                function ($parcel, $index) {
                    return (int)$index >= 0 && !empty($parcel['parcel_id']) && !empty($parcel['block_ids']);
                },
                ARRAY_FILTER_USE_BOTH
        );

            ServiceRequest::editService($data);

        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
        }

        $this->redirectReferer();
    }
}
