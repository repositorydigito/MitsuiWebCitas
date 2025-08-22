<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Mail\PasswordResetMail;
use Carbon\Carbon;

class CustomPasswordController extends Controller
{
    /**
     * Envía el enlace de restablecimiento de contraseña
     */
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_documento' => 'required|in:DNI,CE,RUC',
            'numero_documento' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::byDocument($request->tipo_documento, $request->numero_documento)->first();

        if (!$user) {
            return back()->withErrors(['numero_documento' => 'No se encontró un usuario con esos datos.'])->withInput();
        }

        if (!$user->email) {
            return back()->withErrors(['numero_documento' => 'El usuario no tiene un correo electrónico asociado.'])->withInput();
        }

        // Limpiar tokens expirados
        PasswordResetToken::deleteExpired();

        // Eliminar tokens existentes para este usuario
        PasswordResetToken::where('email', $user->email)->delete();

        // Crear nuevo token
        $token = Str::random(64);
        PasswordResetToken::create([
            'email' => $user->email,
            'document_type' => $request->tipo_documento,
            'document_number' => $request->numero_documento,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        // Generar URL de restablecimiento
        $resetUrl = route('password.reset', ['token' => $token]);

        // Enviar email
        try {
            Mail::to($user->email)->send(new PasswordResetMail(
                $resetUrl,
                $request->tipo_documento,
                $request->numero_documento
            ));

            return back()->with('status', 'Se ha enviado un enlace de restablecimiento a tu correo electrónico.');
        } catch (\Exception $e) {
            // Log the specific error for debugging
            \Log::error('Error sending password reset email', [
                'error' => $e->getMessage(),
                'email' => $user->email,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['email' => 'Error al enviar el correo. Inténtalo de nuevo.']);
        }
    }

    /**
     * Muestra el formulario de restablecimiento de contraseña
     */
    public function showResetForm($token)
    {
        // Limpiar tokens expirados
        PasswordResetToken::deleteExpired();

        $resetToken = PasswordResetToken::where('token', $token)->valid()->first();

        if (!$resetToken) {
            return redirect()->route('password.request')
                ->withErrors(['token' => 'El enlace de restablecimiento es inválido o ha expirado.']);
        }

        return view('filament.pages.auth.reset-password', ['token' => $token]);
    }

    /**
     * Actualiza la contraseña del usuario
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]*$/',
            ],
        ], [
            'password.regex' => 'La contraseña debe contener al menos una mayúscula y un número.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Limpiar tokens expirados
        PasswordResetToken::deleteExpired();

        $resetToken = PasswordResetToken::where('token', $request->token)->valid()->first();

        if (!$resetToken) {
            return back()->withErrors(['token' => 'El enlace de restablecimiento es inválido o ha expirado.']);
        }

        $user = User::where('email', $resetToken->email)
            ->byDocument($resetToken->document_type, $resetToken->document_number)
            ->first();

        if (!$user) {
            return back()->withErrors(['token' => 'No se encontró el usuario asociado a este token.']);
        }

        // Actualizar contraseña
        $user->password = Hash::make($request->password);
        $user->save();

        // Eliminar el token usado
        PasswordResetToken::where('token', $request->token)->delete();

        return redirect()->route('login')->with('status', 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.');
    }
}
