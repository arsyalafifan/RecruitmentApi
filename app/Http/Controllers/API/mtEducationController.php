<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class mtEducationController extends Controller
{
    public function list()
    {
        $data = DB::table('mtEducation')
            ->where('ActiveBool', 1)
            ->orderBy('educID', 'asc')
            ->get([
                'educID',
                'educCode',
                'educName'
            ]);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
}
