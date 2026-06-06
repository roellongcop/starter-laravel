<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Issue a stateless Sanctum bearer token for valid credentials. Tokens never
     * expire (config/sanctum.php) and carry full abilities — authorization is still
     * enforced per-request by spatie policies/permissions.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        return $this->tokenResponse($user, (string) $request->string('device_name'));
    }

    /**
     * Self-registration. Mirrors the web Breeze flow: create the account, assign the
     * read-only "user" role, fire Registered — then issue a token instead of a session.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => (string) $request->string('name'),
            'email' => (string) $request->string('email'),
            'password' => (string) $request->string('password'),
        ]);

        $user->assignRole(SystemRole::User->value);

        event(new Registered($user));

        return $this->tokenResponse($user, (string) $request->string('device_name'), 201);
    }

    /**
     * The authenticated user behind the supplied bearer token.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Revoke only the token used for this request (sign out this device).
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Revoke every token for the user (sign out of all devices).
     */
    public function logoutAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->tokens()->delete();

        return response()->json(['message' => 'Logged out of all devices.']);
    }

    /**
     * Mint a full-ability token and return it with the user payload. Shared by
     * login & register so the issuance/shape stays in one place.
     */
    private function tokenResponse(User $user, string $deviceName, int $status = 200): JsonResponse
    {
        $name = trim($deviceName) ?: 'mobile';
        $token = $user->createToken($name, ['*'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], $status);
    }

    /**
     * Shape shared by login/me. Roles & permissions let the mobile client gate its
     * UI the same way the Inertia frontend does via the shared `auth` props.
     *
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
