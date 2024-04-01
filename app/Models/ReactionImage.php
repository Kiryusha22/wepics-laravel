<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReactionImage extends Model
{
    protected $fillable = [
        'image_id',
        'reaction_id',
        'user_id'
    ];
    public function picture()
    {
        return $this->belongsToMany(Image::class, 'create_reaction_images');
    }
    public function user()
    {
        return $this->belongsToMany(User::class, 'create_reaction_images');
    }
}
