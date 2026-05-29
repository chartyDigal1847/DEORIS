<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdmissionOfficerController extends Controller
{
    public function index(): JsonResponse
    {
        $officers = User::query()
            ->where('role', User::ROLE_ADMISSION_OFFICER)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'created_at', 'updated_at']);

        return response()->json(['data' => $officers]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_ADMISSION_OFFICER,
            'admission_status' => User::ADMISSION_PENDING,
            'enrollment_status' => User::ENROLLMENT_NOT_ENROLLED,
        ]);

        return response()->json([
            'data' => $user->only(['id', 'name', 'email', 'role', 'created_at']),
        ], 201);
    }

    public function show(User $admissionOfficer): JsonResponse
    {
        $this->ensureOfficer($admissionOfficer);

        return response()->json([
            'data' => $admissionOfficer->only(['id', 'name', 'email', 'role', 'created_at', 'updated_at']),
        ]);
    }

    public function update(Request $request, User $admissionOfficer): JsonResponse
    {
        $this->ensureOfficer($admissionOfficer);

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admissionOfficer->id)],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $admissionOfficer->update($validated);

        return response()->json([
            'data' => $admissionOfficer->fresh()->only(['id', 'name', 'email', 'role', 'updated_at']),
        ]);
    }

    public function destroy(User $admissionOfficer): JsonResponse
    {
        $this->ensureOfficer($admissionOfficer);
        $admissionOfficer->delete();

        return response()->json(['message' => 'Admission officer removed.']);
    }

    private function ensureOfficer(User $user): void
    {
        abort_unless($user->role === User::ROLE_ADMISSION_OFFICER, 404);
    }
}
