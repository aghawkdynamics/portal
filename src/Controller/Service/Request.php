<?php

namespace App\Controller\Service;

use App\Core\Controller;
use App\Core\Model\Collection;
use App\Core\Registry;
use App\Model\Account\User;
use App\Model\Parcel;
use App\Model\ServiceRequest;

class Request extends Controller
{


    /**
     * Handle the service request form submission.
     *
     * @return void
     */
    public function handle(): void
    {
        try {

            if ($this->getRequest()->isPost()) {
                $this->create(
                    $this->getRequest()->getPost('service')
                );
                return;
            }

            if ($this->getRequest()->request('id')) {
                $serviceModel = (new ServiceRequest())
                    ->load((int)$this->getRequest()->request('id'));
                if (!$serviceModel->getId()) {
                    throw new \InvalidArgumentException('Service request not found.');
                }
            }

            $parcelCollection = (new Parcel())
                ->getCollection()
                ->setItemMode(Collection::ITEM_MODE_OBJECT)
                ->addFilter(['account_id' => (int)($serviceModel?->get('account_id') ?? User::uid())])
                ->sort('name', 'DESC')
            ;

            /**
             * @todo: new logic: preselect parcels or parcel + block ( seems done)
             */
            $requestedParcelId = (int)$this->getRequest()->request('parcel', 0);
            $requestedParcel = null;
            if ($requestedParcelId) {
                $requestedParcel = (new Parcel())->load($requestedParcelId);
                if (!$requestedParcel->getId()) {
                    throw new \InvalidArgumentException('Requested parcel not found.');
                }
            }
            $requestedBlockId = (int)$this->getRequest()->request('block', 0);

            $this->render(
                'service/request',
                [
                    'serviceModel' => $serviceModel,
                    'readonly' => Registry::get('service_request_readonly', false),
                    'kind' => Registry::get('kind') ?? $this->getRequest('kind', ServiceRequest::KIND_REQUEST),
                    'parcelCollection' => $parcelCollection,
                    'requestedParcel' => $requestedParcel,
                    'requestedBlockId' => $requestedBlockId,
                ]
            );
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
            $this->redirectReferer();
        }
    }

    /**
     * Create a new service request.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function create(array $data): void
    {
        try {
            
            ServiceRequest::createRequest($data);

            $this->getRequest()->addMessage('Service Request has been submitted successfully.');
            
            $this->redirect('/service');

        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
            $this->redirectReferer();
        }
    }
}
