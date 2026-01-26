<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();

            $user = Auth::user();

            if ($user->activo != 1) {
                Auth::logout(); // cerrar sesión
                return back()->withErrors([
                    'email' => 'Tu cuenta ha sido desactivada. Ya no tienes acceso al sistema.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', false));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Manejar credenciales inválidas
            return back()->withErrors([
                'email' => 'Correo y/o contraseña inválidas',
            ]);
        }
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        DB::table('sessions')->where('id', $request->session()->getId())->delete();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
