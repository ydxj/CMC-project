<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationsController extends Controller
{
    public function regions()
    {
        $regions = DB::table('region')
            ->select('id', 'region')
            ->orderBy('region')
            ->get();

        return response()->json($regions);
    }

    public function citiesByRegion(Request $request, int $regionId)
    {
        $search = trim((string) $request->query('q', ''));

        $query = DB::table('ville')
            ->select('id', 'ville', 'region')
            ->where('region', $regionId)
            ->orderBy('ville');

        if ($search !== '') {
            $query->where('ville', 'like', "%{$search}%");
        }

        return response()->json($query->get());
    }
}
