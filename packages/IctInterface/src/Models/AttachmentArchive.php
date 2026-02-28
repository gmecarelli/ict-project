<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Usa Packages\IctInterface\Models\Attachment con relazione polimorfica.
 * Questo model legacy è mantenuto solo per compatibilità con codice esistente.
 */
class AttachmentArchive extends IctModel
{
    protected $guarded = ['form_id', 'report', 'id'];
}
