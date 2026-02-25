<?php

return [
    'upload_dir' => env('UPLOAD_DIR', 'upload'),
    'upload_bill_dir' => env('UPLOAD_BILL_DIR', 'upload/bills'),
    'logger_level' => env('LOGGER_LEVEL', 1),
    'app_url' => env('APP_URL', 'http://localhost:8040'),
    'css_color' => env('APP_CSS_COLOR', '#4d7496'),
    'table_users' => env('TABLE_USERS', 'users'),
];
