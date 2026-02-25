<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Exports\FilterExportController;
use Packages\IctInterface\Exports\ReportExport;
use Packages\IctInterface\Models\Report;
use Packages\IctInterface\Traits\StandardController;

class ExportController extends IctController
{
    use StandardController;
    public $skip;

    public function __construct()
    {
        parent::__construct();
        $this->report = Report::find(request('report'));
        $this->skip = [
            'id',
            'is_enabled',
            'is_required',
            'created_at',
            'updated_at',
        ];
    }
    public function exportBooks()
    {
        $where = $this->_getWhereArrayForDataExport();

        $model = new Book();
        $model->setTable($this->report->table);
        $fileName = $this->_getFileName('books', request('ext'));
        return Excel::download(new ReportExport($model, $where, $this->skip), $fileName);
    }


    /**
     * _getFileName
     * Imposta il nome del file di esportazione
     * @param  mixed $prefix
     * @param  mixed $ext
     * @return void
     */
    private function _getFileName($prefix, $ext)
    {
        $varFileName = '';

        return $prefix . $varFileName . '_' . date('Ymd') . '.' . $ext;
    }

    
    /**
     * _getWhereArrayForDataExport
     * Restituisce l'array con le clausole where dell'ultima ricerca fatta
     * @return array
     */
    private function _getWhereArrayForDataExport()
    {
        $filter = new FilterExportController();
        $filterArray = $filter->prepareWhere();
        return $filterArray;
    }

    private function removeFromSkip(array $keysToRemove)
    {
        return array_diff($this->skip, $keysToRemove);
    }
}
