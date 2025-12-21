Grad Student Admission Management System

Overview
This system manages graduate student onboarding, term-based holds, advising, course registration, assignments, and thesis/project timelines. It provides role-based portals for Students, Faculty (GPD/Major Professor), and Admins.

Tech Stack
- Frontend: Vue 3 + Vite
- Backend: PHP (PDO), session-based auth, JSON APIs
- Database: MySQL

Prerequisites
- Node.js 18+ and npm
- PHP 8+
- MySQL 8+ (or MySQL 5.7 with compatible schema)

Quick Start
1) Database setup
   - Create a database (default name: grad_system).
   - Run the SQL files in order:
     - backend/sql/00_holds_term_code.sql
     - backend/sql/01_registrar_signals.sql
     - backend/sql/02_thesis_projects.sql
     - backend/sql/03_admin_setup.sql
     - backend/sql/04_add_research_method_course.sql
     - backend/sql/05_document_comments.sql
     - backend/sql/06_user_profiles.sql
     - backend/sql/07_core_courses_seed.sql
     - backend/sql/08_documents_doc_type_fix.sql
     - backend/sql/09_assignments.sql
     - backend/sql/10_faculty_courses.sql
     - backend/sql/11_defense_windows.sql
     - backend/sql/12_assignment_grades.sql
     - backend/sql/13_majors_and_programs.sql
     - backend/sql/14_assignment_reads.sql
     - backend/sql/15_advisee_course_actions.sql

How to use (XAMPP setup)
1) Open XAMPP and start Apache.
2) Click Apache config (httpd.conf) and update:
   - DocumentRoot "D:\GitHub\Grad-student-admission-management-system\GradManagementSystem\backend"
   - <Directory "D:\GitHub\Grad-student-admission-management-system\GradManagementSystem\backend">
3) In MySQL Workbench, run:
   - USE grad_system;
   - Then execute the SQL files from backend/sql.
4) Open the "GradManagementSystem" folder in VS Code.
5) Install dependencies and start the frontend:
   - cd frontend
   - npm install
   - npm run dev

2) Backend (PHP)
   - Configure DB connection (environment variables are preferred):
     - DB_HOST (default: 127.0.0.1)
     - DB_PORT (default: 3306)
     - DB_NAME (default: grad_system)
     - DB_USER (default: root)
     - DB_PASS (required)
   - Start the backend (example using PHP built-in server):
     - php -S localhost:8080 -t backend
   - API base will be: http://localhost:8080/api

3) Frontend (Vue)
   - Install dependencies:
     - cd frontend
     - npm install
   - Start dev server:
     - npm run dev
   - If your backend is not on the default URL, set:
     - VITE_API_BASE_URL=http://localhost:8080/api

Default Admin Account
If you ran backend/sql/03_admin_setup.sql, the default admin is:
- Username: admin
- Password: 123456

How to Use (Role Workflow)
Admin
1) Log in as admin.
2) Create or manage users in the Admin portal.
3) Review and approve documents (Admission Letter, Major Professor Form, Research Method Proof).
4) Publish defense date windows.
5) Lift holds when requirements are met.

Student
1) Register and log in.
2) Term 1: Upload Admission Letter (Documents).
3) Term 2: Upload Major Professor Form, then select advisor.
4) Term 3: Upload Research Method Proof and complete required course.
5) Course registration: deficiency courses first, credit limits enforced.
6) View assignments, submit work, and see grades.
7) Term 3-4: Select thesis/project defense and submission dates, then upload thesis/project file.

Faculty
1) Add teaching courses.
2) View advisees and their course registrations.
3) Review Major Professor requests.
4) Review and comment on Research Method Proof and advisee documents.
5) Create assignments, review submissions, and grade.

Important URLs
- Frontend dev: http://localhost:5173
- Backend API: http://localhost:8080/api

Notes
- The system uses session cookies. Keep frontend and backend on localhost to avoid CORS issues.
- Passwords stored in plaintext will be upgraded to hashes on first login.
