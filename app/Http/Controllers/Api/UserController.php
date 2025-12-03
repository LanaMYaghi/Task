<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized, please login'], 401);
        }

        $role = request()->query('role');

        $query = User::query();

        if ($role) {
            if (!in_array($role, ['Admin', 'User'])) {
                return response()->json(['message' => 'Invalid role'], 400);
            }
            $query->role($role);
        }

        $users = $query->select('id', 'name', 'email', 'created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => $users
        ], 200);
    }

    public function update(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'unique:users,email,' . $user->id,
                'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/'
            ],
            'password' => [
                'sometimes',
                'required',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
            'password_confirmation' => 'required_with:password|same:password',
        ], [
            'email.regex' => 'Invalid email format. Example: user@example.com',
            'email.unique' => 'Email is already taken.',
            'password.required' => 'Password is required when updating.',
            'password.regex' => 'Password must contain at least one uppercase letter and one number.',
            'password_confirmation.required_with' =>  'Password confirmation is required when updating password.',
            'password_confirmation.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Your profile has been updated successfully',
            'user' => $user
        ]);
    }

    public function destroy($id)
    {
        $currentUser = auth('api')->user();

        if (!$currentUser->hasRole('Admin')) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found or already deleted'], 404);
        }

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function deleteMyProfile()
    {
        $currentUser = auth('api')->user();

        if (!$currentUser) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $currentUser->delete();

        return response()->json(['message' => 'Your account has been deleted successfully']);
    }
}
