<?php

namespace App\Controller\Service;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Debug;
use App\Core\Model\Collection;
use App\Model\Account;
use App\Model\Account\User;
use App\Model\Block;
use App\Model\Parcel;
use App\Model\ServiceRequest;
use App\Model\ServiceRequest\ServiceParcel;

class Export extends Controller
{

    /**
     * Handle the service request form submission.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $serviceCollection = (new ServiceRequest())->getCollection();

            $rawSql = sprintf(
                'SELECT service.*,
                    a.name AS account_name,
                    p.id AS parcel_id,
                    p.name AS parcel_name,
                    sp.block_ids AS block_ids
                FROM %s service

                LEFT JOIN %s sp ON service.id = sp.service_id
                LEFT JOIN %s a ON service.account_id = a.id
                LEFT JOIN %s p ON sp.parcel_id = p.id
                ',
                ServiceRequest::TABLE,
                ServiceParcel::TABLE,
                Account::TABLE,
                Parcel::TABLE
            );

            if (!User::isAdmin()) {
                // If the user is not an admin, filter blocks by account ID
                $rawSql .= ' WHERE p.account_id = ' . User::uid();
            }

            $rawSql .= ' ORDER BY service.created_at DESC, service.id DESC, p.id DESC';

            $serviceCollection
                ->setRawSql($rawSql)
                ->setItemMode(Collection::ITEM_MODE_OBJECT);

//$sql = $serviceCollection->getRawSql();
//die($sql);
           

            $csvHeaders = [
                'Activity ID', 
                'Status', 
                ...(User::isAdmin() ? ['Account Name'] : []), 
                'Kind', 
                'Crop Type',
                'Reason', 
                'Is Urgent', 
                'Need by Date',
                'Total Acres',
                'Parcel UID', 
                'Parcel Nickname', 
                'Block Nickname', 

                ...array_merge(...array_map(
                    fn($i) => [
                        "Product Type $i",
                        "Product Name $i",
                        "Product Volume $i",
                        "Product UOM $i"
                    ],
                    range(1, 10)
                )),

                "Supplier Name",
                "Supplier Phone",
                "Supplier Contact",
                "Application Rate Volume per acre",
                "Application rate UOM",
                "Complete Date",
                "Complete Temperature",
                "Complete Wind Speed",
                "Complete Restricted Exposure Hours",
                "Total Water Volume",
                "Total Water UOM",

            ];

            // if (!User::isAdmin()) {
            //     // If the user is not an admin, remove the account name from the headers
            //     unset($csvHeaders[2]);
            // }

            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename="activity.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            $output = fopen('php://output', 'w');
            $separator = ';';
            fputcsv($output, $csvHeaders, $separator);

            foreach ($serviceCollection as $service) {
                $block_ids = explode(',', $service->get('block_ids'));
                if (empty($block_ids)) {
                    continue; // Skip if no block IDs are associated with the service
                }
                foreach ($block_ids as $blockId) {
                    //$customProducts = 
                    $csvRow = [
                        $service->get('id'),
                        $service->get('status'),
                        ...(User::isAdmin() ? [$service->get('account_name')] : []),
                        $service->get('kind'),
                        $service->get('type'),
                        $service->get('reason'),
                        $service->get('urgent') ? 'Yes' : 'No',
                        $service->get('date') ? (new \DateTimeImmutable($service->get('date')))->format(Config::get('date_format')) : '',
                        number_format($service->getTotalServiceAcres(), 3),
                        $service->get('parcel_id'),
                        $service->get('parcel_name'),
                        Block::find($blockId)->get('name'),
                        ...(function ($service) {
                            $products = $service->getCustomProducts();
                            $productRows = [];
                            for ($i = 1; $i <= 10; $i++) {
                                $productType = $products[$i - 1]['type'] ?? '';
                                $productName = $products[$i - 1]['name'] ?? '';
                                $productVolume = $products[$i - 1]['volume'] ?? '';
                                $productUOM = $products[$i - 1]['unit'] ?? '';
                                $productRows[] = [
                                    $productType, 
                                    $productName, 
                                    number_format($productVolume, 3),
                                    $productUOM
                                ];
                            }
                            return array_merge(...$productRows);
                        })($service),
                        //implode(', ', $service->getCustomProducts()), // Assuming getProducts() returns an array of product names
                        $service->getCustomSupplier('supplier'),
                        $service->getCustomSupplier('phone'),
                        $service->getCustomSupplier('name'),
                        $service->getAdditionalData()['application']['volume'],
                        $service->getAdditionalData()['application']['unit'],
                        $service->get('completed_at') ? (new \DateTimeImmutable($service->get('completed_at')))->format(Config::get('date_format')) : '',
                        $service->getCompleteData()['temperature'] ?? '',
                        $service->getCompleteData()['wind'] ?? '',
                        $service->getCompleteData()['exposure_hours'] ?? '',
                        $service->getCompleteData()['water_used']['volume'] ?? '',
                        $service->getCompleteData()['water_used']['unit'] ?? '',
                    ];
                    
                    fputcsv($output, $csvRow, $separator);
                }
            }
            fclose($output);
            exit;
        } catch (\Throwable $e) {
            $this->getRequest()->addError(
                'An error occurred while processing your request: ' . $e->getMessage()
            );
            $this->redirectReferer();
        }
       
    }
}
