<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Endroid\QrCode\Builder\Builder;

class CandidateEventController extends Controller
{
    public function getEvents(Request $request)
    {
        $token = str_replace('Bearer ', '', $request->header('Authorization'));

        $account = DB::table('dtAccountJS')
            ->where('LoginToken', $token)
            ->first();

        if (!$account) {
            return response()->json(['status' => false], 401);
        }

        $jobseeker = DB::table('dtJobseeker')
            ->where('AccountID', $account->ID)
            ->first();

        $candidate = DB::table('event_dtCandidate')
            ->where('NIK', $jobseeker->NIK)
            ->first();

        if (!$candidate) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $result = [];

        /*
        ======================
        🔹 EXAM
        ======================
        */
        $exam = DB::table('process_dtExam as p')
            ->leftJoin('event_dtExam as e', 'p.VacExamID', '=', 'e.VacExamID')
            ->leftJoin('event_dtVacancy as v', 'e.VacID', '=', 'v.vacID')
            ->where('p.CandidateID', $candidate->CandidateID)
            ->select(
                'p.CandidateExamID',
                'v.vacTitle',
                'e.ExamDate',
                'e.ExamStartTime',
                'e.ExamEndTime',
                'p.AttendConfirmBool'
            )
            ->get();

        foreach ($exam as $e) {
            $result[] = [
                'id' => $e->CandidateExamID,
                'vacTitle' => $e->vacTitle,
                'inviteType' => 'EXAM',
                'eventDate' => $e->ExamDate,
                'jamStart' => date('H:i', strtotime($e->ExamStartTime)),
                'jamEnd' => date('H:i', strtotime($e->ExamEndTime)),
                'attendConfirmBool' => $e->AttendConfirmBool,
                'cekConfirmasi' => $e->AttendConfirmBool ? 'Confirmed' : 'Pending',
                'namaView' => 'View QR Code'
            ];
        }

        /*
        ======================
        🔹 INTERVIEW
        ======================
        */
        $inter = DB::table('process_dtInter as p')
            ->leftJoin('event_dtInterview as i', 'p.VacInterID', '=', 'i.VacInterID')
            ->leftJoin('event_dtVacancy as v', 'i.VacID', '=', 'v.vacID')
            ->where('p.CandidateID', $candidate->CandidateID)
            ->select(
                'p.InterID',
                'v.vacTitle',
                'i.InterDate',
                'i.InterStartTime',
                'i.InterEndTime',
                'p.AttendConfirmBool'
            )
            ->get();

        foreach ($inter as $i) {
            $result[] = [
                'id' => $i->InterID,
                'vacTitle' => $i->vacTitle,
                'inviteType' => 'INTERVIEW',
                'eventDate' => $i->InterDate,
                'jamStart' => date('H:i', strtotime($i->InterStartTime)),
                'jamEnd' => date('H:i', strtotime($i->InterEndTime)),
                'attendConfirmBool' => $i->AttendConfirmBool,
                'cekConfirmasi' => $i->AttendConfirmBool ? 'Confirmed' : 'Pending',
                'namaView' => 'View QR Code'
            ];
        }

        /*
        ======================
        🔹 MCU
        ======================
        */
        $mcu = DB::table('process_dtMCU as p')
            ->leftJoin('event_dtMCU as m', 'p.VacMCUID', '=', 'm.VacMCUID')
            ->leftJoin('event_dtVacancy as v', 'm.VacID', '=', 'v.vacID')
            ->where('p.CandidateID', $candidate->CandidateID)
            ->select(
                'p.McuID',
                'v.vacTitle',
                'm.MCUDate',
                'm.MCUStartTime',
                'm.MCUEndTime',
                'p.AttendConfirmBool'
            )
            ->get();

        foreach ($mcu as $m) {
            $result[] = [
                'id' => $m->McuID,
                'vacTitle' => $m->vacTitle,
                'inviteType' => 'MCU',
                'eventDate' => $m->MCUDate,
                'jamStart' => date('H:i', strtotime($m->MCUStartTime)),
                'jamEnd' => date('H:i', strtotime($m->MCUEndTime)),
                'attendConfirmBool' => $m->AttendConfirmBool,
                'cekConfirmasi' => $m->AttendConfirmBool ? 'Confirmed' : 'Pending',
                'namaView' => 'View QR Code'
            ];
        }

        /*
        ======================
        🔹 SORT BY DATE
        ======================
        */
        usort($result, function ($a, $b) {
            return strtotime($a['eventDate']) - strtotime($b['eventDate']);
        });

        return response()->json([
            'status' => true,
            'data' => $result
        ]);
    }

    public function confirmAttendance(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer'
        ]);

        $type = strtoupper($request->type);
        $id = $request->id;

        try {

            if ($type === 'EXAM') {
                DB::table('process_dtExam')
                    ->where('CandidateExamID', $id)
                    ->update([
                        'AttendConfirmBool' => 1,
                        'AttendConfirmDate' => now(),
                        // 'AttendConfirmBy' => 'SYS'
                    ]);
            }

            else if ($type === 'INTERVIEW') {
                DB::table('process_dtInter')
                    ->where('InterID', $id)
                    ->update([
                        'AttendConfirmBool' => 1,
                        'AttendConfirmDate' => now(),
                        // 'AttendConfirmBy' => 'SYS'
                    ]);
            }

            else if ($type === 'MCU') {
                DB::table('process_dtMCU')
                    ->where('McuID', $id)
                    ->update([
                        'AttendConfirmBool' => 1,
                        'AttendConfirmDate' => now(),
                        // 'AttendConfirmBy' => 'SYS'
                    ]);
            }

            else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid type'
                ], 400);
            }

            return response()->json([
                'status' => true,
                'message' => 'Attendance confirmed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateQR(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer'
        ]);

        $type = strtoupper($request->type);
        $id = $request->id;

        // 🔐 payload aman
        $payload = base64_encode($type . '|' . $id . '|' . now()->timestamp);

        $result = Builder::create()
            ->data($payload)
            ->size(300)
            ->build();

        $qr = 'data:image/png;base64,' . base64_encode($result->getString());

        return response()->json([
            'status' => true,
            'qr' => $qr, 
        ]);
    }
}
