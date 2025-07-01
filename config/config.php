<?php
return [
    'debug' => true,
    'domain' => 'https://portal.aghawkdynamics.com/',
    'date_format' => 'm/d/Y',
    
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'u631408843_portal_user',
        'password' => '$w0;!A1b',
        'database_name' => 'u631408843_portal'
    ],
    'email' => [
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 587,
        'smtp_username' => 'notifications@portal.aghawkdynamics.com',
        'smtp_password' => '[wirW2A>i[S7',
        'from_email' => 'notifications@portal.aghawkdynamics.com',
        'from_name' => 'Aghawk Dynamics',
        'reply_to' => 'portal@aghawkdynamics.com'
    ],
    'recaptcha' => [
        'enabled' => true,
        'site_key' => '6Ld8DVIrAAAAADFAOfrJDDrLlxSmVPqhar4TLJlQ',
        'secret_key' => '6Ld8DVIrAAAAAO_Dnsd5JOEzA9nNUvjWGRg0D3qO',
    ],
    'paypal' => [

        //sandbox
        // 'url' => 'https://api-m.sandbox.paypal.com', //sandbox
        // 'client_id' => 'AeESlAl2DwCHcUTBTXvQsazPg3JNyI2zOo_6Za7cxQgMeYhbqtt9WxWKkf6eOqDAb2Lz1wSVmk8oqay8',
        // 'secret' => 'EFnRZJOWGLisBQMPc0fG9UKM6dfD2s9xiBTpPgnGk532xnSe2OTJkvb3pAV8WXTtzEz8tnnWzohYhqKw',
        // 'plan_id' => 'P-4K230313VV8377501NBBK6EA', 

        //live
        'url' => 'https://api-m.paypal.com', //live
        'client_id' => 'AbvbXYvgv0FjNzI1XYGtHJRzheSGcQlkLLIAY4nt87e8NlabQ3ZQAVAH-HXzgSZ-ihXV50eQWlZO9Pdl',
        'secret' => 'EEl9CLdMai50DYqeKbnKhd8ksIiy49ZZeQBYLwlFJ1cUXoFQhAoW1BZmqqfXpW662qQgWmxxjEk4iXzJ',
        'plan_id' => 'P-73B28699WX4416934M7LQSDA', 
    ],
    'upload_dir' => 'uploads',

    'max_upload_size' => 10485760, // 10 MB

    'parcel' => [
        'status' => ['active', 'inactive']
    ],
    'crop_category' => ['Orchard', 'Vineyard', 'Row Crops', 'Pasture', 'Grass field', 'Mix'],

    'service_type' => ['Spray', 'Spread', 'Analyze', 'Drying'],

    'service_type_custom_products' => ['Spray', 'Spread'],
    'service_type_water_usage' => ['Spread'],
    'service_type_application' => ['Spray', 'Spread'],

    'acreage_size' => [
        'Under 50' => 'Under 50',
        '50-200' => '50-200',
        '200-500' => '200-500',
        'Above 500' => 'Above 500'
    ],

    'product_types' => [
        'Pesticide',
        'Herbicide',
        'Fungicide',
        'Chemical Thinner',
        'Nutrient',
        'Seed',
        'Fertilizer',
        'Rodent Control',
        'Other'
    ],

    'units' => [
        'gallon',
        'oz',
        'lbs',
        'gram',
        'kg',
    ],

    'states' => [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ],
];
