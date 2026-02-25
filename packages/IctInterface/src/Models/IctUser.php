<?php

namespace Packages\IctInterface\Models;

use Illuminate\Notifications\Notifiable;

class IctUser extends IctModel
{
    use Notifiable;

    protected $table = 'users';

    public function profiles()
    {
        return $this->belongsToMany(Profile::class,'profiles_has_users', 'user_id', 'profile_id');
    }
}
