<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registra cada permiso de config/permisos.php como un Gate.
        foreach (array_keys(config('permisos.permisos', [])) as $permiso) {
            Gate::define($permiso, fn (User $user) => $user->tienePermiso($permiso));
        }
    }
}
