<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReactionImage extends Model
{
    // Заполняемые поля
    protected $fillable = [
        'image_id', 'reaction_id', 'user_id'
    ];

    // Связи
    public function image() {
        return $this->belongsToMany(Image::class);
    }
    public function user() {
        return $this->belongsToMany(User::class);
    }
    public function reaction() {
        return $this->belongsToMany(Reaction::class);
    }
}
