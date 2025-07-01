<?php
namespace App\Model\ServiceRequest;

class Adds
{

    const FIELD = 'adds';
    const FIELD_HAS_CUSTOM_PRODUCTS = 'has_custom_products';
    const FIELD_PRODUCTS = 'products';
    const FIELD_SUPPLIER = 'supplier';

    /**
     * Extracts the 'adds' field from the provided data.
     *
     * @param array $data The data array containing the 'adds' field.
     * @return array The extracted 'adds' array, or an empty array if not found.
     */
    public static function extractAdds($data): array
    {
        $adds = $data[static::FIELD] ?? [];
        if (is_string($adds)) {
            // Decode JSON string to array
            $adds = json_decode($adds, true, 512, JSON_THROW_ON_ERROR);
        }
        if (!is_array($adds)) {
            // If the decoded value is not an array, return an empty array
            $adds = [];
        }

        return $adds;
    }

    /**
     * Prepares the adds data by extracting and formatting it.
     *
     * @param array $data The data array containing the 'adds' field.
     * @return array The prepared data array with the 'adds' field formatted as JSON.
     */
    public static function prepare(array $data): array
    {
        $adds = static::extractAdds($data);

        //prepare custom products structure
        $adds[static::FIELD_PRODUCTS] = $adds[static::FIELD_HAS_CUSTOM_PRODUCTS] 
            ? static::prepareProducts($adds[static::FIELD_PRODUCTS] ?? [])
            : [];

    
        // prepare supplier data
        $adds[static::FIELD_SUPPLIER] = static::prepareSupplier($adds[static::FIELD_SUPPLIER] ?? []);

        // Convert the adds array to a JSON string
        $addsJson = json_encode($adds, JSON_THROW_ON_ERROR);
        if ($addsJson === false) {
            throw new \RuntimeException('While preparing service additionals: Failed to encode adds to JSON: ' . json_last_error_msg());
        }

        $data[static::FIELD] = $addsJson;

        return $data;
        
    }
    
    /**
     * Prepares the products array by filtering out invalid entries.
     *
     * @param array $products The products array to be prepared.
     * @return array The filtered products array.
     */
    public static function prepareProducts(array $products): array
    {
        $products = array_filter(
            $products ?? [],
            function ($product, $k) {
                if (!is_numeric($k)) {
                    return false; // Skip non-numeric keys (template item)
                }
                // Ensure each product has a valid 'type'
                return !empty($product['type']);
            },
            ARRAY_FILTER_USE_BOTH
        );

        return $products;
    }

    /**
     * Prepares the supplier data.
     *
     * @param array $supplier The supplier data to be prepared.
     * @return array The prepared supplier data.
     */
    public static function prepareSupplier(array $supplier)
    {
        return $supplier;
    }

    
}