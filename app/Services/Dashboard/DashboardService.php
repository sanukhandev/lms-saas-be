<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\{
    DashboardStatsDTO,
    ActivityDTO,
    UserDTO,
    CourseProgressDTO,
    UserProgressDTO,
    PaymentStatsDTO
};
use App\Models\{Course, User, StudentProgress, CoursePurchase};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get dashboard statistics for a tenant
     */
    public function getDashboardStats(string $tenantId): DashboardStatsDTO
    {
        $totalUsers = User::where('tenant_id', $tenantId)->count();
        $totalCourses = Course::where('tenant_id', $tenantId)->count();
        $totalEnrollments = $this->getTotalEnrollments($tenantId);
        $totalRevenue = $this->getTotalRevenue($tenantId);

        $userGrowthRate = $this->calculateUserGrowthRate($tenantId, $totalUsers);
        $courseCompletionRate = $this->calculateCourseCompletionRate($tenantId, $totalEnrollments);
        $activeUsers = $this->getActiveUsersCount($tenantId);
        $pendingEnrollments = $this->getPendingEnrollments($tenantId);

        return new DashboardStatsDTO(
            totalUsers: $totalUsers,
            totalCourses: $totalCourses,
            totalEnrollments: $totalEnrollments,
            totalRevenue: $totalRevenue,
            userGrowthRate: round($userGrowthRate, 1),
            courseCompletionRate: round($courseCompletionRate, 1),
            activeUsers: $activeUsers,
            pendingEnrollments: $pendingEnrollments
        );
    }

    /**
     * Get recent activities for a tenant
     */
    public function getRecentActivities(string $tenantId): Collection
    {
        $activities = collect();

        // Get recent enrollments
        $enrollments = $this->getRecentEnrollments($tenantId);
        $activities = $activities->merge($enrollments);

        // Get recent completions
        $completions = $this->getRecentCompletions($tenantId);
        $activities = $activities->merge($completions);

        // Get recent payments
        $payments = $this->getRecentPayments($tenantId);
        $activities = $activities->merge($payments);

        return $activities->sortByDesc('timestamp')->take(15)->values();
    }

    /**
     * Get course progress data for a tenant
     */
    public function getCourseProgress(string $tenantId): Collection
    {
        return Course::where('tenant_id', $tenantId)
            ->with(['instructor'])
            ->withCount([
                'users as enrollments_count' => function ($query) {
                    $query->wherePivot('role', 'student');
                }
            ])
            ->get()
            ->map(function ($course) {
                $completions = StudentProgress::where('course_id', $course->id)
                    ->where('completion_percentage', 100)
                    ->count();

                $completionRate = $course->enrollments_count > 0 ?
                    ($completions / $course->enrollments_count) * 100 : 0;

                $averageProgress = StudentProgress::where('course_id', $course->id)
                    ->avg('completion_percentage') ?? 0;

                return new CourseProgressDTO(
                    id: $course->id,
                    title: $course->title,
                    enrollments: $course->enrollments_count,
                    completions: $completions,
                    completionRate: round($completionRate, 1),
                    averageProgress: round($averageProgress, 1),
                    instructor: $course->instructor->name ?? 'Unknown',
                    status: $course->is_active ? 'active' : 'inactive'
                );
            });
    }

    /**
     * Get user progress data for a tenant
     */
    public function getUserProgress(string $tenantId): Collection
    {
        return User::where('tenant_id', $tenantId)
            ->where('role', '!=', 'super_admin')
            ->get()
            ->map(function ($user) use ($tenantId) {
                $enrollments = $this->getUserEnrollmentCount($user->id, $tenantId);
                $completedCourses = $this->getUserCompletedCoursesCount($user->id);
                $totalProgress = $this->getUserTotalProgress($user->id);

                return new UserProgressDTO(
                    id: $user->id,
                    name: $user->name,
                    email: $user->email,
                    avatar: '/avatars/default.png',
                    enrolledCourses: $enrollments,
                    completedCourses: $completedCourses,
                    totalProgress: round($totalProgress, 1),
                    lastActivity: $user->updated_at->diffForHumans(),
                    role: $user->role
                );
            });
    }

    /**
     * Get payment statistics for a tenant
     */
    public function getPaymentStats(string $tenantId): PaymentStatsDTO
    {
        $totalRevenue = $this->getTotalRevenue($tenantId);
        $monthlyRevenue = $this->getMonthlyRevenue($tenantId);
        $pendingPayments = $this->getPendingPaymentsCount($tenantId);
        $successfulPayments = $this->getSuccessfulPaymentsCount($tenantId);
        $averageOrderValue = $this->getAverageOrderValue($tenantId);
        $revenueGrowth = $this->calculateRevenueGrowth($tenantId, $monthlyRevenue);

        return new PaymentStatsDTO(
            totalRevenue: $totalRevenue,
            monthlyRevenue: $monthlyRevenue,
            pendingPayments: $pendingPayments,
            successfulPayments: $successfulPayments,
            failedPayments: 0, // Not tracked in current schema
            averageOrderValue: round($averageOrderValue, 2),
            revenueGrowth: round($revenueGrowth, 1)
        );
    }

    // Private helper methods

    private function getTotalEnrollments(string $tenantId): int
    {
        return DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->count();
    }

    private function getTotalRevenue(string $tenantId): float
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->sum('amount_paid');
    }

    private function calculateUserGrowthRate(string $tenantId, int $totalUsers): float
    {
        $lastMonth = Carbon::now()->subMonth();
        $usersLastMonth = User::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $lastMonth)
            ->count();

        return $totalUsers > 0 ? ($usersLastMonth / $totalUsers) * 100 : 0;
    }

    private function calculateCourseCompletionRate(string $tenantId, int $totalEnrollments): float
    {
        $completedCourses = StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->count();

        return $totalEnrollments > 0 ? ($completedCourses / $totalEnrollments) * 100 : 0;
    }

    private function getActiveUsersCount(string $tenantId): int
    {
        return User::where('tenant_id', $tenantId)
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->count();
    }

    private function getPendingEnrollments(string $tenantId): int
    {
        return StudentProgress::where('tenant_id', $tenantId)
            ->where('completion_percentage', 0)
            ->count();
    }

    private function getRecentEnrollments(string $tenantId): Collection
    {
        return DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.role', 'student')
            ->where('course_user.created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('course_user.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($enrollment) {
                return new ActivityDTO(
                    id: (string) $enrollment->id,
                    type: 'enrollment',
                    message: $enrollment->name . ' enrolled in ' . $enrollment->title,
                    timestamp: Carbon::parse($enrollment->created_at)->diffForHumans(),
                    user: new UserDTO(
                        name: $enrollment->name,
                        email: $enrollment->email,
                        avatar: '/avatars/default.png'
                    ),
                    metadata: [
                        'course_id' => $enrollment->course_id,
                        'course_title' => $enrollment->title,
                    ]
                );
            });
    }

    private function getRecentCompletions(string $tenantId): Collection
    {
        return StudentProgress::with(['user', 'course'])
            ->where('tenant_id', $tenantId)
            ->where('completion_percentage', 100)
            ->where('completed_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($completion) {
                return new ActivityDTO(
                    id: 'completion_' . $completion->id,
                    type: 'completion',
                    message: $completion->user->name . ' completed ' . $completion->course->title,
                    timestamp: $completion->completed_at->diffForHumans(),
                    user: new UserDTO(
                        name: $completion->user->name,
                        email: $completion->user->email,
                        avatar: '/avatars/default.png'
                    ),
                    metadata: [
                        'course_id' => $completion->course_id,
                        'course_title' => $completion->course->title,
                    ]
                );
            });
    }

    private function getRecentPayments(string $tenantId): Collection
    {
        return CoursePurchase::with(['student', 'course'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return new ActivityDTO(
                    id: 'payment_' . $payment->id,
                    type: 'payment',
                    message: $payment->student->name . ' made a payment of ' . $payment->currency . ' ' . number_format($payment->amount_paid, 2),
                    timestamp: $payment->created_at->diffForHumans(),
                    user: new UserDTO(
                        name: $payment->student->name,
                        email: $payment->student->email,
                        avatar: '/avatars/default.png'
                    ),
                    metadata: [
                        'course_id' => $payment->course_id,
                        'course_title' => $payment->course->title,
                        'amount' => $payment->amount_paid,
                    ]
                );
            });
    }

    private function getUserEnrollmentCount(int $userId, string $tenantId): int
    {
        return DB::table('course_user')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->where('courses.tenant_id', $tenantId)
            ->where('course_user.user_id', $userId)
            ->where('course_user.role', 'student')
            ->count();
    }

    private function getUserCompletedCoursesCount(int $userId): int
    {
        return StudentProgress::where('user_id', $userId)
            ->where('completion_percentage', 100)
            ->count();
    }

    private function getUserTotalProgress(int $userId): float
    {
        return StudentProgress::where('user_id', $userId)
            ->avg('completion_percentage') ?? 0;
    }

    private function getMonthlyRevenue(string $tenantId): float
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount_paid');
    }

    private function getPendingPaymentsCount(string $tenantId): int
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', false)
            ->count();
    }

    private function getSuccessfulPaymentsCount(string $tenantId): int
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }

    private function getAverageOrderValue(string $tenantId): float
    {
        return CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->avg('amount_paid') ?? 0;
    }

    private function calculateRevenueGrowth(string $tenantId, float $monthlyRevenue): float
    {
        $lastMonthRevenue = CoursePurchase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('created_at', '>=', Carbon::now()->subMonth()->startOfMonth())
            ->where('created_at', '<=', Carbon::now()->subMonth()->endOfMonth())
            ->sum('amount_paid');

        return $lastMonthRevenue > 0 ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
    }
}
