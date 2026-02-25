<?php

namespace Packages\IctInterface\Controllers;

use Packages\IctInterface\Models\FormField;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class FormFieldController extends IctController
{
    use LivewireController;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new FormField();
        $this->foreignKey = 'form_id';
    }
}
