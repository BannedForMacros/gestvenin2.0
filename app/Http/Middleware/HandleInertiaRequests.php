<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Config;   // para leer banderas
use Illuminate\Support\Facades\Auth;     // opcional, por legibilidad

class HandleInertiaRequests extends Middleware
{
    /**  Vista Blade raíz que Inertia cargará. */
    protected $rootView = 'app';

    /**  Versión de assets (mantén la de Laravel). */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**  Datos que se envían a TODAS las páginas Inertia. */
    public function share(Request $request): array
    {
        /* -----------------------------------------
         | HELPERS locales
         |----------------------------------------*/
        $user = $request->user();

        return array_merge(parent::share($request), [

            /* ---------- info de autenticación ---------- */
            'auth' => [
                'user'  => $user,
                // Spatie devuelve una Collection => la convertimos en array
                'roles' => $user ? $user->getRoleNames()->toArray()          : [],
                'perms' => $user ? $user->getAllPermissions()->pluck('name')
                                                ->toArray() : [],
            ],

            /* ---------- flashes ---------- */
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],

            /* ---------- feature toggles opcionales ---------- */
            'featureToggles' => [
                // Ejemplo: añade en config/features.php o env('FEATURE_NEW_MENU')
                'newMenu' => Config::get('features.new_menu', false),
            ],
        ]);
    }
}
