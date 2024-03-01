<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Picture extends Model
{
    protected $fillable = [
        'name', 'weight', 'date', 'album_id','hash'
    ];

    // Связь с моделью Album
    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    // Связь с моделью Reaction
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    // Связь с моделью Tag
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
}
