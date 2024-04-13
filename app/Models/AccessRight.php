<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessRight extends Model
{
    use HasFactory;

    // Заполняемые поля
    protected $fillable = [
        'user_id', 'album_id', 'allowed'
    ];

    // Связи
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function album() {
        return $this->belongsTo(Album::class);
    }
}
