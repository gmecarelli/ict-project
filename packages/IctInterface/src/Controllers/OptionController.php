<?php

namespace Packages\IctInterface\Controllers;

use Packages\IctInterface\Models\Option;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class OptionController extends IctController
{
    use LivewireController;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new Option();
        $this->foreignKey = null;
    }
}
