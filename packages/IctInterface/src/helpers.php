<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Models\IctUser;
use Packages\IctInterface\Models\Option;
use Packages\IctInterface\Controllers\Services\Logger;
use Carbon\Carbon;

/**
 * Custom helpers
 */

/**
 * ddr
 * Esegue il dd di debugging includendo il rollback della transazione e la chiusura del ciclo
 * @param  mixed $var
 * @return void
 */
function ddr(...$var)
{
    DB::rollBack();
    dd($var);
}
function _decrypt($val)
{
    if (isset($val)) {
        $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
        return $app->_decrypt($val);
    }
}
function _encrypt($val)
{
    $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
    return $app->_encrypt($val);
}

/**
 * _parser
 * Parsa la stringa dei parametri scritta nel db (formato nome_chiave_1:valore_1,nome_chiave_2:valore_2) e la trasforma in un array associativo
 * @param  mixed $val
 * @return array
 */
function _parser($val) {
    $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
    return $app->stringToArray($val);
}
/**
 * _day
 * Restituisce il giorno di una data
 * @param  mixed $date
 * @return int
 */
function _day($date)
{
    return date("d", strtotime($date));
}

/**
 * _month
 * Restituisce il mese di una data
 * @param  mixed $date
 * @return int
 */
function _month($date)
{
    return date("m", strtotime($date));
}

/**
 * _year
 * Restituisce l'anno di una data
 * @param  mixed $date
 * @return int
 */
function _year($date)
{
    $y = date("Y", strtotime($date));
    return $y == 1970 ? date("Y", _dbDateFormat($date)) : $y;
}

/**
 * _dbDateFormat
 * Restituisce la stringa data in formato yyyy-mm-dd
 * @param  mixed $date
 * @return string
 */
function _dbDateFormat($date)
{

    $objDate = date_create(strtotime((string)$date));
    return strtotime($objDate->format("Y-m-d"));
}


/**
 * _convertDateItToDb
 * Converte la data dal formato italiano dd/mm/yyyy al formato per db yyyy-mm-dd
 * @param  mixed $date
 * @return void
 */
function _convertDateItToDb($date)
{
    $carbonDate = Carbon::createFromFormat('d/m/Y', $date);
    return $carbonDate->format('Y-m-d');
}

/**
 * _convertDateDbTo>It
 * Converte la data dal formato DB (yyyy-mm-dd) al formato italiano dd/mm/yyyy
 * @param  mixed $date
 * @return void
 */
function _convertDateDbToIt($date)
{
    $carbonDate = Carbon::createFromFormat('Y-m-d', $date);
    return $carbonDate->format('d/m/Y');
}

/**
 * _currency
 * Restituisce un numero in formato valuta
 * @param  mixed $val
 * @param  mixed $currency
 * @return void
 */
function _currency($val)
{
    if ($val === '-') {
        return $val;
    }
    $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
    return $app->_currency($val);
}

/**
 * _number
 * helper per i soli numeri interi con separatore delle migliaia
 * @param  mixed $val
 * @return void
 */
function _number($val)
{
    if ($val === '-') {
        return $val;
    }
    $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
    return $app->_integer($val);
}

/**
 * _decimal
 * Alias di _number mi permette solo di stabilire se un numero è o può essere decimale
 * @param  mixed $val
 * @return void
 */
function _decimal($val)
{
    return _number($val);
}

/**
 * _float
 * Restituisce un numero in formato decimale
 * @param  mixed $val
 * @return void
 */
function _float($val)
{
    if ($val === '-') {
        return $val;
    }
    $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
    return $app->_float($val);
}


/**
 * _int
 * Restituisce il dato in formato integer
 * @param  mixed $val
 * @return void
 */
function _int($val)
{
    $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
    return $app->_integer($val);
}

function _percent($val)
{
    return _int($val) . '%';
}

/**
 * _date
 * Converte e restituisce il dato in formato data italiano
 * @param  mixed $date
 * @return void
 */
function _date($date)
{
    $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
    return $app->_date($date);
}
/**
 * _date_time
 * Converte e restituisce il dato in formato data e ora italiano
 * @param  mixed $date
 * @return void
 */
