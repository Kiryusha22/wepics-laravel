<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $fillable = [
        'picture_id',
        'reaction',
        'user_id'
    ];

    public function picture()
    {
        return $this->belongsToMany(Image::class, 'create_reaction_image');
    }
    public function user()
    {
        return $this->belongsToMany(User::class, 'create_reaction_image');
    }
}
