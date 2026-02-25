<?php

namespace Packages\IctInterface\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends IctModel
{
    protected $guarded = ['form_id', 'report', 'id'];

    /**
     * Relationship: relazione 1/n menus -> reports
     */
    public function reports()
    {
        return $this->hasMany('Packages\IctInterface\Models\Report');
    }



}
