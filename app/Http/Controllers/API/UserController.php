<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of users from the same company (Admins only).
     * GET /api/users
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Only admins can list all users
        if (!$user->isAdmin()) {
            return $this->errorResponse('Only admins can view all users', 403);
        }
        
        $query = User::where('company_id', $user->company_id);
        
        // Apply search filters if provided
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }
        
        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($request->per_page ?? 15);
        
        return $this->paginatedResponse($users, 'Users retrieved successfully');
    }

    /**
     * Store a newly created user (Admins only).
     * POST /api/users
     * 
     * @param UserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserRequest $request)
    {
        $user = $request->user();
        
        // Only admins can create users
        if (!$user->isAdmin()) {
            return $this->errorResponse('Only admins can create users', 403);
        }
        
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'company_id' => $user->company_id, // Same company as authenticated user
        ]);
        
        return $this->successResponse($newUser, 'User created successfully', 201);
    }

    /**
     * Display the specified user.
     * GET /api/users/{id}
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, User $user)
    {
        $authUser = $request->user();
        
        // Check if user belongs to the same company
        if ($user->company_id !== $authUser->company_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        
        // Only admins can view other users' details
        if (!$authUser->isAdmin() && $authUser->id !== $user->id) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        
        return $this->successResponse($user, 'User retrieved successfully');
    }

    /**
     * Update the specified user (Admins only for role changes).
     * PUT /api/users/{id}
     * 
     * @param UserRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserRequest $request, User $user)
    {
        $authUser = $request->user();
        
        // Check if user belongs to the same company
        if ($user->company_id !== $authUser->company_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        
        // Only admins can update other users
        if (!$authUser->isAdmin() && $authUser->id !== $user->id) {
            return $this->errorResponse('Only admins can update other users', 403);
        }
        
        // Only admins can change roles
        if ($request->has('role') && !$authUser->isAdmin()) {
            return $this->errorResponse('Only admins can change user roles', 403);
        }
        
        // Update user (Extra)
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        
        if ($request->has('role') && $authUser->isAdmin()) {
            $user->role = $request->role;
        }
        
        $user->save();
        
        return $this->successResponse($user, 'User updated successfully');
    }

    /**
     * Remove the specified user (Admins only).
     * DELETE /api/users/{id}
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, User $user)
    {
        $authUser = $request->user();
        
        // Check if user belongs to the same company
        if ($user->company_id !== $authUser->company_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        
        // Only admins can delete users
        if (!$authUser->isAdmin()) {
            return $this->errorResponse('Only admins can delete users', 403);
        }
        
        // Prevent admin from deleting themselves
        if ($user->id === $authUser->id) {
            return $this->errorResponse('Cannot delete yourself', 400);
        }
        
        $user->delete();
        
        return $this->successResponse(null, 'User deleted successfully');
    }
}