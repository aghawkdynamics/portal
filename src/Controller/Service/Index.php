<?php
namespace App\Controller\Service;

use App\Core\Controller;
use App\Model\Account;
use App\Model\Account\User;
use App\Model\ServiceRequest;
use App\Model\ServiceRequest\Collection;
use App\Model\ServiceRequest\ServiceParcel;

class Index extends Controller
{
    private ?Collection $collection = null;

    

    /**
     * Handle the request to list service requests.
     *
     * @return void
     */
    public function handle()
    {
        $filters = $this->getRequest('filters') ?? [];

        
        $serviceCollection = $this->getCollection();
        $gridSql = $this->getGridSql($filters);

        //die($gridSql);
        $serviceCollection->setRawSql($gridSql);
        $serviceCollection->setItemMode(Collection::ITEM_MODE_OBJECT);
        $serviceCollection->setPage((int)$this->getRequest('page', 1));

        $this->render('service/list', 
            [
                'serviceCollection' => $serviceCollection,
                'filters' => $filters,
            ]
        );
    }

    /**
     * Get the SQL query for the service request grid.
     *
     * @param array $filters
     * @return string
     */
    protected function getGridSql($filters): string
    {
        $gridSql = sprintf(
            'SELECT main.*, 
                a.name AS account_name,
                a.email AS account_email,
                GROUP_CONCAT(sp.parcel_id) AS parcel_ids
            FROM %s main
            INNER JOIN %s a ON main.account_id = a.id %s
            INNER JOIN %s sp ON main.id = sp.service_id %s
            WHERE 1=1 %s
            GROUP BY main.id
            ORDER BY main.created_at DESC
            ',
            ServiceRequest::TABLE,
            Account::TABLE, $this->accountJoinFilter($filters),
            ServiceParcel::TABLE, $this->parcelJoinFilter($filters),
            $this->whereFilter($filters)
        );

        return $gridSql;
    }

    /**
     * Get the JOIN clause for filtering accounts.
     *
     * @param array $filters
     * @return string
     */
    protected function accountJoinFilter(array $filters): string
    {

        $filterSql = '';
            
        if (!empty($filters['account'])) {
            $accountFilter = $filters['account'];
            $filterSql .= sprintf('AND a.name LIKE "%%%s%%"', $accountFilter, $accountFilter);
            // $filterSql .= ' AND (a.name LIKE "%:account%" OR a.email LIKE "%:account%")';
            // $filterSql .= ' AND (a.name LIKE "%:account%"';
            // $this->getCollection()->addParam('account', $accountFilter);
        }

        if (!User::isAdmin()) {
            // If the user is not an admin, filter by the current user's account ID
            
            $filterSql .= ' AND a.id = :account_id';
            $this->getCollection()->addParam('account_id', User::uid());
        }

        return $filterSql;
    }

    /**
     * Get the JOIN clause for filtering service parcels.
     *
     * @param array $filters
     * @return string
     */
    protected function parcelJoinFilter(array $filters): string
    {
        $filterSql = '';

        if (!empty($filters['parcel'])) {
            $parcelFilter = $filters['parcel'];
            $this->getCollection()->addParam('parcel', $parcelFilter);
            $filterSql .= 'AND sp.parcel_id = :parcel';
        }

        return $filterSql;
    }

    /**
     * Get the WHERE clause for filtering service requests.
     *
     * @param array $filters
     * @return string
     */
    protected function whereFilter(array $filters): string
    {
        $where = '';

        if (!empty($filters['status'])) {
            $where .= sprintf(" AND main.status = :status");
            $this->getCollection()->addParam('status', $filters['status']);
        }

        if (!empty($filters['kind'])) {
            $where .= sprintf(" AND main.kind = :kind");
            $this->getCollection()->addParam('kind', $filters['kind']);
        }

        if (!empty($filters['type'])) {
            $where .= sprintf(" AND main.type = :type");
            $this->getCollection()->addParam('type', $filters['type']);
        }

        // if (!empty($filters['reason'])) {
        //     $where .= sprintf(" AND main.reason = :reason");
        //     $this->getCollection()->addParam('reason', $filters['reason']);
        // }

        if (!empty($filters['date_from'])) {
            $where .= sprintf(" AND main.date >= :date_from");
            $this->getCollection()->addParam('date_from', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where .= sprintf(" AND main.date <= :date_to");
            $this->getCollection()->addParam('date_to', $filters['date_to']);
        }

        if (!empty($filters['reason'])) {
            $where .= sprintf(' AND main.reason LIKE "%%%s%%"', $filters['reason']);
            //$this->getCollection()->addParam('reason', $filters['reason']);
        }

         if (!User::isAdmin()) {
            // If the user is not an admin, filter by the current user's account ID
            
            $where .= ' AND a.id = :account_id';
            $this->getCollection()->addParam('account_id', User::uid());
        }


        return $where;
    }

    /**
     * Get the collection of service requests.
     *
     * @return Collection
     */
    protected function getCollection(): Collection
    {
        return $this->collection ??= new Collection();
    }
}