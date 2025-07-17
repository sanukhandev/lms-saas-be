# Dashboard API Documentation

## Overview

This document outlines the updated Dashboard API endpoints with pagination support and chart data functionality.

## Base URL

```
/api/v1/dashboard
```

## Authentication

All endpoints require Bearer token authentication and tenant headers:

-   `Authorization: Bearer {token}`
-   `X-Tenant-Domain: {domain}`
-   `X-Tenant-ID: {tenant_id}`

## Endpoints

### 1. Dashboard Overview

**GET** `/api/v1/dashboard`

Returns a complete dashboard overview with limited results for quick loading.

**Response:**

```json
{
  "status": "success",
  "message": "Dashboard data retrieved successfully",
  "data": {
    "statistics": {
      "totalUsers": 150,
      "totalCourses": 25,
      "totalEnrollments": 300,
      "totalRevenue": 15000.00,
      "userGrowthRate": 12.5,
      "courseCompletionRate": 85.2,
      "activeUsers": 120,
      "pendingEnrollments": 45
    },
    "recent_activities": [...], // Limited to 10 items
    "course_progress": [...],   // Limited to 5 items
    "user_progress": [...],     // Limited to 5 items
    "payment_stats": {...},
    "chart_data": {
      "enrollment_trends": [...],
      "completion_trends": [...],
      "revenue_trends": [...],
      "category_distribution": [...],
      "user_activity_trends": [...],
      "monthly_stats": {...}
    }
  }
}
```

### 2. Dashboard Statistics

**GET** `/api/v1/dashboard/stats`

Returns only the dashboard statistics.

**Response:**

```json
{
    "status": "success",
    "message": "Dashboard statistics retrieved successfully",
    "data": {
        "totalUsers": 150,
        "totalCourses": 25,
        "totalEnrollments": 300,
        "totalRevenue": 15000.0,
        "userGrowthRate": 12.5,
        "courseCompletionRate": 85.2,
        "activeUsers": 120,
        "pendingEnrollments": 45
    }
}
```

### 3. Recent Activities (Paginated)

**GET** `/api/v1/dashboard/activity`

Returns recent activities with pagination support.

**Parameters:**

-   `page` (optional): Page number (default: 1)
-   `per_page` (optional): Items per page (default: 10)

**Example:** `/api/v1/dashboard/activity?page=2&per_page=15`

**Response:**

```json
{
    "status": "success",
    "message": "Recent activity retrieved successfully",
    "data": [
        {
            "id": "enrollment_123",
            "type": "enrollment",
            "message": "John Doe enrolled in React Fundamentals",
            "timestamp": "2 hours ago",
            "user": {
                "name": "John Doe",
                "email": "john@example.com",
                "avatar": "/avatars/default.png"
            },
            "metadata": {
                "course_id": 15,
                "course_title": "React Fundamentals"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 150,
        "last_page": 15
    }
}
```

### 4. Course Progress (Paginated)

**GET** `/api/v1/dashboard/courses`

Returns course progress data with pagination support.

**Parameters:**

-   `page` (optional): Page number (default: 1)
-   `per_page` (optional): Items per page (default: 10)

**Example:** `/api/v1/dashboard/courses?page=1&per_page=20`

**Response:**

```json
{
    "status": "success",
    "message": "Course progress retrieved successfully",
    "data": [
        {
            "id": 15,
            "title": "React Fundamentals",
            "enrollments": 45,
            "completions": 32,
            "completionRate": 71.1,
            "averageProgress": 68.5,
            "instructor": "Jane Smith",
            "status": "active"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 25,
        "last_page": 3
    }
}
```

### 5. User Progress (Paginated)

**GET** `/api/v1/dashboard/users`

Returns user progress data with pagination support.

**Parameters:**

-   `page` (optional): Page number (default: 1)
-   `per_page` (optional): Items per page (default: 10)

**Example:** `/api/v1/dashboard/users?page=1&per_page=25`

