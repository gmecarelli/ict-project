<?php

namespace Packages\IctInterface\Controllers;

use Packages\IctInterface\Models\Report;
use Packages\IctInterface\Models\ReportColumn;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class ReportController extends IctController
{
    use LivewireController;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new Report();
        $this->modelChild = new ReportColumn();
        $this->foreignKey = 'report_id';
    }
}
