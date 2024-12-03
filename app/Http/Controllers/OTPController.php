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
        $otp = random_int(100000, 999999);
        $message = "Your OneStep OTP is: $otp";

        try {

            OTP::updateOrCreate(
                ['telegram_id' => $request->telegram_id],
                [
                    'otp' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10),
                ]
            );

            // Send OTP via Telegram
            $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
            $telegram->sendMessage([
                'chat_id' => $request->telegram_id,
                'text' => $message,
            ]);
            return response()->json(['success' => 'OTP sent successfully'], 201);
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
            $user = User::where('telegram_id', $request->telegram_id);
            if(!$user->exists()){
                return response()->json(['info'=> 'Please Create an Account first'], 404);
        }
            return response()->json(['success' => 'OTP verified successfully', 'user' => [
                'user_id' => $user->id,
                'os_id' => $user->os_id,
                'username' => $user->username,
                'telegram_id' => $user->telegram_id
            ]], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to verify OTP'], 500);
        }
    }

}

