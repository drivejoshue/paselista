<?php
declare(strict_types=1);

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
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        $login = mb_strtolower(trim($credentials['email']));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $authenticated = Auth::attempt([
            $field => $login,
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
    // Docentes: conservan su portal y
    // el cambio obligatorio de contraseña.
    if ($user->role === 'teacher') {
        return redirect()->route(
            $user->must_change_password
                ? 'teacher.password.change'
                : 'teacher.dashboard'
        );
    }

    return match ($user->role) {
        // Administración general del sistema.
        'superadmin' => redirect()
            ->route('sysadmin.dashboard'),

        // Administración y dirección escolar.
        'school_admin',
        'director' => redirect()
            ->to('/admin/schoolcore'),

        // Portales especializados.
        'guardian' => redirect()
            ->route('guardian.home'),

        'student' => redirect()
            ->route('student.home'),

        default => redirect()
            ->route('home'),
    };
}

}