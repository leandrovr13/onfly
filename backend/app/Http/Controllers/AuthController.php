<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * Autentica o usuário e retorna um token de acesso (Sanctum).
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        // Remove tokens antigos, para deixar o fluxo mais controlado
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->transformUser($user),
        ]);
    }

    /**
     * Registra um novo usuário "comum" e já retorna token + dados.
     */
    public function register(Request $request): JsonResponse
    {
        // 1) Validação dos dados
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', Password::min(6)],
            'phone'                 => ['nullable', 'string', 'max:40'],
            'photo'                 => ['nullable', 'image', 'max:2048'], // 2MB
        ]);

        // 2) Upload da foto (se enviada)
        $photoPath = null;

        if ($request->hasFile('photo')) {
            // salva em storage/app/public/avatars
            $photoPath = $request->file('photo')->store('avatars', 'public');
        }

        // 3) Criação do usuário (sempre "user" por padrão)
        $user = User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'photo_path' => $photoPath,
            'role'       => 'user',
            'password'   => Hash::make($validated['password']),
        ]);

        // 4) Gera token (mesma lógica usada no login)
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->transformUser($user),
        ], 201);
    }


    /**
     * Atualiza os dados do usuário autenticado.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Validação
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'phone'                 => ['nullable', 'string', 'max:40'],
            'password'              => ['nullable', 'confirmed', Password::min(6)],
            'photo'                 => ['nullable', 'image', 'max:2048'],
        ]);

        // Upload de nova foto (se enviada)
        if ($request->hasFile('photo')) {
            // apaga a antiga, se existir
            if ($user->photo_path && Storage::disk('public')->exists($user->photo_path)) {
                Storage::disk('public')->delete($user->photo_path);
            }

            $user->photo_path = $request->file('photo')->store('avatars', 'public');
        }

        // Atualiza campos básicos
        $user->name  = $validated['name'];
        $user->phone = $validated['phone'] ?? null;

        // Se você quiser permitir mudança de e-mail:
        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        // Atualiza senha somente se enviada
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'user' => $this->transformUser($user),
        ]);
    }


    /**
     * Normaliza o formato do usuário para o frontend.
     */
    protected function transformUser(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'phone'       => $user->phone,
            'avatar_url'  => $user->photo_path
                ? Storage::disk('public')->url($user->photo_path)
                : null,
            'created_at'  => $user->created_at,
        ];
    }
}
