<?php

namespace App\Http\Controllers;

use App\Mail\LoginOtpMail;
use App\Mail\UserOtpMail;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\FacePhotoEncodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function sendMailWithRetries(callable $send): void
    {
        $last = null;
        for ($i = 0; $i < 3; $i++) {
            try {
                $send();

                return;
            } catch (\Throwable $e) {
                $last = $e;
                if ($i < 2) {
                    usleep(250000);
                }
            }
        }
        throw $last;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'nullable|string|max:128',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        //Activo o no activo
        if (! $user->activo) {
            return response()->json([
            'message' => 'Tu cuenta ha sido desactivada. Contacta a un administrador para reactivarla.',
            ], 403);
        }

        $deviceId = $request->input('device_id');
        if (is_string($deviceId) && $deviceId !== '' &&
            TrustedDevice::where('user_id', $user->id)->where('device_id', $deviceId)->exists()) {
            return response()->json([
                'token' => $user->createToken('token-user')->plainTextToken,
                'user' => $user,
            ]);
        }

        $loginOtp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'login_otp_code' => $loginOtp,
            'login_otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        try {
            $this->sendMailWithRetries(fn () => Mail::to($user->email)->send(new LoginOtpMail($loginOtp)));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo OTP de login', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            $user->update([
                'login_otp_code' => null,
                'login_otp_expires_at' => null,
            ]);

            return response()->json([
                'message' => 'No se pudo enviar el código al correo. Revisa SMTP (Gmail: MAIL_USERNAME y MAIL_FROM_ADDRESS deben ser tu correo; usa contraseña de aplicación).',
            ], 503);
        }

        $payload = [
            'requires_login_otp' => true,
            'email' => $user->email,
            'message' => 'Te enviamos un código a tu correo para marcar este dispositivo como de confianza.',
        ];

        if (config('app.debug')) {
            $payload['otp_debug'] = $loginOtp;
        }

        return response()->json($payload);
    }

    public function verifyLoginDevice(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'otp_code' => 'required|string|size:6',
            'device_id' => 'required|string|max:128',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $otpIn = preg_replace('/\D/', '', (string) $request->otp_code);

        if ($user->login_otp_code !== $otpIn ||
            ! $user->login_otp_expires_at ||
            $user->login_otp_expires_at->isPast()) {
            return response()->json(['message' => 'Código inválido o expirado.'], 422);
        }

        $user->update([
            'login_otp_code' => null,
            'login_otp_expires_at' => null,
        ]);

        TrustedDevice::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $request->device_id,
            ],
            ['updated_at' => now()],
        );

        return response()->json([
            'token' => $user->createToken('token-user')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|size:8|regex:/^[0-9]{8}$/',
            'face_photo_base64' => 'required|string|max:7000000',
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'numeric',
        ]);

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $facePhoto = app(FacePhotoEncodingService::class)
            ->toDataUriForStorage($validatedData['face_photo_base64']);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => Hash::make($validatedData['password']),
            'face_photo_base64' => $facePhoto,
            'face_descriptor' => $validatedData['face_descriptor'],
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        try {
            $this->sendMailWithRetries(fn () => Mail::to($user->email)->send(new UserOtpMail($otp)));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo OTP de registro', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            $user->delete();

            return response()->json([
                'message' => 'No se pudo enviar el correo con el código. Comprueba en .env: MAIL_MAILER=smtp, MAIL_HOST=smtp.gmail.com, MAIL_PORT=587, MAIL_ENCRYPTION=tls, MAIL_USERNAME y MAIL_PASSWORD (contraseña de aplicación de Google). MAIL_FROM_ADDRESS debe ser el mismo correo que MAIL_USERNAME.',
            ], 503);
        }

        $payload = [
            'message' => 'Código enviado. Revisa tu bandeja y la carpeta de spam.',
            'email' => $user->email,
        ];

        if (config('app.debug')) {
            $payload['otp_debug'] = $otp;
        }

        return response()->json($payload, 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)
            ->where('otp_code', $request->otp_code)
            ->where('otp_expires_at', '>', Carbon::now())
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Código inválido o expirado.'], 422);
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified_at' => Carbon::now(),
        ]);

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente y token eliminado.',
        ]);
    }
}
