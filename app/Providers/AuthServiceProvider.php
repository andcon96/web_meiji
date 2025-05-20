<?php

namespace App\Providers;

use App\Models\Settings\Menu;
use App\Models\Settings\MenuAccess;
use App\Models\Settings\Role;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        Passport::ignoreRoutes();

        // Set access tokens to expire in 2 years
        Passport::personalAccessTokensExpireIn(Carbon::now()->addYears(2));

        
        Gate::define('access_menu', function ($user, $linkMenu) {
            $haveAccess = false;

            $role = Role::where('id', $user->role_id)->first();
            $menuAccess = MenuAccess::where('role_id', $role->id)->get();
            $menuCode = Menu::where('menu_route', $linkMenu)->first();
            // dd($menuCode, $menuAccess);

            if ($user->is_super_user == 'Yes') {
                $haveAccess = true;
            } else {
                if ($menuAccess->contains('menu_id', $menuCode->id)) {
                    $haveAccess = true;
                } else {
                    Log::channel('access_menu_log')->info($user->name . ' is trying to access route: ' . $linkMenu);
                }
            }
            return $haveAccess;
        });
    }
}
