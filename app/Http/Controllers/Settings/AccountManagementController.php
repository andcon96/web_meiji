<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Domain;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Illuminate\Http\Request;

class AccountManagementController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);

        return view('setting.accountManagement.index', compact('menuMaster'));
    }

    public function create(Request $request)
    {
        $domains = Domain::orderBy('domain')->get();
    
        return view('setting.accountManagement.create', compact(
            'domains',
        ));
    }

    public function getAccountManagementCode(Request $request)
    {
        $domain_id = $request->domain;
        $type = $request->type;
        $domainMaster = Domain::where('id', $domain_id)->first();

        $listCodes = [];
        
        switch ($type) {
            case 'Account':
                $wsaAccounts = (new WSAServices())->wsaAccount($domainMaster->id, $domainMaster->domain);
                if ($wsaAccounts[0] == 'false') {
                    toast('No account can be found, please contact admin', 'info');
                } else {
                    $listCodes = $wsaAccounts[1];
                }
            break;

            case 'Sub Account':
                $wsaSubAccounts = (new WSAServices())->wsaSubAccount($domainMaster->id, $domainMaster->domain);
                if ($wsaSubAccounts[0] == 'false') {
                    toast('No account can be found, please contact admin', 'info');
                } else {
                    $listCodes = $wsaSubAccounts[1];
                }
            break;

            case 'Cost Center':
                $wsaCostCenter = (new WSAServices())->wsaCostCenter($domainMaster->id, $domainMaster->domain);
                if ($wsaCostCenter[0] == 'false') {
                    toast('No cost center can be found, please contact admin', 'info');
                } else {
                    $listCodes = $wsaCostCenter[1];
                }
            break;

            case 'Project':
                $wsaProject = (new WSAServices())->wsaProject($domainMaster->id, $domainMaster->domain);
                if ($wsaProject[0] == 'false') {
                    toast('No project can be found, please contact admin', 'info');
                } else {
                    $listCodes = $wsaProject[1];
                }
            break;
        }
        
        return $listCodes;
    }
}
