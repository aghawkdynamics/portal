<?php
namespace App\Model;

use App\Core\Model;
use App\Core\Model\Collection;

class Block extends Model
{
    const TABLE = 'block';

    const COLUMN_ID = 'id';
    const COLUMN_NAME = 'name';
    const COLUMN_PARCEL_ID = 'parcel_id';
    const COLUMN_ACCOUNT_ID = 'account_id';
    const COLUMN_ACRES = 'acres';
    const COLUMN_CREATED_AT = 'created_at';
    
    protected string $table = self::TABLE;

    private ?Account $account = null;

    /**
     * Get the account ID associated with this block.
     *
     * @return int
     */
    public function getAccountId(): int
    {
        return (int)($this->data[static::COLUMN_ACCOUNT_ID] ?? 0);
    }

    /**
     * Get the account associated with this block.
     *
     * @return Account
     */
    public function getAccount(): Account
    {
        if (!$this->account instanceof Account) {
            $this->account = (new Account())->load($this->getAccountId());
        }

        return $this->account;
    }
    /**
     * Get the ID of the block.
     *
     * @return int
     */
    public function getId(): int
    {
        return (int)($this->data[static::COLUMN_ID] ?? 0);
    }

    /**
     * Set the ID of the block.
     *
     * @param int $id
     * @return $this
     */
    public function getAcres(): float
    {
        return (float)($this->data[static::COLUMN_ACRES] ?? 0.00);
    }


    /**
     * Get the ID of the block.
     *
     * @return int
     */
    public function getName(): string
    {
        return $this->data[static::COLUMN_NAME] ?? '';
    }

    /**
     * Get the Parcel ID associated with this block.
     *
     * @return int
     */
    public function getParcelId(): int
    {
        return (int)($this->data[static::COLUMN_PARCEL_ID] ?? 0);
    }

    /**
     * Get the Parcel associated with this block.
     * 
     * @return Parcel
     */
    public function getParcel(): Parcel
    {
        return (new Parcel())
            ->load($this->data[static::COLUMN_PARCEL_ID] ?? 0);
    }

    /**
     * Add an attachment to this block.
     * 
     * @param array $data
     */
    public function addAttachment(array $data): void
    {
        $data['block_id'] = $this->getId();
        if (empty($data['path']) || !file_exists($data['path'])) {
            throw new \InvalidArgumentException('Attachment path is invalid or does not exist.');
        }

        (new BlockAttachment())
            ->setData($data)
            ->save();
    }
    
    /**
     * Get the collection of attachments associated with this block.
     *
     * @return Collection
     */
    public function getAttachments(): Collection
    {
        $collection = (new BlockAttachment())->getCollection();
        $collection->setItemMode(Collection::ITEM_MODE_OBJECT);
        $collection->addFilter(['block_id' => $this->getId()]);
        $collection->sort('created_at', 'DESC');

        return $collection;
    }

    /**
     * Delete an attachment by its ID.
     *
     * @param int $attachmentId
     * @throws \InvalidArgumentException
     */
    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = (new BlockAttachment())->load($attachmentId);
        if (!$attachment->getId()) {
            throw new \InvalidArgumentException('Attachment not found.');
        }

        $path = $attachment->get('path');
        
        if ($path && file_exists($path)) {
            unlink($path);
        }

        $attachment->delete($attachment->getId());
    }

}
