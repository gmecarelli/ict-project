<?php

namespace Packages\IctInterface\Controllers;

use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Controllers\Services\ReportService;

class PDFController extends IctController
{
    var $daysOfMonth = [
        '1' => 31,
        '2' => 28,
        '3' => 31,
        '4' => 30,
        '5' => 31,
        '6' => 30,
        '7' => 31,
        '8' => 31,
        '9' => 30,
        '10' => 31,
        '11' => 30,
        '12' => 31,
    ];
    var $log;
    public $report;

    public function __construct() 
    {
        $this->log = new Logger();
        $this->report = new ReportService();
    }

    /**
     * examplePdfCreator
     * FUNZIONE DI ESEMPIO PER LA CREAZIONE CON VISTA DI UN PDF CON VERSIONE DOWLOAD O STORAGE
     * @param  mixed $oPdf
     * @param  mixed $request
     * @return void
     */
    public function examplePdfCreator(PDF $oPdf, Request $request) {

             
        
        $data = [
            'po' => $po,
            // 'drs' => $drs,
            'items' => $items,
            'total' => $this->_currencyFormat($total),
            'today' => date("d/m/Y"),
            'daysOfMonth' => $this->daysOfMonth,
        ];

        $pdf = $oPdf->loadView('pdf.drs',$data);
        //Siccome facoltativo, se retailer è null non viene inserita nel nome del file
        if(!isset($retailerInfo->label) || is_null($po['retailer'])) {
            $pdfFileName='DRS_ICT_'.$brandInfo->acronimo.'*('.ucfirst(Str::camel($po['title'])).'_'.$po['commessa'].'_'.$po['nameMonth']->label.'_'.Str::after($po['num_order'],'G4P-').').pdf';
        } else {
            $pdfFileName='DRS_ICT_'.$brandInfo->acronimo.'_'.$retailerInfo->label.'*('.ucfirst(Str::camel($po['title'])).'_'.$po['commessa'].'_'.$po['nameMonth']->label.'_'.Str::after($po['num_order'],'G4P-').').pdf';
        }
    
        if($request->has('output') && $request->get('output')=='store') {
            //salvo il file nel disco
            $filePath = config('ict.upload_bill_dir').'/'.$pdfFileName;
            if(Storage::put($filePath, $pdf->output())==true) {
                return $pdfFileName;
            } else {
                return false;
            }
        } else {
            //visualizzo nel browser il file
            return $pdf->stream($pdfFileName);
        }
    }

    public function curlCreateDrs(PDF $oPdf, Request $request) {
        return $this->createDrs($oPdf, $request);
    }
    
    /**
     * _currencyFormat
     * Formatta un numero in generale, in particolare per i numeri valuta
     * @param  mixed $number
     * @return void
     */
    private function _currencyFormat($number) {
        return number_format($number, 2, ',', '.');
    }
    
    /**
     * _getNameCode
     * Traduce il dato di un codice dalla rabella options
     * @param  mixed $code
     * @param  mixed $reference
     * @return void
     */
    private function _getNameCode($code, $reference) {
        $arr = Arr::get(DB::table('options')
                    ->where('reference', $reference)
                    ->where('code', $code)
                    ->get()
                    ->toArray(),0);
        return strtoupper($arr->label);
    }
    
    /**
     * _dateFormat
     * Formatta la data
     * @param  mixed $date
     * @return void
     */
    private function _dateFormat($date) {
        $date = date_create($date);
        return date_format($date, 'd/m/Y');
    }
}
