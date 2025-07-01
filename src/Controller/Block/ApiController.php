<?php
namespace App\Controller\Block;

use App\Core\ApiController as CoreApiController;
use App\Core\Exception\ApiException;
use App\Core\Model\Collection;
use App\Model\Account\User;
use App\Model\Parcel;

class ApiController extends CoreApiController
{
   public function list(): void
   {
    try{
        $parcelId = (int)$this->getRequest()->request('parcel_id', 0);
        if (!$parcelId) {
            
            throw new ApiException(
                'Parcel ID is required',
                400
            );
        }

        $parcel = (new Parcel())->load($parcelId);
        if (!$parcel->getId()) {
            
            throw new ApiException(
                'Parcel not found',
                404
            );
        }

        //test
        $u = User::uid();
        $pu = $parcel->getAccountId();
        $isa = User::isAdmin();
        //------------

        if (!User::isAdmin() && (User::uid() != $parcel->getAccountId())) {
            throw new ApiException(
                'You do not have permission to access this parcel.',
                403
            );
        }

        $blocks = $parcel->getBlocks()
            ->setItemMode(Collection::ITEM_MODE_ARRAY)
            ->sort('name', 'ASC');

        $this->json( iterator_to_array($blocks) );
    } catch (\Exception $e) {
        
        $this->jsonError($e->getMessage(), $e->getCode());
    }

   }
}
