<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachmentArchive extends IctModel
{
    protected $guarded = ['form_id', 'report', 'id'];
}
