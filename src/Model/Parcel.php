<?php
namespace App\Model;

use App\Core\Model;
use App\Core\Model\Collection;
use App\Core\Model\CollectionInterface;

class Parcel extends Model
{
    const BLOCKS_LIMIT = 10;
    const TABLE = 'parcel';

    protected string $table = self::TABLE;

    /**
     * @var Account|null
     */
    private ?Account $account = null;

    /**
     * @var CollectionInterface|null
     */
    private ?CollectionInterface $blocks = null;

    /**
     * Get the ID of the parcel.
     *
     * @return int
     */
   public function getAccountId(): int
    {
        return (int)($this->data['account_id'] ?? 0);
    }

    /**
     * Set the account ID for the parcel.
     *
     * @param int $accountId
     * @return $this
     */
    public function getAccount(): Account
    {
        if ($this->account === null) {
            $this->account = (new Account())->load($this->getAccountId());
        }
        return $this->account;
    }

    /**
     * Get the name of the parcel.
     *
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->get('name') ?? null;
    }

    /**
     * Get the collection of blocks associated with this parcel.
     *
     * @return CollectionInterface
     */
    public function getBlocks(): CollectionInterface
    {
        if ($this->blocks === null) {
            $this->blocks = (new Block())
                ->getCollection()
                ->addFilter(['parcel_id' => $this->getId()])
                ->sort('created_at', 'DESC')
                ->setItemMode(Collection::ITEM_MODE_OBJECT);
        }

        return $this->blocks;
    }

    /**
     * Get a specific block by its ID.
     *
     * @param int $blockId
     * @return Block
     */
    public function getBlock(int $blockId): Block
    {
        return (new Block())->load($blockId);
    }


    /**
     * Check if the parcel has any blocks associated with it.
     *
     * @return bool
     */
    public function hasBlocks(): bool
    {
        return !$this->getBlocks()->isEmpty();
    }

    /**
     * Get the IDs of the blocks associated with this parcel.
     *
     * @return array
     */
    public function getBlockIds(): array
    {
        $blockIds = [];
        foreach ($this->getBlocks() as $block) {
            $blockIds[] = $block->getId();
        }

        return $blockIds;
    }

    /**
     * Validate block data before saving.
     *
     * @param array $blockData
     * @return array
     */
    public function isBlockLimitReached(): bool
    {
        return $this->getBlocks()->count() >= self::BLOCKS_LIMIT;
    }
    
    /**
     * Check if the user can request a service based on the presence of blocks.
     *
     * @return bool
     */
    public function canRequestService(): bool
    {
        return !$this->getBlocks()->isEmpty(); 
    }

    /**
     * Check if the user can self-track based on the presence of blocks.
     *
     * @return bool
     */
    public function canSelfTrack(): bool
    {
        return !$this->getBlocks()->isEmpty(); 
    }

}
