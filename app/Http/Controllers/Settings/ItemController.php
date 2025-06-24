<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Item;
use App\Services\ServerURL;
use App\Services\WSAServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $menuMaster = (new ServerURL())->currentURL($request);
        $items = Item::orderBy('im_item_part')->get();

        return view('setting.items.index', compact('menuMaster', 'items'));
    }


    public function loadItem(Request $request)
    {
        $dataItems = (new WSAServices())->wsaitem();
        if ($dataItems[0] == 'true') {
            DB::beginTransaction();

            try {
                $dataItems = $dataItems[1];
                $currentUser = Auth::user()->id;

                foreach ($dataItems as $item) {
                    // Cek dulu supplier code nya ada atau engga.
                    $itemExists = Item::where('im_item_part', $item->t_part)
                        ->first();

                    if ($itemExists) {
                        $itemExists->im_item_part = $item->t_part;
                        $itemExists->im_item_desc = strval($item->t_desc);
                        $itemExists->im_item_um = strval($item->t_um);
                        $itemExists->im_item_prod_line = strval($item->t_prod_line);
                        $itemExists->im_item_type = strval($item->t_part_type);
                        $itemExists->im_item_isRfq = 0;
                        $itemExists->im_item_group = strval($item->t_group);
                        $itemExists->im_item_price = (float) $item->t_price;
                        $itemExists->im_item_promo = strval($item->t_promo);
                        $itemExists->im_item_design = strval($item->t_dsgn_grp);
                        $itemExists->im_item_acc = strval($item->t_acc);
                        $itemExists->im_item_subacc = strval($item->t_subacc);
                        $itemExists->im_item_costcenter = strval($item->t_cc);
                        $itemExists->load_by_id = $currentUser;
                        $itemExists->updated_by = $currentUser;
                        $itemExists->save();
                    } else {
                        // Create supplier baru
                        $newItem = new Item();
                        $newItem->im_item_part = strval($item->t_part);
                        $newItem->im_item_desc = strval($item->t_desc);
                        $newItem->im_item_um = strval($item->t_um);
                        $newItem->im_item_prod_line = strval($item->t_prod_line);
                        $newItem->im_item_type = strval($item->t_part_type);
                        $newItem->im_item_isRfq = 0;
                        $newItem->im_item_group = strval($item->t_group);
                        $newItem->im_item_price = $item->t_price;
                        $newItem->im_item_promo = strval($item->t_promo);
                        $newItem->im_item_design = strval($item->t_dsgn_grp);
                        $newItem->im_item_acc = strval($item->t_acc);
                        $newItem->im_item_subacc = strval($item->t_subacc);
                        $newItem->im_item_costcenter = strval($item->t_cc);
                        $newItem->load_by_id = $currentUser;
                        $newItem->save();
                    }
                }

                DB::commit();
                toast('Item loaded successfully', 'success');
            } catch (\Exception $err) {
                DB::rollBack();

                toast('Failed to load item', 'error');
            }
        } else {
            toast('No data found from WSA', 'info');
        }
        return redirect()->back();
    }

    public function edit($id)
    {
        $item = Item::where('id', $id)->first();

        return view('setting.items.edit', compact('item'));
    }

    public function update(Request $request)
    {
        $id = $request->u_id;
        $hyperlink = $request->itemHyperlink;
        $currentUser = Auth::user()->id;

        DB::beginTransaction();

        try {
            $item = Item::where('id', $id)->first();
            $item->im_item_hyperlink = $hyperlink;
            $item->updated_by = $currentUser;
            $item->save();

            DB::commit();

            toast('Item updated successfully', 'success');
            return redirect()->route('items.index');
        } catch (\Exception $err) {
            DB::rollBack();

            toast('Failed to update item', 'error');
            return redirect()->back();
        }
    }
}
