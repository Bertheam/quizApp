<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'quiz';

    public function user() {
        return $this->belongsTo(User::class, 'users_id');
    }

}
