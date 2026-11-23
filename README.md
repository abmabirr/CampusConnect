# CampusConnect

CampusConnect is a PHP/MySQL campus services portal for students, teachers, and administrators.

## Local setup (XAMPP)

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Import `database.sql` using phpMyAdmin or the MySQL command line. The script creates `campusconnect_db` and sample departments/labs.
3. If you already have the database from an older CampusConnect version, also import `database_migrations/001_student_profile_picture.sql`.
4. To update an existing database for unrestricted lab booking purposes, also import `database_migrations/002_unlimited_lab_booking_purpose.sql`.
5. If your local MySQL credentials differ, update `includes/db.php`.
6. Open `http://localhost/CampusConnect/` and register a student account.

### Create the first administrator

Admin registration is intentionally not public. First generate a password hash using the PHP executable bundled with XAMPP (replace the sample password):

```powershell
C:\xampp\php\php.exe -r "echo password_hash('ChangeThisPassword!', PASSWORD_DEFAULT), PHP_EOL;"
```

In phpMyAdmin, select `campusconnect_db`, open the SQL tab, and run the following statements with your own name, email, and generated hash:

```sql
INSERT INTO users (name, email, password, role)
VALUES ('Campus Administrator', 'admin@example.com', 'PASTE_GENERATED_HASH_HERE', 'Admin');

INSERT INTO admins (user_id)
VALUES (LAST_INSERT_ID());
```

Choose a unique, strong password before generating the hash, then sign in with that email and password.

Administrators can create teacher accounts from the admin dashboard. Student accounts are created through public registration.

## Included modules

- Role-aware login and student registration
- Student profile, notices, complaints, appointment requests, and lab booking
- Student profile picture upload, replacement, and removal (JPEG, PNG, or WebP; up to 5 MB)
- Teacher appointment review
- Admin notice publishing, complaint response, teacher account creation, and lab management
- CSRF protection on forms, prepared SQL statements, password hashing, and role-based authorization
