<?php

namespace Packages\IctInterface\Controllers;

use Packages\IctInterface\Models\ProfileRole;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class ProfileRoleController extends IctController
{
    use LivewireController;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new ProfileRole();
        $this->foreignKey = null;
    }
}
