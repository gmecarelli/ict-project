<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Kris\LaravelFormBuilder\FormBuilder;
use Packages\IctInterface\Controllers\Ajax\AjaxController;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Traits\LivewireController;
use Packages\IctInterface\Traits\StandardController;

class BookController extends IctController
{
    use LivewireController;
    public $foreignKey;
    public $ajax;

    public function __construct()
    {
        parent::__construct();
        $this->__init();
        $this->model = new Book();
        $this->foreignKey = 'author_id';
        $this->ajax = new AjaxController();
    }

    public function loadModal(FormBuilder $formBuilder)
    {
        $classForm = '\Packages\IctInterface\Forms\AppFormsBuilder';
        $edit_id = request()->has('id') && request()->filled('id') ? request('id') : null;
        $form = $this->ajax->loadModalForm($formBuilder, $this->model, $classForm, $edit_id);

        if(!$form) {
            $this->ajax->response['result'] = 'fail';
            $this->ajax->response['html'] = 'Nessun dato caricato';
        } else {
            $this->ajax->render($form, true);
        }
        return $this->ajax->response;
    }

    public function saveModal() {
        return $this->ajax->saveModalForm( $this->model, request('form_id'), request('id'));
    }
}
