<?php
namespace App\Model;

use App\Core\Database;
use App\Core\Model;
use App\Core\Model\Collection;
use App\Model\Account\User;
use App\Model\ServiceRequest\Adds;
use App\Model\ServiceRequest\ServiceParcel;

class ServiceRequest extends Model
{
    const TABLE = 'service_request';

    const COLUMN_KIND = 'kind';
    const COLUMN_STATUS = 'status';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    const KIND_REQUEST = 'request';
    const KIND_SELF_TRACKING = 'self_tracking';

    const KINDS = [
        self::KIND_REQUEST,
        self::KIND_SELF_TRACKING,
    ];

    private ?Account $account = null;
    // private ?Parcel $parcel = null;
    // private ?Block $block = null;

    protected string $table = self::TABLE;

    /**
     * Create a new service request.
     *
     * @param array $data
     * 
     * @return static
     * @throws \RuntimeException
     */
    public static function createRequest(array $data, ?int $account_id = null ): static
    {
        //account_id is optional, if not provided, use the current user's ID
        //Or if the data already contains an account_id, use that
        $data['account_id'] ??= $account_id ?? User::uid();

        $data['adds'] = is_array($data['adds']) ? json_encode($data['adds'] ?? []) : $data['adds'];


        $data['kind'] ??= ServiceRequest::KIND_REQUEST;
        $data['status'] ??= $data['kind'] == ServiceRequest::KIND_REQUEST 
            ? ServiceRequest::STATUS_PENDING 
            : ServiceRequest::STATUS_COMPLETED;

        // Remove empty parcels and ensure each has a valid parcel_id and block_ids
        $data['parcels']  = array_filter(
                $data['parcels'] ?? [],
                function ($parcel, $index) {
                    return (int)$index >= 0 && !empty($parcel['parcel_id']) && !empty($parcel['block_ids']);
                },
                ARRAY_FILTER_USE_BOTH
        );

        self::validateServiceData($data);

        try {
            Database::beginTransaction();
            $serviceRequest = (new static());

            $serviceRequest
                ->setData($data)
                ->save();

            foreach ($data['parcels'] as $parcel) {
                $serviceRequest->addParcel($parcel);
            }

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollback();
            throw new \RuntimeException('Failed to create service request: ' . $e->getMessage());
        }

        if (!$serviceRequest->getId()) {
            throw new \RuntimeException('Service request creation failed');
        }

        return $serviceRequest;
    }

    /**
     * Edit an existing service request.
     *
     * @param array $data
     * 
     * @return static
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function editService(array $data): static
    {
        if (empty($data['id'])) {
            throw new \InvalidArgumentException('Service request ID is required for editing.');
        }

        $serviceRequest = (new static())->load($data['id']);

        if (!$serviceRequest->getId()) {
            throw new \RuntimeException('Service request not found.');
        }

        // Validate and update data
        $data = array_replace($serviceRequest->getData(), $data);

        self::validateServiceData($data);
        $serviceRequest->setData($data);

        $serviceRequest->save();

        // Update parcels
        $serviceRequest->clearParcels();
        foreach ($data['parcels'] as $parcel) {
            $serviceRequest->addParcel($parcel);
        }

        return $serviceRequest;
    }

    /**
     * Create a copy of an existing service request.
     *
     * @param int $id
     * 
     * @return static
     * @throws \RuntimeException
     */
    public static function createCopy(int $id): static
    {
        $original = (new static())->load($id);

        if (!$original->getId()) {
            throw new \RuntimeException('Service request not found.');
        }

        // Create a new service request with the same data, but reset certain fields
        $data = $original->getData();
        $data['parcels'] = []; // Reset parcels for the new request
        foreach ($original->getServiceParcels() as $parcel) {
            $data['parcels'][] = [
                ServiceParcel::COLUMN_PARCEL_ID => $parcel->get('parcel_id'),
                ServiceParcel::COLUMN_BLOCK_IDS => $parcel->get('block_ids'),
            ];
        }

        unset(
            $data['id'], 
            $data['date'], 
            $data['created_at'], 
            $data['updated_at'], 
            $data['completed_at'], 
            $data['completed_by'], 
            $data['complete_data'], 
            $data['urgent']
        );

        $data['status'] = ServiceRequest::STATUS_PENDING; // Reset status for new request
        
        // Create a new service request
        $service = self::createRequest($data);

        // Copy parcels
        // foreach ($original->getServiceParcels() as $parcel) {
        //     $service->addParcel([
        //         ServiceParcel::COLUMN_PARCEL_ID => $parcel->get('parcel_id'),
        //         ServiceParcel::COLUMN_BLOCK_IDS => $parcel->get('block_ids'),
        //     ]);
        // }
       

        return $service;
    }

    /**
     * {@inheritdoc}
     * 
     * Prepares the 'adds' field before saving the service request.
     */
    protected function _beforeSave(): void
    {
        // Prepare 'adds' field for storage
        $this->setData(
            Adds::prepare(
                $this->getData()
            )
        );

        parent::_beforeSave();
    }


