<?php

namespace App\Http\Controllers;

use App\Models\Settings\Domain;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class GeneralController extends Controller
{
    public function getItemCode(Request $request)
    {
        $domain = Domain::where('id', Session::get('domain'))->first();
        $itemCode = $request->search;
        $wsaItemCode = (new WSAServices())->wsaItemByFirstInput($domain->id, $domain->domain, $itemCode);

        return $wsaItemCode;
    }

    public function getTaxIn(Request $request)
    {
        $domain = Domain::where('id', Session::get('domain'))->first();
        $taxClass = $request->taxClass;
        $orderDate = $request->orderDate;
        $wsaGetTaxIn = (new WSAServices())->wsaGetTaxIn($domain->id, $domain->domain, $taxClass, $orderDate);

        return $wsaGetTaxIn;
    }

    public function getCurrencyBySupplier(Request $request)
    {
        // $domain = Domain::where('id', Session::get('domain'))->first();
        $supplierCode = $request->supplierCode;
        $domain_id = $request->domainID;
        $domain = Domain::where('id', $domain_id)->first();

        $cachedData = [];

        if (Cache::has('wsa_currency_by_supplier_' . $domain_id . '_' . $supplierCode)) {
            $cachedData = Cache::get('wsa_currency_by_supplier_'. $domain_id . '_' . $supplierCode);
        } else {
            $wsaCurrencyBySupplier = (new WSAServices())->wsaCurrencyBySupplier($domain->id, $domain->domain, $supplierCode);

            Cache::put('wsa_currency_by_supplier_' . $domain_id . '_' . $supplierCode, $wsaCurrencyBySupplier, now()->addMinutes(10));

            $cachedData = Cache::get('wsa_currency_by_supplier_' . $domain_id . '_' . $supplierCode);
        }

        return response()->json($cachedData);
    }

    public function getExchangeRate(Request $request)
    {
        if ($request->domainID != '') {
            $domain_id = $request->domainID;
        } else {
            $domain_id = Session::get('domain');
        }
        $domain = Domain::where('id', $domain_id)->first();
        $currencyFrom = $request->currFrom;
        $currencyTo  = $request->currTo;

        $cachedData = [];

        if (Cache::has('wsa_exchange_rate_' . $currencyFrom . '_to_' . $currencyTo)) {
            $cachedData = Cache::get('wsa_exchange_rate_' . $currencyFrom . '_to_' . $currencyTo);
        } else {
            $wsaExchangeRate = (new WSAServices())->wsaExchangeRate($domain->id, $domain->domain, $currencyFrom, $currencyTo);

            Cache::put('wsa_exchange_rate_' . $currencyFrom . '_to_' . $currencyTo, $wsaExchangeRate, now()->addMinutes(10));

            $cachedData = $wsaExchangeRate;
        }

        return response()->json($cachedData);
    }

    public function getItemMemo(Request $request)
    {
        $domain = Domain::where('id', Session::get('domain'))->first();
        $itemCode = $request->search;
        $wsaItemCode = (new WSAServices())->wsaItemMemo($domain->id, $domain->domain, $itemCode);

        return $wsaItemCode;
    }

    public function getTaxRate(Request $request)
    {
        $domainID = $request->domainID;
        $taxClass = $request->taxClass;
        $domain = Domain::where('id', $domainID)->first();
        $wsaTaxRate = (new WSAServices())->wsaTaxRate($domain, $taxClass);

        return $wsaTaxRate;
    }

    public function getAccountBySupplier(Request $request)
    {
        $domainID = $request->domainID;
        $supplierCode = $request->supplierCode;
        $domain = Domain::where('id', $domainID)->first();

        $cachedData = [];

        if (Cache::has('wsa_account_by_supplier_' . $domainID . '_' . $supplierCode)) {
            $cachedData = Cache::get('wsa_account_by_supplier_' . $domainID . '_' . $supplierCode);
        } else {
            $wsaAccountBySupplier = (new WSAServices())->wsaAccountBySupplier($domain, $supplierCode);
            $convertedData = json_decode(json_encode($wsaAccountBySupplier), true);

            Cache::put('wsa_account_by_supplier_' . $domainID . '_' . $supplierCode, $convertedData, now()->addMinutes(10));

            $cachedData = Cache::get('wsa_account_by_supplier_' . $domainID . '_' . $supplierCode);
        }

        return response()->json($cachedData);
    }

    public function getInventoryStatus(Request $request)
    {
        $domainID = $request->domain;
        $location = $request->location;

        $domainMaster = Domain::where('id', $domainID)->first();

        $wsaInventoryStatus = (new WSAServices())->getInventoryStatus($domainMaster, $location);

        return $wsaInventoryStatus;
    }

    public function getPackagingItem(Request $request)
    {
        $domain = Domain::where('id', Session::get('domain'))->first();
        $itemCode = $request->search;
        $wsaItemCode = (new WSAServices())->wsaPackagingItemByFirstInput($domain->id, $domain->domain, $itemCode);

        return $wsaItemCode;
    }
}
