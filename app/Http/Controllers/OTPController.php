<?php

namespace App\Http\Controllers;

use App\Models\OTP;
use App\Models\User;
use Telegram\Bot\Api;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class OTPController extends Controller
{


    public function sendOtp(Request $request)
    {
        $request->validate([
            'telegram_id' => 'required',
        ]);
        // if(!User::where('phone', $request->phone_number)->exists()){
        //     return response()->json(['message'=> 'Please Create an Account first'], 404);
        // }
        $otp = random_int(100000, 999999);
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $message = "Your OneStep OTP is: $otp";

        try {

            $otpRecord = OTP::where('telegram_id', $request->telegram_id)->first();
            if ($otpRecord) {
                $otpRecord->delete();
            }
            // Save OTP to the database
            OTP::create([
                'telegram_id' => $request->telegram_id,
                'otp' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
            ]);

            $telegram->sendMessage([
                'chat_id' => $request->telegram_id,
                'text' => $message,
            ]);
            return response()->json(['success' => 'OTP sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send OTP'], 500);
        }
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'telegram_id' => 'required',
            'otp' => 'required|digits:6',
        ]);

        try {
            $otpRecord = OTP::where('telegram_id', $request->telegram_id)
                ->where('expires_at', '>', now())
                ->first();

            if (!$otpRecord || !Hash::check($request->otp, $otpRecord->otp)) {
                return response()->json(['error' => 'Invalid or expired OTP'], 401);
            }

            $otpRecord->delete();
            
            if(!User::where('telegram_id', $request->telegram_id)->exists()){
                return response()->json(['info'=> 'Please Create an Account first'], 404);
        }
            return response()->json(['success' => 'OTP verified successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to verify OTP'], 500);
        }
    }

}

