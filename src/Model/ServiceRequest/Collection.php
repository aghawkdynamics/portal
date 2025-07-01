<?php
namespace App\Model\ServiceRequest;

use App\Model\ServiceRequest;

class Collection extends \App\Core\Model\Collection
{

    public function __construct()
    {
        parent::__construct(ServiceRequest::class);
    }
}