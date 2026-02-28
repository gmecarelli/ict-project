<?php

namespace App\Http\Controllers;

use Packages\IctInterface\Controllers\ExcelController;

class ExportController extends ExcelController
{
    protected $skip = [
        'id',
        'is_enabled',
        'is_required',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();
    }
}
