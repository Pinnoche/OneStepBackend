<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users|max:255',
            'telegram_id' => 'required|unique:users',
            'dob' => 'required|date',
            'phone_number' => 'required|regex:/^\+?[1-9]\d{1,14}$/|unique:users',
            'referralCode' => 'sometimes',
            'passcode' => 'required|max:6|min:6',
        ]);

        $user = User::create([
            'username' => $request->username,
            'telegram_id' => $request->telegram_id,
            'dob' => $request->dob,
            'phone_number' => $request->phone_number,
            'os_id' => Str::uuid(),
            'referralCode' => Str::random(8),
            'passcode' => Hash::make($request->passcode),
        ]);

        return response()->json(['success' => 'Registration successful', 'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'telegram_id' => $user->telegram_id,
                'os_id' => $user->os_id,
            ],], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required',
            'passcode' => 'required',
        ]);

        $user = User::where('username', $validated['username'])->first();
        if (!$user || !Hash::check($validated['passcode'], $user->passcode)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return response()->json(['success' => 'Login successful', 'user' => [
                'id' => $user->id,
                'os_id' => $user->os_id,
                'username' => $user->username,
            ],200]);
    }
}
