<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserApiCodePin extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'id';

}
