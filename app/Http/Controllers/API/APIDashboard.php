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
                // DB::raw('SUM(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
                DB::raw('SUM(xxinv_qtyoh) as qty_diff')
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



    public function getInventoryByStatus()
    {

        try {

            $data = xxinvDet::select(
                'xxinv_loc',
                DB::raw('SUM(xxinv_qty_wrh) as total_qty'),
                // DB::raw('SUM(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
                DB::raw('SUM(xxinv_qtyoh) as qty_diff')
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
        $data = $data->orderBy('id', 'desc')->paginate(10);
    }
    public function getDetailInventoryByStatus(Request $request)
    {
        try {
            $query = xxinvDet::select(
                'xxinv_part as part',
                'xxinv_lot as lot',
                'xxinv_wrh as wrh',
                'xxinv_bin as bin',
                'xxinv_level as level',
                'xxinv_qty_wrh as qty_wrh',
                'xxinv_qtyoh as qty_oh',
                DB::raw('(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
            )->where('xxinv_loc', $request->loc);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('xxinv_part', 'LIKE', '%' . $search . '%')
                        ->orWhere('xxinv_bin', 'LIKE', '%' . $search . '%')
                        ->orWhere('xxinv_level', 'LIKE', '%' . $search . '%')
                        ->orWhere('xxinv_wrh', 'LIKE', '%' . $search . '%');
                });
            }

            $data = $query->orderBy('id', 'desc')->paginate(10);

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data',
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $data->items(),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ],
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
            $query = xxinvDet::select(
                'xxinv_part as part',
                'xxinv_lot as lot',
                'xxinv_wrh as wrh',
                'xxinv_bin as bin',
                'xxinv_level as level',
                'xxinv_qty_wrh as qty_wrh',
                'xxinv_qtyoh as qty_oh',
                DB::raw('(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
            )->where('xxinv_wrh', $request->wrh);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('xxinv_part', 'LIKE', '%' . $search . '%')
                        ->orWhere('xxinv_bin', 'LIKE', '%' . $search . '%')
                        ->orWhere('xxinv_level', 'LIKE', '%' . $search . '%')
                        ->orWhere('xxinv_wrh', 'LIKE', '%' . $search . '%');
                });
            }

            $data = $query->orderBy('id', 'desc')->paginate(10);

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data',
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $data->items(),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function getInventoryByExpDate()
    {
        try {
            $startDate = now()->startOfMonth();
            $endDate = now()->addMonths(6)->endOfMonth();

            $data = xxinvDet::select(
                DB::raw("DATE_FORMAT(xxinv_exp_date, '%Y-%m') as exp_month"),
                DB::raw('SUM(xxinv_qtyoh) as total_qty'),
                DB::raw('SUM(xxinv_qty_wrh) as total_qty_wrh'),
                DB::raw('COUNT(*) as total_item')
            )
                ->whereNotNull('xxinv_exp_date')
                ->whereBetween('xxinv_exp_date', [$startDate, $endDate])
                ->groupBy(
                    DB::raw("DATE_FORMAT(xxinv_exp_date, '%Y-%m')")
                )
                ->orderBy('exp_month')
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

    public function getDetailInventoryByExpDate(Request $request)
    {
        try {
            $query = xxinvDet::select(
                'xxinv_part as part',
                'xxinv_lot as lot',
                'xxinv_wrh as wrh',
                'xxinv_bin as bin',
                'xxinv_level as level',
                'xxinv_exp_date as exp_date',
                'xxinv_qty_wrh as qty_wrh',
                'xxinv_qtyoh as qty_oh',
                DB::raw('(xxinv_qty_wrh - xxinv_qtyoh) as qty_diff')
            )
                ->whereNotNull('xxinv_exp_date');

            // Filter berdasarkan bulan yang diklik
            if ($request->filled('exp_month')) {
                $query->whereRaw(
                    "DATE_FORMAT(xxinv_exp_date, '%Y-%m') = ?",
                    [$request->exp_month]
                );
            }

            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('xxinv_part', 'LIKE', "%{$search}%")
                        ->orWhere('xxinv_lot', 'LIKE', "%{$search}%")
                        ->orWhere('xxinv_bin', 'LIKE', "%{$search}%")
                        ->orWhere('xxinv_level', 'LIKE', "%{$search}%")
                        ->orWhere('xxinv_wrh', 'LIKE', "%{$search}%");
                });
            }

            $data = $query
                ->orderBy('xxinv_exp_date')
                ->paginate(10);

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data',
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $data->items(),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function getWarehouseData()
    {
        try {
            $binStatus = XxinvDet::select([
                'xxinv_wrh',
                'xxinv_level',
                'xxinv_bin',
                DB::raw("CASE WHEN SUM(COALESCE(xxinv_qtyoh, 0)) > 0 THEN 'ISI' ELSE 'KOSONG' END AS status_bin")
            ])
                ->groupBy('xxinv_wrh', 'xxinv_level', 'xxinv_bin');

            $data = DB::table(DB::raw("({$binStatus->toSql()}) as bin_summary"))
                ->mergeBindings($binStatus->getQuery())
                ->select([
                    'xxinv_wrh',
                    DB::raw("COUNT(*) AS total_bins"),
                    DB::raw("SUM(CASE WHEN status_bin = 'ISI' THEN 1 ELSE 0 END) AS total_isi"),
                    DB::raw("SUM(CASE WHEN status_bin = 'KOSONG' THEN 1 ELSE 0 END) AS total_kosong"),
                ])
                ->groupBy('xxinv_wrh')
                ->orderBy('xxinv_wrh')
                ->get();

            return response()->json([
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getWarehouseDetailData()
    {
        $warehouse = request()->input('wrh');
        try {
            $data = XxinvDet::select([
                'xxinv_wrh',
                'xxinv_level',
                'xxinv_bin',

                DB::raw("COALESCE(SUM(xxinv_qtyoh), 0) AS total_qtyoh"),
                DB::raw("COALESCE(SUM(xxinv_qty_wrh), 0) AS total_qty_wrh"),

            ])
                ->where('xxinv_wrh', $warehouse)
                ->groupBy('xxinv_wrh', 'xxinv_level', 'xxinv_bin')
                ->orderBy('total_qty_wrh','desc')
                ->orderBy('total_qtyoh','desc')
                ->orderBy('xxinv_wrh')
                ->orderBy('xxinv_level')
                ->orderBy('xxinv_bin')
                ->get();



            return response()->json([
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
