<?php

namespace App\Http\Controllers;

use App\XxroleMstr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Imports\LocationDetailImport;
use App\Imports\CycleDetailImport;
use App\Models\Settings\Location;
use App\Models\Settings\LocationDetail;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class CycleCountController extends Controller
{

    public function index()
    {
        // dd('index');
        return view('/cyclecount/create');
    }
    
    public function upd(Request $req)
    {  
        $site =  $req->input('site');
        $item = $req->input('item');
        $lot =  $req->input('lot');        
        $wrh = $req->input('wrh');  
        $level = $req->input('level');       
        $bin = $req->input('bin');
        $qty = $req->input('qty');

         $data1 = array('xxinv_qtyoh' => $qty,);
        DB::table('xxinv_det')->where('xxinv_site', $site)
        ->where('xxinv_part', $item)
        ->where('xxinv_lot', $lot)
        ->where('xxinv_wrh', $wrh)
        ->where('xxinv_level', $level)
        ->where('xxinv_bin', $bin)
         ->update($data1);

         return view('/cyclecount/create');

    }
   

     public function uploadcycledetail()
    {
         return view('/cyclecount/upload');
    }
    
     public function confirmcycle(Request $request)
    {
        ini_set('max_execution_time', 3000);

        $filePath = public_path('upload/temp/' . $request->tempFileName);
       

        Excel::import(new CycleDetailImport, $filePath);

        File::delete($filePath);

        toast('Location updated successfully', 'success');
        return view('/cyclecount/create');
    }

    
    public function checkcycle(Request $request)
    {
        $extension = $request->file('file')->extension();
        $data = Excel::toArray([], $request->file('file'));
        $sheetData = $data[0];

        if ($extension != 'xls' && $extension != 'xlsx') {
            return response()->json(['File Extension Must Be .XLS or .XLSX'], 500);
        }

        if (count($sheetData[0]) != 6 && $sheetData[0][0] != 'Item') {
            return response()->json(['Template Berbeda, Pastikan menggunakan template yang disediakan'], 500);
        }

        $image = $request->file('file');

        $imageName = time() . '-' . strtoupper(Str::random(10)) . '.' . $image->extension();
        $image->move(public_path('upload/temp'), $imageName);

        return response()->json(['data' => $sheetData, 'imageName' => $imageName]);
    }


}
