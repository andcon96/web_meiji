<?php

namespace App\Providers;

use App\Models\favMenu\FavMenu;
use App\Models\MenuMaster\MenuMaster;
use App\Models\Settings\Domain;
use App\Models\Settings\ExternalLink;
use App\Models\Settings\Menu;
use App\Models\Settings\MenuAccess;
use App\Models\Settings\MenuStructure;
use App\Models\Test;
use App\Observers\TestObserver;
use App\Services\ServerURL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layout.layout', function ($view) {
            $user = Auth::user();
            $menuAccess = MenuAccess::where('role_id', $user->role_id)->pluck('menu_id');
            $menuStructures = MenuStructure::query()->with('getMenu');

            if ($user->is_super_user != 'Yes') {
                $menuStructures->whereIn('menu_id', $menuAccess);
            }

            $menuStructures = $menuStructures->orderBy('menu_sequence')->tree()->get();
            $menuTree = $menuStructures->toTree();

            $view->with('menuTree', $menuTree);
        });
    }
}
