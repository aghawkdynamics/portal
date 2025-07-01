<?php
namespace App\Controller;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Model\Collection;
use App\Model\Account;
use App\Model\Account\User;
use App\Model\Block;
use App\Model\Parcel;

class BlockController extends Controller
{

    /**
     * Display a list of blocks for the logged-in user.
     *
     * @return void
     */
    public function index(): void
    {
        $blockCollection = (new Block())
            ->getCollection()
            ->setItemMode(Collection::ITEM_MODE_OBJECT)
            ->join(Parcel::TABLE, sprintf('main.parcel_id = %s.id',Parcel::TABLE))
            ->join(Account::TABLE, sprintf('main.account_id = %s.id', Account::TABLE))
            ->applyPostFilters($this->getRequest('filters', []))
            ->sort('created_at', 'DESC');

        if (!User::isAdmin()) {
            $blockCollection->addFilter(['main.account_id' => User::getInstance()->getId()]);
        }
        
        $blockCollection->setPage((int)$this->getRequest('page', 1));

        $this->render('block/index', [
            'blocks' => $blockCollection,
            'filters' => $this->getRequest('filters', []),
        ]);
    }

    /**
     * Add a new block to a parcel.
     *
     * @return void
     */
    public function add(): void
    {
        if (!$this->getRequest()->isPost()) {

            $parcels = (new Parcel())->getCollection()
                ->setItemMode(Collection::ITEM_MODE_ARRAY);

            if (!User::isAdmin()) {
                $parcels->addFilter(['account_id' => User::uid()]);
            }

            $parcels->sort('name', 'ASC');

            $this->render('block/block', [
                'parcels' => $parcels,
            ]);

            return;
        }

        
        try {
            $blockData = $this->getRequest()->post('block');
            
            $parcel = (new Parcel())->load($blockData['parcel_id']);

            $blockData['account_id'] = $blockData['account_id'] ?? $parcel->getAccountId();

            $blockData = $this->validateBlockData($blockData);

            if (!$parcel->getId()) {
                throw new \InvalidArgumentException('Parcel not found');
            }

            if ($parcel->isBlockLimitReached()) {
                throw new \InvalidArgumentException('Blocks limit for the parcel is reached');
            }
        
            (new Block())
                //->setData($blockData)
                //add account_id to block data (temporary solution)
                ->create(
                    ['account_id' => $parcel->getAccountId()] + $blockData
                );

            $this->getRequest()->addInfo(
                'Block "'.$blockData['name'].'" has been created'
            );

        
        } catch (\Throwable $e) {
            $this->getRequest()->addError($e->getMessage());
            $this->redirectReferer();
            return;
        }

        $this->redirectReferer();
    }

    /**
     * Edit an existing block.
     *
     * @return void
     */
    public function edit(): void
    {

        try {
            $pid = (int)$this->getRequest('id', 0);
        
            $block = (new Block())->load($pid);

            if (!$block->getId()) {
                throw new \Exception('Block not found');
            }

            $parcel = $block->getParcel();

            if (!$parcel->getId()) {
                throw new \Exception('Parcel not found');
            }

            if (!User::isAdmin() && User::getInstance()->getId() !== $parcel->getAccountId()) {
                throw new \Exception('You do not have permission to edit this block');
            }

            if ($this->getRequest()->isPost()) {
                
                $block
                    ->setData($this->getRequest()->post('block', []))
                    ->save();

                $this->getRequest()->addInfo('The Block has been updated');
                $this->redirectReferer();
                exit;
            }

            
        } catch (\Throwable $e) {
            $this->getRequest()->addError($e->getMessage());
            $this->redirectReferer();
            exit;
        }

        $this->render('block/block', [
            'blockModel' => $block,
        ]);
    }

