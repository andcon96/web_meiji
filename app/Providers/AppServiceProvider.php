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
        Test::observe(TestObserver::class);
        // $favMenus = FavMenu::with('getMenu.getMenuType.getIcon')->get();
        // $menuMaster = MenuMaster::orderBy('menu_sort_header')->where('menu_is_parent', 0)->get();

        // View::share('global_favMenus', $favMenus);
        // View::share('global_menuMaster', $menuMaster);
        $domains = Domain::orderBy('domain')->get();
        $externalLink = ExternalLink::first();
        if ($externalLink) {
            // $view->with('externalLink', $externalLink->external_link);
            View::share('externalLink', $externalLink->external_link);
        }
        View::share('domains', $domains);

        View::composer('layout.layout', function ($view) {
            $user = Auth::user();
            $menuAccess = MenuAccess::where('role_id', $user->role_id)->pluck('menu_id');
            $menuStructures = MenuStructure::query()->with('getMenu');

            if ($user->is_super_user != 'Yes') {
                $menuStructures->whereIn('menu_id', $menuAccess);
            }

            $menuStructures = $menuStructures->orderBy('menu_sequence')->tree()->get();
            $menuTree = $menuStructures->toTree();
            // dd($menuTree);

            $view->with('menuTree', $menuTree);
        });
    }
}
