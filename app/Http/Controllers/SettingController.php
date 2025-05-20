<?php

namespace App\Http\Controllers;


use App\Models\Qxwsa as ModelsQxwsa;
use App\Models\SiteMstr;
use App\Models\LocMstr;
use App\Models\SPGroupMstr;
use App\Models\SPMstr;
use App\Models\SPTypeMstr;
use App\Models\SuppMstr;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\User;
use Carbon\Carbon;
use Svg\Tag\Rect;
use Response;
use File;
use App\Exports\AssetExport;
use App\Models\favMenu\FavMenu;
use App\Models\MenuMaster\MenuMaster;
use App\Services\ServerURL;
use Maatwebsite\Excel\Facades\Excel;

use Auth;

class SettingController extends Controller
{

    private function httpHeader($req) {
        return array('Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: ""',        // jika tidak pakai SOAPAction, isinya harus ada tanda petik 2 --> ""
            'Content-length: ' . strlen(preg_replace("/\s+/", " ", $req)));
    }
    // User Maint
    public function usermenu()
    {
        $data = DB::table('users')
            ->leftjoin('roles', 'users.role_user', 'roles.role_code')
            ->leftjoin('site_mstrs', 'users.site', 'site_mstrs.site_code')
            ->whereNotIn('username',DB::table('eng_mstr')->pluck('eng_code')->toArray())
            ->orderby('username')
            ->paginate(10);

        $datasite = DB::table('site_mstrs')
            ->get();

        $datadept = DB::table('dept_mstr')
            ->orderby('dept_desc')
            ->get();

        return view('setting.usermaint', ['data' => $data, 'datasite' => $datasite, 'datadept' => $datadept]);
    }

    public function createuser(Request $req)
    {
        $this->validate($req, [
            'username' => 'unique:users|max:6',
            'name' => 'max:24',
            'password' => 'max:15|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'max:15'
        ], [
            'username.unique' => 'Username sudah terdaftar',
            'password.same' => 'Password & Confirm Password Harus sama'
        ]);

        $dataarray = array(
            'username'      => $req->username,
            'name'          => $req->name,
            'email_user'    => $req->email,
            'role_user'     => 'sr',
            'dept_user'     => $req->dept,
            'active'        => 'Yes',
            'password'      => Hash::make($req->password),
            'created_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
            'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
        );
        DB::table('users')->insert($dataarray);

        toast('User Berhasil Dibuat !','success');
        return back();
    }

