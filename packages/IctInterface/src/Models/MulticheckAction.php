<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MulticheckAction extends IctModel
{
    protected $guarded = ['report', 'id'];

    protected $table = 'multicheck_actions';
}
