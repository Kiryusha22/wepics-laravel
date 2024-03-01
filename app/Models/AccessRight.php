<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessRight extends Model
{
    protected $fillable = [
        'user_id', 'album_id', 'allowed'
    ];

    // Связь с моделью User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Связь с моделью Album
    public function album()
    {
        return $this->belongsTo(Album::class);
    }
}
