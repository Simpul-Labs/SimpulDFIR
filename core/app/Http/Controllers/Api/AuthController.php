<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        $storedHash = Cache::get('admin_password_hash', hash('sha256', 'admin'));
        $inputHash = hash('sha256', $password);

        if ($username !== 'admin' || $inputHash !== $storedHash) {
            return response()->json(['detail' => 'Invalid username or password'], 401);
        }

        $token = Str::random(64);
        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'username' => 'admin'
        ]);
    }

    public function changePassword(Request $request)
    {
        $oldPassword = $request->input('old_password');
        $newPassword = $request->input('new_password');

        $storedHash = Cache::get('admin_password_hash', hash('sha256', 'admin'));
        $oldHash = hash('sha256', $oldPassword);

        if ($oldHash !== $storedHash) {
            return response()->json(['detail' => 'Incorrect old password'], 400);
        }

        if (strlen($newPassword) < 4) {
            return response()->json(['detail' => 'New password must be at least 4 characters long'], 400);
        }

        Cache::forever('admin_password_hash', hash('sha256', $newPassword));

        return response()->json(['message' => 'Password changed successfully']);
    }
}
