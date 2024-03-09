<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'nickname', 'password', 'login'
    ];

    protected $hidden = [
        'password',
    ];

    // Связь с моделью AccessRight
    public function accessRights() {
        return $this->hasMany(AccessRight::class);
    }

    // Связь с моделью Reaction
    public function reactions() {
        return $this->hasMany(Reaction::class);
    }

    // Связь с моделью Tag
    public function tags() {
        return $this->hasMany(Tag::class);
    }
    public function generateToken(){
        $token = Token::create([
            'user_id' => $this->id,
            'value' => Str::random(255),

        ]);
        return $token->value;
    }
}
