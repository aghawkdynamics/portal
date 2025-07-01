<?php
namespace App\Controller\Service;

use App\Core\Registry;
use App\Model\Account\User;
use App\Model\ServiceRequest;

class Track extends Request
{

    public function handle(): void
    {
        Registry::set('kind', ServiceRequest::KIND_SELF_TRACKING);
        parent::handle();
    }

    /**
     * Create a new service request.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function create(array $data): void
    {
        if (!User::Account()->isSubscribed() && !User::isAdmin()) {
            $this->getRequest()->addError('You must be subscribed to use self-tracking services.');
            $this->redirectReferer();
            return;
        }

        $data[ServiceRequest::COLUMN_KIND] = ServiceRequest::KIND_SELF_TRACKING;

        parent::create($data);
    }
}
