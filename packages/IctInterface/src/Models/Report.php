<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends IctModel
{
    /**
     * Relationship: relazione 1/n report report_columns
     */
    public function columns()
    {
        return $this->hasMany('Packages\IctInterface\Models\ReportColumn');
    }
    
    /**
     * Relationship: relazione 1/n report report_filters
     */
    public function filters()
    {
        return $this->hasMany('Packages\IctInterface\Models\ReportFilter');
    }

    /**
     * Relationship: relazione 1/n inversa reports -> menus
     */
    public function menu() {
        return $this->belongsTo('Packages\IctInterface\Models\Menu');
    }

    public function forms() {
        return $this->hasMany('Packages\IctInterface\Models\Form');
    }


}
