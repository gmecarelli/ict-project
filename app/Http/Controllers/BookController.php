<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class BookController extends IctController
{
    use LivewireController;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new Book();
        $this->foreignKey = 'author_id';
    }
}
