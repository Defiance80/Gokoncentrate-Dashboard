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
            // Account
            'name'     => ['required', 'string', 'max:120'],
            'company'  => ['required', 'string', 'max:160'],
            'email'    => ['required', 'email', 'max:190', 'unique:publishers,email'],
            'phone'    => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Publication profile (qualification)
            'website'          => ['nullable', 'string', 'max:2048'],
            'content_focus'    => ['required', 'string', 'max:120'],
            'formats'          => ['required', 'array', 'min:1'],
            'formats.*'        => ['string', 'in:veemag,podcast,short_film,music_video'],
            'cadence'          => ['required', 'string', 'max:60'],
            'audience_size'    => ['nullable', 'string', 'max:60'],
            'region'           => ['nullable', 'string', 'max:120'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_youtube'   => ['nullable', 'string', 'max:255'],
            'social_tiktok'    => ['nullable', 'string', 'max:255'],
            'has_content'      => ['nullable', 'string', 'max:10'],
            'sample_url'       => ['nullable', 'string', 'max:2048'],
            'experience'       => ['nullable', 'string', 'max:2000'],
            'pitch'            => ['required', 'string', 'max:3000'],
            'agree_terms'      => ['accepted'],
        ], [
            'agree_terms.accepted' => 'Please confirm you agree to the publisher terms.',
            'formats.required'     => 'Select at least one type of content you plan to publish.',
            'pitch.required'       => 'Tell us a little about what you want to publish.',
        ]);

        $publisher = Publisher::create([
            'name'          => $data['name'],
            'company'       => $data['company'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'website'       => $data['website'] ?? null,
            'content_focus' => $data['content_focus'],
            'password'      => $data['password'],
            'status'        => 'pending',
            'profile'       => [
                'formats'       => $data['formats'],
                'cadence'       => $data['cadence'],
                'audience_size' => $data['audience_size'] ?? null,
                'region'        => $data['region'] ?? null,
                'social'        => [
                    'instagram' => $data['social_instagram'] ?? null,
                    'youtube'   => $data['social_youtube'] ?? null,
                    'tiktok'    => $data['social_tiktok'] ?? null,
                ],
                'has_content'   => ($data['has_content'] ?? null) === 'yes',
                'sample_url'    => $data['sample_url'] ?? null,
                'experience'    => $data['experience'] ?? null,
                'pitch'         => $data['pitch'],
                'applied_at'    => now()->toDateTimeString(),
            ],
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
