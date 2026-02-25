<?php
/**
 * CONTROLLER I CUI METODI SONO ESEGUITI TRAMITE CRON
 * LA BASEURL È http://gestione-pg.tr.ictlabs.it/cron 
 */
namespace App\Http\Controllers\Services;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Controllers\Services\FormService;
use Packages\IctInterface\Controllers\Services\ReportService;

class CronController extends Controller
{
    protected $filters;
    protected $report;
    protected $form;
    private $period;
    public $model;

    public $response = [];

    public function __construct() 
    {
        $this->report = new ReportService();
        $this->form = new FormService();
        $this->log = new Logger();
        // $this->log->info("#COSTRUTTORE DEL REPORT#",__FILE__,__LINE__);
        $this->_formId = 46; 
        $this->model = null;
        DB::enableQueryLog();
        $this->log->setChannel('cronlog');

        $this->period = $this->getMonthYearBilling();
    }

}
