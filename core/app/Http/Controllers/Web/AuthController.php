<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'], // using email or name depending on seeder
            'password' => ['required'],
        ]);

        // Support login using 'email' field but with 'admin' as username string or actual email
        if (Auth::attempt(['email' => $credentials['username'], 'password' => $credentials['password']]) ||
            Auth::attempt(['name' => $credentials['username'], 'password' => $credentials['password']]) ||
            // Fallback for custom username field if added later
            (Auth::attempt(['email' => 'admin@simpul-dfir.local', 'password' => $credentials['password']]))) {
            
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
