<?php

switch (env('APP_ENV')) {
    case 'prod' :
        $edu_url = env('URL_UTALK_PROD');
        break;
    case 'test' :
        $edu_url = env('URL_UTALK_TEST');
        break;
    default:
        $edu_url = env('URL_UTALK_DEV');
}

return [
    'utalk_url' => $edu_url
];
