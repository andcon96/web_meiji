<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Imports\LocationDetailImport;
use App\Models\Settings\Location;
use App\Models\Settings\LocationDetail;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $data = Location::with('getDetailLocation')->orderBy('location_code')->get();

        return view('setting.location.index', compact('menuMaster', 'data'));
    }


    public function edit($id)
    {
        $data = Location::with('getDetailLocation')->where('id', $id)->first();
        $dataDetail = $data->getDetailLocation->toArray();

        return view('setting.location.edit', compact('data', 'dataDetail'));
    }

    public function update(Request $request)
    {
        $idMaster = $request->u_id;
        $dataDetail = $request->menuLocationDetail;
        try {
            DB::beginTransaction();

            foreach ($dataDetail as $dataDetails) {
                $locationDetail = LocationDetail::firstOrNew(['id' => $dataDetails['id']]);
                $locationDetail->ld_lot_serial = $dataDetails['ld_lot_serial'];
                $locationDetail->ld_building = $dataDetails['ld_building'];
                $locationDetail->ld_rak = $dataDetails['ld_rak'];
                $locationDetail->ld_bin = $dataDetails['ld_bin'];
                $locationDetail->ld_location_id = $idMaster;
                $locationDetail->save();
            }

            DB::commit();

            toast('Location updated successfully', 'success');
            return back();
        } catch (Exception $e) {
            DB::rollBack();
            toast('Failed to update data', 'error');
            return back();
        }
    }

    public function loadLocation(Request $request)
    {
        $dataLocations = (new WSAServices())->wsaLocation();
        if ($dataLocations[0] == 'true') {
            DB::beginTransaction();

            try {
                $dataLocations = $dataLocations[1];
                $currentUser = Auth::user()->id;

                foreach ($dataLocations as $location) {
                    // Cek dulu supplier code nya ada atau engga.
                    $locationExists = Location::where('location_code', $location->t_loc)->where('location_site', $location->t_site)
                        ->first();

                    if ($locationExists) {
                        $locationExists->location_site = $location->t_site;
                        $locationExists->location_code = strval($location->t_loc);
                        $locationExists->location_desc = strval($location->t_locdesc);
                        $locationExists->save();
                    } else {
                        // Create supplier baru
                        $newLocation = new Location();
                        $newLocation->location_site = strval($location->t_site);
                        $newLocation->location_code = strval($location->t_loc);
                        $newLocation->location_desc = strval($location->t_locdesc);
                        $newLocation->save();
                    }
                }

                DB::commit();
                toast('Location loaded successfully', 'success');
            } catch (\Exception $err) {
                DB::rollBack();

                toast('Failed to load Location', 'error');
            }
        } else {
            toast('No data found from WSA', 'info');
        }
        return redirect()->back();
    }

    public function downloadTemplateLoadLocation()
    {
        $path = public_path('/template/template_load_location.xlsx');

        return response()->download($path, '', [
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    public function uploadLocationDetail()
    {
        return view('setting.location.upload');
    }

    public function checkFileUploadLocation(Request $request)
    {
        $extension = $request->file('file')->extension();
        $data = Excel::toArray([], $request->file('file'));
        $sheetData = $data[0];

        if ($extension != 'xls' && $extension != 'xlsx') {
            return response()->json(['File Extension Must Be .XLS or .XLSX'], 500);
        }

        if (count($sheetData[0]) != 6 && $sheetData[0][0] != 'Location') {
            return response()->json(['Template Berbeda, Pastikan menggunakan template yang disediakan'], 500);
        }

        $image = $request->file('file');

        $imageName = time() . '-' . strtoupper(Str::random(10)) . '.' . $image->extension();
        $image->move(public_path('upload/temp'), $imageName);

        return response()->json(['data' => $sheetData, 'imageName' => $imageName]);
    }

    public function confirmFileUploadLocation(Request $request)
    {
        $filePath = public_path('upload/temp/' . $request->tempFileName);

        Excel::import(new LocationDetailImport, $filePath);

        File::delete($filePath);

        toast('Location updated successfully', 'success');
        return redirect()->route('locations.index');
    }
}
