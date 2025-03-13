<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'message' => 'Informations utilisateur récupérées avec succès.',
            'user' => new UserResource(Auth::user())
        ]);
    }

    public function update(UserRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Votre profil a été mis à jour avec succès.',
            'user' => new UserResource($this->userService->update($request->validated()))
        ]);
    }
}
