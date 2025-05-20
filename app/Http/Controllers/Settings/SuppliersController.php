<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Supplier;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $suppliers = Supplier::with(['getDomain'])->get();
        
        return view('setting.supplier.index', compact('menuMaster', 'suppliers'));
    }

    public function loadSupplier(Request $request)
    {
        $dataDomain = Auth::user()->getDomain;
        $suppliers = (new WSAServices())->wsasupp($dataDomain->domain);
        if ($suppliers[0] == 'true') {
            DB::beginTransaction();

            try {
                $dataSuppliers = $suppliers[1];
                $currentUser = Auth::user()->id;

                foreach ($dataSuppliers as $supplier) {
                    // Cek dulu supplier code nya ada atau engga.
                    $supplierExists = Supplier::where('domain_id', $dataDomain->id)
                        ->where('supp_code', $supplier->t_suppcode)->first();
                    if ($supplierExists) {
                        $supplierExists->supp_code = (String) $supplier->t_suppcode;
                        $supplierExists->supp_name = (String) $supplier->t_suppname;
                        $supplierExists->supp_addr = (String) $supplier->t_address;
                        // Kalau ada perubahan baru save
                        if ($supplierExists->isDirty()) {
                            $supplierExists->load_by_id = $currentUser;
                            $supplierExists->save();
                        }
                    } else {
                        // Create supplier baru
                        $newSupplier = new Supplier();
                        $newSupplier->domain_id = $dataDomain->id;
                        $newSupplier->supp_code = $supplier->t_suppcode;
                        $newSupplier->supp_name = $supplier->t_suppname;
                        $newSupplier->supp_addr = $supplier->t_address;
                        $newSupplier->load_by_id = $currentUser;
                        $newSupplier->save();
                    }
                }

                DB::commit();
                toast('Supplier loaded successfully', 'success');
            } catch (\Exception $err) {
                DB::rollBack();
                
                toast('Failed to load supplier', 'error');
            }
        } else {
            toast('No data found from WSA', 'info');
        }
        return redirect()->back();
    }
}
