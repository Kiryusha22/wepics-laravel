<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function login(LoginRequest $request){
        $user = User
            ::where('login', $request->login)
            ->where('password', $request->password)
            ->first();
        if(!$user)
            throw new ApiException(401, 'Authorization failed');
        $token= $user->generateToken();
        return response([
            'token'=>$token,
        ]);
    }
    public function reg(RegisterRequest $request){
        $user = User::create($request->all());
        $token= $user->generateToken();
        return response([
            'token'=>$token
        ], 201);
    }
    public function logout(Request $request){
        Token::where('value', $request->bearerToken())->delete();
        return response(null, 204);
    }
}
