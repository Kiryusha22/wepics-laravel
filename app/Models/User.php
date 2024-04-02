<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'nickname',
        'password',
        'login'
    ];
    protected $hidden = [
        'password',
    ];
    protected $casts = [
        'password' => 'hashed',
    ];

    static public function getByToken($token): User {
        $cachePath = "user:token=$token";
        $user = Cache::get($cachePath);
        if (!$user) {
            $tokenDB = Token::where('value', $token)->first();
            if (!$tokenDB)
                throw new ApiException(401, 'Invalid token');

            $user = $tokenDB->user;
            Cache::put($cachePath, $user, 1800);
        }
        return $user;
    }
    public function generateToken(): string {
        $token = Token::create([
            'user_id' => $this->id,
            'value' => Str::random(255),
        ]);
        return $token->value;
    }

    public function accessRights() {
        return $this->hasMany(AccessRight::class);
    }
    public function reactions() {
        return $this->hasMany(Reaction::class);
    }
    public function tags() {
        return $this->hasMany(Tag::class);
    }
    public function tokens() {
        return $this->hasMany(Token::class);
    }
}
