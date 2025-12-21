Grad Student Admission Management System

Overview This system manages graduate student onboarding, term-based holds, advising, course registration, assignments, and thesis/project timelines. It provides role-based portals for Students, Faculty (GPD/Major Professor), and Admins.

Tech Stack

Frontend: Vue 3 + Vite
Backend: PHP (PDO), session-based auth, JSON APIs
Database: MySQL
Prerequisites

Node.js 18+ and npm
PHP 8+
MySQL 8+ (or MySQL 5.7 with compatible schema)
Quick Start

Database setup

Create a database (default name: grad_system).
Run the SQL files in order:
backend/sql/00_holds_term_code.sql
backend/sql/01_registrar_signals.sql
backend/sql/02_thesis_projects.sql
backend/sql/03_admin_setup.sql
backend/sql/04_add_research_method_course.sql
backend/sql/05_document_comments.sql
backend/sql/06_user_profiles.sql
backend/sql/07_core_courses_seed.sql
backend/sql/08_documents_doc_type_fix.sql
backend/sql/09_assignments.sql
backend/sql/10_faculty_courses.sql
backend/sql/11_defense_windows.sql
backend/sql/12_assignment_grades.sql
backend/sql/13_majors_and_programs.sql
backend/sql/14_assignment_reads.sql
backend/sql/15_advisee_course_actions.sql
Backend (PHP)

Configure DB connection (environment variables are preferred):
DB_HOST (default: 127.0.0.1)
DB_PORT (default: 3306)
DB_NAME (default: grad_system)
DB_USER (default: root)
DB_PASS (required)
Start the backend (example using PHP built-in server):
php -S localhost:8080 -t backend
API base will be: http://localhost:8080/api
Frontend (Vue)

Install dependencies:
cd frontend
npm install
Start dev server:
npm run dev
If your backend is not on the default URL, set:
VITE_API_BASE_URL=http://localhost:8080/api
Default Admin Account If you ran backend/sql/03_admin_setup.sql, the default admin is:

Username: admin
Password: 123456
How to Use (Role Workflow) Admin

Log in as admin.
Create or manage users in the Admin portal.
Review and approve documents (Admission Letter, Major Professor Form, Research Method Proof).
Publish defense date windows.
Lift holds when requirements are met.
Student

Register and log in.
Term 1: Upload Admission Letter (Documents).
Term 2: Upload Major Professor Form, then select advisor.
Term 3: Upload Research Method Proof and complete required course.
Course registration: deficiency courses first, credit limits enforced.
View assignments, submit work, and see grades.
Term 3-4: Select thesis/project defense and submission dates, then upload thesis/project file.
Faculty

Add teaching courses.
View advisees and their course registrations.
Review Major Professor requests.
Review and comment on Research Method Proof and advisee documents.
Create assignments, review submissions, and grade.
Important URLs

Frontend dev: http://localhost:5173
Backend API: http://localhost:8080/api
Notes

The system uses session cookies. Keep frontend and backend on localhost to avoid CORS issues.
Passwords stored in plaintext will be upgraded to hashes on first login.
