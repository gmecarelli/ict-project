<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends IctModel
{
    /**
     * Relationship: relazione 1/n forms -> form_fields
     */
    public function fields()
    {
        return $this->hasMany('Packages\IctInterface\Models\FormField');
    }

    public function report() {
        return $this->belongsTo('Packages\IctInterface\Models\Report');
    }


}
