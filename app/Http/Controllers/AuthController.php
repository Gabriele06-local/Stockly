<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Never honor asset-like intended URLs (e.g. /favicon.ico) that
            // would land the user on a blank page after login.
            $intended = session()->pull('url.intended', route('dashboard'));
            if (! is_string($intended) || preg_match('~\.(ico|png|jpe?g|svg|css|js|map|woff2?)(\?.*)?$~i', $intended)) {
                $intended = route('dashboard');
            }

            return redirect()->to($intended);
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
