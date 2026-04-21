<?php 
// Prefer environment variables when available; fall back to defaults for compatibility.
define('CONSUMER_KEY', getenv('CONSUMER_KEY') ?: 'jT5nvemtgBtQg3TC6uDusyg3wRoa');
define('CONSUMER_SECRET', getenv('CONSUMER_SECRET') ?: 'Ka_zGhkONDbXh9Lv4uCDDvXzH88a');
define('WEBSITE_CONFIG_ID', getenv('WEBSITE_CONFIG_ID') ?: 12498);
define('BASE_URL', getenv('BASE_URL') ?: 'https://sandbox.tn-apis.com');
define('BROKER_ID', getenv('BROKER_ID') ?: 9250);
define('SITE_ID', getenv('SITE_ID') ?: 30);
define('HOME_URL', getenv('HOME_URL') ?: 'https://beta.seatoutlet.com');
define('HOME_PATH', getenv('HOME_PATH') ?: '/home/seatoutlet-beta/htdocs/beta.seatoutlet.com/');
define('GAPI_KEY', getenv('GAPI_KEY') ?: 'AIzaSyDgBnZvqpjZiIRdVrnAB45En0dRFB2enmo');
define('GKGSAPI_KEY', getenv('GKGSAPI_KEY') ?: 'AIzaSyCeepfNJk2TtbV4FMH17v3MteUOH825rI4');
define('AWS_ACCOUNT_ID', getenv('AWS_ACCOUNT_ID') ?: '2f20a4f9aec4a1c3b457bc4a6165f503');
define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY') ?: '520632cd9d3ddeac1687d57736eb4707');
define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY') ?: '46602349a317d609f33a5356cf0d7e39b45ef176b3c8e982edaf92bb86ec3900');
define('AWS_BUCKET_NAME', getenv('AWS_BUCKET_NAME') ?: 'seat-outlet-assets');
define('AWS_CDN_URL', getenv('AWS_CDN_URL') ?: 'https://cdn-beta.seatoutlet.com/');


