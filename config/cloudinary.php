<?php

return [
    // You can either set a full CLOUDINARY_URL, or individual parts below.
    // Example CLOUDINARY_URL: cloudinary://<api_key>:<api_secret>@<cloud_name>
    'cloud_url' => env('CLOUDINARY_URL'),

    // If CLOUDINARY_URL is not provided, the package will look at these:
    'cloud' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'djxcjaahb'),
        'api_key' => env('CLOUDINARY_API_KEY', '855154112931948'),
        'api_secret' => env('CLOUDINARY_API_SECRET', 'xMpOz20m9SYVfZZYKUKdWBPnWb8'),
        'url' => env('CLOUDINARY_URL', 'cloudinary://855154112931948:xMpOz20m9SYVfZZYKUKdWBPnWb8@djxcjaahb')
    ],

    // Optional upload preset if you use unsigned uploads, otherwise leave null
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
];
