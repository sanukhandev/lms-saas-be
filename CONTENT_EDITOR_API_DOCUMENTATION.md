# Course Content Editor API Documentation

## Overview

The Course Content Editor API provides comprehensive functionality for creating, managing, and organizing rich course content including lessons, videos, documents, quizzes, assignments, and more.

## Base URL
```
/api/v1/courses/{course_id}/editor
```

## Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer {your-token}
```

## Content Types

The system supports the following content types:

| Type | Description | Can Have Children | Icon |
|------|-------------|-------------------|------|
| `module` | A collection of related lessons | ✅ | folder |
| `chapter` | A section within a module | ✅ | bookmark |
| `lesson` | Individual learning unit | ❌ | book-open |
| `video` | Video content | ❌ | play-circle |
| `document` | PDF, DOC, or other documents | ❌ | file-text |
| `quiz` | Interactive quiz or assessment | ❌ | help-circle |
| `assignment` | Student assignment or project | ❌ | clipboard |
| `text` | Rich text content | ❌ | type |
| `live_session` | Scheduled live class | ❌ | video |

## Endpoints

### 1. Get Content Types
```http
GET /api/v1/courses/{course_id}/editor/content-types
```

**Response:**
```json
{
  "success": true,
  "data": {
    "lesson": {
      "label": "Lesson",
      "description": "Individual learning unit",
      "icon": "book-open",
      "can_have_children": false
    }
    // ... other types
  }
}
```

### 2. Get Course Content
```http
GET /api/v1/courses/{course_id}/editor
```

**Query Parameters:**
- `type` (optional): Filter by content type
- `status` (optional): Filter by status (draft, published, archived)
- `parent_id` (optional): Filter by parent content ID
- `search` (optional): Search in title and description

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "parent_id": null,
      "type": "module",
      "title": "Introduction Module",
      "description": "Getting started with the course",
      "content": null,
      "content_data": null,
      "video_url": null,
      "file_path": null,
      "file_url": null,
      "file_type": null,
      "file_size": null,
      "formatted_file_size": null,
      "learning_objectives": ["Understand basics", "Get familiar with tools"],
      "status": "published",
      "is_required": true,
      "is_free": false,
      "position": 1,
      "sort_order": 1,
      "duration_mins": null,
      "estimated_duration": 120,
      "formatted_duration": "2 hr",
      "published_at": "2025-08-18 10:00:00",
      "content_type_icon": "folder",
      "hierarchy_level": 0,
      "created_at": "2025-08-18 09:00:00",
      "updated_at": "2025-08-18 10:00:00",
      "children": [
        // ... child content items
      ],
      "parent": null
    }
  ],
  "course": {
    // ... course details
  }
}
```

### 3. Create Content
```http
POST /api/v1/courses/{course_id}/editor
```

**Request Body:**
```json
{
  "title": "New Lesson",
  "description": "Lesson description",
  "type": "lesson",
  "parent_id": 1,
  "content": "<h1>Rich HTML content</h1><p>Lesson content here...</p>",
  "content_data": {
    "custom_field": "value"
  },
  "video_url": "https://youtube.com/watch?v=...",
  "learning_objectives": [
    "Learning objective 1",
    "Learning objective 2"
  ],
  "estimated_duration": 30,
  "is_required": true,
  "is_free": false,
  "status": "draft",
  "auto_publish": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Content created successfully",
  "data": {
    // ... created content object
  }
}
```

### 4. Get Specific Content
```http
GET /api/v1/courses/{course_id}/editor/{content_id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    // ... content object with relationships
  }
}
```

### 5. Update Content
```http
PUT /api/v1/courses/{course_id}/editor/{content_id}
```

**Request Body:** (same as create, all fields optional)
```json
{
  "title": "Updated Title",
  "status": "published"
}
```

### 6. Delete Content
```http
DELETE /api/v1/courses/{course_id}/editor/{content_id}
```

**Response:**
```json
{
  "success": true,
  "message": "Content deleted successfully"
}
```

### 7. Duplicate Content
```http
POST /api/v1/courses/{course_id}/editor/{content_id}/duplicate
```

**Response:**
```json
{
  "success": true,
  "message": "Content duplicated successfully",
  "data": {
    // ... duplicated content object
  }
}
```

### 8. Publish/Unpublish Content
```http
PUT /api/v1/courses/{course_id}/editor/{content_id}/publish
```

**Request Body:**
```json
{
  "status": "published"
}
```

### 9. Reorder Content
```http
POST /api/v1/courses/{course_id}/editor/reorder
```

