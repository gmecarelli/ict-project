<?php

namespace Packages\IctInterface\Models;

use Illuminate\Database\Eloquent\Model;
use Packages\IctInterface\Models\IctUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends IctModel
{
    public function users() {
        return $this->belongsToMany(IctUser::class,'profiles_has_users', 'profile_id', 'user_id');
    }
}
