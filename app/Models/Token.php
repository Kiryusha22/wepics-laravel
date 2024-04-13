<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;

    // Заполняемые поля
    protected $fillable = [
        'user_id', 'value'
    ];

    // Связи
    public function user() {
        return $this->belongsTo(User::class);
    }
}
