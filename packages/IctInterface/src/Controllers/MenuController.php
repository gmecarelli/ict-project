<?php

namespace Packages\IctInterface\Controllers;

use Packages\IctInterface\Models\Menu;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class MenuController extends IctController
{
    use LivewireController;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new Menu();
        $this->foreignKey = null;
    }
}
