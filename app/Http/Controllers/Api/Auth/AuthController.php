<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
                                              'email' => 'required|email',
                                              'password' => 'required',
                                          ]);

        if (!Auth::attempt($credentials)) {

            return response()->json([
                                        'message' => 'Lütfen e-posta adresinizi ve parolanızı kontrol edin.',
                                    ], 401);
        }

        $user = Auth::user();

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json([
                                    'token' => $token,
                                    'user' => [
                                        'id' => $user->id,
                                        'name' => $user->name,
                                        'email' => $user->email,
                                    ],
                                ]);
    }
}
