<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'value', 'type'
    ];

    static public function findFromString($string) {
        return Tag::where('value', $string)->first();
    }

    public function images() {
        return $this->belongsToMany(Image::class, 'tag_image');
    }
}
