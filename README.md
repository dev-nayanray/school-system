# School System Application

## Overview
This is a comprehensive school management system designed to manage various roles including admin, teacher, student, and user. The application provides features such as student profiles, classes, subjects, marks, announcements, and dashboards tailored for different roles to facilitate efficient school administration and communication.

## Features
- Role-based access control for Admin, Teacher, Student, and User.
- Student profile management.
- Class and subject management.
- Marks entry and management with exam date tracking.
- Announcements and notifications.
- Payment integration and management.
- Separate dashboards for different user roles.
- Schedule and timetable management.
- Resource management for students and teachers.
- User authentication and authorization.

## Technology Stack
- PHP for backend development.
- MySQL for database management.
- HTML, CSS, and JavaScript for frontend.
- PDO for secure database interactions.
- SSLCommerz payment gateway integration.

## Setup Instructions
1. Import the database schema from `database_schema.sql`.
2. Run the necessary migrations to update the database schema, including adding the `exam_date` column:
   ```sql
   SOURCE database_migrations/add_exam_date_to_marks.sql;
   ```
3. Ensure your PHP environment is configured with PDO and MySQL extensions enabled.
4. Place the project files in your web server root directory (e.g., `htdocs` for XAMPP).
5. Configure your web server to serve the project.
6. Access the application via your web browser.

## Usage
- Admin users can manage classes, subjects, teachers, students, fees, exams, results, announcements, and payments.
- Teachers can manage their schedules, enter marks, and view student information.
- Students can view their profiles, schedules, marks, payments, and announcements.
- Users can register, login, and access features based on their roles.

## Recent Fix
- Added `exam_date` column to the `marks` table to fix a fatal error in the student dashboard caused by a missing column in the database.

## Testing Instructions
- Verify the migration runs successfully.
- Log in as a student and navigate to the student dashboard.
- Confirm the page loads without errors and exam dates appear in the recent exam results.
- Test other roles and pages to ensure no regressions.

## Notes
- Existing marks records will have `NULL` for `exam_date` until updated.
- Update the `marks` table with exam dates as needed for accurate reporting.

## Contact / Support
For support or inquiries, please contact the project maintainer.