**Request Body:**
```json
{
  "items": [
    {
      "id": 1,
      "sort_order": 1,
      "position": 1,
      "parent_id": null
    },
    {
      "id": 2,
      "sort_order": 2,
      "position": 2,
      "parent_id": 1
    }
  ]
}
```

### 10. Get Content Statistics
```http
GET /api/v1/courses/{course_id}/editor/stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_content": 25,
    "published_content": 20,
    "draft_content": 5,
    "total_duration": 1800,
    "content_by_type": {
      "lesson": 15,
      "video": 8,
      "quiz": 2
    },
    "required_content": 18,
    "free_content": 3
  }
}
```

### 11. Upload File
```http
POST /api/v1/courses/{course_id}/editor/upload
```

**Request:** (multipart/form-data)
- `file`: File to upload (max 100MB)
- `type`: Optional file type hint

**Response:**
```json
{
  "success": true,
  "data": {
    "file_path": "course-content/1/document.pdf",
    "file_url": "http://localhost/storage/course-content/1/document.pdf",
    "file_name": "document.pdf",
    "file_type": "application/pdf",
    "file_size": 1048576,
    "formatted_size": "1.00 MB"
  }
}
```

## Content Structure

### Hierarchical Organization
- **Course** (root level)
  - **Module** (can contain chapters and lessons)
    - **Chapter** (can contain lessons)
      - **Lesson** (atomic content unit)
      - **Video** (video content)
      - **Document** (file attachment)
      - **Quiz** (assessment)
      - **Assignment** (student work)

### Content Data Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique identifier |
| `course_id` | integer | Parent course ID |
| `parent_id` | integer | Parent content ID (for hierarchy) |
| `type` | string | Content type (see types above) |
| `title` | string | Content title |
| `description` | text | Content description |
| `content` | longtext | Rich HTML content |
| `content_data` | json | Flexible data storage |
| `video_url` | string | Video embed URL |
| `file_path` | string | Uploaded file path |
| `file_type` | string | MIME type |
| `file_size` | integer | File size in bytes |
| `learning_objectives` | json | Array of learning goals |
| `status` | enum | draft, published, archived |
| `is_required` | boolean | Required for completion |
| `is_free` | boolean | Free preview content |
| `position` | integer | Order within parent |
| `sort_order` | integer | Display order |
| `estimated_duration` | integer | Duration in minutes |
| `published_at` | timestamp | Publication date |

## Error Responses

All endpoints return standard error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["validation error message"]
  }
}
```

Common HTTP status codes:
- `200` - Success
- `201` - Created
- `422` - Validation Error
- `404` - Not Found
- `403` - Forbidden
- `500` - Server Error

## Content Editor Features

### ✅ Implemented Features
- **Rich Content Types**: Support for lessons, videos, documents, quizzes, assignments
- **Hierarchical Structure**: Modules, chapters, and lessons with parent-child relationships
- **File Upload**: Support for documents, videos, images up to 100MB
- **Content Management**: Create, read, update, delete operations
- **Content Organization**: Drag-and-drop reordering and hierarchy management
- **Publishing Workflow**: Draft/published status with publishing dates
- **Content Statistics**: Comprehensive analytics and metrics
- **Content Duplication**: Clone existing content with modifications
- **Learning Objectives**: Track learning goals for each content item
- **Content Search**: Full-text search across titles and descriptions

### 🚧 Planned Enhancements
- **Rich Text Editor Integration**: WYSIWYG editor for content creation
- **Media Player**: Integrated video/audio players
- **Content Templates**: Pre-built templates for common content types
- **Version Control**: Track content changes and revisions
- **Content Import/Export**: Bulk content management
- **Content Analytics**: View tracking and engagement metrics
- **Content Comments**: Instructor and student feedback system
- **Content Prerequisites**: Define content dependencies

## Frontend Integration

The API is designed to work with modern frontend frameworks. Key integration points:

1. **Content Tree Display**: Use the hierarchical data structure for tree views
2. **Drag & Drop**: Implement reordering with the reorder endpoint
3. **File Upload**: Use the upload endpoint with progress tracking
4. **Real-time Updates**: Consider WebSocket integration for live updates
5. **Content Preview**: Use the content data for student-facing views
6. **Statistics Dashboard**: Integrate stats endpoint for analytics views

## Security Considerations

- All endpoints require authentication
- Tenant isolation ensures data security
- File upload validation prevents malicious files
- Content sanitization for XSS prevention
- Permission checks for content access