    /**
     * Get the collection of service parcels associated with this service request.
     *
     * @return \Traversable
     */
    public function getServiceParcels(): \Traversable
    {
        $collection = (new ServiceParcel())->getCollection();
        $collection->setItemMode(Collection::ITEM_MODE_OBJECT);
        $collection->addFilter([ServiceParcel::COLUMN_SERVICE_ID => $this->getId()]);
        $collection->setPageSize(10000); // Adjust as needed

        foreach( $collection as $serviceParcel) {
            yield $serviceParcel;
        }
    }

    /**
     * Get the ID of the service request.
     *
     * @return ?int
     */
    public function getAccountId(): ?int
    {
        return $this->get('account_id');
    }

    /**
     * Get the custom products associated with the service request.
     *
     * @return array
     */
    public function getCustomProducts(): array
    {
        $adds = $this->getAdditionalData();
        return $adds[Adds::FIELD_PRODUCTS] ?? [];
    }

    /**
     * Get a specific custom product by index.
     *
     * @param int $index
     * @return mixed|null
     */
    public function getCustomProduct(int $index)
    {
        $products = $this->getCustomProducts();
        return $products[$index] ?? null;
    }

    /**
     * Check if the service request has custom products.
     *
     * @return bool
     */
    public function hasCustomProducts(): bool
    {
        $products = $this->getCustomProducts();

        return !empty($products) && is_array($products);

    }

    /**
     * Get the custom supplier associated with the service request.
     *
     * @return mixed
     */
    public function getCustomSupplier(?string $key = null): mixed
    {
        $adds = Adds::extractAdds($this->getData());
        $supplier = $adds[Adds::FIELD_SUPPLIER] ?? [];
        
        if ($key !== null) {
            return $supplier[$key] ?? null;
        }

        return $supplier;
    }

    /**
     * Check if the service request has a custom supplier.
     *
     * @return bool
     */
    public function hasCustomSupplier(): bool
    {
        return !empty($this->getCustomSupplier('supplier'));
    }

