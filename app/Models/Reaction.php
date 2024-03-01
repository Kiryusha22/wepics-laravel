<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $fillable = [
        'picture_id', 'reaction', 'user_id'
    ];

    // Связь с моделью Picture
    public function picture()
    {
        return $this->belongsTo(Picture::class);
    }

    // Связь с моделью User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
