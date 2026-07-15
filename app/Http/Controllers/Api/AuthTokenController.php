<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\LoginResource;
use Illuminate\Http\JsonResponse;

class AuthTokenController extends Controller
{
    /**
     * APIトークンを発行するエンドポイント
     *
     * @param LoginRequest $request ログイン情報
     * @return JsonResponse APIトークン
     */
    public function login(LoginRequest $request): LoginResource|JsonResponse
    {
        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'ログイン情報が​登録されていません。'
            ], 401);
        }

        $user = $request->user();

        $data = [
            'name' => $user->name,
            'token' => $user->createToken('api-token')->plainTextToken,
        ];

        return new LoginResource($data);
    }
}
