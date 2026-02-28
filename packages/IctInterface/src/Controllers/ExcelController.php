<?php

namespace Packages\IctInterface\Controllers;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Exports\FilterExportController;
use Packages\IctInterface\Exports\ReportExport;
use Packages\IctInterface\Models\Form;
use Packages\IctInterface\Models\FormField;
use Packages\IctInterface\Models\Option;
use Packages\IctInterface\Models\ProfileRole;
use Packages\IctInterface\Models\Report;
use Packages\IctInterface\Models\ReportColumn;

class ExcelController extends IctController
{
    protected $skip = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * exportExcel
     * Export generico per qualsiasi lista/report applicativo
     */
    public function exportExcel()
    {
        $where = $this->_getWhereArrayForDataExport();
        $resource = Report::find(request('report'))->route;
        $modelClass = 'App\\Models\\' . Str::studly(Str::singular($resource));
        $model = new $modelClass();
        $fileName = $this->_getFileName($resource, request('ext'));
        return Excel::download(new ReportExport($model, $where, $this->skip), $fileName);
    }

    /**
     * exportReport
     * Esporta su excel/csv i dati visualizzati nel report di Configura Report
     */
    public function exportReport()
    {
        $where = $this->_getWhereArrayForDataExport();
        $model = new Report();
        $fileName = $this->_getFileName('exportReport', request('ext'));
        return Excel::download(new ReportExport($model, $where, $this->skip), $fileName);
    }

    /**
     * exportReportCols
     * Esporta su excel/csv i dati visualizzati nel report di Configura Report Cols
     */
    public function exportReportCols()
    {
        $where = $this->_getWhereArrayForDataExport();
        $model = new ReportColumn();
        $fileName = $this->_getFileName('exportReportCols', request('ext'));
        return Excel::download(new ReportExport($model, $where, $this->skip), $fileName);
    }

    /**
     * exportForm
     * Esporta su excel/csv i form visualizzati nel report
     */
    public function exportForm()
    {
        $where = $this->_getWhereArrayForDataExport();
        $model = new Form();
        $fileName = $this->_getFileName('exportForm', request('ext'));
        return Excel::download(new ReportExport($model, $where, $this->skip), $fileName);
    }

    /**
     * exportFormFields
     * Esporta su excel/csv i dati visualizzati nel report di Configura Form Fields
     */
    public function exportFormFields()
    {
        $where = $this->_getWhereArrayForDataExport();
        $model = new FormField();
        $fileName = $this->_getFileName('exportFormFields', request('ext'));
        return Excel::download(new ReportExport($model, $where, $this->skip), $fileName);
    }

    /**
     * exportProfileRoles
     * Esporta su excel/csv i dati visualizzati nel report di Profili e Ruoli
     */
    public function exportProfileRoles()
    {
        $where = $this->_getWhereArrayForDataExport();
        $model = new ProfileRole();
        $fileName = $this->_getFileName('exportProfileRoles', request('ext'));
        return Excel::download(new ReportExport($model, $where, $this->skip), $fileName);
    }

    /**
     * exportOptions
     * Esporta su excel/csv i dati visualizzati nel report di Opzioni
     */
    public function exportOptions()
    {
        $where = $this->_getWhereArrayForDataExport();
        $model = new Option();
        $fileName = $this->_getFileName('exportOptions', request('ext'));
        return Excel::download(new ReportExport($model, $where, $this->skip), $fileName);
    }

    /**
     * _getFileName
     * Imposta il nome del file di esportazione
     */
    protected function _getFileName($prefix, $ext)
    {
        return $prefix . '_' . date('Ymd') . '.' . $ext;
    }

    /**
     * _getWhereArrayForDataExport
     * Restituisce l'array con le clausole where dell'ultima ricerca fatta
     * Usa FilterExportController per gestire tutti i tipi di where
     */
    protected function _getWhereArrayForDataExport()
    {
        $filter = new FilterExportController();
        return $filter->prepareWhere();
    }
}
