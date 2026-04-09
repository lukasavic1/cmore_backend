<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ExampleTasksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/google
     *
     * Accepts a Firebase ID token, verifies it with the Firebase REST API,
     * then finds or creates the user and returns a Sanctum bearer token.
     */
    public function googleSignIn(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        // Verify the Firebase ID token using the Firebase REST API.
        // The accounts:lookup endpoint validates the token and returns user data.
        $response = Http::post(
            'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . config('services.firebase.api_key'),
            ['idToken' => $request->token]
        );

        if (!$response->ok()) {
            return response()->json(['message' => 'Invalid Firebase token.'], 401);
        }

        $users = $response->json('users');

        if (empty($users)) {
            return response()->json(['message' => 'Firebase user not found.'], 401);
        }

        $firebaseUser = $users[0];

        // Find or create our application user from the Firebase profile
        $user = User::firstOrCreate(
            ['google_id' => $firebaseUser['localId']],
            [
                'name'   => $firebaseUser['displayName'] ?? 'User',
                'email'  => $firebaseUser['email'],
                'avatar' => $firebaseUser['photoUrl'] ?? null,
            ]
        );

        $wasNew = $user->wasRecentlyCreated;

        $user->fill([
            'name'   => $firebaseUser['displayName'] ?? $user->name,
            'email'  => $firebaseUser['email'] ?? $user->email,
            'avatar' => $firebaseUser['photoUrl'] ?? $user->avatar,
        ])->save();

        if ($wasNew) {
            app(ExampleTasksService::class)->seedForUser($user);
        }

        // Rotate tokens: revoke old ones and issue a fresh Sanctum token
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userShape($user),
        ]);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userShape($request->user()),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    private function userShape(User $user): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'avatar' => $user->avatar,
        ];
    }
}
