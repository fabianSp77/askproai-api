<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->has('role')) {
            $query->where('role', $request->get('role'));
        }

        $users = $query->paginate($request->get('per_page', 20));

        return response()->json($users);
    }

    public function show($id): JsonResponse
    {
        $user = User::findOrFail($id);

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:super_admin,admin,manager',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:super_admin,admin,manager',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete your own account'
            ], 422);
        }

        $user->delete();

        return response()->json(null, 204);
    }

    public function resetPassword(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function toggle2FA(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $enabled = $request->get('enabled', !$user->two_factor_enabled);

        if ($enabled && !$user->two_factor_secret) {
            // Generate 2FA secret
            $user->two_factor_secret = \Google2FA::generateSecretKey();
        }

        $user->two_factor_enabled = $enabled;
        $user->save();

        return response()->json([
            'message' => $enabled ? '2FA enabled' : '2FA disabled',
            'two_factor_enabled' => $user->two_factor_enabled,
        ]);
    }
}