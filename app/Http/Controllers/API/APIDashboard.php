<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\API\xxinvDet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class APIDashboard extends Controller
{
    public function getInventoryByWarehouse()
    {

        try {

            $data = xxinvDet::select(
                'xxinv_wrh',
                DB::raw('SUM(xxinv_qty_wrh) as total_qty'),
                DB::raw('SUM(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
            )
                ->groupBy('xxinv_wrh')
                ->orderBy('xxinv_wrh')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }

    }

    public function getDetailInventoryByWarehouse(Request $request)
    {
        try {

            $data = xxinvDet::select(
                'xxinv_part as part',
                'xxinv_lot as lot',
                'xxinv_wrh as wrh',
                'xxinv_bin as bin',
                'xxinv_level as level',
                'xxinv_qty_wrh as qty_wrh',
                'xxinv_qtyoh as qty_oh',
                DB::raw('(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
            )
                ->where('xxinv_wrh', $request->wrh)
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data',
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getInventoryByStatus()
    {

        try {

            $data = xxinvDet::select(
                'xxinv_loc',
                DB::raw('SUM(xxinv_qty_wrh) as total_qty'),
                DB::raw('SUM(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
            )
                ->groupBy('xxinv_loc')
                ->orderBy('xxinv_loc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }

    }

    public function getDetailInventoryByStatus(Request $request)
    {
        try {

            $data = xxinvDet::select(
                'xxinv_part as part',
                'xxinv_lot as lot',
                'xxinv_wrh as wrh',
                'xxinv_bin as bin',
                'xxinv_level as level',
                'xxinv_qty_wrh as qty_wrh',
                'xxinv_qtyoh as qty_oh',
                DB::raw('(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
            )
                ->where('xxinv_loc', $request->loc)
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data',
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);

        } catch (\Throwable $th) {

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
