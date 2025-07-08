<?php
namespace App\Model;

use App\Core\Model;

class ServiceRequestAttachment extends Model
{
    protected string $table = 'service_request_attachment';


    /**
     * Get the ID of the attachment.
     *
     * @return int
     */
    public function getService(): ServiceRequest
    {
        $serviceId = $this->get('service_request_id');

        $service = (new ServiceRequest())->load((int)$serviceId);

        if (!$service->getId()) {
            throw new \RuntimeException('Service request not found for attachment ID: ' . $this->get('id'));
        }

        return $service;
    }

    /**
     * Get the URL of the attachment.
     *
     * @return string
     */
    public function getUrl(): string
    {

        return '/service/attachment/download?id=' . $this->get('id');
    }

    
    /**
     * Get the file path of the attachment.
     *
     * @return string
     */
    public function getBaseName(): string
    {
        $base = $this->get('basename', '');
        if (empty($base)) {
            $base = $this->get('path', '');
        }
        
        return basename($base);
    }

    /**
     * Get the name of the attachment.
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->get('comment') ?? '';
    }

}
