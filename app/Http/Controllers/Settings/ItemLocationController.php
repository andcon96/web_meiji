<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Imports\ItemLocationImport;
use App\Imports\LocationDetailImport;
use App\Models\Settings\Item;
use App\Models\Settings\ItemLocation;
use App\Models\Settings\Location;
use App\Models\Settings\LocationDetail;
use App\Services\ServerURL;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ItemLocationController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $data = Location::with('getDetailLocation')->orderBy('location_code')->get();

        return view('setting.itemlocation.index', compact('menuMaster', 'data'));
    }

    public function edit($id)
    {
        $data = Location::with('getDetailLocation')->where('id', $id)->first();

        return view('setting.itemlocation.edit', compact('data'));
    }

    public function store(Request $request)
    {
        $newData = new ItemLocation();
        $newData->il_ld_id = $request->idLocation;
        $newData->il_item_id = $request->item;
        $newData->save();


        toast('Item Location successfully saved', 'success');
        return back();
    }

    public function destroy($id)
    {
        $deleteData = ItemLocation::find($id);
        $deleteData->delete();

        toast('Item Location successfully deleted', 'success');
        return back();
    }

    public function itemlocationdetail(Request $request, $id)
    {
        $data = LocationDetail::with(['getMaster', 'getListItem.getItem'])->find($id);

        return view('setting.itemlocation.editDetail', compact('data'));
    }

    public function createItemLocationDetail($id)
    {
        $data = LocationDetail::with(['getMaster', 'getListItem.getItem'])->find($id);
        $item = Item::get();
        return view('setting.itemlocation.create', compact('data', 'item'));
    }

    public function downloadTemplateLoadItemLocation(Request $request)
    {
        $path = public_path('/template/template_load_item_location.xlsx');

        return response()->download($path, '', [
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    public function uploadItemLocationDetail()
    {
        return view('setting.itemlocation.upload');
    }

    public function checkFileUploadItemLocation(Request $request)
    {
        $extension = $request->file('file')->extension();
        $data = Excel::toArray([], $request->file('file'));
        $sheetData = $data[0];

        if ($extension != 'xls' && $extension != 'xlsx') {
            return response()->json(['File Extension Must Be .XLS or .XLSX'], 500);
        }

        if (count($sheetData[0]) != 7 && $sheetData[0][0] != 'Item Part') {
            return response()->json(['Template Berbeda, Pastikan menggunakan template yang disediakan'], 500);
        }

        $image = $request->file('file');

        $imageName = time() . '-' . strtoupper(Str::random(10)) . '.' . $image->extension();
        $image->move(public_path('upload/temp'), $imageName);

        return response()->json(['data' => $sheetData, 'imageName' => $imageName]);
    }

    public function confirmFileUploadItemLocation(Request $request)
    {
        try {
            $filePath = public_path('upload/temp/' . $request->tempFileName);

            $import = new ItemLocationImport;
            Excel::import($import, $filePath);
            File::delete($filePath);

            if (count($import->errorList) > 0) {
                $error = '';
                foreach ($import->errorList as $errorLists) {
                    $error .= '<li>' . $errorLists . '</li>';
                }
                alert()->html('<strong>Error</strong>', $error)->persistent('Dismiss');
                return back();
            }

            toast('Item Location successfully uploaded', 'success');
            return back();
        } catch (Exception $e) {
            alert()->error('Error', 'Upload Gagal, Silahkan coba lagi');
            return back();
        }
    }
}
