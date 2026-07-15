<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $account = DB::table('auth_accounts')
            ->where('provider', 'password')
            ->where('provider_id', $request->email)
            ->first();

        if (! $account || ! Hash::check($request->password, $account->password_hash)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        $user = Usuario::find($account->user_id);
        if (! $user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'data' => [
                'id' => $user->uuid,
                'name' => $user->nombre,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    public function google(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $googleResponse = Http::get(
            'https://oauth2.googleapis.com/tokeninfo',
            ['id_token' => $request->id_token]
        );

        if (! $googleResponse->ok()) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        $googleUser = $googleResponse->json();

        DB::beginTransaction();

        try {
            $user = Usuario::where('google_id', $googleUser['sub'])->first();

            if (! $user) {
                $user = Usuario::where('email', $googleUser['email'])->first();

                if ($user && ! $user->google_id) {
                    $user->update(['google_id' => $googleUser['sub']]);
                }

                if (! $user) {
                    $now = gmdate('Y-m-d H:i:s');
                    $user = Usuario::create([
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'google_id' => $googleUser['sub'],
                        'nombre' => $googleUser['name'] ?? 'Usuario',
                        'email' => $googleUser['email'],
                        'avatar' => $googleUser['picture'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'sync_status' => 'synced',
                    ]);
                }
            }

            $token = $user->createToken('mobile')->plainTextToken;

            DB::commit();

            return response()->json([
                'token' => $token,
                'data' => [
                    'id' => $user->uuid,
                    'name' => $user->nombre,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al autenticar',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|min:6',
        ]);

        DB::beginTransaction();

        try {
            $now = gmdate('Y-m-d H:i:s');
            $user = Usuario::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'nombre' => $request->nombre,
                'email' => $request->email,
                'created_at' => $now,
                'updated_at' => $now,
                'sync_status' => 'synced',
            ]);

            DB::table('auth_accounts')->insert([
                'user_id' => $user->id,
                'provider' => 'password',
                'provider_id' => $request->email,
                'password_hash' => Hash::make($request->password),
                'created_at' => now(),
            ]);

            DB::commit();

            $token = $user->createToken('mobile')->plainTextToken;

            return response()->json([
                'token' => $token,
                'data' => [
                    'id' => $user->uuid,
                    'name' => $user->nombre,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al registrar',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
