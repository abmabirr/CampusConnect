CREATE DATABASE IF NOT EXISTS campusconnect_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE campusconnect_db;

CREATE TABLE IF NOT EXISTS departments (
    department_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Teacher', 'Admin') NOT NULL DEFAULT 'Student',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    department_id INT UNSIGNED NOT NULL,
    phone VARCHAR(30) NOT NULL,
    semester VARCHAR(30) NOT NULL,
    address VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) NULL,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_students_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teachers (
    teacher_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    department_id INT UNSIGNED NOT NULL,
    phone VARCHAR(30) NOT NULL,
    designation VARCHAR(100) NOT NULL,
    room_no VARCHAR(40) NOT NULL,
    CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_teachers_department FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_admins_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notices (
    notice_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    publish_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Published', 'Archived') NOT NULL DEFAULT 'Published',
    CONSTRAINT fk_notices_admin FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    subject VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    complaint_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Pending',
    response TEXT NULL,
    CONSTRAINT fk_complaints_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    INDEX idx_complaints_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    purpose MEDIUMTEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE,
    INDEX idx_appointments_teacher_slot (teacher_id, appointment_date, appointment_time, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS labs (
    lab_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lab_name VARCHAR(120) NOT NULL UNIQUE,
    lab_location VARCHAR(180) NOT NULL,
    capacity SMALLINT UNSIGNED NOT NULL,
    availability_status ENUM('Available', 'Unavailable') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lab_bookings (
    booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    lab_id INT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose VARCHAR(500) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lab_bookings_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_lab_bookings_lab FOREIGN KEY (lab_id) REFERENCES labs(lab_id) ON DELETE CASCADE,
    INDEX idx_lab_bookings_slot (lab_id, booking_date, start_time, end_time, status)
) ENGINE=InnoDB;

INSERT IGNORE INTO departments (department_name) VALUES
    ('Computer Science and Engineering'),
    ('Electrical and Electronic Engineering'),
    ('Business Administration'),
    ('English'),
    ('Civil Engineering');

INSERT INTO labs (lab_name, lab_location, capacity)
SELECT 'Computer Lab 1', 'Science Building, Room 201', 40
WHERE NOT EXISTS (SELECT 1 FROM labs WHERE lab_name = 'Computer Lab 1');
INSERT INTO labs (lab_name, lab_location, capacity)
SELECT 'Computer Lab 2', 'Science Building, Room 202', 30
WHERE NOT EXISTS (SELECT 1 FROM labs WHERE lab_name = 'Computer Lab 2');
INSERT INTO labs (lab_name, lab_location, capacity)
SELECT 'Electronics Lab', 'Engineering Building, Room 105', 25
WHERE NOT EXISTS (SELECT 1 FROM labs WHERE lab_name = 'Electronics Lab');
