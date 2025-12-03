<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoleRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RoleRequestController extends Controller
{
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if ($user->hasRole('Admin')) {
            return response()->json(['message' => 'Admins cannot request admin role'], 403);
        }

        $existing = RoleRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already have a request pending or approved'], 400);
        }

        $requestRole = RoleRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Role request submitted successfully',
            'request' => $requestRole
        ], 201);
    }

    public function index()
    {
        $user = auth('api')->user();

        if (!$user->hasRole('Admin')) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $requests = RoleRequest::with('user:id,name,email')->get();

        return response()->json([
            'requests' => $requests
        ]);
    }

    public function myRequests()
    {
        $user = auth('api')->user();

        $requests = $user->roleRequests()->get();

        return response()->json([
            'requests' => $requests
        ]);
    }

    public function update(Request $request, $id)
    {
        $admin = Auth::user();

        if (!$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $roleRequest = RoleRequest::findOrFail($id);

        if ($roleRequest->status === 'rejected') {
            return response()->json(['message' => 'Rejected requests cannot be updated'], 400);
        }

        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $oldStatus = $roleRequest->status;
        $newStatus = $request->status;

        if ($oldStatus === 'approved' && $newStatus === 'pending') {
            return response()->json(['message' => 'Cannot change approved request back to pending'], 400);
        }

        $roleRequest->status = $newStatus;
        $roleRequest->save();

        $user = $roleRequest->user;

        if ($oldStatus === 'pending' && $newStatus === 'approved') {
            $user->syncRoles(['midAdmin']);
        } elseif ($oldStatus === 'pending' && $newStatus === 'rejected') {
            $user->syncRoles(['User']);
        } elseif ($oldStatus === 'approved' && $newStatus === 'rejected') {
            $user->syncRoles(['User']);
        }

        return response()->json([
            'message' => 'Request updated successfully',
            'request' => $roleRequest,
            'user_role' => $user->getRoleNames(),
        ]);
    }
}