    public function cekuser(Request $req)
    {
        $cek = DB::table('users')
            ->where('username','=',$req->input('username'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    public function edituser(Request $req)
    {

        $username = $req->input('e_username');
        $nm       = $req->input('e_name');
        $email    = $req->input('e_email');
        $password = $req->input('e_password');
        $confpass = $req->input('e_password_confirmation');
        $dept     = $req->input('e_dept');
        $aktif    = $req->input('e_active');          
        if($password != $confpass)
        {
            toast("Password & Confirm Password Berbeda","error");
            return back();
        }else{
            if (is_null($password)) {
                DB::table('users')
                    ->where('username', $username)
                    ->update([
                        'email_user'    => $email,
                        'name'          => $nm,
                        'role_user'     => 'sr',
                        'active'        => $aktif,
                        'dept_user'     => $dept,
                        'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                    ]);
            } else {
                DB::table('users')
                    ->where('username', $username)
                    ->update([
                        'email_user'    => $email,
                        'name'          => $nm,
                        'role_user'     => 'sr',
                        'active'        => $aktif,
                        'dept_user'     => $dept,
                        'password'      => Hash::make($password),
                        'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                    ]);
            }

            toast('User successfully updated', 'success');
            return back();
        }
       
    }

    public function deleteuser(Request $req)
    {

        DB::table("users")
            ->where('username', '=', $req->tmp_username)
            ->delete();

        //session()->flash('updated', 'User berhasil dihapus');
        toast('User Berhasil Didelete !','success');
        return back();
    }

    public function getmenuuser(Request $req)
    {
        if ($req->ajax()) {
            $data = DB::table('users')
                ->where('username', '=', $req->search)
                ->first();

            return response($data->flag);
        }
    }

    public function userpaging(Request $req)
    {
        if ($req->ajax()) {

            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $username = $req->get('username');
            $divisi = $req->get('divisi');

            if ($username == '' and $divisi == '') {
                
                $data = DB::table('users')
                    ->leftjoin('roles', 'users.role_user', 'roles.role_code')
                    ->leftjoin('site_mstrs', 'users.site', 'site_mstrs.site_code')
                    ->whereNotIn('username',DB::table('eng_mstr')->pluck('eng_code')->toArray())
                    ->orderby('username')
                    ->paginate(10);

                return view('setting.table-usermaint', ['data' => $data]);
            } else {
                $kondisi = "id > 0";

                if ($username != '') {
                    $kondisi = "username like '%" . $username . "%'";
                }
                if ($divisi != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and dept_user like '%" . $divisi . "%'";
                    } else {
                        $kondisi = "dept_user like '%" . $divisi . "%'";
                    }
                }
               
                $data = DB::table('users')
                    ->leftjoin('roles', 'users.role_user', 'roles.role_code')
                    ->leftjoin('site_mstrs', 'users.site', 'site_mstrs.site_code')
                    ->whereNotIn('username',DB::table('eng_mstr')->pluck('eng_code')->toArray())
                    ->whereRaw($kondisi)
                    ->orderby('username')
                    ->paginate(10);

                return view('setting.table-usermaint', compact('data'));
            }
        }
    }

    // Role Menu
    public function rolemenu(Request $req)
    {
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        // Mengambil menu header
        $menuHeaders = DB::table('menu_master')
            ->where('menu_is_parent', 1)
            ->where('menu_level', 1)
            ->orderBy('menu_sort_header')
            ->get();

        // Mengambil submenu untuk setiap menu header
        $menuStructure = [];
        foreach ($menuHeaders as $menuHeader) {
            // Mengambil dan mengurutkan submenu level 2
            $submenusLevel2 = DB::table('menu_master')
                                ->where('menu_parent_id', $menuHeader->id)
                                ->orderBy('menu_sort_sub')
                                ->get();

            foreach ($submenusLevel2 as $submenuLevel2) {
                // Mengambil dan mengurutkan submenu level 3
                $submenusLevel3 = DB::table('menu_master')
                                    ->where('menu_parent_id', $submenuLevel2->id)
                                    ->orderBy('menu_sort_child')
                                    ->get();

                // Jika ada submenu level 3, gunakan itu, jika tidak, gunakan level 2
                if (count($submenusLevel3) > 0) {
                    $menuStructure[$menuHeader->id][$submenuLevel2->id] = $submenusLevel3;
                } else {
                    $menuStructure[$menuHeader->id][$submenuLevel2->id] = $submenuLevel2;
                }
            }
        }


        $data = DB::table('roles')
            ->orderBy('role_code', 'ASC')
            ->paginate(10);

        $user = DB::table('users')
            ->get();

        return view('setting.rolemaster', ['data' => $data, 'users' => $user, 'menuMaster' => $menuMaster, 'isFav' => $isFav, 'menuHeaders' => $menuHeaders, 'menuStructure' => $menuStructure]);
    }

    public function createrole(Request $req)
    {

        // Mengumpulkan akses menu
        $access = '';
        $menuCodes = $req->except(['_token', 'role_code', 'role_desc', 't_acc']); // asumsi t_acc adalah input lain

        foreach ($menuCodes as $menuCode => $value) {
            if ($value) { // Memeriksa apakah checkbox dicentang
                $access .= $value;
            }
        }
        
        $this->validate($req, [
            'role_code' => 'unique:roles|max:24',
            'role_desc' => 'unique:roles|max:50',
        ], [
            'role_code.unique' => 'Role Already Registered',
            'role_desc.required' => 'Description Cannot Blank',
            'role_desc.unique' => 'Description Already Registered'
        ]);
        $dataarray = array(
            'role_code' => $req->role_code,
            'role_desc' => $req->role_desc,
            'role_access' => $req->t_acc,
            'menu_access' => $access,
            'created_at' => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
            'updated_at' => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
            'edited_by' => Session::get('username'),
        );
        DB::table('roles')->insert($dataarray);


        toast('Role Created.', 'success');
        return back();
    }

    public function cekrole(Request $req)
    {
        $cek = DB::table('roles')
            ->where('role_code','=',$req->input('role_code'))
            ->orWhere('role_desc','=',$req->input('role_code'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    public function cekrole2(Request $req)
    {
        $cek = DB::table('roles')
            ->where('role_code','<>',$req->input('code'))
            ->Where('role_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    public function editrole(Request $req)
    {
        // dd($req->all());
        // Mengumpulkan akses menu
        $access = '';
        $menuCodes = $req->except(['_token', 'e_rolecode', 'e_roledesc', 'e_acc']); // asumsi t_acc adalah input lain

        foreach ($menuCodes as $menuCode => $value) {
            if ($value) { // Memeriksa apakah checkbox dicentang
                $access .= $value;
            }
        }        

        DB::table("roles")
            ->where('role_code', '=', $req->e_rolecode)
            ->update([
                'role_desc' => $req->e_roledesc,
                'role_access' => $req->e_acc,
                'menu_access' => $access,
                'updated_at' => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                'edited_by' => Session::get('username'),
            ]);

        // session()->flash('updated', 'Role berhasil diupdate');
        toast('Role Updated.', 'success');
        return back();
    }

    public function deleterole(Request $req)
    {
        $cek = DB::table('users')
            ->where('role_user','=',$req->tmp_rolecode)
            ->get();

        if ($cek->count() == 0) {
            DB::table("roles")
            ->where('role_code', '=', $req->tmp_rolecode)
            ->delete();

            toast('Role Deleted.', 'success');
            return back();
        } else {
            toast('Failed to Delete !!!', 'error');
            return back();
        }
    }

    public function menugetrole(Request $req)
    {        
        if ($req->ajax()) {
            $data = DB::table('roles')
                ->where('role_code', '=', $req->search)
                ->first();
            return response($data->menu_access);
        }
    }

    public function rolepaging(Request $req)
    {

        if ($req->ajax()) {

            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $rolecode = $req->get('rolecode');
            $roledesc = $req->get('roledesc');


            if ($rolecode == '' and $roledesc == '') {
                $data = DB::table('roles')
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-rolemaster', ['data' => $data]);
            } else {
                $kondisi = "role_code != ''";

                if ($rolecode != '') {
                    $kondisi .= " and role_code like '%" . $rolecode . "%'";
                    // dd($kondisi);
                }
                if ($roledesc != '') {
                    $kondisi .= " and role_desc like '%" . $roledesc . "%'";
                }


                $data = DB::table('roles')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                //dd($data);
                return view('setting.table-rolemaster', ['data' => $data]);
            }
        }
    }

    //supplier maint
    public function suppmenu()
    {
        if (strpos(Session::get('menu_access'), 'MTE06') !== false) {
            
            $data = DB::table('supp_mstrs')
                ->paginate(10);

            return view('setting.suppmaint', ['datas' => $data]);
        } else {
            Session()->flash('error', 'You do not have menu access, please contact admin.');
            return back();
        }
    }

    public function supppaging(Request $req)
    {

        if ($req->ajax()) {

            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $suppcode = $req->get('suppcode');
            $suppdesc = $req->get('suppdesc');



            if ($suppcode == '' and $suppdesc == '') {
                // dd('aaaa');
                $data = DB::table('supp_mstrs')
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-suppmaint', ['datas' => $data]);
            } else {
                $kondisi = "supp_code != ''";

                if ($suppcode != '') {
                    $kondisi .= ' and supp_code = "' . $suppcode . '"';
                    // dd($kondisi);
                }
                if ($suppdesc != '') {
                    $kondisi .= ' and supp_desc = "' . $suppdesc . '"';
                }

                $data = DB::table('supp_mstrs')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                //dd($data);
                return view('setting.table-suppmaint', ['datas' => $data]);
            }
        }
    }

    //untuk menampilkan menu site master
    public function sitemaster(Request $req)
    {      
        $scode = $req->s_code;
        $sdesc = $req->s_desc;

        // $currentUrl = $req->getRequestUri(); ini dirubah karena tidak dapat digunakan untuk fungsi Search di Form
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();
        
        $data = DB::table('site_mstrs');

        if($scode) {
            $data = $data->where('site_code','like','%'.$scode.'%');
        }
        if($sdesc) {
            $data = $data->where('site_desc','like','%'.$sdesc.'%');
        }

        $data = $data->paginate(10);

        return view('setting.site-mstr', compact('data','isFav','menuMaster','scode','sdesc'));

    }

    //cek site sebelum input
    public function ceksite(Request $req)
    {
        $cek = DB::table('site_mstrs')
            ->where('site_code','=',$req->input('code'))
            ->orWhere('site_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create site
    public function createsite(Request $req)
    {

        $this->validate($req, [
            'site_code' => 'unique:site_mstrs',
            'site_desc' => 'unique:site_mstrs'
        ], [
            'site_code.unique' => 'Site Code is Already Registerd!!',
            'site_desc.unique' => 'Site Description is Already Registerd!!'
        ]);

        DB::table('site_mstrs')
            ->insert([
                'site_code'     => $req->site_code,
                'site_desc'     => $req->site_desc,
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

        toast('Site Created.', 'success');
        return back();
    }

    //untuk edit site
    public function editsite(Request $req)
    {
        //Fungsi edit site hanya untuk mengganti flag yang berarti site tersebut aktif atau tidak
        
        // dd($req->all());
        /* $cekData = DB::table('site_mstrs')
            ->where('site_code','<>',$req->te_sitecode)
            ->Where('site_desc','=',$req->te_sitedesc)
            ->get();

        if ($cekData->count() == 0) { */
            if($req->te_cek) {
                $flag = "yes";
            } else {
                $flag = null;
            }

            DB::table('site_mstrs')
            ->where('site_code', '=', $req->te_sitecode)
            ->update([
                // 'site_desc'     => $req->te_sitedesc,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Site Updated.', 'success');
            return back();
        /* } else {
            toast('Site Description is Already Registerd!!', 'error');
            return back();
        } */
    }

    //untuk delete site
    public function deletesite(Request $req)
    {
        // cek dari tabel location
        $cekData = DB::table('loc_mstr')
                ->where('loc_site','=',$req->d_sitecode)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('site_mstrs')
            ->where('site_code', '=', $req->d_sitecode)
            ->delete();

            toast('Site Successfully Deleted', 'success');
            return back();
        } else {
            toast('Site Can Not Deleted!!', 'error');
            return back();
        }
    }

    //untuk paginate site
    public function sitepagination(Request $req)
    {

        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            if ($code == '' && $desc == '') {
                $data = DB::table('site_mstrs')
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-site', ['data' => $data]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "site_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and site_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "site_desc like '%" . $desc . "%'";
                    }
                }
                
                //dd($kondisi);
                $data = DB::table('site_mstrs')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-site', ['data' => $data]);
            }
        }
    }

    //untuk load site dari QAD dengan WSA
    public function loadsite(){
        $domain = ModelsQxwsa::first();

        $sitedata = (new WSAServices())->wsagetsite($domain->wsas_domain);

        if ($sitedata === false) {
            toast('WSA Failed', 'error')->persistent('Dismiss');
            return redirect()->back();
        } else {
            //update active semua location menjadi No, agar dapat tau data site yang update di QAD
            DB::table('site_mstrs')
                ->update([
                    'site_flag' => 'No',
                ]);

            // delete site yang tersimpan kecuali yang sudah digunakan dalam transaksi
            $ceksite = DB::table('site_mstrs')
                ->whereNotIn('site_code',function($query) {
                    $query->select('ir_site')->from('inv_required')->groupBy('ir_site');
                })
                ->delete();

            if ($sitedata[1] == "false") {
                toast('Data Site tidak ditemukan', 'error')->persistent('Dismiss');
                return redirect()->back();
            } else {
                foreach ($sitedata[0] as $datas) {
                    $sites = SiteMstr::firstOrNew(['site_code'=>$datas->t_site,
                                                    'site_desc'=> $datas->t_site_desc]);
                        $sites->site_code = $datas->t_site;
                        $sites->site_desc = $datas->t_site_desc;
                        $sites->site_flag = 'Yes';
                        $sites->created_at = Carbon::now()->toDateTimeString();
                        $sites->updated_at = Carbon::now()->toDateTimeString();
                        $sites->edited_by = Session::get('username');
                        $sites->save();
                }
            }
        }

        toast('Site Loaded.', 'success');
        return back();
    }

    // Approval Maint
    public function approvalmenu(Request $req)
    {
        if (strpos(Session::get('menu_access'), 'MT11') !== false) {
            $data = DB::table('site_mstrs')
                ->leftjoin('approvals', 'approvals.site_app', 'site_mstrs.site_code')
                ->groupBy('site_code')
                ->get();

            // dd($data);

            $user = DB::table('users')
                ->orderBy('name', 'ASC')
                ->get();

            //dd($data);

            return view('setting.approvalmaint', ['data' => $data, 'user' => $user]);
        } else {
            Session()->flash('error', 'You do not have menu access, please contact admin.');
            return back();
        }
    }

    public function createapproval(Request $req)
    {
        // dd($req->all());

        $site = $req->site;
        $site = substr($site, 0, strpos($site, ' '));


        if (is_null($req->userid) ) {
            
        } else {
            $listapprover = '';
            $flg = 0;
            $order = '';
            // hitung ada brp data
            foreach ($req->userid as $data) {
                $flg += 1;
            }
            // loop & kasi error klo ada duplikat
            for ($x = 0; $x < $flg; $x++) {

                if (strpos($listapprover, $req->userid[$x]) !== false) {
                    // Approver sama kirim error
                    session()->flash("error", "Approver tidak boleh sama");
                    return back();
                }
                if ($order == $req->order[$x]) {
                    return redirect()->back()->with('error', 'Nomor order tidak boleh sama');
                }

                $order .= $req->order[$x];

                $listapprover .= $req->userid[$x];
            }
        }

        DB::table('approvals')
                ->where('site_app', '=', $site)
                ->delete();


        if(is_null($req->userid)){
            session()->flash('updated', 'Approval Berhasil Didelete untuk Site : ' . $site);
            return back();
        }else{
            if (count($req->userid) > 0) {
                foreach ($req->userid as $item => $v) {

                    $data2 = array(
                        'userid' => $req->userid[$item],
                        'site_app' => $site,
                        'order' => $req->order[$item],
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    );
                    DB::table('approvals')->insert($data2);
                }
            }
        }

        session()->flash('updated', 'Approval Berhasil Diupdate untuk Site : ' . $site);
        return back();
    }

    public function approvalsearch(Request $req)
    {
        // dd($req->search)
        if ($req->ajax()) {
            $data = DB::table('approvals')
                ->where('site_app', '=', $req->search)
                ->get();

            $datauser = DB::table('users')
                ->orderBy('name', 'ASC')
                ->get();

            if (!is_null($data)) {
                $output = "";

                foreach ($data as $data) {
                    $output .= "<tr>" .

                        "<td data-label='Approver'>
                        <select id='userid[]' class='form-control userid' name='userid[]' required autofocus>";
                    foreach ($datauser as $newuser) :
                        if ($data->userid == $newuser->username) :
                            $output .= '<option value=' . $newuser->username . ' Selected >' . $newuser->username . ' -- ' . $newuser->name . '</option>';
                        else :
                            $output .= '<option value=' . $newuser->username . ' >' . $newuser->username . ' -- ' . $newuser->name . '</option>';
                        endif;
                    endforeach;
                    $output .= "</select>
                    </td>" .

                        "<td data-label='Order'> 
                        <input type='number' class='form-control order' min='1' step='1' Autocomplete='Off' id='order[]' name='order[]' style='height:38px' value='" . $data->order . "' required/>
                    </td>" .

                        "<td data-title='Action'><input type='button' class='ibtnDel btn btn-danger'  value='Delete'></td>" .

                        "<tr>";
                }
                return response($output);
            }
        }
    }

    //30112020
    public function searchingapp(Request $req){
        if ($req->ajax()) {
            $sitecode = $req->get('sitecode');
            $sitedesc = $req->get('sitedesc');

            if ($sitecode == '' and $sitedesc == '') {
                // dd('aaaa');
                $data = DB::table('site_mstrs')
                    ->leftjoin('approvals', 'approvals.site_app', 'site_mstrs.site_code')
                    ->groupBy('site_code')
                    ->get();

                $user = DB::table('users')
                    ->orderBy('name', 'ASC')
                    ->get();

                    return view('setting.table-menuapproval', ['data' => $data, 'user' => $user]);
            } else {
                $kondisi = "site_code != ''";

                if ($sitecode != '') {
                    $kondisi .= ' and site_code = "' . $sitecode . '"';
                    // dd($kondisi);
                }
                if ($sitedesc != '') {
                    $kondisi .= ' and site_desc = "' . $sitedesc . '"';
                }

                $data = DB::table('site_mstrs')
                        ->leftjoin('approvals', 'approvals.site_app', 'site_mstrs.site_code')
                        ->whereRaw($kondisi)
                        ->groupBy('site_code')
                        ->get();

                $user = DB::table('users')
                        ->orderBy('name', 'ASC')
                        ->get();

                //dd($data);
                return view('setting.table-menuapproval', ['data' => $data, 'user' => $user]);
            }
        }
    }

    public function indchangepass(Request $req)
    {
        $value = $req->session()->get('username');
        $value1 = $req->session()->get('userid');

        $users = DB::table("users")
                    ->where("users.id",$value1)
                    ->first();

        return view('/auth/changepw', compact('users'));
    }

    public function changepass(Request $request)
    {
        $id = $request->input('id');
        $password = $request->input('password');
        $confpass = $request->input('confpass');
        $oldpass = $request->input('oldpass');

        $hasher = app('hash');

        $users = DB::table("users")
                    ->select('id','password','username')
                    ->where("users.id",$id)
                    ->first();

        if($hasher->check($oldpass,$users->password))
        {
            if($password != $confpass)
            {
                session()->flash("error","Password & Confirm Password Berbeda");
                return back();
            }else{
                DB::table('users')
                ->where('id', $id)
                ->update(['password' => Hash::make($password)]);

                /** Menyimpan data di tabel history perubahan data user */
                DB::table('user_hist')
                ->insert([
                    'ush_username'      => $users->username,
                    'ush_password'      => Hash::make($password),
                    'ush_action'        => 'Password Updated',
                    'ush_editby'        => Session::get('username'),
                    'created_at'        => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                    'updated_at'        => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                ]);

                toast('Password successfully updated', 'success');
                return back();
            }
        }else{
                toast('Old password is wrong', 'error');
                return back();    
        }  
    }
/* Area Master */
    //untuk menampilkan menu area master
    public function areamaster(Request $req)
    {   
        $scode = $req->s_code;
        $sdesc = $req->s_desc;
        $sscode = $req->ss_code;
        $ssdesc = $req->ss_desc;

        // $currentUrl = $req->getRequestUri(); ini dirubah karena tidak dapat digunakan untuk fungsi Search di Form
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        $dataSite = DB::table('site_mstrs')
            ->get();

        $data = DB::table('loc_mstr')
            ->join('site_mstrs','site_code','=','loc_site')
            ->orderby('loc_site')
            ->orderby('loc_code');

        if($scode) {
            $data = $data->where('site_code','like','%'.$scode.'%');
        }
        if($sdesc) {
            $data = $data->where('site_desc','like','%'.$sdesc.'%');
        }
        if($sscode) {
            $data = $data->where('loc_code','like','%'.$sscode.'%');
        }
        if($ssdesc) {
            $data = $data->where('loc_desc','like','%'.$ssdesc.'%');
        }

        $data = $data->paginate(10);

        return view('setting.area-mstr', compact('data','dataSite','isFav','menuMaster',
            'scode','sdesc','sscode','ssdesc'));
    }

    //cek area sebelum input
    public function cekarea(Request $req)
    {
        dd('ini belum bisa');
        $code = $req->input('code');
        $desc = $req->input('desc');

        $cek = DB::table('loc_mstr')
            ->where('loc_site','=',$req->input('site'))
            ->Where(function($query) {
                $query->where('loc_code','=',$code)
                      ->orWhere('loc_desc','=',$desc);
            })
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create area
    public function createarea(Request $req)
    {
        $cekData = DB::table('loc_mstr')
                ->where('loc_site','=',$req->t_site)
                ->where('loc_code','=',$req->t_locationid)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('loc_mstr')
            ->insert([
                'loc_site'      => $req->t_site,
                'loc_code'      => $req->t_locationid,
                'loc_desc'      => $req->t_locationdesc,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Location Successfully.', 'success');
            return back();
        } else {
            toast('Location is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit area
    public function editarea(Request $req)
    {
        $cekData = DB::table('loc_mstr')
                ->where('loc_site','=',$req->te_site)
                ->where('loc_desc','=',$req->te_locationdesc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('loc_mstr')
            ->where('loc_site','=',$req->te_site)
            ->where('loc_code','=',$req->te_locationid)
            ->update([
                'loc_desc'      => $req->te_locationdesc,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Location Updated.', 'success');
            return back();
        } else {
            toast('Location Description is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk delete area
    public function deletearea(Request $req)
    {
        //cek data di asset
        $cekData = DB::table('asset_mstr')
                ->where('asset_loc', '=', $req->d_locationid)
                ->where('asset_site', '=', $req->d_site)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('loc_mstr')
            ->where('loc_code', '=', $req->d_locationid)
            ->where('loc_site', '=', $req->d_site)
            ->delete();

            toast('Deleted Location Successfully.', 'success');
            return back();
        } else {
            toast('Location Can Not Deleted!!', 'error');
            return back();
        }
    }

    //untuk paginate area
    public function areapagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by    = $req->get('sortby');
            $sort_type  = $req->get('sorttype');
            $code       = $req->get('code');
            $desc       = $req->get('desc');
            $scode      = $req->get('scode');
            $sdesc      = $req->get('sdesc');

            if ($code == '' && $desc == '' && $scode == '' && $sdesc == '') {
                $data = DB::table('loc_mstr')
                    ->join('site_mstrs','site_code','=','loc_site')
                    ->select('site_code','site_desc','loc_site','loc_code','loc_desc','loc_active')
                    ->orderby('loc_site')
                    ->orderby('loc_code');
                   
                $data = $data->paginate(10);

                return view('setting.table-area', ['data' => $data]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "site_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and site_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "site_desc like '%" . $desc . "%'";
                    }
                }
                if ($scode != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and loc_code like '%" . $scode . "%'";
                    } else { 
                        $kondisi = "loc_code like '%" . $scode . "%'";
                    }
                }
                if ($sdesc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and loc_desc like '%" . $sdesc . "%'";
                    } else {
                        $kondisi = "loc_desc like '%" . $sdesc . "%'";
                    }
                }
                $data = DB::table('loc_mstr')
                    ->join('site_mstrs','site_code','=','loc_site')
                    ->whereRaw($kondisi)
                    ->orderby('loc_site')
                    ->orderby('loc_code')
                    ->paginate(10);

                return view('setting.table-area', ['data' => $data]);
            }
        }
    }

    //untuk load location dari QAD dengan WSA
    public function loadloc(){

        $domain = ModelsQxwsa::first();

        $locdata = (new WSAServices())->wsagetloc($domain->wsas_domain, "");

        if ($locdata === false) {
            toast('WSA Failed', 'error')->persistent('Dismiss');
            return redirect()->back();
        } else {

            //update status active menjadi no terlebih dahulu, agar mengetahui lokasi yang paling update dari qad
            DB::table("loc_mstr")
            ->update([
                'loc_active' => 'No'
            ]);
            
            //hapus data lokasi kecuali lokasi yang sudah ada transaksi -> request transfer, wo transfer, wo finish
            DB::table('loc_mstr')
                ->delete();

            if ($locdata[1] == "false") {
                toast('Data Location tidak ditemukan', 'error')->persistent('Dismiss');
                return redirect()->back();
            } else {

                //cek data site di web
                $datasite = DB::table('site_mstrs')
                    ->whereSite_flag('Yes')
                    ->orderBy('site_code')
                    ->get();
               
                foreach ($locdata[0] as $datas) {
                    //cek apakah data site sudah ada di tabel web
                    $ceksite = $datasite->where('site_code','=',$datas->t_site)->count();
                    if($ceksite > 0) {
                        // jika kode site dan kode lokasi kosong maka tidak disimpan ke table
                        if($datas->t_site != "" && $datas->t_loc != "") {
                            $rsloc = LocMstr::firstOrNew(['loc_site'=>$datas->t_site,
                                    'loc_code'=>$datas->t_loc,
                                    'loc_desc'=> $datas->t_loc_desc]);

                            $rsloc->loc_site = $datas->t_site;
                            $rsloc->loc_code = $datas->t_loc;
                            $rsloc->loc_desc = $datas->t_loc_desc;
                            $rsloc->loc_active = "Yes";
                            $rsloc->created_at = Carbon::now()->toDateTimeString();
                            $rsloc->updated_at = Carbon::now()->toDateTimeString();
                            $rsloc->edited_by = Session::get('username');
                            $rsloc->save();
                        }
                    }                    
                }
            }
        }

        toast('Location Loaded.', 'success');
        return back();
    }
/* End Area Master */

/* Asset Type Master */
    //untuk menampilkan menu asset type
    public function assettypemaster(Request $req)
    {
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();
            $data = DB::table('asset_type')
                ->orderby('astype_code')
                ->paginate(10);

            $datasearch = DB::table('asset_type')
                ->orderby('astype_code')
                ->get();

            return view('setting.asset-type', ['data' => $data, 'datasearch' => $datasearch, 'isFav' => $isFav, 'menuMaster' => $menuMaster]);

    }

    //cek asset type sebelum input
    public function cekassettype(Request $req)
    {
        $cek = DB::table('asset_type')
            ->where('astype_code','=',$req->input('code'))
            ->orWhere('astype_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    // cari UM dari spare part
    public function viewum(Request $req)
    {
        if ($req->ajax()) {

            $output = DB::table('sp_mstr')->where('spm_code','=',$req->input('code'))->value('spm_um');

            return response($output);
        }
    }

    //untuk create asset type
    public function createassettype(Request $req)
    {
        $cekData = DB::table('asset_type')
                ->where('astype_code','=',$req->t_code)
                ->orWhere('astype_desc','=',$req->t_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('asset_type')
            ->insert([
                'astype_code'   => $req->t_code,
                'astype_desc'   => $req->t_desc,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Asset Type Created.', 'success');
            return back();
        } else {
            toast('Asset Type is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit asset type
    public function editassettype(Request $req)
    {
        $cekData = DB::table('asset_type')
                ->where('astype_desc','=',$req->te_desc)
                ->where('astype_code','<>',$req->te_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('asset_type')
            ->where('astype_code','=',$req->te_code)
            ->update([
                'astype_desc'   => $req->te_desc,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Asset Type Updated.', 'success');
            return back();
        } else {
            toast('Asset Type Description is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk delete asset type
    public function deleteassettype(Request $req)
    {
        //cek data dari asset
        $cekData = DB::table('asset_mstr')
                ->where('asset_type','=',$req->d_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('asset_type')
            ->where('astype_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Asset Type Successfully.', 'success');
            return back();
        } else {
            toast('Asset Type Can Not Deleted!!!', 'error');
            return back();
        }
    }

    //untuk paginate asset type
    public function assettypepagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $datasearch = DB::table('asset_type')
                ->orderby('astype_code')
                ->get();

            if ($code == '' && $desc == '') {
                $data = DB::table('asset_type')
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-asset-type', ['data' => $data, 'datasearch' => $datasearch]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "astype_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and astype_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "astype_desc like '%" . $desc . "%'";
                    }
                }

                $data = DB::table('asset_type')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-asset-type', ['data' => $data, 'datasearch' => $datasearch]);
            }
        }
    }
/* End Asset Type Master */

/* Asset Group Master */
    //untuk menampilkan menu asset group
    public function assetgroupmaster(Request $req)
    {
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        $data = DB::table('asset_group')
            ->orderby('asgroup_code')
            ->paginate(10);

        $datasearch = DB::table('asset_group')
            ->orderby('asgroup_code')
            ->get();

        return view('setting.asset-group', ['data' => $data, 'datasearch' => $datasearch, 'isFav' => $isFav, 'menuMaster' => $menuMaster]);

    }

    //cek asset group sebelum input
    public function cekassetgroup(Request $req)
    {
        $cek = DB::table('asset_group')
            ->where('asgroup_code','=',$req->input('code'))
            ->orWhere('asgroup_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create asset group
    public function createassetgroup(Request $req)
    {
        $cekData = DB::table('asset_group')
                ->where('asgroup_code','=',$req->t_code)
                ->orWhere('asgroup_desc','=',$req->t_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('asset_group')
            ->insert([
                'asgroup_code'  => $req->t_code,
                'asgroup_desc'  => $req->t_desc,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Asset Group Created.', 'success');
            return back();
        } else {
            toast('Asset Group is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit asset group
    public function editassetgroup(Request $req)
    {
        $cekData = DB::table('asset_group')
                ->where('asgroup_desc','=',$req->te_desc)
                ->where('asgroup_code','<>',$req->te_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('asset_group')
            ->where('asgroup_code','=',$req->te_code)
            ->update([
                'asgroup_desc'  => $req->te_desc,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Asset Group Updated.', 'success');
            return back();
        } else {
            toast('Asset Group Description is Already Registerd!!', 'error');
            return back();
        }

        
    }

    //untuk delete asset group
    public function deleteassetgroup(Request $req)
    {
        //cek dari tabel asset
        $cekData = DB::table('asset_mstr')
                ->where('asset_group','=',$req->d_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('asset_group')
            ->where('asgroup_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Asset Group Successfully.', 'success');
            return back();
        } else {
            toast('Asset Group Can Not Deleted!!!', 'error');
            return back();
        }
    }

    //untuk paginate asset group
    public function assetgrouppagination(Request $req)
    {

        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $datasearch = DB::table('asset_group')
                ->orderby('asgroup_code')
                ->get();
      
            if ($code == '' && $desc == '') {
                $data = DB::table('asset_group')
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-asset-group', ['data' => $data, 'datasearch' => $datasearch]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "asgroup_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and asgroup_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "asgroup_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('asset_group')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-asset-group', ['data' => $data, 'datasearch' => $datasearch]);
            }
        }
    }
/* End Asset Group Master */

/* Failure Master */
    //untuk menampilkan menu failure
    public function fnmaster(Request $req)
    {   
        $scode = $req->s_code;
        $sdesc = $req->s_desc;

        // $currentUrl = $req->getRequestUri(); ini dirubah karena tidak dapat digunakan untuk fungsi Search di Form
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();
    
        $data = DB::table('fn_mstr')
            ->orderby('fn_code');
        
        if($scode) {
            $data = $data->where('fn_code','like','%'.$scode.'%');
        }
        if($sdesc) {
            $data = $data->where('fn_desc','like','%'.$sdesc.'%');
        }

        $data = $data->paginate(10);

        $dataasgroup = DB::table('asset_group')
            ->orderby('asgroup_code')
            ->get();

        $datasearch = DB::table('fn_mstr')
            ->orderby('fn_code')
            ->get();

        return view('setting.failure', compact('data','dataasgroup','datasearch','isFav', 'menuMaster',
            'scode','sdesc'));
    }

    //cek asset type sebelum input
    public function cekfn(Request $req)
    {
        $cek = DB::table('fn_mstr')
            ->where('fn_code','=',$req->input('code'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create failure
    public function createfn(Request $req)
    {
        $cekData = DB::table('fn_mstr')
                ->where('fn_code','=',$req->t_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('fn_mstr')
            ->insert([
                'fn_code'       => $req->t_code,
                'fn_desc'       => $req->t_desc,             
                'fn_impact'     => $req->t_imp,              
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Fauilure Master Created.', 'success');
            return back();
        } else {
            toast('Fauilure Master is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit failure
    public function editfn(Request $req)
    {
        DB::table('fn_mstr')
        ->where('fn_code','=',$req->te_code)
        ->update([
            'fn_desc'       => $req->te_desc,       
            'fn_impact'     => $req->te_imp,         
            'updated_at'    => Carbon::now()->toDateTimeString(),
            'edited_by'     => Session::get('username'),
        ]);

        toast('Fauilure Master Updated.', 'success');
        return back();
    }

    //untuk delete failure
    public function deletefn(Request $req)
    {
        DB::table('fn_mstr')
        ->where('fn_code', '=', $req->d_code)
        ->delete();

        toast('Deleted Failure Master Successfully.', 'success');
        return back();
    }

    //untuk paginate failure
    public function fnpagination(Request $req) {
        if ($req->ajax()) {
            // dd($req->all());
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');
            $imp = $req->get('imp');

            $dataasgroup = DB::table('asset_group')
                ->orderby('asgroup_code')
                ->get();
      
            if ($code == '' && $desc == '' && $imp == '') {
                $data = DB::table('fn_mstr')
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-failure', ['data' => $data, 'dataasgroup' => $dataasgroup]);
            } else {
                $kondisi = 'id > 0';

                if ($code != '') {
                    $kondisi .= " and fn_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    $kondisi .= " and fn_desc like '%" . $desc . "%'";
                }
                if ($imp != '') {
                    $kondisi .= " and fn_impact like '%" . $imp . "%'";
                }
                // dd($kondisi);
                $data = DB::table('fn_mstr')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);
                    // ->get();

                // dd($data);

                return view('setting.table-failure', ['data' => $data, 'dataasgroup' => $dataasgroup]);
            }
        }
    }
/* End Failure Master */

/* Supplier Master */
    //untuk menampilkan menu supplier master
    public function suppmaster(Request $req)
    {
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        $data = DB::table('supp_mstr')
            ->orderby('supp_code')
            ->paginate(10);

        return view('setting.supplier', ['data' => $data, 'isFav' => $isFav, 'menuMaster' => $menuMaster]);

    }

    public function loadsupp(){
        $domain = ModelsQxwsa::first();

        $suppdata = (new WSAServices())->wsasupp($domain->wsas_domain);

        if ($suppdata === false) {
            toast('WSA Failed', 'error')->persistent('Dismiss');
            return redirect()->back();
        } else {

            if ($suppdata[1] == "false") {
                toast('Data Supplier tidak ditemukan', 'error')->persistent('Dismiss');
                return redirect()->back();
            } else {
                
                foreach ($suppdata[0] as $datas) {
                    $supp = SuppMstr::firstOrNew(['supp_code'=>$datas->t_suppcode,
                                                    'supp_desc'=> $datas->t_suppname]);
                        $supp->supp_code = $datas->t_suppcode;
                        $supp->supp_desc = $datas->t_suppname;
                        $supp->created_at = Carbon::now()->toDateTimeString();
                        $supp->updated_at = Carbon::now()->toDateTimeString();
                        $supp->save();
                }
            }
        }

        toast('Supplier Loaded.', 'success');
        return back();
    }

    //cek supplier sebelum input
    public function ceksupp(Request $req)
    {
        $cek = DB::table('supp_mstr')
            ->where('supp_code','=',$req->input('code'))
            ->orWhere('supp_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create supplier master
    public function createsupp(Request $req)
    {
        $cekData = DB::table('supp_mstr')
                ->where('supp_code','=',$req->t_code)
                ->orWhere('supp_desc','=',$req->t_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('supp_mstr')
            ->insert([
                'supp_code'     => $req->t_code,
                'supp_desc'     => $req->t_desc,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Supplier Created.', 'success');
            return back();
        } else {
            toast('Supplier is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit supplier master
    public function editsupp(Request $req)
    {
        $cekData = DB::table('supp_mstr')
                ->where('supp_code','<>',$req->te_code)
                ->where('supp_desc','=',$req->te_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('supp_mstr')
            ->where('supp_code','=',$req->te_code)
            ->update([
                'supp_desc'     => $req->te_desc,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Supplier Updated.', 'success');
            return back();
        } else {
            toast('Supplier Description is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk delete supplier master
    public function deletesupp(Request $req)
    {
        $cek = DB::table('sp_mstr')
            ->where('spm_supp','=',$req->d_code)
            ->get();

        $cek2 = DB::table('asset_mstr')
            ->where('asset_supp','=',$req->d_code)
            ->get();

        if ($cek->count() == 0 and $cek2->count() == 0) {
            DB::table('supp_mstr')
            ->where('supp_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Supplier Successfully.', 'success');
            return back();
        } else {
            toast('Supplier Can Not Deleted!!!', 'error');
            return back();
        }
    }

    //untuk paginate supplier master
    public function supppagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');
      
            if ($code == '' && $desc == '') {
                $data = DB::table('supp_mstr')
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-supplier', ['data' => $data]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "supp_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and supp_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "supp_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('supp_mstr')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-supplier', ['data' => $data]);
            }
        }
    }
/* End Supplier Master */

/* Asset Master */
    //untuk menampilkan menu asset master
    public function assetmaster(Request $req) /** blade setting.asset */
    {
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        $s_code = $req->s_code;
        $s_loc = $req->s_loc;
        $s_type = $req->s_type;
        $s_group = $req->s_group;
        $s_note = $req->s_note;
        $s_spec = $req->s_spec;

            $data = DB::table('asset_mstr')
                ->selectRaw('asset_type.*, asset_group.*, asset_loc.*, asset_mstr.id as asset_id, asset_code, asset_desc, 
                asset_site, asset_loc, asset_um, asset_sn, 
                asset_supp, asset_prcdate, asset_prcprice, asset_type, asset_group, asset_accounting, asset_note, asset_spec,
                asset_active, asset_image, 
                asset_imagepath, asset_upload, asset_editedby, asset_renew, asloc_desc, astype_desc, asgroup_desc')
                ->leftjoin('asset_type','asset_mstr.asset_type','asset_type.astype_code')
                ->leftjoin('asset_group','asset_mstr.asset_group','asset_group.asgroup_code')
                ->leftJoin('asset_loc','asloc_code','=','asset_loc')
                ->orderByRaw("REGEXP_REPLACE(asset_code, '[0-9]', '') ASC")
                ->orderByRaw("CAST(REGEXP_REPLACE(asset_code, '\\D', '') AS UNSIGNED) ASC");
    
            if($s_code) {
                $data = $data->where(function($query) use ($s_code) {
                    $query->where('asset_code','like','%'.$s_code.'%')
                    ->orwhere('asset_desc','like','%'.$s_code.'%');
                });
            }
            if($s_loc) {
                $data = $data->where(function($query) use ($s_loc) {
                    $query->where('asloc_code','like','%'.$s_loc.'%')
                    ->orwhere('asloc_desc','like','%'.$s_loc.'%');
                });
            }
            if($s_type) {
                $data = $data->where(function($query) use ($s_type) {
                    $query->where('astype_code','like','%'.$s_type.'%')
                    ->orwhere('astype_desc','like','%'.$s_type.'%');
                });
            }
            if($s_group) {
                $data = $data->where(function($query) use ($s_group) {
                    $query->where('asgroup_code','like','%'.$s_group.'%')
                    ->orwhere('asgroup_desc','like','%'.$s_group.'%');
                });
            }
            if($s_note) {
                $data = $data->where('asset_note','like','%'.$s_note.'%');
            }
            if($s_spec) {
                $data = $data->where('asset_spec','like','%'.$s_spec.'%');
            }
            
            $data = $data->paginate(10);

            $datasite = DB::table('asset_site')
                ->orderby('assite_code')
                ->get();

            $dataloc = DB::table('asset_loc')
                ->orderby('asloc_site')
                ->orderby('asloc_code')
                ->get();

            $dataastype = DB::table('asset_type')
                ->orderby('astype_code')
                ->get();

            $dataasgroup = DB::table('asset_group')
                ->orderby('asgroup_code')
                ->get();

            $datasupp = DB::table('supp_mstr')
                ->orderby('supp_code')
                ->get();

            $datafn = DB::table('fn_mstr')
                ->orderby('fn_code')
                ->get();

            $repaircode = DB::table('rep_master')
                ->get();

            $repairgroup = DB::table('xxrepgroup_mstr')
                ->select('xxrepgroup_nbr','xxrepgroup_desc')
                ->distinct('xxrepgroup_nbr','xxrepgroup_desc')
                ->get();

            $datasearch = DB::table('asset_mstr')
                ->orderby('asset_code')
                ->get();

            $datameaum = DB::table('um_mstr')
                ->orderBy('um_code')
                ->get();

            /* Load data asset dari QAD */

            Schema::create('temp_asset', function ($table) {
                $table->increments('id');
                $table->string('temp_domain');
                $table->string('temp_entity');
                $table->string('temp_code')->nullable();
                $table->string('temp_desc')->nullable();
                $table->temporary();
            });

            /* ini ditutup dulu, nanti dibuka lagi */
            $domain = ModelsQxwsa::first();
            $datawsa = (new WSAServices())->wsaassetqad($domain->wsas_domain);

            if ($datawsa === false) {
                // toast('WSA Failed', 'error')->persistent('Dismiss');
                // return redirect()->back();
            } else {
                foreach ($datawsa[0] as $datas) {
                    DB::table('temp_asset')->insert([
                        'temp_code' => $datas->t_code,
                        'temp_desc' => $datas->t_desc,
                    ]);
                }
            } 

            $dataassetqad = DB::table('temp_asset')
                ->orderBy('temp_code')
                ->get();

            Schema::dropIfExists('temp_asset');

            return view('setting.asset', ['data' => $data, 'datasite' => $datasite, 'dataloc' => $dataloc, 
            'dataastype' => $dataastype, 'dataasgroup' => $dataasgroup, 'datasupp' => $datasupp, 'datafn' => $datafn, 
            'repaircode' => $repaircode, 'repairgroup' => $repairgroup, 'datasearch' => $datasearch, 
            'dataassetqad' => $dataassetqad, 'datameaum' => $datameaum, 
            's_code' =>$req->s_code, 's_loc' =>$req->s_loc, 's_type' =>$req->s_type, 's_group' =>$req->s_group, 
            's_note' => $req->s_note, 's_spec' => $req->s_spec,
            'isFav' => $isFav, 'menuMaster' => $menuMaster]);
    }

    //cek site sebelum input
    public function cekasset(Request $req)
    {
        $cek = DB::table('asset_mstr')
            ->where('asset_code','=',$req->input('code'))
            // ->Where('asset_loc','=',$req->input('loc'))
            // ->Where('asset_site','=',$req->input('site'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create asset master
    public function createasset(Request $req)
    {
        // dd($req->all());
        if ($req->t_mea == "C") {
            if ($req->t_cal == "") {
                toast('Calendar Cannot Be Empty!!', 'error');
                return back();
            }
        }

        if ($req->t_mea == "M") {
            if ($req->t_meter == "") {
                toast('Meter Cannot Be Empty!!', 'error');
                return back();
            }
        }
        
        $repair = "";
        if ($req->crepairtype == "group") {
            $repair = $req->crepairgroup;
        } elseif ($req->crepairtype == "code") {
            if(!is_null($req->crepaircode)) {
                $flg = 0;
                foreach ($req->crepaircode as $ds) {
                    if ($flg == 0) {
                        $repair =  $req->crepaircode[$flg];
                    } else {
                        $repair = $repair . "," . $req->crepaircode[$flg] ;
                    }
                    $flg += 1;
                }
            }
        } else {
            $repair = "";
        }

        $cekData = DB::table('asset_mstr')
                ->where('asset_code','=',$req->t_code)
                // ->Where('asset_loc','=',$req->t_loc)
                // ->Where('asset_site','=',$req->t_site)
                ->get();

        if ($cekData->count() == 0) {

            $imagename = "";
            $imagepath = "";
            if($req->hasFile('t_image')){
                $file = $req->file('t_image');
                $imagename  = $req->t_code . '-' .$file->getClientOriginalName();
                $imagepath = public_path('/uploadassetimage/');
                $file->move($imagepath,$imagename);
            }
            
            DB::table('asset_mstr')
            ->insert([
                'asset_code'        => $req->t_code,
                'asset_desc'        => $req->t_desc,    
                'asset_site'        => $req->t_site,
                'asset_loc'         => $req->t_loc,
                'asset_um'          => $req->t_um,
                'asset_sn'          => $req->t_sn,
                'asset_prcdate'    => $req->t_prc_date,
                'asset_prcprice'   => $req->t_prc_price,
                'asset_type'        => $req->t_type,
                'asset_group'       => $req->t_group,
                'asset_supp'        => $req->t_supp,
                'asset_note'        => $req->t_note,  
                'asset_spec'        => $req->t_spec,  
                'asset_active'      => $req->t_active,    
                'asset_renew'      => $req->t_renew,    
                'asset_image'       => $imagename,  
                'asset_imagepath'  => $imagepath,  
                'asset_accounting'         => $req->t_qad,   
                'created_at'        => Carbon::now()->toDateTimeString(),
                'updated_at'        => Carbon::now()->toDateTimeString(),
                'asset_editedby'         => Session::get('username'),
                // 'asset_upload'      => $savepath.$filename
            ]);

            // Menyimpan file upload
            if($req->hasFile('filename')){
                foreach($req->file('filename') as $upload){
                    $dataTime = date('Ymd_His');
                    $filename = $dataTime . '-' .$upload->getClientOriginalName();
                    
                    // Simpan File Upload pada Public
                    $savepath = public_path('uploadasset/');
                    $filepath = 'uploadasset/';
                    $upload->move($savepath, $filename);
                
                    // Simpan ke DB Upload
                    DB::table('asset_upload')
                            ->insert([
                                'filepath' => $filepath . $filename,
                                'asset_code' => $req->t_code,
                                'created_at' => Carbon::now()->toDateTimeString(),
                                'updated_at' => Carbon::now()->toDateTimeString(),
                            ]);
                            
                }

            }

            toast('Asset Created.', 'success');
            return back();
        } else {
            toast('Asset is Already Registerd!!', 'error');
            return back();
        }
    }

    public function downloadfile($id){
        // dd($id);
        $asset = DB::table('asset_upload')
                    ->where('id','=',$id)
                    ->first();

        if($asset){

            $lastindex = strrpos($asset->filepath, "/");
            $filename = substr($asset->filepath, $lastindex + 1);

            return Response::download($asset->filepath, $filename);
        }else{
            toast('There is no file', 'error');
            return back();
        }
    }


    public function listupload($id){
        // dd($id);
        $data = DB::table('asset_upload')
                        ->where('asset_code',$id)
                        ->get();
        // dd($data);
        $output = '';
        foreach($data as $data){

            $lastindex = strrpos($data->filepath, "/");
            $filename = substr($data->filepath, $lastindex + 1);


            $output .=  '<tr>
                            <td> 
                            <a href="' . $data->filepath . '" target="_blank">' . $filename . '</a>
                            </td>
                            <td>
                            <a href="#" class="btn deleterow btn-danger">
                            <i class="icon-table fa fa-trash fa-lg"></i>
                            </a>
                            <input type="hidden" value="'.$data->id.'" class="rowval"/>
                            </td>
                        </tr>';
        }

        return response($output);
    }

    public function deleteupload($id){
        $data1 = DB::table('asset_upload')
                    ->where('id',$id)
                    ->first();

        if($data1){
            $lastindex = strrpos($data1->filepath, "/");
            $filename = substr($data1->filepath, $lastindex + 1);

            $filename = public_path('/uploadasset/'.$filename);

            if(File::exists($filename)) {
                File::delete($filename);

                DB::table('asset_upload')
                            ->where('id',$id)
                            ->delete();
            }

        }

        $data = DB::table('asset_upload')
                        ->where('asset_code',$data1->asset_code)
                        ->get();

        $output = '';
        foreach($data as $data){

            $lastindex = strrpos($data->filepath, "/");
            $filename = substr($data->filepath, $lastindex + 1);


            $output .=  '<tr>
                            <td> 
                            <a href="downloadfile/'.$data->id.'" target="_blank">'.$filename.'</a> 
                            </td>
                            <td>
                            <a href="#" class="btn deleterow btn-danger">
                            <i class="icon-table fa fa-trash fa-lg"></i>
                            </a>
                            <input type="hidden" value="'.$data->id.'" class="rowval"/>
                            </td>
                        </tr>';
        }

        return response($output);
    }

    //untuk kode repair
    public function repaircode(Request $req)
    {
        if($req->ajax()){
            $dataRep = DB::table('rep_master')
                ->get();

            $array = json_decode(json_encode($dataRep), true);

            return response()->json($array);
        }
    }

    //untuk edit asset master
    public function editasset(Request $req)
    {
        // dd($req->all());
        /* Validasi inputan */
        if ($req->te_mea == "C") {
            if ($req->te_cal == "") {
                toast('Calendar Cannot Be Empty!!', 'error');
                return back();
            }
        }

        if ($req->t_mea == "M") {
            if ($req->te_meter == "") {
                toast('Meter Cannot Be Empty!!', 'error');
                return back();
            }
        }

        if($req->hasFile('te_image')){
            $file = $req->file('te_image');
            $imagename  = $req->te_code . '-' .$file->getClientOriginalName();
            $imagepath = public_path('/uploadassetimage/');
            $file->move($imagepath,$imagename);

            DB::table('asset_mstr')
            ->where('asset_code','=',$req->te_code)
            ->update([ 
                'asset_image'       => $imagename,  
                'asset_imagepath'  => $imagepath,   
            ]);
        }

        if (!is_null($req->file('filename'))) {
            foreach($req->file('filename') as $upload){
                $dataTime = date('Ymd_His');
                $filename = $dataTime . '-' .$upload->getClientOriginalName();
                
                // Simpan File Upload pada Public
                $savepath = public_path('uploadasset/');
                $filepath = 'uploadasset/';
                $upload->move($savepath, $filename);
            
                // Simpan ke DB Upload
                DB::table('asset_upload')
                        ->insert([
                            'filepath' => $filepath . $filename,
                            'asset_code' => $req->te_code,
                            'created_at' => Carbon::now()->toDateTimeString(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ]);

            }

            
        }

        DB::table('asset_mstr')
        ->where('asset_code','=',$req->te_code)
        ->update([
            'asset_desc'        => $req->te_desc,
            'asset_site'        => $req->te_site,
            'asset_loc'         => $req->te_loc,
            'asset_um'          => $req->te_um,
            'asset_sn'          => $req->te_sn,
            'asset_prcdate'    => $req->te_prc_date,
            'asset_prcprice'   => $req->te_prc_price,
            'asset_type'        => $req->te_type,
            'asset_group'       => $req->te_group,
            'asset_supp'        => $req->te_supp,
            'asset_note'        => $req->te_note,        
            'asset_spec'        => $req->te_spec,        
            'asset_active'      => $req->te_active, 
            'asset_renew'      => $req->te_renew, 
            'asset_accounting'         => $req->te_qad,  
            'updated_at'        => Carbon::now()->toDateTimeString(),
            'asset_editedby'         => Session::get('username'),
        ]); 

        toast('Asset Updated.', 'success');
        return back();
    }

    //untuk delete asset master
    public function deleteasset(Request $req)
    {
        $cekAspar1 = 0;
        $cekAspar1 = DB::table('asset_par')
                    ->where('aspar_par','=',$req->d_code)
                    ->count();

        $cekAspar2 = 0;
        $cekAspar2 = DB::table('asset_par')
                    ->where('aspar_child','=',$req->d_code)
                    ->count();

        $ceksr = 0;
        $ceksr = DB::table('service_req_mstr')
            ->whereSr_asset($req->d_code)
            ->count();

        $cekwo = 0;
        $cekwo = DB::table('wo_mstr')
            ->whereWoAssetCode($req->d_code)
            ->count();

        if ($cekAspar1 > 0 || $cekAspar2 > 0 || $cekwo > 0 || $ceksr > 0) {
            toast('Asset data can not be deleted, asset code has been used for the transaction!!!', 'error');
            return back();
        }

        DB::table('asset_mstr')
            ->where('asset_code', '=', $req->d_code)
            ->where('asset_site', '=', $req->d_site)
            ->where('asset_loc', '=', $req->d_loc)
            ->delete();

        DB::table('asset_upload')
            ->whereAssetCode($req->d_code)
            ->delete();

        toast('Deleted Asset Successfully.', 'success');
        return back();
    }

    //untuk paginate asset master
    public function assetpagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $type = $req->get('type');
            $group = $req->get('group');

            $posisi = strpos($req->get('loc'),".");
            $site = substr($req->get('loc'),0,$posisi);
            $loc = substr($req->get('loc'),$posisi+1);

            $datasite = DB::table('asset_site')
                ->orderby('assite_code')
                ->get();

            $dataloc = DB::table('asset_loc')
                ->orderby('asloc_code')
                ->get();

            $dataastype = DB::table('asset_type')
                ->orderby('astype_code')
                ->get();

            $dataasgroup = DB::table('asset_group')
                ->orderby('asgroup_code')
                ->get();

            $datasupp = DB::table('supp_mstr')
                ->orderby('supp_code')
                ->get();

            $datafn = DB::table('fn_mstr')
                ->orderby('fn_code')
                ->get();

             $repaircode = DB::table('rep_master')
                ->get();

            $repairgroup = DB::table('xxrepgroup_mstr')
                ->distinct('xxrepgroup_nbr')
                ->get();

            $datasearch = DB::table('asset_mstr')
                ->orderby('asset_code')
                ->get();
     
            if ($code == '' && $loc == '' && $type == '' && $group == '') {
                $data = DB::table('asset_mstr')
                        ->leftjoin('asset_type','asset_mstr.asset_type','asset_type.astype_code')
                        ->leftjoin('asset_group','asset_mstr.asset_group','asset_group.asgroup_code')
                        ->select('asset_mstr.asset_code','asset_mstr.asset_desc','astype_desc','asgroup_desc',
                            'asset_mstr.asset_site','asset_mstr.asset_loc','asset_mstr.asset_um','asset_mstr.asset_sn',
                            'asset_mstr.asset_type','asset_mstr.asset_group','asset_mstr.asset_failure',
                            'asset_mstr.asset_measure','asset_mstr.asset_supp','asset_mstr.asset_meter','asset_mstr.asset_cal',
                            'asset_mstr.asset_note','asset_mstr.asset_active','asset_mstr.asset_repair_type',
                            'asset_mstr.asset_repair','asset_mstr.asset_prc_date','asset_mstr.asset_daya',
                            'asset_mstr.asset_prc_price','asset_mstr.asset_start_mea',
                            'asset_mstr.asset_upload','asset_tolerance','asset_last_usage','asset_last_usage_mtc',
                            'asset_last_mtc','asset_on_use','asset_image','asset_qad','asset_mea_um')
                        ->orderby('asset_code')
                        ->paginate(10);

                return view('setting.table-asset', ['data' => $data, 'datasite' => $datasite, 'dataloc' => $dataloc, 'dataastype' => $dataastype, 'dataasgroup' => $dataasgroup, 'datasupp' => $datasupp, 'datafn' => $datafn, 'repaircode' => $repaircode, 'repairgroup' => $repairgroup, 'datasearch' => $datasearch]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "asset_code like '%" . $code . "%'";
                }
                if ($site != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and asset_site like '%" . $site . "%'";
                    } else {
                        $kondisi = "asset_site like '%" . $site . "%'";
                    }
                }
                if ($loc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and asset_loc like '%" . $loc . "%'";
                    } else {
                        $kondisi = "asset_loc like '%" . $loc . "%'";
                    }
                }
                if ($type != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and asset_type like '%" . $type . "%'";
                    } else {
                        $kondisi = "asset_type like '%" . $type . "%'";
                    }
                }
                if ($group != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and asset_group like '%" . $group . "%'";
                    } else {
                        $kondisi = "asset_group like '%" . $group . "%'";
                    }
                }

                $data = DB::table('asset_mstr')
                        ->leftjoin('asset_type','asset_mstr.asset_type','asset_type.astype_code')
                        ->leftjoin('asset_group','asset_mstr.asset_group','asset_group.asgroup_code')
                        ->select('asset_mstr.asset_code','asset_mstr.asset_desc','astype_desc','asgroup_desc',
                            'asset_mstr.asset_site','asset_mstr.asset_loc','asset_mstr.asset_um','asset_mstr.asset_sn',
                            'asset_mstr.asset_type','asset_mstr.asset_group','asset_mstr.asset_failure',
                            'asset_mstr.asset_measure','asset_mstr.asset_supp','asset_mstr.asset_meter','asset_mstr.asset_cal',
                            'asset_mstr.asset_note','asset_mstr.asset_active','asset_mstr.asset_repair_type',
                            'asset_mstr.asset_repair','asset_mstr.asset_prc_date','asset_mstr.asset_daya',
                            'asset_mstr.asset_prc_price','asset_mstr.asset_start_mea',
                            'asset_mstr.asset_upload','asset_tolerance','asset_last_usage','asset_last_usage_mtc',
                            'asset_last_mtc','asset_on_use','asset_image','asset_qad','asset_mea_um')
                        ->whereRaw($kondisi)
                        ->orderBy($sort_by, $sort_group)
                        ->paginate(10);

                return view('setting.table-asset', ['data' => $data, 'datasite' => $datasite, 'dataloc' => $dataloc, 'dataastype' => $dataastype, 'dataasgroup' => $dataasgroup, 'datasupp' => $datasupp, 'datafn' => $datafn, 'repaircode' => $repaircode, 'repairgroup' => $repairgroup, 'datasearch' => $datasearch]);
            }
        }
    }

    //untuk search location by asset
    public function locasset(Request $req) /** Dari asset.blade */
    {
        if ($req->ajax()) {
            $t_site = $req->get('t_site');
      
            $data = DB::table('asset_loc')
                    ->where('asloc_site','=',$t_site)
                    ->get();

            $output = '<option value="" >Select</option>';
            foreach($data as $data){
                $output .= '<option value="'.$data->asloc_code.'" >'.$data->asloc_code.' -- '.$data->asloc_desc.'</option>';
            }

            return response($output);
        }
    }

    //untuk search location spatepart
    public function searchlocsp(Request $req)
    {
        if ($req->ajax()) {
            // dd($req->all());
            $t_site = $req->get('t_site');
      
            $data = DB::table('loc_mstr')
                    ->where('loc_site','=',$t_site)
                    ->whereNotNull('loc_code')
                    ->where('loc_code','<>','')
                    ->orderBy('loc_code')
                    ->get();

            $output = '<option value="" >Select</option>';
            foreach($data as $data){

                $output .= '<option value="'.$data->loc_code.'" >'.$data->loc_code.' -- '.$data->loc_desc.'</option>';
                           
            }

            return response($output);
        }
    }

    //untuk search location by asset
    public function searchlocsp2(Request $req)
    {
        if ($req->ajax()) {
            // dd($req->all());
            $site = $req->get('site');
            $loc = $req->get('loc');
      
            $data = DB::table('loc_mstr')
                    ->where('loc_site','=',$site)
                    ->whereNotNull('loc_code')
                    ->where('loc_code','<>','')
                    ->orderBy('loc_code')
                    ->get();

            // dd($data);

            $output = '<option value="" >Select</option>';
            foreach($data as $data){
                if ($data->loc_code == $loc) {
                    $output .= '<option value="'.$data->loc_code.'" selected>'.$data->loc_code.' -- '.$data->loc_desc.'</option>';
                } else {
                    $output .= '<option value="'.$data->loc_code.'" >'.$data->loc_code.' -- '.$data->loc_desc.'</option>';
                }         
            }
            return response($output);
        }
    }

    //untuk search location by asset
    public function locasset2(Request $req)
    {
        if ($req->ajax()) {
            $site = $req->get('site');
            $loc = $req->get('loc');
      
            $data = DB::table('asset_loc')
                    ->where('asloc_site','=',$site)
                    ->get();

            $output = '<option value="" >Select</option>';
            foreach($data as $data){
                if ($data->asloc_code == $loc) {
                    $output .= '<option value="'.$data->asloc_code.'" selected>'.$data->asloc_code.' -- '.$data->asloc_desc.'</option>';
                } else {
                    $output .= '<option value="'.$data->asloc_code.'" >'.$data->asloc_code.' -- '.$data->asloc_desc.'</option>';
                }         
            }
            return response($output);
        }
    }

    public function excelasset(Request $req)
    {
        // dd($req->all());
        $sasset    = $req->sasset;
        $sloc    = $req->sloc;
        $stype    = $req->stype;
        $sgroup    = $req->sgroup;
        $snote    = $req->snote;
        $sspec    = $req->sspec;

        return Excel::download(new AssetExport($sasset,$sloc,$stype,$sgroup,$snote,$sspec), 'EAMS Master Asset.xlsx');
    }
/* End Asset Master */

/* Asset Hierarchy Master */
    //untuk menampilkan menu Asset Hierarchy master
    public function asparmaster(Request $req)
    {
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        $data = DB::table('asset_par')
            ->orderby('aspar_par')
            ->paginate(10);

        $dataasset = DB::table('asset_mstr')
            ->orderby('asset_code')
            ->get();

        return view('setting.asset-par', ['data' => $data, 'dataasset' => $dataasset, 'isFav' => $isFav, 'menuMaster' => $menuMaster]);

    }

    //untuk create Asset Hierarchy master
    public function createaspar(Request $req)
    {
        // harus ada inputan anak
        if (is_null($req->barang)) {
            toast('Please Input Child Items!!', 'error');
             return back();
        }

        // cek loping parent
        $flg = 0;
        foreach ($req->barang as $barang) {
            $cekData = DB::table('asset_par')
                ->where('aspar_par','=',$req->barang[$flg])
                ->where('aspar_child','=',$req->t_code)
                ->get();

            if($cekData->count() > 0) {
                toast('Looping Parent - Child Item!!', 'error');
                return back();
            }

            $cekData = DB::table('asset_par')
                ->where('aspar_child','=',$req->barang[$flg])
                ->get();

            foreach ($cekData as $ck) {
                $cekData1 = DB::table('asset_par')
                    ->where('aspar_child','=',$ck->aspar_par)
                    ->get();

                foreach ($cekData1 as $ck1) {
                    if ($ck1->aspar_par == $req->barang[$flg]) {
                        toast('Looping Parent - Child Item!!', 'error');
                        return back();
                    }
                }
            }

            $flg =+ 1;
        }   

        // cek kalau anak sama emak sama
        $flg = 0;
        foreach ($req->barang as $barang) {

            if($req->barang[$flg] == $req->t_code) {
                toast('Child Can Not Same with Parent Items!!', 'error');
                return back();
            }

            $flg += 1;
        }  

        //cek parent sudah terdaftar atau belum
        $cekData = DB::table('asset_par')
                ->where('aspar_par','=',$req->t_code)
                ->count();
        if ($cekData > 0) {
            toast('Parent Items Already Registered!!', 'error');
            return back();
        }

        $flg = 0;
        foreach($req->barang as $barang){
            DB::table('asset_par')
            ->insert([
                'aspar_par'     => $req->t_code,
                'aspar_child'   => $req->barang[$flg],        
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            $flg += 1;
        }    

        toast('Asset Hierarchy Created.', 'success');
        return back();
    }

    //menampilkan detail edit
    public function editdetailaspar(Request $req)
    {
        if ($req->ajax()) {
            $data = DB::table('asset_par')
                    ->join('asset_mstr','asset_code','=','aspar_child')
                    ->where('aspar_par','=',$req->code)
                    ->get();

            $output = '';
            foreach ($data as $data) {
                $output .= '<tr>'.
                            '<td><input type="text" class="form-control" name="barang[]" readonly value="'.$data->aspar_child.'" size="13"></td>'.
                            '<td>'.$data->asset_desc.'</td>'.
                            '<td><input type="checkbox" name="cek[]" class="cek" id="cek" value="0">
                            <input type="hidden" name="tick[]" id="tick" class="tick" value="0"></td>'.
                            '</tr>';
            }

            return response($output);
        }
    }

    //untuk edit Asset Hierarchy master
    public function editaspar(Request $req)
    {

        //cek harus input anak
        $flg = 0;
        $x = 0;
        foreach ($req->barang as $barang) {
            if($req->tick[$flg] == 0) {
                $x = 1;
            }
            $flg += 1;
        }  
        if ($x == 0) {
            toast('Input Child!!', 'error');
            return back();
         } 

        // cek looping parent
        $flg = 0;
        foreach ($req->barang as $barang) {
            if($req->tick[$flg] == 0) {
                $cekData = DB::table('asset_par')
                    ->where('aspar_par','=',$req->barang[$flg])
                    ->where('aspar_child','=',$req->h_code)
                    ->get();

                if($cekData->count() > 0) {
                    toast('Looping Parent - Child Item!!', 'error');
                    return back();
                }
            }

            $cekData = DB::table('asset_par')
                ->where('aspar_child','=',$req->barang[$flg])
                ->get();

            foreach ($cekData as $ck) {
                $cekData1 = DB::table('asset_par')
                    ->where('aspar_child','=',$ck->aspar_par)
                    ->get();

                foreach ($cekData1 as $ck1) {
                    if ($ck1->aspar_par == $req->barang[$flg]) {
                        toast('Looping Parent - Child Item!!', 'error');
                        return back();
                    }
                }
            }

            $flg += 1;
        }   

        // cek kalau anak sama emak sama
        $flg = 0;
        foreach ($req->barang as $barang) {
            if($req->tick[$flg] == 0) {
                if($req->barang[$flg] == $req->h_code) {
                    toast('Child Can Not Same with Parent Items!!', 'error');
                    return back();
                }
            }

            $flg += 1;
        }  


        // sukses cek
        DB::table('asset_par')
            ->where('aspar_par','=',$req->h_code)
            ->delete();

        $flg = 0;
        foreach($req->barang as $barang){
            if($req->tick[$flg] == 0) {
                DB::table('asset_par')
                ->insert([
                    'aspar_par'     => $req->h_code,
                    'aspar_child'   => $req->barang[$flg],        
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'updated_at'    => Carbon::now()->toDateTimeString(),
                    'edited_by'     => Session::get('username'),
                ]);
            }
            $flg += 1;
        } 

        toast('Asset Hierarchy Updated.', 'success');
        return back();

    }

    //untuk delete Asset Hierarchy master
    public function deleteaspar(Request $req)
    {
        DB::table('asset_par')
            ->where('aspar_par', '=', $req->d_code)
            ->where('aspar_child', '=', $req->d_child)
            ->delete();

        toast('Deleted Asset Hierarchy Successfully.', 'success');
        return back();
    }

    //untuk paginate Asset Hierarchy master
    public function asparpagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');
            $code1 = $req->get('code1');
            $desc1 = $req->get('desc1');
      
            if ($code == '' && $desc == '' && $code1 == '' && $desc1 == '') {
                $data = DB::table('asset_par')
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                $dataasset = DB::table('asset_mstr')
                    ->orderby('asset_code')
                    ->get();
                return view('setting.table-asset-par', ['data' => $data,'dataasset' =>$dataasset]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "t.aspar_par like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and t1.asset_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "t1.asset_desc like '%" . $desc . "%'";
                    }
                }
                if ($code1 != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and t.aspar_child like '%" . $code1 . "%'";
                    } else {
                        $kondisi = "t.aspar_child like '%" . $code1 . "%'";
                    }
                }
                if ($desc1 != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and t2.asset_desc like '%" . $desc1 . "%'";
                    } else {
                        $kondisi = "t2.asset_desc like '%" . $desc1 . "%'";
                    }
                }

                $data = DB::table('asset_par as t')
                    ->select('t.aspar_par', 't.aspar_child')
                    ->join('asset_mstr as t1','t.aspar_par','=','t1.asset_code')
                    ->join('asset_mstr as t2','t.aspar_child','=','t2.asset_code')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                $dataasset = DB::table('asset_mstr')
                    ->orderby('asset_code')
                    ->get();

                return view('setting.table-asset-par', ['data' => $data,'dataasset' => $dataasset]);
            }
        }
    }
/* End Asset Hierarchy Master */

/* Spare Part Type Master */
    //untuk menampilkan menu Spare Part Type
    public function sptmaster(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT10') !== false) {
            $data = DB::table('sp_type')
                ->orderby('spt_code')
                ->paginate(10);

            $datasearch = DB::table('sp_type')
                ->orderby('spt_code')
                ->get();

            return view('setting.sp-type', ['data' => $data, 'datasearch' => $datasearch]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Spare Part Type sebelum input
    public function cekspt(Request $req)
    {
        $cek = DB::table('sp_type')
            ->where('spt_code','=',$req->input('code'))
            ->orWhere('spt_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Spare Part Type
    public function createspt(Request $req)
    {
        $cekData = DB::table('sp_type')
                ->where('spt_code','=',$req->t_code)
                ->orWhere('spt_desc','=',$req->t_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('sp_type')
            ->insert([
                'spt_code'      => $req->t_code,
                'spt_desc'      => $req->t_desc,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Spare Part Type Created.', 'success');
            return back();
        } else {
            toast('Spare Part Type is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit Spare Part Type
    public function editspt(Request $req)
    {
        $cekData = DB::table('sp_type')
                ->where('spt_desc','=',$req->te_desc)
                ->where('spt_code','<>',$req->te_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('sp_type')
            ->where('spt_code','=',$req->te_code)
            ->update([
                'spt_desc'      => $req->te_desc,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Spare Part Type Updated.', 'success');
            return back();
        } else {
            toast('Spare Part Type Description is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk delete Spare Part Type
    public function deletespt(Request $req)
    {
        $cek = DB::table('sp_mstr')
            ->where('spm_type','=',$req->d_code)
            ->get();

        if ($cek->count() == 0) {
            DB::table('sp_type')
            ->where('spt_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Spare Part Type Successfully.', 'success');
            return back();
        } else {
            toast('Spare Part Type Can Not Deleted', 'error');
            return back();
        } 
        
    }

    //untuk paginate Spare Part Type
    public function sptpagination(Request $req)
    {
       //dd($req->all());
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $datasearch = DB::table('sp_type')
                ->orderby('spt_code')
                ->get();

            if ($code == '' && $desc == '') {
                $data = DB::table('sp_type')
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-sp-type', ['data' => $data, 'datasearch' => $datasearch]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "spt_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and spt_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "spt_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('sp_type')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-sp-type', ['data' => $data]);
            }
        }
    }

    public function loadsptype(Request $req){
        $domain = ModelsQxwsa::first();

        $spdata = (new WSAServices())->wsagetsptype($domain->wsas_domain);

        if ($spdata === false) {
            toast('WSA Failed', 'error')->persistent('Dismiss');
            return redirect()->back();
        } else {

            if ($spdata[1] == "false") {
                toast('Data Spare Part Type tidak ditemukan', 'error')->persistent('Dismiss');
                return redirect()->back();
            } else {
                
                foreach ($spdata[0] as $datas) {
                    $spg = SPTypeMstr::firstOrNew(['spt_code'=>$datas->t_codevalue,
                                              'spt_desc'=> $datas->t_comment]);
                        $spg->spt_code = $datas->t_codevalue;
                        $spg->spt_desc = $datas->t_comment;
                        $spg->created_at = Carbon::now()->toDateTimeString();
                        $spg->updated_at = Carbon::now()->toDateTimeString();
                        $spg->save();
                }
            }
        }

        toast('Spare Part Type Loaded.', 'success');
        return back();
    }
/* End Spare Part Type Master */

/* Spare Part Group Master */
    //untuk menampilkan menu Spare Part Group
    public function spgmaster(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT11') !== false) {
            $data = DB::table('sp_group')
                ->orderby('spg_code')
                ->paginate(10);

            $datasearch = DB::table('sp_group')
                ->orderby('spg_code')
                ->get();

            return view('setting.sp-group', ['data' => $data, 'datasearch' => $datasearch]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Spare Part Group sebelum input
    public function cekspg(Request $req)
    {
        $cek = DB::table('sp_group')
            ->where('spg_code','=',$req->input('code'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Spare Part Group
    public function createspg(Request $req)
    {
        $cekData = DB::table('sp_group')
                ->where('spg_code','=',$req->t_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('sp_group')
            ->insert([
                'spg_code'      => $req->t_code,
                'spg_desc'      => $req->t_desc,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Spare Part Group Created.', 'success');
            return back();
        } else {
            toast('Spare Part Group is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit Spare Part Group
    public function editspg(Request $req)
    {
        DB::table('sp_group')
        ->where('spg_code','=',$req->te_code)
        ->update([
            'spg_desc'      => $req->te_desc,
            'updated_at'    => Carbon::now()->toDateTimeString(),
            'edited_by'     => Session::get('username'),
        ]);

        toast('Spare Part Group Updated.', 'success');
        return back();
    }

    //untuk delete Spare Part Group
    public function deletespg(Request $req)
    {
        $cek = DB::table('sp_mstr')
            ->where('spm_group','=',$req->d_code)
            ->get();

        if ($cek->count() == 0) {
            DB::table('sp_group')
            ->where('spg_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Spare Part Group Successfully.', 'success');
            return back();
        } else {
            toast('Spare Part Group Can Not Deleted', 'error');
            return back();
        }
    }

    //untuk paginate Spare Part Group
    public function spgpagination(Request $req)
    {
       
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $datasearch = DB::table('sp_group')
                    ->orderBy($sort_by, $sort_type)
                    ->get();

            if ($code == '' && $desc == '') {
                $data = DB::table('sp_group')
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-sp-group', ['data' => $data, 'datasearch' => $datasearch]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "spg_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and spg_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "spg_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('sp_group')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-sp-group', ['data' => $data, 'datasearch' => $datasearch]);
            }
        }
    }

    public function loadspgroup(Request $req){
        $domain = ModelsQxwsa::first();

        $spdata = (new WSAServices())->wsagetspgroup($domain->wsas_domain);

        if ($spdata === false) {
            toast('WSA Failed', 'error')->persistent('Dismiss');
            return redirect()->back();
        } else {

            if ($spdata[1] == "false") {
                toast('Data Spare Part Group tidak ditemukan', 'error')->persistent('Dismiss');
                return redirect()->back();
            } else {
                
                foreach ($spdata[0] as $datas) {
                    $spg = SPGroupMstr::firstOrNew(['spg_code'=>$datas->t_codevalue,
                                              'spg_desc'=> $datas->t_comment]);
                        $spg->spg_code = $datas->t_codevalue;
                        $spg->spg_desc = $datas->t_comment;
                        $spg->created_at = Carbon::now()->toDateTimeString();
                        $spg->updated_at = Carbon::now()->toDateTimeString();
                        $spg->save();
                }
            }
        }

        toast('Spare Part Group Loaded.', 'success');
        return back();
    }
/* End Spare Part Group Master */

/* Spare Part Master */
    //untuk menampilkan menu Spare Part Master
    public function spmmaster(Request $req) /** blade : setting.sp-mstr */
    {   
        $scode = $req->s_code;
        $sdesc = $req->s_desc;

        // $currentUrl = $req->getRequestUri(); ini dirubah karena tidak dapat digunakan untuk fungsi Search di Form
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        $data = DB::table('sp_mstr')
            ->leftJoin('site_mstrs','site_code','=','spm_site')
            ->leftJoin('loc_mstr', function ($join) {
                $join->on('loc_mstr.loc_site', '=', 'sp_mstr.spm_site')
                        ->on('loc_mstr.loc_code', '=', 'sp_mstr.spm_loc');
            })
            ->orderby('spm_code');
        
        if($scode) {
            $data = $data->where('spm_code','like','%'.$scode.'%');
        }
        if($sdesc) {
            $data = $data->where('spm_desc','like','%'.$sdesc.'%');
        }

        $data = $data->paginate(10);

        $datasupp = DB::table('supp_mstr')
            ->orderby('supp_code')
            ->get();

        $dataSite = DB::table('site_mstrs')
            ->get();

        $dataLoc = DB::table('loc_mstr')
            ->orderby('loc_code')
            ->get();

        $datasearch = DB::table('sp_mstr')
            ->orderby('spm_code')
            ->get();

        return view('setting.sp-mstr', compact('data','datasupp','dataSite','dataLoc','datasearch',
            'isFav','menuMaster','scode','sdesc'));
    }

    //cek Spare Part Type sebelum input
    public function cekspm(Request $req)
    {
        $cek = DB::table('sp_mstr')
            ->where('spm_code','=',$req->input('code'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Spare Part Master
    public function createspm(Request $req)
    {
        $cekData = DB::table('sp_mstr')
                ->where('spm_code','=',$req->t_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('sp_mstr')
            ->insert([
                'spm_code'      => $req->t_code,
                'spm_desc'      => $req->t_desc, 
                'spm_um'        => $req->t_um,
                'spm_site'      => $req->t_site,
                'spm_loc'      => $req->t_loc,
                'spm_lot'      => $req->t_lot,
                'spm_type'      => $req->t_type,
                'spm_group'     => $req->t_group,
                'spm_price'     => $req->t_prc_price,
                'spm_safety'    => $req->t_safety,
                'spm_supp'      => $req->t_supp,        
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Spare Part Created.', 'success');
            return back();
        } else {
            toast('Spare Part is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit Spare Part Master
    public function editspm(Request $req)
    {
        DB::table('sp_mstr')
        ->where('spm_code','=',$req->te_code)
        ->update([
            'spm_desc'          => $req->te_desc, 
            'spm_type'          => $req->te_type,
            'spm_um'            => $req->te_um,
            'spm_group'         => $req->te_group,
            'spm_price'         => $req->te_prc_price,
            'spm_safety'        => $req->te_safety,
            'spm_supp'          => $req->te_supp,               
            'spm_site'          => $req->te_site,               
            'spm_loc'          => $req->te_loc,               
            'spm_lot'          => $req->te_lot,               
            'updated_at'        => Carbon::now()->toDateTimeString(),
            'edited_by'         => Session::get('username'),
        ]);

        toast('Spare Part Updated.', 'success');
        return back();
    }

    //untuk delete Spare Part Master
    public function deletespm(Request $req)
    {
        $cek = DB::table('rep_part')
            ->where('reppart_sp','=',$req->d_code)
            ->get();

        if ($cek->count() == 0) {
            DB::table('sp_mstr')
            ->where('spm_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Spare Part Successfully.', 'success');
            return back();
        } else {
            toast('Spare Part Can Not Deleted', 'error');
            return back();
        }
       
    }

    //untuk paginate Spare Part Master
    public function spmpagination(Request $req)
    {
        // dd($req->all());
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');
            $type = $req->get('type');
            $group = $req->get('group');

            // $datatype = DB::table('sp_type')
            //     ->orderby('spt_code')
            //     ->get();

            // $datagroup = DB::table('sp_group')
            //     ->orderby('spg_code')
            //     ->get();

            $datasupp = DB::table('supp_mstr')
                ->orderby('supp_code')
                ->get();

            $datasearch = DB::table('sp_mstr')
                    ->orderBy($sort_by, $sort_group)
                    ->get();

            $dataSite = DB::table('site_mstrs')
                ->get();

            $dataLoc = DB::table('loc_mstr')
                ->orderby('loc_code')
                ->get();
      
            if ($code == '' && $desc == '' /* && $type == '' && $group == '' */) {
                $data = DB::table('sp_mstr')
                    ->leftJoin('site_mstrs','site_code','=','spm_site')
                    ->leftJoin('loc_mstr', function ($join) {
                        $join->on('loc_mstr.loc_site', '=', 'sp_mstr.spm_site')
                            ->on('loc_mstr.loc_code', '=', 'sp_mstr.spm_loc');
                    })
                    ->orderby('spm_code')
                    ->paginate(10);

                // return view('setting.table-sp-mstr', ['data' => $data, 'datatype' => $datatype, 'datagroup' => $datagroup, 'datasupp' => $datasupp, 'datasearch' => $datasearch]);
                return view('setting.table-sp-mstr', ['data' => $data, 'datasupp' => $datasupp,'dataSite' => $dataSite, 
                'dataLoc' => $dataLoc, 'datasearch' => $datasearch]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "spm_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and spm_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "spm_desc like '%" . $desc . "%'";
                    }
                }
                // if ($type != '') {
                //     if ($kondisi != '') {
                //         $kondisi .= " and spm_type like '%" . $type . "%'";
                //     } else {
                //         $kondisi = "spm_type like '%" . $type . "%'";
                //     }
                // }
                // if ($group != '') {
                //     if ($kondisi != '') {
                //         $kondisi .= " and spm_group like '%" . $group . "%'";
                //     } else {
                //         $kondisi = "spm_group like '%" . $group . "%'";
                //     }
                // }

                $data = DB::table('sp_mstr')
                    ->leftJoin('site_mstrs','site_code','=','spm_site')
                    ->leftJoin('loc_mstr', function ($join) {
                        $join->on('loc_mstr.loc_site', '=', 'sp_mstr.spm_site')
                            ->on('loc_mstr.loc_code', '=', 'sp_mstr.spm_loc');
                    })
                    ->whereRaw(($kondisi))
                    ->orderby('spm_code')
                    ->paginate(10);

                // return view('setting.table-sp-mstr', ['data' => $data, 'datatype' => $datatype, 'datagroup' => $datagroup, 'datasupp' => $datasupp, 'datasearch' => $datasearch]);
                return view('setting.table-sp-mstr', ['data' => $data, 'datasupp' => $datasupp,'dataSite' => $dataSite, 
                'dataLoc' => $dataLoc, 'datasearch' => $datasearch]);
            }
        }
    }

    public function loadsparepart(Request $req){
        $domain = ModelsQxwsa::first();

        $datasite = DB::table('site_mstrs')
            ->whereSite_flag('Yes')
            ->orderBy('site_code')
            ->get();

        foreach($datasite as $da) {
            $spdata = (new WSAServices())->wsagetsp($domain->wsas_domain,$da->site_code);

            if ($spdata === false) {
                toast('WSA Failed', 'error')->persistent('Dismiss');
                return redirect()->back();
            } else {
                DB::table('sp_mstr')
                    ->delete();

                if ($spdata[1] == "false") {
                    // toast('Data Sparepart pada Site ' . $da->site_code . ' tidak ditemukan', 'error')->persistent('Dismiss');
                    // return redirect()->back();
                } else {
                    
                    foreach ($spdata[0] as $datas) {
                        $sp = SPMstr::firstOrNew(['spm_code'=>$datas->t_spcode,
                                                'spm_desc'=> $datas->t_spname,
                                                ]);
                            $sp->spm_dom = $datas->t_dom;
                            $sp->spm_site = $datas->t_site;
                            $sp->spm_code = $datas->t_spcode;
                            $sp->spm_desc = str_replace('"', '', rtrim($datas->t_spname));
                            $sp->spm_um = $datas->t_spum;
                            $sp->spm_loc = $datas->t_loc;
                            $sp->spm_lot = $datas->t_lotser;
                            $sp->spm_group = $datas->t_group;
                            $sp->spm_type = $datas->t_sptype;
                            $sp->spm_active = 'Yes';
                            $sp->created_at = Carbon::now()->toDateTimeString();
                            $sp->updated_at = Carbon::now()->toDateTimeString();
                            $sp->edited_by = Session::get('username');
                            $sp->save();
                    }
                }
            } /** else ($spdata === false) { */
        } /* foreach($datasite as $da) { */

        toast('Spare Part Loaded.', 'success');
        return back();
    }
/* End Spare Part Master */

/* Tool Master */
    //untuk menampilkan menu Tool Master
    public function toolmaster(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT14') !== false) {
            $data = DB::table('tool_mstr')
                ->orderby('tool_code')
                ->paginate(10);

            $datatool = DB::table('tool_mstr')
                ->orderby('tool_code')
                ->get();

            return view('setting.tool', ['data' => $data, 'datatool' => $datatool]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Tool Master sebelum input
    public function cektool(Request $req)
    {
        $cek = DB::table('tool_mstr')
            ->where('tool_code','=',$req->input('code'))
            ->orWhere('tool_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Tool Master
    public function createtool(Request $req)
    {
        $cekData = DB::table('tool_mstr')
                ->where('tool_code','=',$req->t_code)
                ->orWhere('tool_desc','=',$req->t_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('tool_mstr')
            ->insert([
                'tool_code'     => $req->t_code,
                'tool_desc'     => $req->t_desc,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Tool Created.', 'success');
            return back();
        } else {
            toast('Tool is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit Tool Master
    public function edittool(Request $req)
    {
        $cekData = DB::table('tool_mstr')
                ->where('tool_desc','=',$req->te_desc)
                ->where('tool_code','<>',$req->te_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('tool_mstr')
            ->where('tool_code','=',$req->te_code)
            ->update([
                'tool_desc'     => $req->te_desc,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Tool Updated.', 'success');
            return back();
        } else {
            toast('Tool Description is Already Registerd!!', 'error');
            return back();
        }

        
    }

    //untuk delete Tool Master
    public function deletetool(Request $req)
    {
        $cek = DB::table('rep_ins')
            ->where('repins_tool','=',$req->d_code)
            ->get();

        if ($cek->count() == 0) {
            DB::table('tool_mstr')
            ->where('tool_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Tool Successfully.', 'success');
            return back();
        } else {
            toast('Tool Can Note Deleted!!!', 'error');
            return back();
        }
        
    }

    //untuk paginate Tool Master
    public function toolpagination(Request $req)
    {

        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $datatool = DB::table('tool_mstr')
                ->orderby('tool_code')
                ->get();
      
            if ($code == '' && $desc == '') {
                $data = DB::table('tool_mstr')
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-tool', ['data' => $data, 'datatool' => $datatool]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "tool_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and tool_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "tool_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('tool_mstr')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-tool', ['data' => $data, 'datatool' => $datatool]);
            }
        }
    }
/* End Tool Master */

/* Repair Master */
    //untuk menampilkan menu Repair
    public function repmaster(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT15') !== false) {
            $data = DB::table('rep_mstr')
                ->orderby('rep_code')
                ->orderby('rep_num')
                ->paginate(10);

            return view('setting.repair', ['data' => $data]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Repair sebelum input
    public function cekrep(Request $req)
    {
        $cek = DB::table('rep_mstr')
            ->where('rep_code','=',$req->input('code'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Repair
    public function createrep(Request $req)
    {
        $cekData = DB::table('rep_mstr')
                ->where('rep_code','=',$req->t_code)
                ->orWhere('rep_desc','=',$req->t_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('rep_mstr')
            ->insert([
                'rep_code'      => $req->t_code,
                'rep_num'       => $req->t_num,
                'rep_desc'      => $req->t_desc,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Repair Master Created.', 'success');
            return back();
        } else {
            toast('Repair Master is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit Repair
    public function editrep(Request $req)
    {
        DB::table('rep_mstr')
        ->where('rep_code','=',$req->te_code)
        ->update([
            'rep_num'       => $req->te_num,
            'rep_desc'      => $req->te_desc,
            'updated_at'    => Carbon::now()->toDateTimeString(),
            'edited_by'     => Session::get('username'),
        ]);

        toast('Repair Master Updated.', 'success');
        return back();
    }

    //untuk delete Repair
    public function deleterep(Request $req)
    {
        $cek = DB::table('rep_ins')
            ->where('repins_code','=',$req->d_code)
            ->get();

        $cek2 = DB::table('rep_part')
            ->where('reppart_code','=',$req->d_code)
            ->get();

        if ($cek->count() == 0 and $cek2->count() == 0) {
            DB::table('rep_mstr')
            ->where('rep_code', '=', $req->d_code)
            ->where('rep_num', '=', $req->d_num)
            ->delete();

            toast('Deleted Repair Master Successfully.', 'success');
            return back();
        } else {
            toast('Repair Master Can Not Deleted!!', 'error');
            return back();
        }
    }

    //untuk paginate Repair
    public function reppagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');
      
            if ($code == '' && $desc == '') {
                $data = DB::table('rep_mstr')
                    ->orderBy($sort_by, $sort_group)
                    ->orderby('rep_num')
                    ->paginate(10);

                return view('setting.table-repair', ['data' => $data]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "rep_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and rep_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "rep_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('rep_mstr')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->orderby('rep_num')
                    ->paginate(10);

                return view('setting.table-repair', ['data' => $data]);
            }
        }
    }
/* End Repair Master */

/* Repair Master B */
    //untuk menampilkan menu Repair
    public function repmasterb(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MTE26') !== false) {
            $data = DB::table('rep_master')
                ->orderby('repm_code')
                ->paginate(10);

            $datains = DB::table('ins_group')
                ->select('insg_code','insg_desc')
                ->distinct('insg_code','insg_desc')
                ->orderby('insg_code')
                ->get();

            $datapart = DB::table('rep_partgroup')
                ->select('reppg_code','reppg_desc')
                ->distinct('reppg_code','reppg_desc')
                ->orderby('reppg_code')
                ->get();    

            return view('setting.repairb', ['data' => $data, 'datains' => $datains, 'datapart' => $datapart]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    // //cek Repair sebelum input
    // public function cekrepb(Request $req)
    // {
    //     $cek = DB::table('rep_master')
    //         ->where('repm_code','=',$req->input('code'))
    //         ->get();

    //     if ($cek->count() == 0) {
    //         return "tidak";
    //     } else {
    //         return "ada";
    //     }
    // }

    //untuk create Repair
    public function createrepb(Request $req)
    {
        $cekData = DB::table('rep_master')
                ->where('repm_code','=',$req->t_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('rep_master')
            ->insert([
                'repm_code'      => $req->t_code,
                'repm_desc'      => $req->t_desc,                
                'repm_ins'       => $req->t_ins,                                
                'repm_ref'       => $req->t_ref,                                
                'created_at'     => Carbon::now()->toDateTimeString(),
                'updated_at'     => Carbon::now()->toDateTimeString(),
                'edited_by'      => Session::get('username'),
            ]);

            toast('Repair Master Created.', 'success');
            return back();
        } else {
            toast('Repair Master is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit Repair
    public function editrepb(Request $req)
    {
        DB::table('rep_master')
        ->where('repm_code','=',$req->te_code)
        ->update([
            'repm_desc'      => $req->te_desc,                
            'repm_ins'       => $req->te_ins,                       
            'repm_ref'       => $req->te_ref,                       
            'updated_at'     => Carbon::now()->toDateTimeString(),
            'edited_by'      => Session::get('username'),
        ]);

        toast('Repair Master Updated.', 'success');
        return back();
    }

    //untuk delete Repair
    public function deleterepb(Request $req)
    {
        DB::table('rep_master')
        ->where('repm_code', '=', $req->d_code)
        ->delete();

        toast('Deleted Repair Master Successfully.', 'success');
        return back();
    }

    //untuk paginate Repair
    public function reppaginationb(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $datains = DB::table('ins_group')
                ->select('insg_code','insg_desc')
                ->distinct('insg_code')
                ->orderby('insg_code')
                ->get();

            $datapart = DB::table('rep_partgroup')
                ->select('reppg_code','reppg_desc')
                ->distinct('reppg_code')
                ->orderby('reppg_code')
                ->get();    
      
            if ($code == '' && $desc == '') {
                $data = DB::table('rep_master')
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);
                return view('setting.table-repairb', ['data' => $data, 'datains' => $datains, 'datapart' => $datapart]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "repm_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and repm_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "repm_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('rep_master')
                    ->whereRaw($kondisi)
                    ->select('repm_code','repm_desc','repm_ins','repm_part','repm_ref')
                    ->orderby('repm_code')
                    ->paginate(10);

                return view('setting.table-repairb', ['data' => $data, 'datains' => $datains, 'datapart' => $datapart]);
            }
        }
    }
/* End Repair Master B */

/* Instruction Detail */
    //untuk menampilkan menu Instruction Detail
    public function insmaster(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MTF35') !== false) {
            $data = DB::table('ins_mstr')
                ->orderby('ins_code')
                ->paginate(10);

            // $dataPart = DB::table('rep_part')
            //     ->orderby('reppart_code')
            //     ->get();

            $dataPart = DB::table('sp_mstr')
                    ->orderby('spm_code')
                    ->get();

            $datains = DB::table('ins_mstr')
                ->orderby('ins_code')
                ->get();

            return view('setting.instruction', ['data' => $data, 'dataPart' => $dataPart, 'datains' => $datains]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Instruction Detail sebelum input
    public function cekins(Request $req)
    {
        $cek = DB::table('ins_mstr')
            ->where('ins_code','=',$req->input('code'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk cari data tool
    public function viewtool(Request $req)
    {

        if($req->ajax()){
            $data = DB::table('tool_mstr')
                ->get();

            $array = json_decode(json_encode($data), true);

            return response()->json($array);
        }
    }

    //untuk cari data part
    public function viewpart(Request $req)
    {

        if($req->ajax()){
            $data = DB::table('sp_mstr')
                ->orderBy('spm_code')
                ->get();

            $array = json_decode(json_encode($data), true);

            return response()->json($array);
        }
    }

    //untuk create Instruction Detail
    public function createins(Request $req)
    {
        // $tool = "";
        // if(!is_null($req->t_tool)) {
        //     $flg = 0;
        //     foreach ($req->t_tool as $ds) {
        //         if($flg == 0){
        //             $tool = $tool . $req->t_tool[$flg];
        //         }
        //         else{
        //             $tool = $tool . ',' .$req->t_tool[$flg];
        //         }

        //         $flg += 1;
        //     }
        // }

        $part = "";
        if(!is_null($req->t_part)) {
            $flg = 0;
            foreach ($req->t_part as $ds) {
                if($flg == 0){
                    $part = $part . $req->t_part[$flg];
                }
                else{
                    $part = $part . ',' .$req->t_part[$flg];
                }

                $flg += 1;
            }
        }

        // $part = $req->t_part;

        $cekData = DB::table('ins_mstr')
                ->where('ins_code','=',$req->t_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('ins_mstr')
            ->insert([
                'ins_code'       => $req->t_code,
                'ins_desc'       => $req->t_desc,   
                'ins_ref'        => $req->t_ref,
                'ins_part'       => $part,
                // 'ins_tool'       => $tool,
                'ins_hour'       => $req->t_hour,
                'ins_check'      => $req->t_check,
                'ins_check_desc' => $req->t_check_desc,
                'ins_check_mea'  => $req->t_check_mea,          
                'ins_created_at'     => Carbon::now()->toDateTimeString(),
                'ins_updated_at'     => Carbon::now()->toDateTimeString(),
                'ins_edited_by'      => Session::get('username'),
            ]);

            toast('Instruction Created.', 'success');
            return back();
        } else {
            toast('Instruction is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit Instruction Detail
    public function editins(Request $req)
    {
        // $tool = "";
        // if(!is_null($req->te_tool)) {
        //     $flg = 0;
        //     foreach ($req->te_tool as $ds) {
        //         if($flg == 0){
        //             $tool = $tool . $req->te_tool[$flg];
        //         }
        //         else{
        //             $tool = $tool . ',' . $req->te_tool[$flg];
        //         }
        //         // $tool = $tool . $req->te_tool[$flg] . "," ;
        //         $flg += 1;
        //     }
        // }
//dd($req->te_part);
        $part = "";
        if(!is_null($req->te_part)) {
            $flg = 0;
            foreach ($req->te_part as $ds) {
                if($flg == 0){
                    $part = $req->te_part[$flg];
                }
                else{
                    $part .= ',' .$req->te_part[$flg];
                }

                $flg += 1;
            }
        }

        DB::table('ins_mstr')
        ->where('ins_code','=',$req->te_code)
        ->update([
            'ins_desc'       => $req->te_desc,
            'ins_ref'        => $req->te_ref,
            // 'ins_tool'       => $tool,
            'ins_part'       => $part,
            'ins_hour'       => $req->te_hour,
            'ins_check'      => $req->te_check,
            'ins_check_desc' => $req->te_check_desc,
            'ins_check_mea'  => $req->te_check_mea,  
            'ins_updated_at'     => Carbon::now()->toDateTimeString(),
            'ins_edited_by'      => Session::get('username'),
        ]);

        toast('Instruction Updated.', 'success');
        return back();
    }

    //add sparepart
    public function addpart(Request $req)
    {
        // dd($req->all());
        if ($req->ajax()) {
            $data = DB::table('insd_det')
                    ->join('sp_mstr','spm_code','insd_part')
                    ->where('insd_code','=',$req->code)
                    ->orderBy('insd_part')
                    ->get();

            $output = '';
            foreach ($data as $data) {
                $descspm = DB::table('sp_mstr')->where('spm_code','=',$data->insd_part)->value('spm_desc');
                $output .= '<tr>'.
                            '<td>'.$data->insd_part.' -- '.$descspm.
                            '<input type="hidden" name="partcode[]" value="'.$data->insd_part.'"></td>'.
                            '<td><input type="text" class="form-control" name="partum[]" readonly value="'.$data->insd_um.'" size="13"></td>'.
                            '<td><input type="number" class="form-control" name="partqty[]" readonly value="'.$data->insd_qty.'" size="13" autocomplete="off"></td>'.
                            '<td><input type="checkbox" name="cek[]" class="cek" id="cek" value="0">
                            <input type="hidden" name="tick[]" id="tick" class="tick" value="0"></td>'.
                            '</tr>';
            }

            return response($output);
        }
    }

    //untuk simpan sparepart di instruction
    public function saveaddpart(Request $req)
    {
        // dd($req->all());
        DB::table('insd_det')
            ->where('insd_code', '=', $req->ta_code)
            ->delete();
        
        $flg = 0;
        foreach($req->partcode as $partcode){
            if($req->tick[$flg] == 0) {

                $descspm = DB::table('sp_mstr')->where('spm_code','=',$req->partcode[$flg])->value('spm_desc');

                DB::table('insd_det')
                ->insert([
                    'insd_code'     => $req->ta_code,
                    'insd_part'   => $req->partcode[$flg],  
                    'insd_part_desc' => $descspm,
                    'insd_um'     => $req->partum[$flg] == NULL ? "NN" : $req->partum[$flg], 
                    'insd_qty'   => $req->partqty[$flg] == NULL ? 0 : $req->partqty[$flg],
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'updated_at'    => Carbon::now()->toDateTimeString(),
                    'insd_edited_by'     => Session::get('username'),
                ]);
            }
            $flg += 1;
        }    

        toast('Sparepart Added.', 'success');
        return back();
    }

    //untuk delete Instruction Detail
    public function deleteins(Request $req)
    {
        $cek = DB::table('rep_ins')
            ->where('repins_ins','=',$req->d_code)
            ->get();

        if ($cek->count() == 0) {
            DB::table('ins_mstr')
            ->where('ins_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Instruction Successfully.', 'success');
            return back();
        } else {
            toast('Instruction Can Not Deleted!!', 'error');
            return back();
        }
    }

    //untuk paginate Instruction Detail
    public function inspagination(Request $req)
    {

        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $dataPart = DB::table('sp_mstr')
                    ->orderby('spm_code')
                    ->get();

            $datains = DB::table('ins_mstr')
                ->orderby('ins_code')
                ->get();
      
            if ($code == '' && $desc == '') {
                $data = DB::table('ins_mstr')
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-instruction', ['data' => $data, 'dataPart' => $dataPart, 'datains' => $datains]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "ins_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and ins_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "ins_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('ins_mstr')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-instruction', ['data' => $data, 'dataPart' => $dataPart, 'datains' => $datains]);
            }
        }
    }
/* End Instruction Detail */

/* Instruction Group */
    //untuk menampilkan menu Instruction Group
    public function insgroup(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT24') !== false) {
            $data = DB::table('ins_group')
                // ->paginate(10);
                ->selectRaw('MIN(insg_code) as insg_code , MIN(insg_desc) as insg_desc')
                ->groupBy('insg_code')
                ->orderby('insg_code')
                ->paginate(10);
//dd($data);
            $datains = DB::table('ins_mstr')
                ->orderby('ins_code')
                ->get();

            return view('setting.insgroup', ['data' => $data, 'datains' => $datains]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //untuk create Instruction Group
    public function createinsg(Request $req)
    {
            
        // harus ada inputan anak
        if (is_null($req->line)) {
            toast('Please Line Items!!', 'error');
             return back();
        }
        

        if (is_null($req->barang)) {
            toast('Please Input Instruction Code!!', 'error');
             return back();
        }
        
        $flg = 0;
        foreach($req->barang as $barang){
            DB::table('ins_group')
            ->insert([
                'insg_code'     => $req->t_code,
                'insg_desc'     => $req->t_desc,   
                'insg_line'     => $req->line[$flg],        
                'insg_ins'      => $req->barang[$flg],        
                'insg_created_at'    => Carbon::now()->toDateTimeString(),
                'insg_updated_at'    => Carbon::now()->toDateTimeString(),
                'insg_edited_by'     => Session::get('username'),
            ]);

            $flg += 1;
        } 

        toast('Instruction Group Created.', 'success');
        return back();
    }

    //menampilkan detail edit
    public function editinsgroup(Request $req)
    {
        if ($req->ajax()) {
            $data = DB::table('ins_group')
                    ->join('ins_mstr','ins_code','=','insg_ins')
                    ->where('insg_code','=',$req->code)
                    ->orderby('insg_line')
                    ->get();

            $output = '';
            foreach ($data as $data) {
                $output .= '<tr>'.
                    '<td><input type="hidden" name=line[] id="line" value="'.$data->insg_line.'">'.$data->insg_line.'</td>'.
                    '<td><input type="hidden" class="form-control" name="barang[]" readonly value="'.$data->insg_ins.'" size="13">'.$data->insg_ins.' - '.$data->ins_desc.'</td>'.
                    '<td><input type="checkbox" name="cek[]" class="cek" id="cek" value="0">
                    <input type="hidden" name="tick[]" id="tick" class="tick" value="0"></td>'.
                    '</tr>';
            }

            return response($output);
        }
    }

    //untuk edit Instruction Group
    public function editinsg(Request $req)
    {
        DB::table('ins_group')
            ->where('insg_code','=',$req->h_code)
            ->delete();

        $flg = 0;
        foreach($req->barang as $barang){
            if($req->tick[$flg] == 0) {
                DB::table('ins_group')
                ->insert([
                    'insg_code'     => $req->h_code,
                    'insg_desc'     => $req->h_desc,   
                    'insg_line'     => $req->line[$flg],        
                    'insg_ins'      => $req->barang[$flg],        
                    'insg_created_at'    => Carbon::now()->toDateTimeString(),
                    'insg_updated_at'    => Carbon::now()->toDateTimeString(),
                    'insg_edited_by'     => Session::get('username'),
                ]);
            }
            $flg += 1;
        } 

        toast('Instruction Group Updated.', 'success');
        return back();

    }

    //untuk delete Instruction Group
    public function deleteinsg(Request $req)
    {
        DB::table('ins_group')
            ->where('insg_code', '=', $req->d_code)
            ->delete();

        toast('Deleted Instruction Group Successfully.', 'success');
        return back();
    }

    //untuk paginate Instruction Group
    public function insgpagination(Request $req)
    {
        if ($req->ajax()) {
            $code = $req->get('code');
            $desc = $req->get('desc');
      
            $datains = DB::table('ins_mstr')
                ->orderby('ins_code')
                ->get();

            if ($code == '' && $desc == '') {
                $data = DB::table('ins_group')
                    ->selectRaw('MIN(insg_code) as insg_code , MIN(insg_desc) as insg_desc')
                    ->groupBy('insg_code')
                    ->orderby('insg_code')
                    ->paginate(10);

                return view('setting.table-insgroup', ['data' => $data, 'datains' => $datains]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "insg_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and insg_ins like '%" . $desc . "%'";
                    } else {
                        $kondisi = "insg_ins like '%" . $desc . "%'";
                    }
                }
                
                $data = DB::table('ins_group')
                    ->selectRaw('MIN(insg_code) as insg_code , MIN(insg_desc) as insg_desc')
                    ->whereRaw($kondisi)
                    ->groupBy('insg_code')
                    ->orderby('insg_code')
                    ->paginate(10);

                return view('setting.table-insgroup', ['data' => $data, 'datains' => $datains]);
            }
        }
    }

/* End Instruction Group */

/* Repair Part */
    //untuk menampilkan menu Repair Part
    public function reppart(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT17') !== false) {
            // $data = DB::table('rep_part')
            //     ->join('rep_mstr','rep_code','=','reppart_code')
            //     ->select('reppart_code','rep_desc')
            //     ->groupBy('reppart_code')
            //     ->orderby('reppart_code')
            //     ->paginate(10);

            $datarep = DB::table('rep_mstr')
                ->get();

            $datasp = DB::table('sp_mstr')
                ->get();

            $data = DB::table('rep_part')
                ->orderby('reppart_code')
                ->paginate(10);

            return view('setting.repair-part-step', ['data' => $data, 'datarep' => $datarep, 'datasp' => $datasp]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Repair Part sebelum input
    public function cekreppart(Request $req)
    {

        $cek = DB::table('rep_part')
            ->where('reppart_code','=',$req->input('code'))
            ->where('reppart_step','=',$req->input('step'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Repair Part
    public function createreppart(Request $req)
    {
        // $cek = DB::table('rep_part')
        //     ->where('reppart_code','=',$req->t_code)
        //     ->where('reppart_step','=',$req->t_step)
        //     ->get();

        // if ($cek->count() == 0) {
            DB::table('rep_part')
            ->insert([
                'reppart_code'      => $req->t_code,
                'reppart_desc'      => $req->t_desc,
                // 'reppart_step'      => $req->t_step,
                // 'reppart_sp'        => $req->t_spm, 
                // 'reppart_qty'       => $req->t_qty,               
                'created_at'        => Carbon::now()->toDateTimeString(),
                'updated_at'        => Carbon::now()->toDateTimeString(),
                'edited_by'         => Session::get('username'),
            ]);

            toast('Repair Part Created.', 'success');
            return back();
        // } else {
        //     toast('Repair Part is Already Registerd!!', 'error');
        //     return back();
        // }
    }

    //untuk edit Repair Part
    public function editreppart(Request $req)
    {
        DB::table('rep_part')
        ->where('reppart_code','=',$req->te_code)
        // ->where('reppart_step','=',$req->te_step)
        // ->where('reppart_sp','=',$req->te_spm)
        ->update([
            // 'reppart_qty'   => $req->te_qty,
            'reppart_desc'      => $req->te_desc,
            'updated_at'    => Carbon::now()->toDateTimeString(),
            'edited_by'     => Session::get('username'),
        ]);

        toast('Repair Part Updated.', 'success');
        return back();
    }

    //untuk delete Repair Part
    public function deletereppart(Request $req)
    {
        DB::table('rep_part')
            ->where('reppart_code', '=', $req->d_code)
            ->delete();

        toast('Deleted Repair Part Successfully.', 'success');
        return back();
    }

    //untuk delete Detail Repair Part
    public function deletedetailreppart(Request $req)
    {
        DB::table('rep_part')
            ->where('reppart_code', '=', $req->dd_code)
            ->where('reppart_step', '=', $req->dd_step)
            ->delete();

        toast('Deleted Repair Part Successfully.', 'success');
        return back();
    }

    //untuk paginate Repair Part
    public function reppartpagination(Request $req)
    {

        if ($req->ajax()) {
            $code = $req->get('code');
            $desc = $req->get('desc');
      
            if ($code == '' && $desc == '') {
                // $data = DB::table('rep_part')
                //     ->join('rep_mstr','rep_code','=','reppart_code')
                //     ->select('reppart_code','rep_desc')
                //     ->groupBy('reppart_code')
                //     ->orderBy($sort_by, $sort_group)
                //     ->paginate(10);

                $data = DB::table('rep_part')
                ->orderby('reppart_code')
                ->paginate(10);

                return view('setting.table-repair-part', ['data' => $data]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "reppart_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and reppart_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "reppart_desc like '%" . $desc . "%'";
                    }
                }

                // $data = DB::table('rep_part')
                //     ->join('rep_mstr','rep_code','=','reppart_code')
                //     ->select('reppart_code','rep_desc')
                //     ->distinct()
                //     ->whereRaw($kondisi)
                //     ->orderBy($sort_by, $sort_group)
                //     ->paginate(10);

                $data = DB::table('rep_part')
                ->select('reppart_code','reppart_desc')
                ->whereRaw($kondisi)
                ->orderby('reppart_code')
                ->paginate(10);

                return view('setting.table-repair-part', ['data' => $data]);
            }
        }
    }

    //untuk Detail Data Repair Part
    public function detailreppart(Request $req)
    {
        if($req->ajax()){
            
             $data = DB::table('rep_part')
                ->where('reppart_code','=',$req->code)
                ->orderby('reppart_step')
                ->get();

            $datasp = DB::table('sp_mstr')
                ->get();

            $datarep = DB::table('rep_mstr')
                ->get();

            if($data){
                $output = '';
                foreach($data as $data){
                    $descSpm    = $datasp->where('spm_code','=',$data->reppart_sp)->first();
                    $descRep    = $datarep->where('rep_code','=',$data->reppart_code)->first();

                    $output .= '<tr>'.
                            '<td>'.$data->reppart_step.'</td>'.
                            '<td>'.$data->reppart_sp.'</td>'.
                            '<td>'.$descSpm->spm_desc.'</td>'.
                            '<td>'.$data->reppart_qty.'</td>'.
                            '<td>'.
                            '<a href="" class="editarea2" id="editdata" data-toggle="modal" data-target="#editModal" data-code="'.$data->reppart_code.'" 
                                data-codedesc="'.$descRep->rep_desc.'" data-step="'.$data->reppart_step.'" data-spm="'.$data->reppart_sp.'" data-descspm="'.$descSpm->spm_desc.'" data-qty="'.$data->reppart_qty.'" ><i class="icon-table fa fa-edit fa-lg"></i></a>'.
                            '&ensp;'.
                            '<a href="" class="deletedetail" id="deletedetail" data-toggle="modal" data-target="#deletedetailmodal" data-code="'.$data->reppart_code.'" data-step="'.$data->reppart_step.'"><i class="icon-table fa fa-trash fa-lg"></i>'.
                            '</td>'.
                            '</tr>';
                }

                return response($output);
            }

        }
    }
/* End Repair Part */

/* Repair Part Group */
    //untuk menampilkan menu Repair Part Group
    public function reppartgroup(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT25') !== false) {
            $data = DB::table('rep_partgroup')
                ->orderby('reppg_code')
                ->paginate(10);

            $dataspm = DB::table('sp_mstr')
                ->orderby('spm_code')
                ->get();

            return view('setting.repair-partgroup', ['data' => $data, 'dataspm' => $dataspm]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //untuk create Repair Part Group
    public function createreppg(Request $req)
    {
            
        // harus ada inputan anak
        if (is_null($req->barang)) {
            toast('Please Input Repair Part Code!!', 'error');
             return back();
        }
        
        $flg = 0;
        foreach($req->barang as $barang){
            DB::table('rep_partgroup')
            ->insert([
                'reppg_code'    => $req->t_code,
                'reppg_desc'    => $req->t_desc,   
                'reppg_qty'     => $req->qty[$flg],        
                'reppg_part'    => $req->barang[$flg],        
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            $flg += 1;
        } 

        toast('Repair Part Group Created.', 'success');
        return back();
    }

    //menampilkan detail edit
    public function editreppgroup(Request $req)
    {
        if ($req->ajax()) {

            $data = DB::table('rep_partgroup')
                    ->join('sp_mstr','spm_code','=','reppg_part')
                    ->where('reppg_code','=',$req->code)
                    ->get();

            $output = '';

            foreach ($data as $data) {
                $output .= '<tr>'.
                    '<td><input type="hidden" class="form-control" name="barang[]" readonly value="'.$data->reppg_part.'" size="13">'.$data->reppg_part.' - '.$data->spm_desc.'</td>'.
                    '<td><input type="hidden" name=qty[] id="qty" value="'.$data->reppg_qty.'">'.$data->reppg_qty.'</td>'.
                    '<td><input type="checkbox" name="cek[]" class="cek" id="cek" value="0">
                    <input type="hidden" name="tick[]" id="tick" class="tick" value="0"></td>'.
                    '</tr>';
            }

            return response($output);
        }
    }

    //untuk edit Repair Part Group
    public function editreppg(Request $req)
    {
        DB::table('rep_partgroup')
            ->where('reppg_code','=',$req->h_code)
            ->delete();

        $flg = 0;
        foreach($req->barang as $barang){
            if($req->tick[$flg] == 0) {
                DB::table('rep_partgroup')
                ->insert([
                    'reppg_code'    => $req->h_code,
                    'reppg_desc'    => $req->h_desc,   
                    'reppg_qty'     => $req->qty[$flg],        
                    'reppg_part'    => $req->barang[$flg],        
                    'created_at'    => Carbon::now()->toDateTimeString(),
                    'updated_at'    => Carbon::now()->toDateTimeString(),
                    'edited_by'     => Session::get('username'),
                ]);
            }
            $flg += 1;
        } 

        toast('Repair Part Group Updated.', 'success');
        return back();

    }

    //untuk delete Repair Part Group
    public function deletereppg(Request $req)
    {
        DB::table('rep_partgroup')
            ->where('reppg_code', '=', $req->d_code)
            ->where('reppg_part', '=', $req->d_part)
            ->delete();

        toast('Deleted Repair Part Line Successfully.', 'success');
        return back();
    }

    //untuk paginate Repair Part Group
    public function reppgpagination(Request $req)
    {

        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');
      
            $datains = DB::table('ins_mstr')
                ->orderby('ins_code')
                ->get();

            if ($code == '' && $desc == '') {
                $data = DB::table('rep_partgroup')
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.insgroup', ['data' => $data, 'datains' => $datains]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = 'insg_code like "%' . $code . '%"';
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= ' and insg_ins like "%' . $desc . '%"';
                    } else {
                        $kondisi = 'insg_ins like "%' . $desc . '%"';
                    }
                }
                //dd($kondisi);
                $data = DB::table('rep_partgroup')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.insgroup', ['data' => $data, 'datains' => $datains]);
            }
        }
    }

/* End Repair Part Group */

/* Repair Instruction */
    //untuk menampilkan menu Repair Instruction
    public function repins(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT18') !== false) {
            $data = DB::table('rep_ins')
                ->join('rep_mstr','rep_code','=','repins_code')
                ->select('repins_code','rep_desc')
                ->distinct()
                ->orderby('repins_code')
                ->paginate(10);

            $dataDetail = DB::table('rep_ins')
                ->get();

            $datarep = DB::table('rep_mstr')
                ->get();

            $datains = DB::table('ins_mstr')
                ->get();

            $datatool = DB::table('tool_mstr')
                ->get();

            return view('setting.repair-ins', ['data' => $data, 'datarep' => $datarep, 'datains' => $datains, 'datatool' => $datatool, 'datadetail' => $dataDetail]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Repair Instruction sebelum input
    public function cekrepins(Request $req)
    {

        $cek = DB::table('rep_ins')
            ->where('repins_code','=',$req->input('code'))
            ->where('repins_step','=',$req->input('step'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Repair Instruction
    public function createrepins(Request $req)
    {
        $cekData = DB::table('rep_ins')
                ->where('repins_code','=',$req->t_code)
                ->where('repins_step','=',$req->t_step)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('rep_ins')
            ->insert([
                'repins_code'   => $req->t_code,
                'repins_step'   => $req->t_step,
                'repins_ins'    => $req->t_ins, 
                'repins_tool'   => $req->t_tool,               
                'repins_hour'   => $req->t_hour,               
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Repair Instruction Created.', 'success');
            return back();
        } else {
            toast('Repair Instruction Already Registerd!!!', 'error');
            return back();
        }    
    }

    //untuk edit Repair Instruction
    public function editrepins(Request $req)
    {
        DB::table('rep_ins')
        ->where('repins_code','=',$req->te_code)
        ->where('repins_step','=',$req->te_step)
        ->update([
            'repins_ins'    => $req->te_ins,
            'repins_tool'   => $req->te_tool,
            'repins_hour'   => $req->te_hour,
            'updated_at'    => Carbon::now()->toDateTimeString(),
            'edited_by'     => Session::get('username'),
        ]);

        toast('Repair Instruction Updated.', 'success');
        return back();
    }

    //untuk delete Repair Instruction
    public function deleterepins(Request $req)
    {
        DB::table('rep_ins')
            ->where('repins_code', '=', $req->d_code)
            ->delete();

        toast('Deleted Repair Instruction Successfully.', 'success');
        return back();
    }

    //untuk delete Detail Repair Instruction
    public function deletedetailrepins(Request $req)
    {
        DB::table('rep_ins')
            ->where('repins_code', '=', $req->d_code)
            ->delete();

        toast('Deleted Repair Instruction Successfully.', 'success');
        return back();
    }


    //untuk paginate Repair Instruction
    public function repinspagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');
      
            if ($code == '' && $desc == '') {
                $data = DB::table('rep_ins')
                    ->join('rep_mstr','rep_code','=','repins_code')
                    ->select('repins_code','rep_desc')
                    ->distinct()
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-repair-ins', ['data' => $data]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = 'repins_code like "%' . $code . '%"';
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= ' and rep_desc like "%' . $desc . '%"';
                    } else {
                        $kondisi = 'rep_desc like "%' . $desc . '%"';
                    }
                }

                $data = DB::table('rep_ins')
                    ->join('rep_mstr','rep_code','=','repins_code')
                    ->select('repins_code','rep_desc')
                    ->distinct()
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_group)
                    ->paginate(10);

                return view('setting.table-repair-ins', ['data' => $data]);
            }
        }
    }

    //untuk Detail Data Repair Instruction
    public function detailrepins(Request $req)
    {
        if($req->ajax()){
            
             $data = DB::table('rep_ins')
                ->where('repins_code','=',$req->code)
                ->get();

            $datarep = DB::table('rep_mstr')
                ->get();

            $datains = DB::table('ins_mstr')
                ->get();

            $datatool = DB::table('tool_mstr')
                ->get();

            if($data){
                $output = '';
                foreach($data as $data){
                    $descRep    = $datarep->where('rep_code','=',$data->repins_code)->first();
                    $descIns    = $datains->where('ins_code','=',$data->repins_ins)->first();
                    // $desctool   = $datatool->where('tool_code','=',$data->repins_tool)->first();
                    
                    $output .= '<tr>'.
                            '<td>'.$data->repins_step.'</td>'.
                            '<td>'.$data->repins_ins.'</td>'.
                            '<td>'.$descIns->ins_desc.'</td>'.
                            // '<td>'.$data->repins_tool.'</td>'.
                            // '<td>'.$desctool->tool_desc.'</td>'.
                            // '<td>'.$data->repins_hour.'</td>'.
                            '<td>'.
                            '<a href="" class="editarea2" id="editdata" data-toggle="modal" data-target="#editModal" data-code="'.$data->repins_code.'" 
                                data-codeDesc="'.$descRep->rep_desc.'" data-step="'.$data->repins_step.'" data-ins="'.$data->repins_ins.'" data-insDesc="'.$descIns->ins_desc.'"
                                ><i class="icon-table fa fa-edit fa-lg"></i></a>'.
                            '&ensp;'.
                            '<a href="" class="deletedetail" id="deletedetail" data-toggle="modal" data-target="#deletedetailmodal" data-code="'.$data->repins_code.'" data-step="'.$data->repins_step.'"><i class="icon-table fa fa-trash fa-lg"></i>'.
                            '</td>'.
                            '</tr>';
                }

                return response($output);
            }

        }
    }
/* End Repair Instruction */

/* Repair Detail */
    //untuk menampilkan menu Repair Detail
    public function repdet(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT23') !== false) {
            $data = DB::table('rep_det')
                ->join('rep_mstr','rep_code','=','repdet_code')
                ->select('repdet_code','rep_desc')
                ->groupby('repdet_code','rep_desc')
                ->orderby('repdet_code')
                ->paginate(10);

            $dataDetail = DB::table('rep_ins')
                ->get();

            $datarep = DB::table('rep_mstr')
                ->get();

            $datains = DB::table('ins_mstr')
                ->get();

            $datapart = DB::table('sp_mstr')
                ->get();

            return view('setting.repair-detail', ['data' => $data, 'datarep' => $datarep, 'datains' => $datains, 'datadetail' => $dataDetail, 'datapart' => $datapart]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Repair Detail sebelum input
    public function cekrepdet(Request $req)
    {
        $cek = DB::table('rep_ins')
            ->where('repdet_code','=',$req->input('code'))
            ->where('repdet_step','=',$req->input('step'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Repair Detail
    public function createrepdet(Request $req)
    {
        $cekData = DB::table('rep_det')
                ->where('repdet_code','=',$req->t_code)
                ->where('repdet_step','=',$req->t_step)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('rep_det')
            ->insert([
                'repdet_code'   => $req->t_code,
                'repdet_step'   => $req->t_step,
                'repdet_ins'    => $req->t_ins, 
                'repdet_part'   => $req->t_part,               
                'repdet_std'    => $req->t_std,     
                'repdet_qty'    => $req->t_qty,          
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Repair Detail Created.', 'success');
            return back();
        } else {
            toast('Repair Detail Already Registerd!!!', 'error');
            return back();
        }    
    }

    //untuk edit Repair Detail
    public function editrepdet(Request $req)
    {
        DB::table('rep_det')
        ->where('repdet_code','=',$req->te_code)
        ->where('repdet_step','=',$req->te_step)
        ->update([
            'repdet_ins'    => $req->te_ins,
            'repdet_part'   => $req->te_part,
            'repdet_std'    => $req->te_std,
            'repdet_qty'    => $req->te_qty,
            'updated_at'    => Carbon::now()->toDateTimeString(),
            'edited_by'     => Session::get('username'),
        ]);

        toast('Repair Detail Updated.', 'success');
        return back();
    }

    //untuk delete Repair Detail
    public function deleterepdet(Request $req)
    {
        DB::table('rep_det')
            ->where('repdet_code', '=', $req->d_code)
            ->delete();

        toast('Deleted Repair Detail Successfully.', 'success');
        return back();
    }

    //untuk delete Detail Repair Detail
    public function deletedetailrepdet(Request $req)
    {
        DB::table('rep_det')
            ->where('repdet_code', '=', $req->ddd_code)
            ->where('repdet_step','=',$req->ddd_desc)
            ->delete();

        toast('Deleted Repair Detail Successfully.', 'success');
        return back();
    }

    //untuk paginate Repair Detail
    public function repdetpagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $dataDetail = DB::table('rep_ins')
                ->get();

            $datarep = DB::table('rep_mstr')
                ->get();

            $datains = DB::table('ins_mstr')
                ->get();

            $datapart = DB::table('sp_mstr')
                ->get();
      
            if ($code == '' && $desc == '') {
                $data = DB::table('rep_det')
                    ->join('rep_mstr','rep_code','=','repdet_code')
                    ->select('repdet_code','rep_desc')
                    ->groupby('repdet_code','rep_desc')
                    ->orderby('repdet_code')
                    ->paginate(10);

                return view('setting.table-repair-detail', ['data' => $data, 'datarep' => $datarep, 'datains' => $datains, 'datadetail' => $dataDetail, 'datapart' => $datapart]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = 'repdet_code like "%' . $code . '%"';
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= ' and rep_desc like "%' . $desc . '%"';
                    } else {
                        $kondisi = 'rep_desc like "%' . $desc . '%"';
                    }
                }

                $data = DB::table('rep_det')
                    ->join('rep_mstr','rep_code','=','repdet_code')
                    ->select('repdet_code','rep_desc')
                    ->whereRaw($kondisi)
                    ->groupby('repdet_code','rep_desc')
                    ->orderby('repdet_code')
                    ->paginate(10);

                return view('setting.table-repair-detail', ['data' => $data, 'datarep' => $datarep, 'datains' => $datains, 'datadetail' => $dataDetail, 'datapart' => $datapart]);
            }
        }
    }

    //untuk Detail Data Repair Detail
    public function detailrepdet(Request $req)
    {
        if($req->ajax()){
            
             $data = DB::table('rep_det')
                ->where('repdet_code','=',$req->code)
                ->get();

            $datarep = DB::table('rep_mstr')
                ->get();

            $datains = DB::table('ins_mstr')
                ->get();

            $datapart = DB::table('sp_mstr')
                ->get();

            if($data){
                $output = '';
                foreach($data as $data){
                    $descRep    = $datarep->where('rep_code','=',$data->repdet_code)->first();
                    $descIns    = $datains->where('ins_code','=',$data->repdet_ins)->first();
                    $descpart   = $datapart->where('spm_code','=',$data->repdet_part)->first();
                    
                    $output .= '<tr>'.
                            '<td>'.$data->repdet_step.'</td>'.
                            '<td>'.$data->repdet_ins.'</td>'.
                            '<td>'.$descIns->ins_desc.'</td>'.
                            '<td>'.$data->repdet_part.'</td>'.
                            '<td>'.$descpart->spm_desc.'</td>'.
                            '<td>'.$data->repdet_std.'</td>'.
                            '<td>'.
                            '<a href="" class="editarea2" id="editdata" data-toggle="modal" data-target="#editModal" data-code="'.$data->repdet_code.'" data-codeDesc="'.$descRep->rep_desc.'" data-step="'.$data->repdet_step.'" data-ins="'.$data->repdet_ins.'" data-part="'.$data->repdet_part.'" data-partDesc="'.$descpart->spm_desc.'" data-std="'.$data->repdet_std.'" data-insDesc="'.$descIns->ins_desc.'" data-qty="'.$data->repdet_qty.'" ><i class="icon-table fa fa-edit fa-lg"></i></a>'.
                            '&ensp;'.
                            '<a href="" class="deletedetail" id="deletedetail" data-toggle="modal" data-target="#deletedetailmodal" data-code="'.$data->repdet_code.'" data-step="'.$data->repdet_step.'"><i class="icon-table fa fa-trash fa-lg"></i>'.
                            '</td>'.
                            '</tr>';
                }
                
                return response($output);
            }

        }
    }
/* End Repair Detail */

/* Inventory */
    //untuk menampilkan menu Inventory
    public function inv(Request $req)
    {   
        if (strpos(Session::get('menu_access'), 'MT19') !== false) {
            $data = DB::table('inv_mstr')
                ->join('site_mstrs','site_code','=','inv_site')
                ->join('loc_mstr',function($join){
                    $join->on('loc_code','=','inv_loc')
                        ->on('loc_site','=','inv_site');
                    })
                ->orderby('inv_site')
                ->paginate(10);
                //dd($data->inv_site);

            $datasite = DB::table('site_mstrs')
                ->orderby('site_code')
                ->get();

            $dataloc = DB::table('loc_mstr')
                ->orderby('loc_site')
                ->orderby('loc_code')
                ->get();

            $datasupp = DB::table('supp_mstr')
                ->orderby('supp_code')
                ->get();

            $dataspm = DB::table('sp_mstr')
                ->orderby('spm_code')
                ->get();

            return view('setting.inventory', ['datas' => $data, 'datasite' => $datasite, 'dataloc' => $dataloc, 'datasupp' => $datasupp, 'dataspm' => $dataspm]);
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //cek Inventory sebelum input
    public function cekinv(Request $req)
    {
        $cek = DB::table('inv_mstr')
            ->where('inv_site','=',$req->input('site'))
            ->Where('inv_loc','=',$req->input('loc'))
            ->Where('inv_sp','=',$req->input('spm'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Inventory
    public function createinv(Request $req)
    {
        $cek = DB::table('inv_mstr')
            ->where('inv_site','=',$req->input('site'))
            ->Where('inv_loc','=',$req->input('loc'))
            ->Where('inv_sp','=',$req->input('spm'))
            ->get();

        if ($cek->count() == 0) {
            DB::table('inv_mstr')
            ->insert([
                'inv_site'          => $req->t_site,
                'inv_loc'           => $req->t_loc, 
                'inv_sp'            => $req->t_spm,
                'inv_qty'           => $req->t_qty,
                'inv_lot'           => $req->t_lot,
                'inv_date'          => $req->t_date,
                'inv_supp'          => $req->t_supp,        
                'created_at'        => Carbon::now()->toDateTimeString(),
                'updated_at'        => Carbon::now()->toDateTimeString(),
                'edited_by'         => Session::get('username'),
            ]);

            toast('Inventory Created.', 'success');
            return back();
        } else {
            toast('Inventory Already Registered!!!', 'error');
            return back();
        }
    }

    //untuk edit Inventory
    public function editinv(Request $req)
    {

            DB::table('inv_mstr')
            ->where('inv_site','=',$req->te_site)
            ->Where('inv_loc','=',$req->te_loc)
            ->Where('inv_sp','=',$req->te_spm)
            ->update([
                'inv_qty'           => $req->te_qty,
                'inv_lot'           => $req->te_lot,
                'inv_date'          => $req->te_date,
                'inv_supp'          => $req->te_supp,               
                'updated_at'        => Carbon::now()->toDateTimeString(),
                'edited_by'         => Session::get('username'),
            ]);

            toast('Inventory Updated.', 'success');
            return back();
    }

    //untuk delete Inventory
    public function deleteinv(Request $req)
    {
        DB::table('inv_mstr')
            ->where('inv_site', '=', $req->d_site)
            ->Where('inv_loc','=',$req->d_loc)
            ->Where('inv_sp','=',$req->d_spm)
            ->delete();

        toast('Deleted Inventory Successfully.', 'success');
        return back();
    }

    //untuk paginate Inventory
    public function invpagination(Request $req)
    {

        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $site = $req->get('site');
            $loc = $req->get('loc');
            $spm = $req->get('spm');

            $datasite = DB::table('site_mstrs')
                ->orderby('site_code')
                ->get();

            $dataloc = DB::table('loc_mstr')
                ->orderby('loc_site')
                ->orderby('loc_code')
                ->get();

            $datasupp = DB::table('supp_mstr')
                ->orderby('supp_code')
                ->get();

            $dataspm = DB::table('sp_mstr')
                ->orderby('spm_code')
                ->get();
      
            if ($site == '' && $loc == '' && $spm == '') {
                $data = DB::table('inv_mstr')
                ->join('site_mstrs','site_code','=','inv_site')
                ->join('loc_mstr',function($join){
                    $join->on('loc_code','=','inv_loc')
                        ->on('loc_site','=','inv_site');
                    })
                ->orderby('inv_site')
                ->paginate(10);

                return view('setting.table-inventory', ['datas' => $data, 'datasite' => $datasite, 'dataloc' => $dataloc, 'datasupp' => $datasupp, 'dataspm' => $dataspm]);
            } else {
                $kondisi = '';
                if ($site != '') {
                    $kondisi = "inv_site like '%" . $site . "%'";
                }
                if ($loc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and inv_loc like '%" . $loc . "%'";
                    } else {
                        $kondisi = "inv_loc like '%" . $loc . "%'";
                    }
                }
                if ($spm != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and inv_sp like '%" . $spm . "%'";
                    } else {
                        $kondisi = "inv_sp like '%" . $spm . "%'";
                    }
                }
                
                $data = DB::table('inv_mstr')
                ->join('site_mstrs','site_code','=','inv_site')
                ->join('loc_mstr',function($join){
                    $join->on('loc_code','=','inv_loc')
                        ->on('loc_site','=','inv_site');
                    })
                ->whereRaw($kondisi)
                ->orderby('inv_site')
                ->paginate(10);

                return view('setting.table-inventory', ['datas' => $data, 'datasite' => $datasite, 'dataloc' => $dataloc, 'datasupp' => $datasupp, 'dataspm' => $dataspm]);
            }
        }
    }

/* End Inventory */

/* Engineer Master */
    //untuk menampilkan menu Engineer Master
    public function engmaster(Request $req) /** Blade : setting.eng */
    {   
        if (strpos(Session::get('menu_access'), 'STU01') !== false) {

            // dd($req->all());
            // $currentUrl = $req->getRequestUri(); ini dirubah karena tidak dapat digunakan untuk fungsi Search di Form
            $serverURL = (new ServerURL())->defineServerUrl();
            $currentUrl = $req->getRequestUri();
            $parsedUrl = parse_url($currentUrl);
            $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
            $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

            $s_code = $req->s_code;
            $s_desc = $req->s_desc;
            $s_dept = $req->s_dept;
            $s_deptsub = $req->s_deptsub; 
            $s_role = $req->s_role;

            $datauser = DB::table('users')
                ->orderby('username');
            // dd($datauser->get());

            if($s_code) {
                $datauser = $datauser->where('username','like','%'.$s_code.'%');
            }
            if($s_desc) {
                $datauser = $datauser->where('name','like','%'.$s_desc.'%');
            }
            if($s_dept) {
                $datauser = $datauser->where('dept_user','like','%'.$s_dept.'%');
            }
            if($s_deptsub) {
                $datauser = $datauser->where('dept_sub_user','like','%'.$s_deptsub.'%');
            }
            if($s_role) {
                $datauser = $datauser->where('role_user','like','%'.$s_role.'%');
            }
           

            $datauser = $datauser->paginate(10);

            $data = DB::table('eng_mstr')
                ->orderby('eng_code')
                ->get();

            $datarole = DB::table('roles')
                ->orderby('role_code')
                ->get();

            $dataeng = DB::table('dept_mstr')
                ->orderby('dept_code')
                ->get();

            $dataskill = DB::table('skill_mstr')
                ->orderby('skill_desc')
                ->get();

            $datasite = DB::table('site_mstrs')
                ->orderBy('site_code')
                ->get();

            $dataloc = DB::table('loc_mstr')
                ->join('site_mstrs','site_code','=','loc_site')
                ->whereNotNull('loc_code')
                ->where('loc_code','<>','')
                ->orderBy('loc_site')
                ->orderBy('loc_code')
                ->get();

            $datadeptsub = DB::table('sub_dept_mstr')
                ->orderBy('sub_code')
                ->get();

            return view('setting.eng', compact('data','datarole','dataeng', 
                'dataskill','datauser','dataloc','datasite','datadeptsub',
                'isFav', 'menuMaster','s_code','s_desc','s_dept','s_deptsub','s_role'));
        } else {
            toast('You do not have menu access, please contact admin.', 'error');
            return back();
        }
    }

    //untuk search sub department sesuai dengan department yang dipilih
    public function searchsubdept(Request $req)
    {
        if ($req->ajax()) {
            $code = $req->get('code');
      
            $data = DB::table('sub_dept_mstr')
                    ->leftJoin('dept_mstr','dept_mstr.id','=','sub_dept_mstr.dept_id')
                    ->where('dept_code','=',$code)
                    ->get();

            $output = '<option value="" >Select</option>';
            foreach($data as $data){

                $output .= '<option value="'.$data->sub_code.'" >'.$data->sub_code.' -- '.$data->sub_desc.'</option>';
                           
            }

            return response($output);
        }
    }

    //untuk search role by access
    public function engrole(Request $req)
    {
        if ($req->ajax()) {
            $data = DB::table('roles')
                    ->where('role_access','=',$req->code)
                    ->get();

            $output = '<option value="" >Select</option>';
            foreach($data as $data){
                $output .= '<option value="'.$data->role_code.'" >'.$data->role_code.'</option>';              
            }

            return response($output);
        }
    }

    //untuk cek Engineering Master
    public function cekeng(Request $req)
    {
        
        $cekeng = DB::table('eng_mstr')
            ->where('eng_code','=',$req->code)
            ->count();

        $cekuser = DB::table('users')
            ->where('username','=',$req->code)
            ->count();

        $cek = $cekeng + $cekuser;
            
            
        if ($cek == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Engineer Master
    public function createeng(Request $req)
    {
        $jumlahusernow = DB::table('users')
                        ->count();
        
        //pembatas hanya bisa bikin 10 user
        // Untuk DK tidak dibatasi usernya
        // if($jumlahusernow >= 14){  // ID user actavis 25, ID user imi 4
        //     toast('Max. Create 14 User Only', 'error');
        //     return back();
        // }

        $skill = "";
        if(!is_null($req->enjiners)) {
            $flg = 0;
            foreach ($req->enjiners as $ds) {
                $skill = $skill . $req->enjiners[$flg] . "," ;
                $flg += 1;
            }
        }   

        if($req->t_active == 'Yes') {
            if ($req->t_role == '' && $req->password == '') {
                toast('Please input role and password!!', 'error');
                return back();
            }            

            if ($req->t_role != '' && $req->password == '') {
                toast('Please input password!!', 'error');
                return back();
            }

            if ($req->t_role == '' && $req->password != '') {
                toast('Please input role!!', 'error');
                return back();
            }
        }

        $this->validate($req, [
            'password' => 'max:15|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'max:15'
        ], [
            'username.unique' => 'Username sudah terdaftar',
            'password.same' => 'Password & Confirm Password Harus sama'
        ]);

        $cekData = DB::table('users')
                    ->where('username','=',$req->t_code)
                    ->get();

        if ($cekData->count() > 0) {
            toast('Engineer '.$req->t_code.' Already Registerd', 'error');
            return back();
        } else {
            $filename = "";
            if($req->hasFile('t_photo')){
                $file = $req->file('t_photo');
                $filename = $req->t_code;
                $ext = $file->getClientOriginalExtension();

                $ekstensi_diperbolehkan = array('png','jpg');
                if(in_array($ext, $ekstensi_diperbolehkan) === true){

                    // Simpan File Upload pada Public
                    $savepath = public_path('/upload/');
                    $file->move($savepath, $filename);
                    
                } else {
                    toast('File Extentions Allowed : .jpg and .png', 'error');
                    return back();
                }
            }

            if (is_null($req->t_role)) {
                $role = '';
                $pass = '';
            } else {
                $role = $req->t_role;
                $pass = $req->password;
            }

            if($req->t_acc == 'Engineer') {
                DB::table('eng_mstr')
                ->insert([
                    'eng_code'          => $req->t_code,
                    'eng_desc'          => $req->t_desc,
                    'eng_dept'          => $req->t_dept,
                    'eng_dept_sub'          => $req->t_deptsub,
                    'approver'          => $req->t_app,
                    'eng_birth_date'    => $req->t_brt_date,
                    'eng_active'        => $req->t_active,
                    'eng_join_date'     => $req->t_join,
                    'eng_rate_hour'     => $req->t_rate,
                    'eng_skill'         => $skill,
                    'eng_email'         => $req->t_email,
                    'eng_role'          => $req->t_role,
                    'eng_photo'         => $filename,
                    'eng_role'          => $req->t_role,
                    // 'eng_site'          => $req->t_site,
                    // 'eng_loc'           => $req->t_loc,
                    'created_at'        => Carbon::now()->toDateTimeString(),
                    'updated_at'        => Carbon::now()->toDateTimeString(),
                    'edited_by'         => Session::get('username'),
                ]);

            }

            // input ke tabel user
            $dataarray = array(
                'username'      => $req->t_code,
                'name'          => $req->t_desc,
                'dept_user'     => $req->t_dept,
                'dept_sub_user'     => $req->t_deptsub,
                'email_user'    => $req->t_email,
                'role_user'     => $req->t_role,
                'active'        => $req->t_active,
                'access'        => $req->t_acc,
                'password'      => Hash::make($req->password),
                'created_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                'edited_by'     => Session::get('username'),
                );
            DB::table('users')->insert($dataarray);

            /** Menyimpan data di tabel history perubahan data user */
            DB::table('user_hist')
                ->insert([
                    'ush_username'          => $req->t_code,
                    'ush_name'          => $req->t_desc,
                    'ush_dept'          => $req->t_dept,
                    'ush_dept_sub'          => $req->t_deptsub,
                    'ush_approver'          => $req->t_app,
                    'ush_birth_date'    => $req->t_brt_date,
                    'ush_active'        => $req->t_active,
                    'ush_access'        => $req->t_acc,
                    'ush_join_date'     => $req->t_join,
                    'ush_rate_hour'     => $req->t_rate,
                    'ush_skill'         => $skill,
                    'ush_email'         => $req->t_email,
                    'ush_role'          => $req->t_role,
                    'ush_photo'         => $filename,
                    'ush_password'      => Hash::make($req->password),
                    'ush_action'        => 'User Created',
                    'ush_editby'         => Session::get('username'),
                    'created_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                    'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                ]);

            toast('User Created.', 'success');
            return back();
            
        }
    }

    //untuk cari skill berdasar eng code
    public function engskill(Request $req)
    {
        if($req->ajax()){
            $eng = DB::table('skill_mstr')
                ->get();

            $array = json_decode(json_encode($eng), true);

            return response()->json($array);
        }
    }

    //untuk search role by access
    public function engrole2(Request $req)
    {
        if ($req->ajax()) {
            $data = DB::table('roles')
                    ->where('role_access','=',$req->acc)
                    ->get();

            $dataUser = DB::table('users')
                        ->where('username','=',$req->code)
                        ->first();

            $output = '<option value="" >Select</option>';
            foreach($data as $data){
                if ($data->role_code == $dataUser->role_user) {
                    $output .= '<option value="'.$data->role_code.'" selected>'.$data->role_code.'</option>';    
                } else {
                    $output .= '<option value="'.$data->role_code.'" >'.$data->role_code.'</option>';    
                }          
            }

            return response($output);
        }
    }

    //untuk search sub departemen untuk menu edit yang ada di user maintenance
    public function searchsubdeptedit(Request $req)
    {
        if ($req->ajax()) {
            $code = $req->get('code');
            $sub = $req->get('sub');
      
            $data = DB::table('sub_dept_mstr')
                    ->leftJoin('dept_mstr','dept_mstr.id','=','sub_dept_mstr.dept_id')
                    ->where('dept_code','=',$code)
                    ->get();

            $output = '<option value="" >Select</option>';
            foreach($data as $data){
                if ($data->sub_code == $sub) {
                    $output .= '<option value="'.$data->sub_code.'" selected>'.$data->sub_code.' -- '.$data->sub_desc.'</option>';  
                } else {
                    $output .= '<option value="'.$data->sub_code.'" >'.$data->sub_code.' -- '.$data->sub_desc.'</option>';
                }              
            }

            return response($output);
        }
    }

    //untuk edit Engineer Master
    public function editeng(Request $req)
    {
        // dd($req->all());
        $skill = "";
        if(!is_null($req->te_enjiners)) {
            $flg = 0;
            foreach ($req->te_enjiners as $ds) {
                $skill = $skill . $req->te_enjiners[$flg] . "," ;
                $flg += 1;
            }
        }

        $this->validate($req, [
            'te_pass' => 'max:15|required_with:te_pass_confirmation|same:te_pass_confirmation',
            'te_pass_confirmation' => 'max:15'
        ], [
            'username.unique' => 'Username sudah terdaftar',
            'password.same' => 'Password & Confirm Password Harus sama'
        ]);

        if($req->te_acc == 'Engineer') {
            if($req->hasFile('te_photo')){  
                $file = $req->file('te_photo');
                $filename = $req->te_code;
                $ext = $file->getClientOriginalExtension();

                $ekstensi_diperbolehkan = array('png','jpg');
                if(in_array($ext, $ekstensi_diperbolehkan) === true){
                    DB::table('eng_mstr')
                    ->updateOrInsert(
                        ['eng_code' => $req->te_code],
                        [
                        'eng_desc'          => $req->te_desc,
                        'eng_dept'          => $req->te_dept,
                        'eng_dept_sub'          => $req->te_deptsub,
                        'approver'          => $req->te_app,
                        'eng_birth_date'    => $req->te_brt_date,
                        'eng_active'        => $req->te_active,
                        'eng_join_date'     => $req->te_join,
                        'eng_rate_hour'     => $req->te_rate,
                        'eng_skill'         => $skill,
                        'eng_email'         => $req->te_email,
                        'eng_role'          => $req->te_role,
                        'eng_photo'         => $filename,
                        // 'eng_site'          => $req->te_site,
                        // 'eng_loc'           => $req->te_loc,
                        'updated_at'        => Carbon::now()->toDateTimeString(),
                        'edited_by'         => Session::get('username'),
                    ]);

                    // Simpan File Upload pada Public
                    $savepath = public_path('/upload/');
                    $file->move($savepath, $filename);
                    
                } else {
                    toast('File Extentions Allowed : .jpg and .png', 'error');
                    return back();
                }
            } else {
                DB::table('eng_mstr')
                ->updateOrInsert(
                    ['eng_code' => $req->te_code],
                    [
                    'eng_desc'          => $req->te_desc,
                    'eng_dept'          => $req->te_dept,
                    'eng_dept_sub'          => $req->te_deptsub,
                    'approver'          => $req->te_app,
                    'eng_birth_date'    => $req->te_brt_date,
                    'eng_active'        => $req->te_active,
                    'eng_join_date'     => $req->te_join,
                    'eng_rate_hour'     => $req->te_rate,
                    'eng_skill'         => $skill,
                    'eng_email'         => $req->te_email,
                    'eng_role'          => $req->te_role,
                    // 'eng_site'          => $req->te_site,
                    // 'eng_loc'           => $req->te_loc,
                    'updated_at'        => Carbon::now()->toDateTimeString(),
                    'edited_by'         => Session::get('username'),
                ]);
            }
        } else {
            DB::table('eng_mstr')
                ->where('eng_code','=',$req->te_code)
                ->update([
                    'eng_active'        => 'No',
                ]);
        }

        //input ke tabel user
        if ($req->te_active == 'Yes') {
            if(is_null($req->te_pass)) {
                // dd($req->all());
                DB::table('users')
                ->where('username','=',$req->te_code)
                ->update([
                    'name'          => $req->te_desc,
                    'email_user'    => $req->te_email,
                    'role_user'     => $req->te_role,
                    'dept_user'     => $req->te_dept,
                    'dept_sub_user'     => $req->te_deptsub,
                    'active'        => $req->te_active,
                    'access'        => $req->te_acc,
                    'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                    'edited_by'     => Session::get('username'),
                ]);
            } else {
                DB::table('users')
                ->where('username','=',$req->te_code)
                ->update([
                    'name'          => $req->te_desc,
                    'email_user'    => $req->te_email,
                    'role_user'     => $req->te_role,
                    'dept_user'     => $req->te_dept,
                    'dept_sub_user'     => $req->te_deptsub,
                    'active'        => $req->te_active,
                    'access'        => $req->te_acc,
                    'password'      => Hash::make($req->te_pass),
                    'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                    'edited_by'     => Session::get('username'),
                ]);
            }
        } else {
            DB::table('users')
            ->where('username','=',$req->te_code)
            ->update([
                'name'          => $req->te_desc,
                'email_user'    => $req->te_email,
                'role_user'     => $req->te_role,
                'dept_user'     => $req->te_dept,
                'dept_sub_user'     => $req->te_deptsub,
                'active'        => $req->te_active,
                'access'        => $req->te_acc,
                'password'      => Hash::make('no'),
                'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);
        }

        /** Menyimpan data di tabel history perubahan data user */
        if(is_null($req->te_pass)) {
            /** Mengambil password dari data yang sebelumnya */
            $cekpassuser = DB::table('users')
                ->where('username','=',$req->te_code)
                ->first();
            $pass = $cekpassuser->password;
        } else {
            $pass = Hash::make($req->te_pass);
        }
        DB::table('user_hist')
            ->insert([
                'ush_username'      => $req->te_code,
                'ush_name'          => $req->te_desc,
                'ush_dept'          => $req->te_dept,
                'ush_dept_sub'          => $req->te_deptsub,
                'ush_approver'      => $req->te_app,
                'ush_birth_date'    => $req->te_brt_date,
                'ush_active'        => $req->te_active,
                'ush_access'        => $req->te_acc,
                'ush_join_date'     => $req->te_join,
                'ush_rate_hour'     => $req->te_rate,
                'ush_skill'         => $skill,
                'ush_email'         => $req->te_email,
                'ush_role'          => $req->te_role,
                'ush_photo'         => '',
                'ush_password'      => $pass,
                'ush_action'        => 'User Updated',
                'ush_editby'        => Session::get('username'),
                'created_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                'updated_at'    => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
            ]);

        toast('User Data Updated.', 'success');
        return back();
    }

    //untuk delete Engineer Master
    public function deleteeng(Request $req)
    {
        /** Pengecekan user apakah sudah ada transaksi atau belum. kalau sudah ada untuk transaksi maka tidak boleh dihapus */
        /** Pengecekan di transaksi Service Request */
        $ceksr = DB::table('service_req_mstr')
            ->whereSrReqBy($req->d_code)
            ->count();

        /** Pengecekan di transaksi Work Order */
        $cekwo = DB::table('wo_mstr')
            ->whereWoCreatedby($req->d_code)
            ->orWhere('Wo_releasedby','=',$req->d_code)
            ->count();

        if($ceksr > 0) {
            toast('User cannot be deleted. User has already been used in Service Request transactions.', 'error');
            return back();
        } elseif($cekwo > 0) {
            toast('User cannot be deleted. User has already been used in Service RequestWork Order transactions.', 'error');
            return back();
        } else {
            DB::table('users')
                ->where('username', '=', $req->d_code)
                ->delete();

            DB::table('eng_mstr')
                ->where('eng_code', '=', $req->d_code)
                ->delete();

            /** Menyimpan data di tabel history perubahan data user */
            DB::table('user_hist')
                ->insert([
                    'ush_username'      => $req->d_code,
                    'ush_action'        => 'User Deleted',
                    'ush_editby'        => Session::get('username'),
                    'created_at'        => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                    'updated_at'        => Carbon::now('ASIA/JAKARTA')->toDateTimeString(),
                ]);

            toast('Deleted User Successfully.', 'success');
            return back();
        }
    }

    //untuk paginate Engineer Master
    public function engpagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');
            $dept = $req->get('dept');
            $role = $req->get('role');

            $data = DB::table('eng_mstr')
                ->orderby('eng_code')
                ->get();

            $datarole = DB::table('roles')
                ->orderby('role_code')
                ->get();

            $dataeng = DB::table('dept_mstr')
                ->orderby('dept_code')
                ->get();

            $dataskill = DB::table('skill_mstr')
                ->orderby('skill_desc')
                ->get();

      
            if ($code == '' && $desc == '' && $dept == '' && $role == '') {
                $datauser = DB::table('users')
                    ->orderby('username')
                    ->paginate(10);

                return view('setting.table-eng', ['data' => $data, 'datarole' => $datarole, 'dataeng' => $dataeng, 'dataskill' => $dataskill, 'datauser' => $datauser]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "username like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and name   like '%" . $desc . "%'";
                    } else {
                        $kondisi = "name like '%" . $desc . "%'";
                    }
                }
                if ($dept != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and dept_user like '%" . $dept . "%'";
                    } else {
                        $kondisi = "dept_user like '%" . $dept . "%'";
                    }
                }
                if ($role != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and role_user like '%" . $role . "%'";
                    } else {
                        $kondisi = "role_user like '%" . $role . "%'";
                    }
                }
                //dd($kondisi);
                $datauser = DB::table('users')
                ->whereRaw($kondisi)
                ->orderby('username')
                ->paginate(10);

                return view('setting.table-eng', ['data' => $data, 'datarole' => $datarole, 'dataeng' => $dataeng, 'dataskill' => $dataskill, 'datauser' => $datauser]);
            }
        }
    }
/* End Engineer Master */

/* Departemen Master */
    //untuk menampilkan menu departmene
    public function deptmaster(Request $req)
    {

        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        $data = DB::table('dept_mstr')
            ->orderby('dept_code')
            ->paginate(10);

        return view('setting.departemen', ['data' => $data, 'isFav' => $isFav, 'menuMaster' => $menuMaster]);
    }

    //cek departmene sebelum input
    public function cekdept(Request $req)
    {
        $cek = DB::table('dept_mstr')
            ->where('dept_code','=',$req->input('code'))
            ->orWhere('dept_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create departmene
    public function createdept(Request $req)
    {
        $cekData = DB::table('dept_mstr')
                ->where('dept_code','=',$req->t_code)
                ->orWhere('dept_desc','=',$req->t_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('dept_mstr')
            ->insert([
                'dept_code'   => $req->t_code,
                'dept_desc'   => $req->t_desc,
                'dept_running_nbr' => $req->t_runningnbr,                
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Departemen Created.', 'success');
            return back();
        } else {
            toast('Departemen is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit departmene
    public function editdept(Request $req)
    {
        $cekData = DB::table('dept_mstr')
                ->where('dept_desc','=',$req->te_desc)
                ->where('dept_code','<>',$req->te_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('dept_mstr')
            ->where('dept_code','=',$req->te_code)
            ->update([
                'dept_desc'   => $req->te_desc,
                'dept_running_nbr' => $req->te_runningnbr,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Departemen Updated.', 'success');
            return back();
        } else {
            toast('Departemen Description is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk delete departmene
    public function deletedept(Request $req)
    {
        //cek data dari asset
        $cekData = DB::table('users')
                ->where('dept_user','=',$req->d_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('dept_mstr')
            ->where('dept_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Departemen Successfully.', 'success');
            return back();
        } else {
            toast('Departemen Can Not Deleted!!!', 'error');
            return back();
        }
    }

    //untuk paginate departmene
    public function deptpagination(Request $req)
    {
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            if ($code == '' && $desc == '') {
                $data = DB::table('dept_mstr')
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-departemen', ['data' => $data]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "dept_code like '%" . $code ."%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= "and dept_desc like '%'" . $desc . "%'";
                    } else {
                        $kondisi = "dept_desc like '%" . $desc . "%'";
                    }
                }
                //dd($kondisi);
                $data = DB::table('dept_mstr')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-departemen', ['data' => $data]);
            }
        }
    }

/* End Departemen Master */

/* Picking Logic */
    public function picklogic(){
        $runningdata = DB::table('running_mstr')
                        ->first();

        $rsdata = DB::table('pick_mstr')
            ->wherePick_active(1)
            ->get();
            
        if (!$rsdata->isEmpty()) {
            $pick = $rsdata->first();
            $pick = $pick->pick_code;
         } else {
            $pick = "loc";
         }
        
        return view('setting.picklogic', ['pick' => $pick]);
    }

    public function picksave(Request $req){
        //dd($req->all());

        DB::table('pick_mstr')
            ->where('pick_active','=',1)
            ->update([
                'pick_active'   => 0,
            ]);

        DB::table('pick_mstr')
            ->insert([
                'pick_code' => $req->rdlogic,
                'pick_active' => 1,
                'created_at' => Carbon::now()->toDateTimeString(),
                'pick_edited_by' => Session::get('username'),                    
            ]);

        toast("Data is Successfully Updated !", "success");
                
        return back();
    }
/* End Picking Logic */

    // running number master
    public function runningmstr(){
        $runningdata = DB::table('running_mstr')
                        ->first();

        if(empty($runningdata)){
            $prefixsr = 'SR';
            $prefixwo = 'WO';
            $prefixwt = 'WT';
            $prefixwd = 'WD';
            $prefixbo = 'BO';
            $prefixrt = 'RT';
            $srnbr = '000000';
            $wtnbr = '000000';
            $wonbr = '000000';
            $wdnbr = '000000';
            $bonbr = '000000';
            $rtnbr = '000000';
            $year = Carbon::now()->format('y');

            DB::table('running_mstr')
                ->insert([
                        'sr_prefix' => $prefixsr,
                        'wo_prefix' => $prefixwo,
                        'wt_prefix' => $prefixwt,
                        'wd_prefix' => $prefixwd,
                        'bo_prefix' => $prefixbo,
                        'rt_prefix' => $prefixrt,
                        'sr_nbr' => $srnbr,
                        'wo_nbr' => $wonbr,
                        'wt_nbr' => $wtnbr,
                        'wd_nbr' => $wdnbr,
                        'bo_nbr' => $bonbr,
                        'rt_nbr' => $rtnbr,
                        'year' => $year,
                ]);

            $runningdata = DB::table('running_mstr')
                ->first();
        }

        return view('setting.runningmaster', ['alert' => $runningdata]);
    }

    public function updaterunning(Request $req){
//dd($req->all());
        $prefixsr = $req->input('srprefix');
        $srnbr = $req->input('srnumber');
        $prefixwo = $req->input('woprefix');
        $prefixwt = $req->input('wtprefix');
        $prefixwd = $req->input('wdprefix');
        $prefixbo = $req->input('boprefix');
        $prefixrs = $req->input('rsprefix');
        $prefixrt = $req->input('rtprefix');
        $wtnbr = $req->input('wtnumber');
        $wonbr = $req->input('wonbr');
        $wdnbr = $req->input('wdnumber');
        $bonbr = $req->input('bonumber');
        $rsnbr = $req->input('rsnumber');
        $rtnbr = $req->input('rtnumber');
        $year = Carbon::now()->format('y');

        $data = DB::Table('running_mstr')
                    ->count();

        if($data == 0){
            DB::table('running_mstr')
                ->insert([
                        'sr_prefix' => $prefixsr,
                        'wo_prefix' => $prefixwo,
                        'wt_prefix' => $prefixwt,
                        'wd_prefix' => $prefixwd,
                        'bo_prefix' => $prefixbo,
                        'rs_prefix' => $prefixrs,
                        'rt_prefix' => $prefixrt,
                        'sr_nbr' => $srnbr,
                        'wo_nbr' => $wonbr,
                        'wt_nbr' => $wtnbr,
                        'wd_nbr' => $wdnbr,
                        'bo_nbr' => $bonbr,
                        'rs_nbr' => $rsnbr,
                        'rt_nbr' => $rtnbr,
                        'year' => $year,
                        
                ]);

            toast("Data is Successfully Updated !", "success");
                  
            return back();
        }else{

            DB::table('running_mstr')
                ->update([
                    'sr_prefix' => $prefixsr,
                    'wo_prefix' => $prefixwo,
                    'wt_prefix' => $prefixwt,
                    'wd_prefix' => $prefixwd,
                    'bo_prefix' => $prefixbo,
                    'rs_prefix' => $prefixrs,
                    'rt_prefix' => $prefixrt,
                    'sr_nbr' => $srnbr,
                    'wo_nbr' => $wonbr,
                    'wt_nbr' => $wtnbr,
                    'wd_nbr' => $wdnbr,
                    'bo_nbr' => $bonbr,
                    'rs_nbr' => $rsnbr,
                    'rt_nbr' => $rtnbr,
                    'year' => $year,
                ]);

            toast("Data is Successfully Updated !", "success");
                  
            return back();
        }       
    }

/* Skill Master */
    //untuk menampilkan menu Skill
    public function skillmaster(Request $req)
    {   
        $s_code = $req->s_code;
        $s_desc = $req->s_desc;

        // $currentUrl = $req->getRequestUri(); ini dirubah karena tidak dapat digunakan untuk fungsi Search di Form
        $serverURL = (new ServerURL())->defineServerUrl();
        $currentUrl = $req->getRequestUri();
        $parsedUrl = parse_url($currentUrl);
        $menuMaster = MenuMaster::where('menu_url', substr(str_replace($serverURL, '', $parsedUrl['path']), 1))->first();
        $isFav = FavMenu::where('fm_user_id', Auth::user()->id)
            ->where('fm_mm_id', $menuMaster->id)->first();

        $data = DB::table('skill_mstr')
            ->orderby('skill_code');
        
        if($s_code) {
            $data = $data->where('skill_code','like','%'.$s_code.'%');
        }
        if($s_desc) {
            $data = $data->where('skill_desc','like','%'.$s_desc.'%');
        }
        
        $data = $data->paginate(10);

        return view('setting.skillb', compact('data','isFav','menuMaster'));

    }

    //cek Skill sebelum input
    public function cekskill(Request $req)
    {
        $cek = DB::table('skill_mstr')
            ->where('skill_code','=',$req->input('code'))
            ->orWhere('skill_desc','=',$req->input('desc'))
            ->get();

        if ($cek->count() == 0) {
            return "tidak";
        } else {
            return "ada";
        }
    }

    //untuk create Skill
    public function createskill(Request $req)
    {
        $cekData = DB::table('skill_mstr')
                ->where('skill_code','=',$req->t_code)
                ->orWhere('skill_desc','=',$req->t_desc)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('skill_mstr')
            ->insert([
                'skill_code'   => $req->t_code,
                'skill_desc'   => $req->t_desc, 
                'skill_certification'   => $req->t_cer,                    
                'created_at'    => Carbon::now()->toDateTimeString(),
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Skill Created.', 'success');
            return back();
        } else {
            toast('Skill is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk edit Skill
    public function editskill(Request $req)
    {
        $cekData = DB::table('skill_mstr')
                ->where('skill_desc','=',$req->te_desc)
                ->where('skill_code','<>',$req->te_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('skill_mstr')
            ->where('skill_code','=',$req->te_code)
            ->update([
                'skill_desc'   => $req->te_desc,
                'skill_certification'   => $req->te_cer,
                'updated_at'    => Carbon::now()->toDateTimeString(),
                'edited_by'     => Session::get('username'),
            ]);

            toast('Skill Updated.', 'success');
            return back();
        } else {
            toast('Skill Description is Already Registerd!!', 'error');
            return back();
        }
    }

    //untuk delete Skill
    public function deleteskill(Request $req)
    {
        // cek data dari engineering
        $cekData = DB::table('eng_mstr')
                 ->select('eng_skill')
                 ->where('eng_skill','<>','')
                 ->get();

        foreach($cekData as $cd) {
            $a = explode(",", $cd->eng_skill);
            if (in_array($req->d_code, $a)) {
                toast('Skill Can Not Deleted!!!', 'error');
                return back();
            }
        }

        // if ($cekData->count() == 0) {
            DB::table('skill_mstr')
            ->where('skill_code', '=', $req->d_code)
            ->delete();

            toast('Deleted Skill Successfully.', 'success');
            return back();
        // } else {
        //     toast('Skill Can Not Deleted!!!', 'error');
        //     return back();
        // }
    }

    //untuk paginate Skill
    public function skillpagination(Request $req)
    {
        //dd($req->all());
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_type = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            if ($code == '' && $desc == '') {
                $data = DB::table('skill_mstr')
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-skillb', ['data' => $data]);
            } else {
                //dd($kondisi);
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "skill_code LIKE '%".$code."%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and skill_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "skill_desc like '%" . $desc . "%'";
                    }
                }
                
                $data = DB::table('skill_mstr')
                    ->whereRaw($kondisi)
                    ->orderBy($sort_by, $sort_type)
                    ->paginate(10);

                return view('setting.table-skillb', ['data' => $data]);
            }
        }
    }
/* End Skill Master */
    

/* Repair Code */
    public function repcode()
    {
        $data = DB::table('rep_master')
            ->orderby('repm_code')
            ->paginate(10);
            // dd($data);
        $datains = DB::table('ins_mstr')
            ->orderby('ins_code')
            ->get();

        $datarepair = DB::table('rep_master')
            ->orderby('repm_code')
            ->get();

        return view('setting.repair-code', ['data' => $data, 'datains' => $datains, 'datarepair' => $datarepair]);     
    }
    
    public function createrepcode(Request $req)
    {    
        // harus ada inputan anak
        if (is_null($req->line)) {
            toast('Please Line Items!!', 'error');
             return back();
        }

        if (is_null($req->barang)) {
            toast('Please Input Repair Code!!', 'error');
             return back();
        }

        $cekData = DB::table('rep_master')
                ->where('repm_code','=',$req->t_code)
                ->get();

        if ($cekData->count() == 0) {
            DB::table('rep_master')
            ->insert([
                'repm_code'      => $req->t_code,
                'repm_desc'      => $req->t_desc,                                              
                'repm_ref'       => $req->t_ref,                                
                'created_at'     => Carbon::now()->toDateTimeString(),
                'updated_at'     => Carbon::now()->toDateTimeString(),
                'edited_by'      => Session::get('username'),
            ]);

            $flg = 0;
            foreach($req->barang as $barang){
                DB::table('rep_det')
                ->insert([
                    'repdet_code'  => $req->t_code,
                    'repdet_step'  => $req->line[$flg],
                    'repdet_ins'   => $req->barang[$flg],   
                    'created_at'   => Carbon::now()->toDateTimeString(),
                    'updated_at'   => Carbon::now()->toDateTimeString(),
                    'edited_by'    => Session::get('username'),             
                ]);

                $flg += 1;
            }    

            toast('Repair Code Created.', 'success');
            return back();
        } else {
            toast('Repair Code is Already Registerd!!', 'error');
            return back();
        }
    }

    //menampilkan detail edit
    public function editdetailrepcode(Request $req)
    {
        if ($req->ajax()) {
            $data = DB::table('rep_det')
                    ->leftjoin('ins_mstr','ins_code','=','repdet_ins')
                    ->where('repdet_code','=',$req->code)
                    ->orderby('repdet_step')
                    ->get();

            $dataIns = DB::table('ins_mstr')
                    ->get();

            $output = '';
            foreach ($data as $data) {
                $output .= '<tr>'.
                '<td>'.$data->repdet_step.'</td>'.
                '<td>'.$data->repdet_ins.' -- '.$data->ins_desc.'</td>'.
                '<td><input type="checkbox" name="cek[]" class="cek" id="cek" value="0">'.
                '<input type="hidden" name="tick[]" id="tick" class="tick" value="0">'.
                '<input type="hidden" name="einsesc[]" id="einsesc" class="einsesc" value="'.$data->ins_desc.'"></td>'.
                '<input type="hidden" name="barang[]" id="barang" class="barang" value="'.$data->repdet_ins.'"></td>'.
                '<input type="hidden" name="line[]" id="line" class="line" value="'.$data->repdet_step.'"></td>'.
                '</tr>';
            }

            return response($output);
        }
    }
    
    public function editrepcode(Request $req)
    {
        DB::table('rep_master')
        ->where('repm_code','=',$req->te_code)
        ->update([
            'repm_desc'   => $req->te_desc,
            'repm_ref'   => $req->te_ref,
            'updated_at'    => Carbon::now()->toDateTimeString(),
            'edited_by'     => Session::get('username'),
        ]);

        DB::table('rep_det')
        ->where('repdet_code','=',$req->te_code)
        ->delete();

        $flg = 0;
        foreach($req->barang as $barang){
            /* tick = 0 --> tidak dicentang delete, dan di save */
            if ($req->tick[$flg] == 0) {
                DB::table('rep_det')
                ->insert([
                    'repdet_code'  => $req->te_code,
                    'repdet_step'  => $req->line[$flg],
                    'repdet_ins'   => $req->barang[$flg],   
                    'created_at'   => Carbon::now()->toDateTimeString(),
                    'updated_at'   => Carbon::now()->toDateTimeString(),
                    'edited_by'    => Session::get('username'),             
                ]);
            }            

            $flg += 1;
        }    

        toast('Repair Group Updated.', 'success');
        return back();
    }

     public function deleterepcode(Request $req)
    {
            
        DB::table('rep_master')
            ->where('repm_code', '=', $req->d_code)
            ->delete();

         DB::table('rep_det')
            ->where('repdet_code', '=', $req->d_code)
            ->delete();

        toast('Deleted Repair Code Successfully.', 'success');
        return back();
    }
    
    public function repcodepagination(Request $req)
    {
        //dd($req->all());
        if ($req->ajax()) {
            $sort_by = $req->get('sortby');
            $sort_group = $req->get('sorttype');
            $code = $req->get('code');
            $desc = $req->get('desc');

            $datarepair = DB::table('rep_master')
                ->orderby('repm_code')
                ->get();
      
            if ($code == '' && $desc == '') {
                $data = DB::table('rep_master')
                    ->orderby('repm_code')
                    ->paginate(10);

                return view('setting.table-repair-code', ['data' => $data,'datarepair' => $datarepair]);
            } else {
                $kondisi = '';
                if ($code != '') {
                    $kondisi = "repm_code like '%" . $code . "%'";
                }
                if ($desc != '') {
                    if ($kondisi != '') {
                        $kondisi .= " and repm_desc like '%" . $desc . "%'";
                    } else {
                        $kondisi = "repm_desc like '%" . $desc . "%'";
                    }
                }
                $data = DB::table('rep_master')
                    ->whereRaw($kondisi)
                    ->orderby('repm_code')
                    ->paginate(10);

                return view('setting.table-repair-code', ['data' => $data,'datarepair' => $datarepair]);
            }
        }
    }
/* End Repair Code */

}
