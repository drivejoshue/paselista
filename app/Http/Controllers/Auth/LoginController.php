<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectAuthenticatedUser(
                Auth::user()
            );
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        $authenticated = Auth::attempt([
            'email' => mb_strtolower(
                trim($credentials['email'])
            ),
            'password' => $credentials['password'],
            'status' => 'active',
        ], $remember);

        if (! $authenticated) {
            return back()
                ->withErrors([
                    'email' => (
                        'Las credenciales no son correctas '
                        .'o la cuenta está suspendida.'
                    ),
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectAuthenticatedUser(
            $request->user()
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectAuthenticatedUser(
        User $user
    ): RedirectResponse {
        return match ($user->role) {
            'superadmin' => redirect()
                ->route('sysadmin.dashboard'),

            'school_admin',
            'director' => redirect()
                ->route('admin.dashboard'),

            'guardian' => redirect()
                ->route('guardian.home'),

            'student' => redirect()
                ->route('student.home'),

            default => redirect()
                ->route('home'),
        };
    }
}