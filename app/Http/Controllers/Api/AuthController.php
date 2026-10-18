<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Autentica o usuário e gera um token de acesso.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Sempre compara a senha com algum hash, mesmo quando o e-mail não existe:
        // assim o tempo de resposta não revela quais e-mails estão cadastrados.
        $passwordMatches = Hash::check(
            $credentials['password'],
            $user?->password ?? $this->fallbackHash()
        );

        if (! $user || ! $passwordMatches) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->resolve(),
        ]);
    }

    /**
     * Revoga o token de acesso usado na requisição atual.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Hash descartável, gerado com o custo atual do bcrypt, usado só para
     * igualar o tempo de resposta quando o usuário não existe.
     */
    private function fallbackHash(): string
    {
        return Cache::rememberForever(
            'auth.fallback-hash',
            fn () => Hash::make(Str::random(40))
        );
    }
}
