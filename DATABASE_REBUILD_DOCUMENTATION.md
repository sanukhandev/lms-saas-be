# Database Rebuild Documentation

## Migration Structure Overview

The database migrations have been rebuilt with optimized structure and correct dependency ordering. The migrations follow this sequence:

1. `create_tenants_table` - Foundation for the multi-tenant architecture
2. `create_users_table` - User accounts with tenant relationships
3. `create_categories_table` - Course categories with self-referencing parent-child relationships
4. `create_courses_table` - Courses with hierarchical structure for modules and chapters
5. `create_course_user_table` - Many-to-many relationship between courses and users
6. `create_course_contents_table` - Content items for courses
7. `create_class_sessions_table` - Live class sessions
8. `create_exams_table` - Course assessments
9. `create_exam_questions_table` - Questions for exams
10. `create_exam_results_table` - Student exam results
11. `create_student_progress_table` - Tracking student progress through courses
12. `create_course_purchases_table` - Course purchase records
13. `create_certificates_table` - Certificates awarded to students
14. `create_feedback_table` - Course feedback and ratings

## Seeder Dependency Structure

The seeders have been organized to respect dependencies between data types:

1. **DemoTenantSeeder**
   - Creates initial tenant records
   - Foundation for all other seeders

2. **TenantThemeConfigSeeder**
   - Depends on: Tenants
   - Adds theme configuration to each tenant
   - Sets up branding, colors, typography, etc.

3. **DashboardContentSeeder**
   - Depends on: Tenants
   - Creates users, categories, courses, and related data
   - Sets up demo content for the learning platform

4. **CourseTreeStructureSeeder**
   - Depends on: Tenants, Categories
   - Creates hierarchical course structures with modules and chapters
   - Demonstrates the tree structure capability of the platform

## How to Use This Rebuild

1. Run the `rebuild_database.sh` script to reset the database and apply the new structure:
   ```bash
   bash rebuild_database.sh
   ```

2. This will:
   - Reset the database (all data will be lost)
   - Run migrations in the correct order
   - Replace the DatabaseSeeder.php with the optimized version
   - Run seeders in the correct dependency order

## Data Model Features

- **Multi-tenant Architecture**: Each record is associated with a tenant
- **Hierarchical Course Structure**: Courses contain modules, which contain chapters
- **Tree Structure Pattern**: Implemented using parent_id and position fields
- **User Role System**: Super admin, tenant admin, staff, tutor, and student roles
- **Category Hierarchy**: Categories can have subcategories
- **Content Types**: Different types of course content (video, text, etc.)
- **Progress Tracking**: Student progress through course content
- **Assessment System**: Exams, questions, and results
- **Certificate Management**: Certificates issued upon course completion

## Optimization Features

- Proper indexes on foreign keys and frequently queried fields
- Consistent naming conventions for all tables and columns
- Timestamps for created_at and updated_at on all tables
- Soft deletes where appropriate
- JSON columns for flexible data storage (settings, learning_objectives, etc.)
