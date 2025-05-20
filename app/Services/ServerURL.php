<?php

namespace App\Services;

use App\Models\Settings\Menu;
use Exception;
use Illuminate\Support\Facades\Request;

class ServerURL
{
    public function defineServerUrl()
    {
        $serverURL = '/';

        return $serverURL;
    }

    public function currentURL($request)
    {
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $request->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = Menu::where('menu_route', str_replace($serverURL, '', $parsedUrl['path']), 1)->first();
        return $menuMaster;
    }
}