<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;

class BookController extends IctController
{
    use LivewireController { edit as traitEdit; }

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new Book();
        $this->foreignKey = 'author_id';
    }
    public function edit($id)
    {
        $book = Book::findOrFail($id);
        _log()->sql($book->toSql(), __FILE__, __LINE__, $id, 'dump');
        return $this->traitEdit($id);
    }
}
