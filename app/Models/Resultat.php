<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resultat extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public function quiz() {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'users_id');
    }
}
