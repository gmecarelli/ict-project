<?php
namespace Packages\IctInterface\Exports;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Controllers\Services\ReportService;

class FilterExportController extends IctController
{
    
    public function __construct()
    {
        parent::__construct();
        $this->report = new ReportService();
    }

    public function simpleWhere() {

    }

    /**
     * prepareWhere
     * Restituisce l'array con le clausole where dell'ultima ricerca fatta
     * @return array
     */
    public function prepareWhere() {
        $where = [
            'where' => [],
        ];
        $req = request()->all();
        Arr::forget($req, ['report','filter', 'ext', 'form_id', 'ob', 'ot', 'page']);

        foreach($req as $field => $value) {
            if(!is_array($value)) {
                if(Str::length($value)==0 || is_null($value)) {continue;}
            }
            
            if(preg_match("/^where[A-Za-z\-]+/",$field)) {
                $func = $this->_getFunction($field);
                if(isset($where[$func])) {
                    $where[$func][] = [$this->_getFieldByComposite($field), $this->report->_getOperator($field), $value];
                } else {
                    $where = Arr::add($where, $func.'.0', [$this->_getFieldByComposite($field), $this->report->_getOperator($field), $value]);
                }

                continue;
            }
                    
            if(is_numeric($value)) {
                if($field == 'prot_commessa') {
                    $where['where'][] =  [$field, 'like', '%'.$value.'%'];
                    continue;
                }
                $where['where'][] = [$field, '=', $value];
            } else {
                $where['where'][] = [$field, 'like', '%'.$value.'%'];
            }
        }

        return $where;
    }

    /**
     * _getFieldByComposite
     * Restituisce la parte del nome del campo che segue l'underscore
     * @param string $str
     * @return string
     */
    private function _getFieldByComposite($str) {
        return Str::after($str, '_');
    }


    /**
     * _getFunction
     * Returns the part of the string before the first hyphen
     * @param string $str
     * @return string
     */
    private function _getFunction($str) {
        return Str::before($str, '-');
    }
}
