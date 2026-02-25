<?php

namespace Packages\IctInterface\Controllers;

use Illuminate\Support\Arr;
use Packages\IctInterface\Exports\ReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Packages\IctInterface\Models\Form;
use Packages\IctInterface\Models\Report;
use Packages\IctInterface\Models\FormField;
use Packages\IctInterface\Models\ReportColumn;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Controllers\Services\ReportService;
use Packages\IctInterface\Models\ProfileRole;

class ExcelController extends IctController
{
    public $report;

    public function __construct()
    {
        $this->report = new ReportService();
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
        return Excel::download(new ReportExport($model, $where), $fileName);
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
        return Excel::download(new ReportExport($model, $where), $fileName);
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
        return Excel::download(new ReportExport($model, $where), $fileName);
    }

    /**
     * exportFormFields
     * Esporta su excel/csv i dati visualizzati nel report di Configura DRS Items
     */
    public function exportFormFields()
    {
        $where = $this->_getWhereArrayForDataExport();
        $model = new FormField();
        $fileName = $this->_getFileName('exportFormFields', request('ext'));
        return Excel::download(new ReportExport($model, $where), $fileName);
    }

    /**
     * exportProfileRoles
     * Esporta su excel/csv i dati visualizzati nel report di Configura DRS Items
     */
    public function exportProfileRoles()
    {
        $where = $this->_getWhereArrayForDataExport();
        $model = new ProfileRole();
        $fileName = $this->_getFileName('exportProfileRoles', request('ext'));
        return Excel::download(new ReportExport($model, $where), $fileName);
    }

    /**
     * _getFileName
     * Imposta il nome del file di esportazione
     */
    private function _getFileName($prefix, $ext)
    {
        return $prefix . '_' . date('Ymd') . '.' . $ext;
    }

    /**
     * _getWhereArrayForDataExport
     * Restituisce l'array con le clausole where dell'ultima ricerca fatta
     */
    private function _getWhereArrayForDataExport()
    {
        $where = [];
        $req = request()->all();
        Arr::forget($req, ['report', 'filter', 'ext', 'form_id']);

        foreach ($req as $field => $value) {
            if (is_numeric($value)) {
                $where[] = [$field, '=', $value];
            } else {
                $where[] = [$field, 'like', '%' . $value . '%'];
            }
        }
        return $where;
    }
}
