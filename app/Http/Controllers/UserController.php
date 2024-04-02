<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserEditRequest;
use App\Http\Requests\UserEditSelfRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    public function login(UserLoginRequest $request) {
        $credentials = request(['login', 'password']);
        if (!Auth::attempt($credentials))
            throw new ApiException(401, 'Authorization failed');

        $user = Auth::user();

        $token = $user->generateToken();
        return response([
            'token' => $token,
        ]);
    }
    public function reg(UserRegisterRequest $request) {
        $user = User::create($request->all());
        $token= $user->generateToken();
        return response([
            'token' => $token
        ], 201);
    }
    public function logout(Request $request) {
        $token = $request->bearerToken();
        Token::where('value', $token)->delete();
        Cache::delete("user:token=$token");
        return response(null, 204);
    }
    public function showAll() {
        return response(User::all());
    }
    public function show(int $id) {
        $user = User::find($id);

        if (!$user)
            throw new ApiException(404, 'User not found');

        return response($user);
    }
    public function create(UserCreateRequest $request) {
        $user = User::create($request->all());
        return response(null, 204);
    }
    public function edit(UserEditRequest $request, int $id) {
        $user = User::find($id);

        if (!$user)
            throw new ApiException(404, 'User not found');

        $user->update($request->all());
        return response(null, 204);
    }
    public function editSelf(UserEditSelfRequest $request) {
        $user = User::find($request->user()->id);
        if ($request->nickname) $user->update(['nickname' => $request->nickname]);
        if ($request->password) $user->update(['password' => $request->password]);
        return response(null, 204);
    }
    public function destroy(int $id) {
        $user = User::find($id);

        if (!$user)
            throw new ApiException(404, 'User not found');

        $user->delete();
        return response(null, 204);
    }
}
