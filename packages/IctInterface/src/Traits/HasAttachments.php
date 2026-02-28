<?php

namespace Packages\IctInterface\Traits;

use Packages\IctInterface\Models\Attachment;

trait HasAttachments
{
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
