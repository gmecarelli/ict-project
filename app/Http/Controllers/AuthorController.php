<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;
use Packages\IctInterface\Traits\StandardController;

class AuthorController extends IctController {
    use LivewireController;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new Author();
        $this->modelChild = new Book();
        $this->foreignKey = 'author_id';
    }
}
