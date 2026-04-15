<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function profile(Request $request)
    {
        // Ambil token (support Bearer atau raw)
        $token = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $token);

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Token required'
            ], 401);
        }

        // Cari account berdasarkan token
        $account = DB::table('dtAccountJS')
            ->where('LoginToken', $token)
            ->where('TokenExpiredAt', '>', now())
            ->where('ActiveBool', 1)
            ->first();

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized / Token expired'
            ], 401);
        }

        // Ambil profile dari dtJobseeker
        $profile = DB::table('dtJobseeker')
            ->where('AccountID', $account->ID)
            ->first();

        return response()->json([
            'status' => true,
            'data' => [
                'account' => [
                    'ID' => $account->ID,
                    'AccountName' => $account->AccountName,
                    'Email' => $account->Mail
                ],
                'profile' => $profile,
                'photo' => $profile->PhotoProfile 
                    ? asset('storage/' . $profile->PhotoProfile) 
                    : null
            ]
        ]);
    }

    public function update(Request $request)
    {

        $token = str_replace('Bearer ', '', $request->header('Authorization'));
        // dd($token);

        $account = DB::table('dtAccountJS')
        ->where(function ($query) use ($token) {
            $query->where('LoginToken', $token)
            ->orWhere('LoginToken', hash('sha256', $token));
        })
        // ->where('LoginToken', $token)
        ->where('TokenExpiredAt', '>', now()->subHours(7))
        ->first();

        // dd([
        //     'token_from_request' => $token,
        //     'account_found' => $account
        // ]);

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // // Ambil account dari middleware
        // $account = $request->account;

        // Validasi input
        $request->validate([
            // 'NIK' => 'nullable|string|max:50',
            'Name' => 'nullable|string|max:100',
            'Gender' => 'nullable|string|max:20',
            'HP1' => 'nullable|string|max:20',
            // 'Email' => 'nullable|email',
            'Birth_date' => 'nullable|date',
            'Birth_place' => 'nullable|string|max:100',
            'marital' => 'nullable|string|max:50',
            'Religion' => 'nullable|string|max:50',
            'Race' => 'nullable|string|max:50',
            'LastEducLevel' => 'nullable|string|max:50',
            'LastEducMajor' => 'nullable|string|max:100',
            'LastEducInstitu' => 'nullable|string|max:150',
        ]);

        // Ambil data lama
        $jobseeker = DB::table('dtJobseeker')
            ->where('AccountID', $account->ID)
            ->first();

        if (!$jobseeker) {
            return response()->json([
                'status' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        // Update hanya field yang dikirim
        $updateData = [];

        foreach ($request->all() as $key => $value) {
            if (!is_null($value)) {
                $updateData[$key] = $value;
            }
        }

        $updateData['UpdateDate'] = now();

        DB::table('dtJobseeker')
            ->where('AccountID', $account->ID)
            ->update($updateData);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully'
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $token = preg_replace('/^Bearer\s+/i', '', trim($authHeader));

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

        // VALIDASI FILE
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $file = $request->file('photo');

        // nama file unik
        $filename = 'profile_' . $account->ID . '_' . time() . '.' . $file->getClientOriginalExtension();

        // simpan ke storage/public/profile
        $path = $file->storeAs('public/profile', $filename);

        // hapus prefix public/
        $path = str_replace('public/', '', $path);

        // update ke DB
        DB::table('dtJobseeker')
            ->where('AccountID', $account->ID)
            ->update([
                'PhotoProfile' => $path
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Photo uploaded successfully',
            'url' => asset('storage/' . $path)
        ]);
    }

}
