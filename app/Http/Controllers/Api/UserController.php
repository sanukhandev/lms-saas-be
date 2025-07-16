<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::where('tenant_id', Auth::user()->tenant_id);

            // Filter by role
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            // Search functionality
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            // Filter by verification status
            if ($request->filled('verified')) {
                if ($request->boolean('verified')) {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            // Transform data to include additional information
            $users->getCollection()->transform(function ($user) {
                $user->enrolled_courses_count = $user->courses()
                    ->wherePivot('role', 'student')
                    ->count();
                $user->teaching_courses_count = $user->courses()
                    ->wherePivot('role', 'instructor')
                    ->count();
                
                // Hide sensitive information
                $user->makeHidden(['password', 'remember_token']);
                
                return $user;
            });

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Users retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving users'
            ], 500);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('tenant_id', Auth::user()->tenant_id);
                    })
                ],
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:admin,staff,tutor,student',
                'send_welcome_email' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'tenant_id' => Auth::user()->tenant_id,
                'email_verified_at' => now() // Auto-verify for admin-created users
            ]);

            // TODO: Send welcome email if requested
            // if ($request->boolean('send_welcome_email')) {
            //     // Send welcome email with login credentials
            // }

            $user->makeHidden(['password', 'remember_token']);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating user'
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        try {
            // Check if user belongs to current tenant
            if ($user->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Load relationships
            $user->load([
                'courses' => function ($query) {
                    $query->withPivot('role', 'created_at');
                },
                'studentProgress' => function ($query) {
                    $query->with('course');
                }
            ]);

            // Add statistics
            $user->statistics = [
                'enrolled_courses' => $user->courses()->wherePivot('role', 'student')->count(),
                'teaching_courses' => $user->courses()->wherePivot('role', 'instructor')->count(),
                'average_progress' => $user->studentProgress()->avg('completion_percentage') ?? 0,
                'completed_courses' => $user->studentProgress()->where('completion_percentage', 100)->count(),
                'total_study_time' => $user->studentProgress()->sum('time_spent_mins')
            ];

            $user->makeHidden(['password', 'remember_token']);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user'
            ], 500);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            // Check if user belongs to current tenant
            if ($user->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('tenant_id', Auth::user()->tenant_id);
                    })->ignore($user->id)
                ],
                'password' => 'sometimes|required|string|min:8|confirmed',
                'role' => 'sometimes|required|in:admin,staff,tutor,student',
                'email_verified_at' => 'sometimes|nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only(['name', 'email', 'role', 'email_verified_at']);

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            $user->makeHidden(['password', 'remember_token']);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating user'
            ], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // Check if user belongs to current tenant
            if ($user->tenant_id !== Auth::user()->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Prevent deletion of the current user
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 409);
            }

            // Check if user has active enrollments
            $hasActiveEnrollments = $user->courses()->wherePivot('role', 'student')->exists();
            if ($hasActiveEnrollments) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete user with active course enrollments'
                ], 409);
            }

            DB::beginTransaction();

            // Remove user from all courses
            $user->courses()->detach();

            // Delete user's progress records
            $user->studentProgress()->delete();

            // Delete the user
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user'
            ], 500);
        }
    }

    /**
     * Get users by role
     */
    public function getByRole(Request $request, string $role): JsonResponse
    {
        try {
            $validRoles = ['admin', 'staff', 'tutor', 'student'];
            
            if (!in_array($role, $validRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role specified'
                ], 400);
            }

            $query = User::where('tenant_id', Auth::user()->tenant_id)
                ->where('role', $role);

            // Search functionality
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination or all results
            if ($request->boolean('paginate', true)) {
                $perPage = $request->get('per_page', 15);
                $users = $query->paginate($perPage);
                $users->getCollection()->transform(function ($user) {
                    $user->makeHidden(['password', 'remember_token']);
                    return $user;
                });
            } else {
                $users = $query->get(['id', 'name', 'email', 'role', 'created_at']);
            }

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => ucfirst($role) . 's retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving users by role: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving users by role'
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $stats = [
                'total_users' => User::where('tenant_id', $tenantId)->count(),
                'users_by_role' => User::where('tenant_id', $tenantId)
                    ->select('role', DB::raw('count(*) as count'))
                    ->groupBy('role')
                    ->get()
                    ->pluck('count', 'role'),
                'verified_users' => User::where('tenant_id', $tenantId)
                    ->whereNotNull('email_verified_at')
                    ->count(),
                'recent_registrations' => User::where('tenant_id', $tenantId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
                'monthly_registrations' => User::where('tenant_id', $tenantId)
                    ->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'User statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user statistics'
            ], 500);
        }
    }

    /**
     * Bulk import users
     */
    public function bulkImport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'users' => 'required|array|min:1|max:100',
                'users.*.name' => 'required|string|max:255',
                'users.*.email' => 'required|email|max:255',
                'users.*.role' => 'required|in:admin,staff,tutor,student',
                'users.*.password' => 'sometimes|string|min:8',
                'send_welcome_emails' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $successCount = 0;
            $errors = [];
            $createdUsers = [];

            foreach ($request->users as $index => $userData) {
                try {
                    // Check for duplicate email within tenant
                    $existingUser = User::where('tenant_id', Auth::user()->tenant_id)
                        ->where('email', $userData['email'])
                        ->first();

                    if ($existingUser) {
                        $errors[] = "Row " . ($index + 1) . ": Email {$userData['email']} already exists";
                        continue;
                    }

                    // Generate password if not provided
                    $password = $userData['password'] ?? str()->random(12);

                    $user = User::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'password' => Hash::make($password),
                        'role' => $userData['role'],
                        'tenant_id' => Auth::user()->tenant_id,
                        'email_verified_at' => now()
                    ]);

                    $createdUsers[] = $user->only(['id', 'name', 'email', 'role']);
                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk import completed. $successCount users created.",
                'data' => [
                    'created_count' => $successCount,
                    'error_count' => count($errors),
                    'created_users' => $createdUsers,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk user import: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error in bulk user import'
            ], 500);
        }
    }
}
