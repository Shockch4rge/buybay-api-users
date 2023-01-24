<?php

namespace App\Http\Controllers;

use App\Jobs\UserDeletedJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api")->except("login", "register", "restore");
    }

    public function login(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required|string",
        ]);

        if ($validation->fails()) {
            return response([
                "message" => "Invalid credentials",
                "errors" => $validation->errors(),
            ], 400);
        }

        if (!User::query()->where('email', $request->email)->exists()) {
            return response([
                'message' => 'Email not found.',
            ], 404);
        }

        $token = Auth::attempt($request->all());

        if (!$token) {
            return response([
                "message" => "Invalid credentials.",
            ], 401);
        }

        $user = Auth::user();

        return response([
            "message" => "Successfully logged in.",
            "user" => $user,
            "token" => $token,
        ]);
    }

    public function register(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string',
            "avatar" => "image|mimes:jpg,png,bmp,jpeg",
        ]);

        if ($validation->fails()) {
            return response([
                "message" => "Invalid credentials",
                "errors" => $validation->errors(),
            ], 400);
        }

        if (User::query()->where('email', $request->email)->exists()) {
            return response([
                'message' => 'Email already exists',
            ], 409);
        }

        $avatarUrl = null;

        if ($request->hasFile("avatar")) {
            $disk = Storage::disk();
            $file = $request->file("avatar");
            $s3Path = 'avatars/' . time() . "." . $file->getClientOriginalExtension();
            $avatarUrl = $disk->url($disk->put($s3Path, file_get_contents($file)));
        }

        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            "avatar_url" => $avatarUrl,
        ]);

        $token = Auth::login($user);

        return response([
            'message' => 'User created successfully',
            'user' => $user,
            "token" => $token,
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response([
            "message" => "Logged out successfully",
        ]);
    }

    public function me()
    {
        return response([
            "message" => "Returning current user",
            "user" => Auth::user(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'string|max:255',
            "username" => "string|max:255",
            'email' => 'string|email|max:255|unique:users',
            'password' => 'string',
        ]);

        if ($validation->fails()) {
            return response([
                "message" => "Invalid credentials",
                "errors" => $validation->errors(),
            ], 400);
        }

        $user = Auth::user();
        $user->update($request->all());

        return response([
            'status' => 'success',
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $user = Auth::user();

        if (Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'New password must be different from old one!',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        Auth::logout();

        return response([
            "message" => "Password reset successfully!",
        ]);
    }

    public function destroy(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "password" => "required|string",
        ]);

        if ($validation->fails()) {
            return response([
                "message" => "Password required",
                "errors" => $validation->errors(),
            ], 400);
        }

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user) {
            return response([
                "message" => "Not logged in",
            ], 401);
        }

        $user->delete();
        UserDeletedJob::dispatch($user);
        Auth::logout();

        return response([
            "message" => "User deleted successfully",
        ]);
    }

    public function restore(Request $request) {
        $validation = Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required|string",
        ]);

        if ($validation->fails()) {
            return response([
                "message" => "Invalid credentials",
                "errors" => $validation->errors(),
            ], 400);
        }

        $trashedUser = User::withTrashed()->where("email", $request->email)->first();

        if (!$trashedUser) {
            return response([
                'message' => 'Account associated with that email has not been deleted',
            ], 404);
        }

        $trashedUser->restore();

        $token = Auth::attempt($request->all());
        $user = Auth::user();

        return response([
            "message" => "Successfully restored account",
            "user" => $user,
            "token" => $token,
        ]);
    }
}
