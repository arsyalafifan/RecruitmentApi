<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VacancyController extends Controller
{
    public function index()
    {
        $vacancies = DB::table('event_dtVacancy')
            ->where('active_bool', 1)
            ->where(function ($q) {
                $q->where('CloseAdvertise', 0)
                  ->orWhereNull('CloseAdvertise'); // jaga2 kalau null
            })
            ->orderBy('DatePublish', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $vacancies
        ]);
    }

    public function apply(Request $request)
    {
        // 🔥 1. Ambil token
        $token = str_replace('Bearer ', '', $request->header('Authorization'));

        $account = DB::table('dtAccountJS')
            ->where('LoginToken', $token)
            ->where('TokenExpiredAt', '>', now())
            ->first();

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // 🔥 2. Ambil jobseeker (untuk NIK)
        $jobseeker = DB::table('dtJobseeker')
            ->where('AccountID', $account->ID)
            ->first();

        if (!$jobseeker || !$jobseeker->NIK) {
            return response()->json([
                'status' => false,
                'message' => 'NIK belum diisi di profile'
            ], 400);
        }

        $vacID = $request->input('VacID');

        if (!$vacID) {
            return response()->json([
                'status' => false,
                'message' => 'VacID required'
            ], 400);
        }

        // 🔥 Ambil jobseeker
        $jobseeker = DB::table('dtJobseeker')
            ->where('AccountID', $account->ID)
            ->first();

        if (!$jobseeker) {
            return response()->json([
                'status' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $lastEduc = $jobseeker->LastEducLevel;

        // 🔥 Ambil manReqID dari vacancy
        $vacancy = DB::table('event_dtVacancy')
            ->where('vacID', $vacID)
            ->first();

        if (!$vacancy) {
            return response()->json([
                'status' => false,
                'message' => 'Vacancy not found'
            ], 404);
        }

        $manReqID = $vacancy->manReqID;

        $requirements = DB::table('Request_dtQualif')
            ->where('manReqID', $manReqID)
            ->where('active_bool', 1)
            ->pluck('quaEduc')
            ->map(fn($v) => strtoupper($v));

        $lastEduc = strtoupper($jobseeker->LastEducLevel);

        // ❌ kalau belum ada requirement
        if ($requirements->count() === 0) {
            return response()->json([
                'status' => false,
                'message' => 'Kualifikasi belum tersedia untuk lowongan ini'
            ], 422);
        }

        // ❌ kalau tidak match
        if (!$requirements->contains($lastEduc)) {
            return response()->json([
                'status' => false,
                'message' => 'Last education tidak sesuai kualifikasi'
            ], 422);
        }

        // 🔥 3. CEK SUDAH APPLY BELUM
        $exist = DB::table('event_dtApplican')
            ->where('VacID', $vacID)
            ->where('NIK', $jobseeker->NIK)
            ->where('Activebool', 1)
            ->first();

        if ($exist) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah melamar pada posisi ini'
            ], 409);
        }

        // 🔥 4. INSERT APPLY
        DB::table('event_dtApplican')->insert([
            'NIK' => $jobseeker->NIK,
            'VacID' => $vacID,
            'Activebool' => 1,
            'Insertby' => $account->AccountName ?? 'USER',
            'InsertDate' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Lamaran berhasil dikirim'
        ]);
    }

    public function checkApplied(Request $request)
    {
        $token = str_replace('Bearer ', '', $request->header('Authorization'));

        $account = DB::table('dtAccountJS')
            ->where('LoginToken', $token)
            ->where('TokenExpiredAt', '>', now())
            ->first();

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $jobseeker = DB::table('dtJobseeker')
            ->where('AccountID', $account->ID)
            ->first();

        if (!$jobseeker || !$jobseeker->NIK) {
            return response()->json([
                'status' => true,
                'applied' => false
            ]);
        }

        $vacID = $request->query('vacID');

        $exist = DB::table('event_dtApplican')
            ->where('VacID', $vacID)
            ->where('NIK', $jobseeker->NIK)
            ->where('Activebool', 1)
            ->exists();

        return response()->json([
            'status' => true,
            'applied' => $exist
        ]);
    }
}