    /**
     * Get the status of the service request.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->get('status') ?? self::STATUS_PENDING;
    }

    /**
     * Set the status of the service request.
     *
     * @param string $status
     * @throws \InvalidArgumentException
     */
    public function setStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->set('status', $status);
    }

    /**
     * Check if the service request is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->getStatus() === self::STATUS_COMPLETED;
    }

    /**
     * Check if the service request is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    /**
     * Check if the service request is of kind 'request'.
     *
     * @return bool
     */
    public function isSelfTracking(): bool
    {
        return $this->get('kind') === self::KIND_SELF_TRACKING;
    }

    /**
     * Check if the service request can be completed.
     *
     * @return bool
     */
    public function canCancel(): bool
    {
        return 
            in_array($this->getStatus(), [self::STATUS_PENDING])
            && $this->get('kind') !== self::KIND_SELF_TRACKING;
    }

    public function isCancelled(): bool
    {
        return $this->getStatus() === self::STATUS_CANCELLED;
    }

    /**
     * Check if the service request can be completed.
     *
     * @return bool
     */
    public function canComplete(): bool
    {
        return in_array($this->getStatus(), [self::STATUS_PENDING]);
    }

    /**
     * Check if the service request can be edited.
     *
     * @return bool
     */
    public function canEdit(): bool
    {
        return in_array($this->getStatus(), [self::STATUS_PENDING]);
    }

    /**
     * Complete the service request.
     * 
     * @param array $data Additional data to store with the completion.
     * 
     * @return static
     */
    public function complete(array $data = []): static
    {
        if (!$this->canComplete()) {
            throw new \Exception('Service request cannot be completed');
        }

        $this->setStatus(self::STATUS_COMPLETED);
        $this->set('complete_data', json_encode($data));
        $this->set('completed_at', date('Y-m-d H:i:s'));
        $this->set('completed_by', User::getInstance()->getId());
        $this->save();

        return $this;
    }

    /**
     * Get the complete data associated with the service request.
     *
     * @return array
     */
    public function getCompleteData(): array
    {
        $data = $this->get('complete_data');
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        return is_array($data) ? $data : [];
    }

    /**
     * Set the complete data for the service request.
     *
     * @param array $data
     */
    public function setCompleteData(array $data): void
    {
        $this->set('complete_data', json_encode($data));
    }

    /**
     * Add a parcel to the service request.
     *
     * @param array $data
     * 
     * @return ServiceParcel
     * @throws \InvalidArgumentException
     */
    public function addParcel(array $data): ServiceParcel
    {
        if (!$this->getId()) {
            throw new \RuntimeException('Cannot add parcel to non-existent service request');
        }

        $parcelId = (int)$data[ServiceParcel::COLUMN_PARCEL_ID];

        if (empty($parcelId) || !is_numeric($parcelId) || $parcelId <= 0) {
            throw new \InvalidArgumentException('Cannot add parcel: Invalid parcel ID');
        }

        //  prepare block IDs: explode by comma, trim each ID, and join back with commas
        $blockIds = join(
            ',', 
            array_map('trim', explode(
                ',', 
                $data[ServiceParcel::COLUMN_BLOCK_IDS]
            ))
        );

        if (empty($blockIds)) {
            throw new \InvalidArgumentException('Cannot add parcel: Block IDs cannot be empty');
        }

        $serviceParcel = (new ServiceParcel())
            ->setData([
                ServiceParcel::COLUMN_SERVICE_ID => $this->getId(),
                ServiceParcel::COLUMN_PARCEL_ID => $parcelId,
                ServiceParcel::COLUMN_BLOCK_IDS => $blockIds,
            ])
            ->save();

        return $serviceParcel;
    }

    /**
     * Clear all parcels associated with this service request.
     *
     * @throws \RuntimeException
     */
    public function clearParcels(): void
    {
        $collection = (new ServiceParcel())->getCollection();
        $collection->setItemMode(Collection::ITEM_MODE_OBJECT);
        $collection->addFilter([ServiceParcel::COLUMN_SERVICE_ID => $this->getId()]);

        foreach ($collection as $serviceParcel) {
            $serviceParcel->delete($serviceParcel->getId());
        }
    }


    /**
     * Get the additional data associated with the service request.
     *
     * @return array
     */
    public function getAdditionalData(): array
    {
        return Adds::extractAdds($this->getData());

        // $adds = $this->get('adds');
        // if (is_string($adds)) {
        //     $adds = json_decode($adds, true);
        // }
        // return is_array($adds) ? $adds : [];
    }

    /**
     * Set additional data for the service request.
     *
     * @param array $adds
     */
    public function setAdditionalData(array $adds): void
    {
        $this->set('adds', json_encode($adds));
    }

   

    /**
     * Get the user who created the service request.
     *
     * @return User
     */
    public function getAccount(): Account
    {
        if (!$this->account instanceof Account) {
            $this->account = (new Account())
            ->load($this->get('account_id'));
        }

        return $this->account;
    }

    /**
     * Get the total acres of all service parcels associated with this service request.
     *
     * @return float
     */
    public function getTotalServiceAcres(): float
    {
        $totalAcres = 0.00;
        foreach ($this->getServiceParcels() as $serviceParcel) {
            $totalAcres += $serviceParcel->getTotalAcres();
        }
        return $totalAcres;
    }

    /**
     * Get the supplier data associated with the service request.
     *
     * @return mixed
     */
    public function getSupplierData(?string $key = null): mixed
    {
        $adds = Adds::extractAdds($this->getData());
        $data = $adds[Adds::FIELD_SUPPLIER] ?? [];

        if ($key !== null) {
            return $data[$key] ?? [];
        }

        return $data;
    }


    /**
     * Add an attachment to this service request.
     *
     * @param array $data
     */
    public function addAttachment(array $data): void
    {
        $data['service_request_id'] = $this->getId();
        if (empty($data['path']) || !file_exists($data['path'])) {
            throw new \InvalidArgumentException('Attachment path is invalid or does not exist.');
        }

        (new ServiceRequestAttachment())
            ->setData($data)
            ->save();
    }
    
    /**
     * Get the collection of attachments associated with this service request.
     *
     * @return Collection
     */
    public function getAttachments(): Collection
    {
        $collection = (new ServiceRequestAttachment())->getCollection();
        $collection->setItemMode(Collection::ITEM_MODE_OBJECT);
        $collection->addFilter(['service_request_id' => $this->getId()]);
        $collection->sort('created_at', 'DESC');

        return $collection;
    }

    /**
     * Delete an attachment by its ID.
     *
     * @param int $id
     * @throws \InvalidArgumentException
     */
    public function deleteAttachment(int $id): void
    {
        $attachment = (new ServiceRequestAttachment())->load($id);
        if (!$attachment->getId()) {
            throw new \InvalidArgumentException('Attachment not found.');
        }

        $path = $attachment->get('path');

        $attachment->delete($attachment->getId());

        if ($path && file_exists($path)) {
            if (!unlink($path)) {
                //throw new \RuntimeException('Failed to delete attachment file: ' . $path);
            }
        }



    }

    /**
     * Validate service request data.
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    public static function validateServiceData(array $data): void
    {
        if (empty($data['account_id'])) {
            throw new \InvalidArgumentException('Account ID is required.');
        }

        // if ((int)$data['account_id'] !== (int)User::uid() && !User::isAdmin()) {
        //     throw new \InvalidArgumentException('Permissions issue');
        // }

        if (empty($data['type'])) {
            throw new \InvalidArgumentException('Service type is required.');
        }

        
        if (empty($data['kind']) || !in_array($data['kind'], self::KINDS)) {
            throw new \InvalidArgumentException('Invalid service kind.');
        }

        if (empty($data['status']) || !in_array($data['status'], self::STATUSES)) {
            throw new \InvalidArgumentException('Invalid service status.');
        }
        
        if (!is_array($data['parcels']) || empty($data['parcels'])) {
            throw new \InvalidArgumentException('Parcels are required.');
        }

    }

}