    /**
     * Upload an attachment to a block.
     *
     * @return void
     */
    public function uploadAttachment(): void
    {
        try {
            if (!User::isAdmin() ){
                throw new \RuntimeException('Access denied. Only admins can upload attachments.');
            }

            $blockId = (int)$this->getRequest()->request('block_id', 0);
            $block = (new Block())->load($blockId);

            $comment = $this->getRequest()->request('comment', '');

            if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException('File upload error: ' . $_FILES['attachment']['error']);
            }

            $file = $_FILES['attachment'];

            if (!$block->getId()) {
                throw new \RuntimeException('Block not found');
            }

            if (!$this->getRequest()->isPost()) {
                throw new \RuntimeException('Invalid request method. Please use POST to upload files.');
            }
            
            if (empty($file['name'])) {
                throw new \RuntimeException('No file uploaded');
            }
            // $allowedTypes = Config::get('upload_types');
            // if (!in_array($file['type'], $allowedTypes)) {
            //     throw new \RuntimeException('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
            // }
            if ($file['size'] > Config::get('max_upload_size')) {
                throw new \RuntimeException('File size exceeds the maximum limit');
            }


            $uploadRelDir = 
                 Config::get('upload_dir') 
                . DIRECTORY_SEPARATOR
                .'attachments' 
                . DIRECTORY_SEPARATOR 
                . 'block_' . $block->getId();

            if (!is_dir($uploadRelDir) && !mkdir($uploadRelDir, 0755, true)) {
                throw new \RuntimeException('Failed to create upload directory: ' . $uploadRelDir);
            }

            $filePath = 
                getcwd() . DIRECTORY_SEPARATOR . $uploadRelDir . DIRECTORY_SEPARATOR . basename($file['name']);

            $relFilePath = 
                $uploadRelDir . DIRECTORY_SEPARATOR . basename($file['name']);

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new \RuntimeException('Failed to move uploaded file to ' . $filePath);
            }
            // Save the attachment information to the block
            $attachmentData = [
                'path' => $relFilePath,
                'comment' => $comment
            ];

            
            $block->addAttachment($attachmentData);

            $this->getRequest()->addMessage(
                'File uploaded successfully: ' . basename($file['name'])
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => $attachmentData
            ]);
            
        } catch (\Throwable $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
            return;
        }
    }

    /**
     * Delete an attachment from a block.
     *
     * @return void
     */
    function deleteAttachment(): void
    {

        
        try {
            if (!User::isAdmin()) {
                throw new \RuntimeException('Access denied. Only admins can delete attachments.');
            }

            $attachmentId = (int)$this->getRequest()->request('attachment_id', 0);
            $blockId = (int)$this->getRequest()->request('block_id', 0);

            if (!$attachmentId || !$blockId) {
                throw new \RuntimeException('Attachment ID and Block ID are required');
            }

            $block = (new Block())->load($blockId);
            if (!$block->getId()) {
                throw new \RuntimeException('Block not found');
            }

            $block->deleteAttachment($attachmentId);

            $this->getRequest()->addMessage('Attachment deleted successfully');
        } catch (\Throwable $e) {
            $this->getRequest()->addError('An error occurred: ' . $e->getMessage());
        }

        $this->redirectReferer();
    }

    /**
     * Validate block data.
     *
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function validateBlockData(array $data): array
    {
        if (empty($data['parcel_id'])) {
            throw new \InvalidArgumentException('Parcel ID is required');
        }

        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Block name is required');
        }
        
        return $data;
    }

    /**
     * Export the list of parcels as a CSV file.
     *
     * @return void
     */
     public function exportAll(): void
    {
        $blockCollection = (new Block())->getCollection();

        $rawSql = sprintf(
            'SELECT b.*, p.name AS parcel_name, a.name AS account_name FROM %s b LEFT JOIN %s p ON b.parcel_id = p.id LEFT JOIN %s a ON b.account_id = a.id',
            $blockCollection->getTable(),
            (new Parcel())->getCollection()->getTable(),
            (new Account())->getCollection()->getTable()
        );

        if (!User::isAdmin()) {
            // If the user is not an admin, filter blocks by account ID
            $rawSql .= sprintf(' WHERE b.account_id = %d', User::getInstance()->getId());
        }

        $blockCollection->setRawSql($rawSql);
        $blockCollection->setItemMode(Collection::ITEM_MODE_ARRAY);
        $blockCollection->sort('created_at', 'DESC');


        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="blocks.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        $output = fopen('php://output', 'w');
        $separator = ';';
        $headers = ['Block UID', 'Block Nickname', 'Parcel UID', 'Parcel Nickname', 'Business Name', 'Acres', 'Notes'];
        fputcsv($output, $headers, $separator);

        foreach ($blockCollection as $block) {
            fputcsv($output, [
                $block['id'],
                $block['name'],
                $block['parcel_id'],
                $block['parcel_name'],
                $block['account_name'],
                number_format($block['acres'], 3),
                $block['notes']
            ], $separator);
        }
        fclose($output);
        exit;

    }


}
