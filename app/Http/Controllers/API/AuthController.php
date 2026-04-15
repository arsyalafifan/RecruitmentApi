<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Jobseeker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class AuthController extends Controller
{
    // REGISTER (pakai email)
    public function register(Request $request)
    {
        $request->validate([
            'nik' => 'required|unique:dtJobseeker,NIK',
            'name' => 'required',
            'email' => 'required|email|unique:dtAccountJS,Mail',
            'password' => 'required|min:6'
        ]);

        DB::beginTransaction();

        try {

            // 1. Insert dtAccountJS
            $account = Account::create([
                'AccountName' => trim($request->name),
                'Mail' => trim($request->email),
                'AccountPass' => Hash::make($request->password),
                'ActiveBool' => 1,
                'InsertDate' => now(),
                'CreatedAt' => now(),
                'UpdatedAt' => now()
            ]);

            // 2. Insert dtJobseeker
            Jobseeker::create([
                'AccountID' => $account->ID,
                'Name' => trim($request->name),
                'NIK' => $request->nik,
                'Email' => $request->email,
                'Insertby' => $account->ID,
                'InsertDate' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Register successful'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Register failed',
                'error' => $e->getMessage() // optional, bisa dihapus di production
            ], 500);
        }
    }

    // LOGIN (pakai email)
    public function login(Request $request)
    {
        $request->validate([
            'Mail' => 'required|email',
            'password' => 'required'
        ]);

        $account = Account::where('Mail', $request->Mail)->first();

        if (!$account || !Hash::check($request->password, $account->AccountPass)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        if ($account->ActiveBool != 1) {
            return response()->json([
                'status' => false,
                'message' => 'Account is not active'
            ], 403);
        }

        // Generate token
        $token = Str::random(60);
        $expired = Carbon::now()->addHours(2);

        $account->update([
            'LoginToken' => $token,
            'TokenExpiredAt' => $expired,
            'LastLogin' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Login success',
            'token' => $token,
            'expired_at' => $expired,
            'user' => [
                'ID' => $account->ID,
                'AccountName' => $account->AccountName,
                'Mail' => $account->Mail
            ]
        ]);
    }

    // PROFILE (pakai token)
    public function profile(Request $request)
    {
        $token = $request->header('Authorization');

        $account = Account::where('LoginToken', $token)
            ->where('TokenExpiredAt', '>', now())
            ->first();

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'status' => true,
            'data' => $account
        ]);
    }

    // LOGOUT (optional tapi penting)
    public function logout(Request $request)
    {
        $token = $request->header('Authorization');

        $account = Account::where('LoginToken', $token)->first();

        if ($account) {
            $account->update([
                'LoginToken' => null,
                'TokenExpiredAt' => null
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Logout success'
        ]);
    }
}