**Response:**

```json
{
    "status": "success",
    "message": "User progress retrieved successfully",
    "data": [
        {
            "id": 123,
            "name": "John Doe",
            "email": "john@example.com",
            "avatar": "/avatars/default.png",
            "enrolledCourses": 5,
            "completedCourses": 3,
            "totalProgress": 75.5,
            "lastActivity": "2 hours ago",
            "role": "student"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 150,
        "last_page": 15
    }
}
```

### 6. Payment Statistics

**GET** `/api/v1/dashboard/payments`

Returns payment statistics.

**Response:**

```json
{
    "status": "success",
    "message": "Payment statistics retrieved successfully",
    "data": {
        "totalRevenue": 15000.0,
        "monthlyRevenue": 3500.0,
        "pendingPayments": 12,
        "successfulPayments": 245,
        "failedPayments": 0,
        "averageOrderValue": 125.5,
        "revenueGrowth": 15.2
    }
}
```

### 7. Chart Data

**GET** `/api/v1/dashboard/chart-data`

Returns data for dashboard charts and visualizations.

**Response:**

```json
{
    "status": "success",
    "message": "Chart data retrieved successfully",
    "data": {
        "enrollment_trends": [
            {
                "date": "2025-07-01",
                "count": 15
            },
            {
                "date": "2025-07-02",
                "count": 23
            }
        ],
        "completion_trends": [
            {
                "date": "2025-07-01",
                "count": 8
            },
            {
                "date": "2025-07-02",
                "count": 12
            }
        ],
        "revenue_trends": [
            {
                "date": "2025-07-01",
                "total": 1250.0
            },
            {
                "date": "2025-07-02",
                "total": 1875.0
            }
        ],
        "category_distribution": [
            {
                "category": "Programming",
                "count": 15
            },
            {
                "category": "Design",
                "count": 10
            }
        ],
        "user_activity_trends": [
            {
                "date": "2025-07-01",
                "count": 45
            },
            {
                "date": "2025-07-02",
                "count": 67
            }
        ],
        "monthly_stats": {
            "enrollments": 125,
            "completions": 89,
            "revenue": 3500.0,
            "month": "July 2025"
        }
    }
}
```

## Performance Optimizations

### 1. Caching

-   All endpoints use Redis caching
-   Cache duration varies by endpoint:
    -   Dashboard stats: 5 minutes
    -   Activities: 5 minutes
    -   Course/User progress: 10 minutes
    -   Chart data: 15 minutes

### 2. Database Optimizations

-   Bulk queries to reduce N+1 problems
-   Optimized joins and indexes
-   Pagination to handle large datasets

### 3. Pagination Best Practices

-   Use reasonable `per_page` limits (max 50 recommended)
-   Cache paginated results
-   Include meta information for frontend pagination controls

## Error Handling

All endpoints follow the same error response format:

```json
{
    "status": "error",
    "message": "Error description",
    "errors": []
}
```

Common HTTP status codes:

-   `200`: Success
-   `401`: Unauthorized
-   `403`: Forbidden
-   `404`: Not Found
-   `422`: Validation Error
-   `500`: Internal Server Error

## Frontend Integration

### Card Layout Recommendations

1. **Statistics Cards** (Top Row)

    - Total Users, Courses, Enrollments, Revenue
    - Use grid layout (4 columns on desktop)

2. **Chart Section** (Middle)

    - Enrollment & Completion Trends (Line Chart)
    - Revenue Trends (Bar Chart)
    - Category Distribution (Pie Chart)

3. **Data Tables** (Bottom)
    - Recent Activities (with pagination)
    - Course Progress (with pagination)
    - User Progress (with pagination)

### Loading States

-   Show skeleton loaders for paginated content
-   Cache chart data to reduce API calls
-   Implement infinite scroll for activities

This updated API provides better performance, pagination support, and comprehensive chart data for rich dashboard visualizations.
