<?php

namespace App\Http\Controllers\api;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends ResponseController
{
    public function signup(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'     => 'required|string|max:100',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $data             = $validated->validated();
            $data['password'] = Hash::make($data['password']);

            $user = User::create($data);

            return $this->sendResponse($user, 'User created successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'email'    => 'required|email',
                'password' => 'required|string',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $credentials = $validated->validated();
           if (!Auth::attempt($credentials)) {
                return $this->sendError(
                    'Invalid credentials.',
                    'Email & password do not match our records.',
                    401
                );
            }

            $user  = Auth::user();
            $token = $user->createToken('api_token')->plainTextToken;

            return $this->sendResponse(
                [
                    'user'       => $user,
                    'token'      => $token,
                    'token_type' => 'bearer',
                ],
                'User logged in successfully.'
            );
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();

            return $this->sendResponse(null, 'User logged out successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
