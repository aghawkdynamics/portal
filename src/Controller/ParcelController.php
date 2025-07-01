<?php
namespace App\Controller;


use App\Core\Controller;
use App\Model\Parcel;
use App\Core\Model\Collection;
use App\Model\Account;
use App\Model\Account\User;

class ParcelController extends Controller
{

    /**
     * @var Parcel|null
     */
    private ?Parcel $parcel = null;

    /**
     * Get the Parcel model by ID.
     *
     * @return Parcel
     * @throws \Exception
     */
    protected function getParcel(): Parcel
    {
        if (!$this->parcel instanceof Parcel) {

            $pid = (int)$this->getRequest('id', 0);
            
            $parcel = (new Parcel())
                ->load($pid);

            if (!$parcel->getId()) {
                throw new \Exception('Parcel not found');
            }
        }

        if (!User::isAdmin() && $parcel->get('account_id') != User::getInstance()->getId()) {
            throw new \Exception('You do not have permission to access this parcel');
        }

        return $this->parcel = $parcel;
    }

    /**
     * Display a list of parcels for the logged-in user.
     *
     * @return void
     */
    public function index(): void
    {
        $collection = (new Parcel())->getCollection()
            ->setItemMode(Collection::ITEM_MODE_OBJECT)
            ->sort('name', 'ASC');

        $filters = $this->getRequest()->request('filters', []);

        $collection->applyPostFilters(
            $filters
        );

        if (!User::isAdmin()) {
            $collection->addFilter(['account_id' => User::uid()]);
        } else {
            $collection->join(
                Account::TABLE,
                sprintf(
                    'main.account_id = %s.id',
                    Account::TABLE
                )
            );
        }

        

        $collection->setPage((int)$this->getRequest('page', 1));

        $this->render('parcel/index', [
            'parcels'     => $collection,
            'filters'     => $filters,
        ]);
    }

    /**
     * Add a new parcel.
     *
     * @return void
     */
    public function add(): void
    {
        if (!$this->getRequest()->isPost()) {
            // If the request is not a POST, render the add parcel form
            $this->render(
                'parcel/parcel',
                ['parcelModel' => new Parcel()]
            );
            return;
        }

        // If the request is a POST, attempt to create a new parcel
        try {
            $data = $this->getRequest()->post('parcel', []);

            $collection  = (new Parcel())->getCollection();
            $collection->addFilter(['name' => $data['name'] ?? '']);
            if ($collection->count() > 0) {
                throw new \Exception('Parcel with this ID already exists.');
            }


            (new Parcel())->create(
                ['account_id' => User::uid()] + $data
            );
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'Unable to create Parcel: ' . $e->getMessage()
            );
        } finally {
            $this->redirect('/parcel');
        }
    }

    /**
     * Edit an existing parcel.
     *
     * @return void
     */
    public function edit(): void
    {

        try {
            
            $parcel = $this->getParcel();

            if (!$parcel instanceof Parcel) {
                throw new \Exception('Parcel not found');
            }

            if ($this->getRequest()->isPost()) {
                $pdata = $this->getRequest()->post('parcel', []);

                $parcel
                    ->setData($pdata)
                    ->save();

                $this->getRequest()->addInfo('Parcel has been updated');
                $this->redirectReferer();
                exit;
            }

            
        } catch (\Throwable $e) {
            $this->getRequest()->addError($e->getMessage());
            $this->redirectReferer();
            exit;
        }

        $blocks = $parcel->getBlocks();

        if ($blocks->count() < 1) {
            $this->getRequest()->addWarning('Parcel must have at least one block to be able to create a service request.');
        }

        $this->render(
            'parcel/parcel',
            ['parcelModel' => $parcel]
        );
    }

    /**
     * Export the list of parcels as a CSV file.
     *
     * @return void
     */
     public function exportAll(): void
    {
        $parcelCollection = (new Parcel())->getCollection();

        $rawSql = sprintf(
            'SELECT p.*, a.name AS account_name FROM %s p LEFT JOIN %s a ON p.account_id = a.id',
            (new Parcel())->getTable(),
            (new Account())->getTable()
        );

        if (!User::isAdmin()) {
            // If the user is not an admin, filter blocks by account ID
            $rawSql .= ' WHERE p.account_id = ' . User::uid();
        }

        $parcelCollection->setRawSql($rawSql);
        $parcelCollection->setItemMode(Collection::ITEM_MODE_ARRAY);
        $parcelCollection->sort('created_at', 'DESC');


        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="parcels.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        $output = fopen('php://output', 'w');
        $separator = ';';
        fputcsv($output, ['Parcel UID', 'Parcel Nickname', 'Business Name', 'Parcel Address', 'City', 'State', 'ZIP', 'Acres', 'Notes'], $separator);

        foreach ($parcelCollection as $parcel) {
            fputcsv($output, [
                $parcel['id'],
                $parcel['name'],
                $parcel['account_name'],
                $parcel['street'],
                $parcel['city'],
                $parcel['state'],
                $parcel['zip'],
                number_format($parcel['estimated_acres'], 3),
                $parcel['notes'] ?? ''
            ], $separator);
        }
        fclose($output);
        exit;

    }
}
