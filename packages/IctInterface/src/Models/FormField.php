<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends IctModel
{
    protected $guarded = ['report', 'id'];
    /**
     * Relationship: relazione 1/n inversa form_fields -> forms
     */
    public function form()
    {
        return $this->belongsTo('Packages\IctInterface\Models\Form');
    }


}
