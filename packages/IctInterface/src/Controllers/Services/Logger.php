<?php

namespace Packages\IctInterface\Controllers\Services;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Logger
{
    protected $_channel = 'log';
    
    protected $level;
    protected $displayLevel = [
        0 => [
            'info',
            'debug',
            'sql',
            'error',
            'rollback',
            'commit',
        ],
        1 => [
            'debug',
            'sql',
            'error',
            'rollback',
            'commit',
        ],
        2 => [
            'sql',
            'error',
            'rollback',
            'commit',
        ],
    ];

    public function __construct()
    {
        $this->level = config('ict.logger_level', 1);
    }
    
    public function log($str, $file, $line, $type='debug') {
        if(in_array($type, $this->displayLevel[$this->level])) {
            Log::channel($this->_channel)->$type($this->_extra($file, $line). " " . $str );
        }
    }

    public function info($str, $file, $line) {
        $this->log($str, $file, $line, 'info');
    }

    public function debug($str, $file, $line) {
        $this->log($str, $file, $line, 'debug');
    }

    public function error($str, $file, $line, $marckup = '') {
        $this->log($marckup." ".$str, $file, $line, 'error');
    }

    public function rollback($file, $line) {
        $this->log('@@ ROLLBACK @@', $file, $line, 'error');
    }

    public function commit($file, $line) {
        $this->log('$$ COMMIT $$', $file, $line, 'debug');
    }

    private function _extra($file, $line) {
        $user = session()->get('loggedUser');
        // dd($user);
        $userMail = isset($user) ? $user->email : 'root';

        return "[".$userMail."] [".basename($file)." on line {$line}]";
    }
    
    /**
     * sql
     * Stampa su file di log la stringa dell'ultima query eseguita
     * @param  mixed $arr
     * @param  mixed $file
     * @param  mixed $line
     * @param  mixed $res
     * @param  mixed $printOut [false, dump, return]
     * @return void
     */
    public function sql($arr, $file, $line, $res = '', $printOut = false) {
        $query = end($arr);
        $sql = $query['query'];
        // $sql = Str::replaceArray('?', $query['bindings'], $sql);
        foreach($query['bindings'] as $binding) {
            $binding = is_numeric($binding) ? $binding : "'{$binding}'";
            $sql = Str::replaceFirst("?",$binding, $sql);
        }

        if($printOut == 'dump') {
            dump("[{$sql}] res[{$res}] [time:{$query['time']}]");
        } elseif($printOut == 'return') {
             return $sql;
        } else {
            $this->log("SQL [{$sql}] r[{$res}] [time:{$query['time']}]", $file, $line, 'debug');
        }
    }

    /**
     * print_r_row
     * Stampa su un'unica riga il contenuto di un array/oggetto per i fields specificati
     * @param  mixed $arr
     * @param  mixed $file
     * @param  mixed $line
     * @param  mixed $fields [optional, fields da stampare]
     * @param  mixed $printOut [false (stampa sul file di log), dump, return]
     * @return void
     */
    public function print_r_row($arr, $file, $line, $fields = [], $printOut = false) {
        $arr = (array)$arr;
        if(count($fields) == 0) {
            $fields = array_keys($arr);
        }
        foreach($fields as $field) {
            $str = " - {$field}: {$arr[$field]}";
        }
        if($printOut == 'dump') {
            dump($str);
        } elseif($printOut == 'return') {
             return $str;
        } else {
            $this->log("DATA {$str}", $file, $line, 'debug');
        }
        
    }
    
    /**
     * setChannel
     * Imposta il canale di debug
     * @param  mixed $value
     * @return void
     */
    public function setChannel($value = 'log') {
        $this->_channel = $value;
    }
        
    /**
     * setLevel
     * Imposta il livello di debug
     * @param  mixed $value
     * @return void
     */
    public function setLevel($value = 0) {
        $value > 2 ?: $this->level = $value;
    }
}

