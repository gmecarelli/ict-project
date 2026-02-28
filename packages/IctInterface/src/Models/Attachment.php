<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'file_name_server',
        'file_name_original',
        'description',
        'path',
        'ext',
        'attachable_type',
        'attachable_id',
    ];

    /**
     * Relazione polimorfica inversa
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Path completo per accesso pubblico
     */
    public function getFullPathAttribute(): string
    {
        return $this->path . '/' . $this->file_name_server;
    }

    /**
     * URL pubblica per download
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->full_path);
    }
}
