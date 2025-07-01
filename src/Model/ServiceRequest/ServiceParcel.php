<?php
namespace App\Model\ServiceRequest;

use App\Core\Model;
use App\Model\Parcel;

class ServiceParcel extends Model
{
    const TABLE = 'service_parcel';

    const COLUMN_SERVICE_ID = 'service_id';
    const COLUMN_PARCEL_ID = 'parcel_id';
    const COLUMN_BLOCK_IDS = 'block_ids';

    protected string $table = self::TABLE;

    /**
     * Get the ID of the parcel associated with this service parcel.
     *
     * @return int
     */
    public function getParcel(): Parcel
    {
        $parcelId = $this->get(self::COLUMN_PARCEL_ID);
        if (!$parcelId) {
            throw new \RuntimeException('Parcel ID is not set for this service parcel.');
        }

        return (new Parcel())->load($parcelId);
    }

    /**
     * Get the ID of the service associated with this service parcel.
     *
     * @return int
     */
    public function getServiceId(): int
    {
        return (int)($this->get(self::COLUMN_SERVICE_ID) ?? 0);
    }

    /**
     * Get the IDs of the blocks associated with this service parcel.
     *
     * @return array
     */
    public function getBlockIds(): array
    {
        $blockIds = $this->get(self::COLUMN_BLOCK_IDS);
        if (is_string($blockIds)) {
            $blockIds = explode(',', $blockIds);
        }
        return is_array($blockIds) ? $blockIds : [];
    }

    /**
     * Get the blocks associated with this service parcel.
     *
     * @return \Traversable
     */
    public function getBlocks(): \Traversable
    {
        foreach ($this->getBlockIds() as $blockId) {
            yield (new \App\Model\Block())->load($blockId);
            
        }
    }

    /**
     * Get the total acres of all blocks associated with this service parcel.
     *
     * @return float
     */
    public function getTotalAcres(): float
    {
        $totalAcres = 0.0;
        foreach ($this->getBlocks() as $block) {
            $totalAcres += $block->getAcres();
        }

        return $totalAcres;
    }
}