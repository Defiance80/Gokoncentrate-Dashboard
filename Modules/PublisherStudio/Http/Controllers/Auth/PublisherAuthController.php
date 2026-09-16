<?php

namespace Modules\PublisherStudio\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\PublisherStudio\Models\Publisher;

class PublisherAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('publisher')->check()) {
            return redirect()->route('studio.dashboard');
        }
        return view('publisherstudio::auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $publisher = Publisher::where('email', $data['email'])->first();

        if ($publisher && $publisher->isSuspended()) {
            throw ValidationException::withMessages([
                'email' => 'This publisher account has been suspended. Please contact GoKoncentrate.',
            ]);
        }

        if (! Auth::guard('publisher')->attempt($data, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('studio.dashboard'));
    }

    public function showRegister()
    {
        if (Auth::guard('publisher')->check()) {
            return redirect()->route('studio.dashboard');
        }
        return view('publisherstudio::auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'company'  => ['nullable', 'string', 'max:160'],
            'email'    => ['required', 'email', 'max:190', 'unique:publishers,email'],
            'phone'    => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $publisher = Publisher::create([
            'name'     => $data['name'],
            'company'  => $data['company'] ?? null,
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => $data['password'],
            'status'   => 'pending',
        ]);

        Auth::guard('publisher')->login($publisher);
        $request->session()->regenerate();

        return redirect()->route('studio.dashboard')
            ->with('status', 'Welcome to GoKoncentrate Publisher Studio! Your account is under review, but you can start building your first publication now.');
    }

    public function logout(Request $request)
    {
        Auth::guard('publisher')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('studio.login');
    }
}
