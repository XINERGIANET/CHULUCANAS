<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\EndingContractsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\User;
use App\Models\Pdf as PdfModel;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class DistrictController extends Controller
{
    public function api(Request $request)
    {
        $q = trim((string) $request->q);

        $items = DB::table('districts')
            ->join('provinces', 'districts.province_id', '=', 'provinces.id')
            ->join('departments', 'provinces.department_id', '=', 'departments.id')
            ->select(
                'districts.id',
                DB::raw("CONCAT(departments.name, ', ', provinces.name, ', ', districts.name) as text"),
                'departments.name as department',
                'provinces.name as province',
                'districts.name as district'
            )
            ->when($q !== '', function ($query) use ($q) {
                return $query->where(function ($query) use ($q) {
                    $query->where('departments.name', 'like', "%{$q}%")
                        ->orWhere('provinces.name', 'like', "%{$q}%")
                        ->orWhere('districts.name', 'like', "%{$q}%");
                });
            })
            ->orderBy('departments.name')
            ->orderBy('provinces.name')
            ->orderBy('districts.name')
            ->limit(200)
            ->get();

        return response()->json(['items' => $items]);
    }

    
}