function _date_time($date)
{
    $app = new \Packages\IctInterface\Controllers\Services\ApplicationService();
    return $app->_dateTime($date);
}

/**
 * _is_valid_date
 * Verifica se una data è valida
 * @param  mixed $date
 * @param  mixed $format
 * @return void
 */
function _is_valid_date($date, $format = 'Y-m-d')
{
    return !empty($date) &&
        $date !== '0000-00-00' &&
        Carbon::createFromFormat($format, $date) !== false;
}


/**
 * _log
 * Helper per scrivere il file log
 * @param  mixed $channel
 * @return object
 */
function _log($channel = 'log')
{
    $log = new Logger();
    $log->setChannel($channel);
    return $log;
}
/**
 * _commit
 * Esegue il commit per tutte le transazioni aperte
 * @param  mixed $file
 * @param  mixed $line
 * @return void
 */
function _commit($file, $line)
{
    for ($i = 0; $i < DB::transactionLevel(); $i++) {
        DB::commit();
        _log()->commit($file, $line);
    }
}

/**
 * _rollback
 * Esegue il rollBack per tutte le transazioni aperte
 * @param  mixed $file
 * @param  mixed $line
 * @return void
 */
function _rollback($file, $line)
{
    for ($i = 0; $i < DB::transactionLevel(); $i++) {
        DB::rollBack();
        _log()->rollBack($file, $line);
    }
}

/**
 * _sql
 * Logger per stringhe di query sql
 * @param  mixed $file
 * @param  mixed $line
 * @param  mixed $channel
 * @return void
 */
function _sql($file, $line, $channel = 'log')
{
    $log = _log($channel);
    $log->sql(DB::getQueryLog(), $file, $line);
}

function callback_clean($data)
{
    return addslashes($data);
}

/**
 * _findDate
 * Ricava una data prima o dopo un numero di giorni dati
 * @param  mixed $date [data di riferimento]
 * @param  mixed $days [giorni di scostamento. In caso di cercare una data precedente il numero deve essere negativo]
 * @return void
 */
function _find_date($date, $days)
{
    $d = _day($date);
    $m = _month($date);
    $Y = _year($date);
    return date("Y-m-d", mktime(0, 0, 0, $m, $d + $days, $Y));
}

/**
 * _option
 * Restituisce tutti i dati di un record status per la leggibilità del dato
 * @param  mixed $code [se null restituisce tutti i valori del reference in un array di oggetti]
 * @param  mixed $reference
 * @return object
 */
function _option($code, $reference)
{
    $obj = Option::where('reference', $reference);
    return is_null($code) ? $obj->get() : $obj->where('code', $code)->first();
}

/**
 * time_start
 * Restituisce il tempo di inizio di esecuzione del codice
 * @return void
 */
function time_start()
{
    return round(microtime(true), 3);
}

/**
 * time_end
 * Resituisce i secondi necessari per l'esecuzione del codice
 * @param  mixed $start
 * @return void
 */
function time_end($start)
{
    $end = round(microtime(true), 3);
    return $end - $start;
}

/**
 * _user
 * Restituisce l'utente loggato
 * @return object
 */
function _user()
{
    return IctUser::find(session('loggedUser')->id);
}
/**
 * _is_admin
 * Restituisce true se l'utente è amministratore
 * @return void
 */
function _is_admin()
{
    return session('is_admin');
}
/**
 * _profiles
 * Restituisce i profili dell'utente
 * @return void
 */
function _profiles()
{
    return session('profiles');
}


/**
 * _select_months
 * Restituisce l'html della select di una select con l'elenco dei mesi da inserire direttamente in un form
 * @param  mixed $name
 * @return void
 */
function _select_months($name = 'month', $required = true)
{
    $months = Option::where('reference', 'MONTH')
        ->get();
    $m = date("m");
    $select = [];
    $select[] = '<select id="' . $name . '" name="' . $name . '" class="form-control" required="' . $required . '">';
    $select[] = '<option label="" value="">- Seleziona</option>';
    foreach ($months as $month) {
        $sel = $month->code == $m ? 'selected' : '';
        $select[] = '<option label="' . $month->label . '" value="' . $month->code . '" ' . $sel . '>' . $month->label . '</option>';
    }
    $select[] = '</select>';
    return implode("\n", $select);
}
