-- EduBridge Uganda - Database Schema
-- Multi-tenant school management system

-- Schools (Tenants)
CREATE TABLE IF NOT EXISTS schools (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    logo_url VARCHAR(500),
    website VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Uganda',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_deleted_at (deleted_at)
);

-- Users with tenant isolation
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'teacher', 'student') NOT NULL DEFAULT 'staff',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_tenant (email, tenant_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_deleted_at (deleted_at)
);

-- Students
CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    student_id_number VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'graduated', 'withdrawn') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_id (student_id_number, tenant_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_deleted_at (deleted_at)
);

-- Courses
CREATE TABLE IF NOT EXISTS courses (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    description TEXT,
    credits INT DEFAULT 3,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_code (code, tenant_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_deleted_at (deleted_at)
);

-- Requirements
CREATE TABLE IF NOT EXISTS requirements (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    requirement_type ENUM('assignment', 'project', 'exam', 'participation', 'lab', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    status ENUM('pending', 'submitted', 'graded', 'overdue') DEFAULT 'pending',
    grade DECIMAL(5, 2),
    max_grade DECIMAL(5, 2) DEFAULT 100.00,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_status (status),
    INDEX idx_deleted_at (deleted_at)
);

-- Audit Log for compliance
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_created_at (created_at)
);

-- Insert sample data for testing
INSERT INTO schools (name, slug, website, email, phone, city, country)
VALUES ('St. Mary\'s Secondary School', 'st-marys', 'https://stmarys.ug', 'admin@stmarys.ug', '+256123456789', 'Kampala', 'Uganda');

INSERT INTO users (tenant_id, name, email, password_hash, role, status)
VALUES (1, 'Admin User', 'admin@stmarys.ug', '$2y$10$p5WS4q/iSlLIqhTZAqVSR.EuSHBt0lHSCc63TZ6HwFvkkAG3Z/.sG', 'admin', 'active');

-- Password hash is for: password123
