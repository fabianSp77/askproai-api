<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Fehlerhafte Eingaben.', 'errors' => $validator->errors()], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => bcrypt($password)])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Passwort erfolgreich zurückgesetzt.']);
        }

        return response()->json(['message' => 'Fehler beim Zurücksetzen des Passworts.'], 500);
    }
}
