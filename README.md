# EduBridge Uganda

A secure, native PHP multi-tenant school management SaaS platform built with semantic HTML5, Tailwind CSS, and MySQL.

## Tech Stack

- **Frontend:** Semantic HTML5 + Tailwind CSS (CDN)
- **Backend:** Native PHP (no frameworks)
- **Database:** MySQL with PDO
- **Authentication:** Session-based with password hashing
- **Security:** Prepared statements, multi-tenant isolation

## Features

- ✅ Multi-tenant architecture with strict data isolation
- ✅ Secure authentication with session management
- ✅ Light/Dark mode toggle
- ✅ Responsive design (mobile-first)
- ✅ Dashboard with sidebar navigation
- ✅ School branding with logo support
- ✅ Audit logging for compliance
- ✅ SQL injection protection via PDO

## Installation

### 1. Prerequisites

- PHP 7.4+ with PDO extension
- MySQL 5.7+
- Composer (optional, only for package management)

### 2. Database Setup

Create a new MySQL database:

```bash
mysql -u root -p
CREATE DATABASE edubridge_uganda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Import the schema:

```bash
mysql -u root -p edubridge_uganda < schema.sql
```

### 3. Environment Configuration

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```
DB_HOST=localhost
DB_NAME=edubridge_uganda
DB_USER=root
DB_PASS=your_password
DB_PORT=3306
```

### 4. Server Setup

For local development, use PHP's built-in server:

```bash
php -S localhost:8000
```

Then navigate to `http://localhost:8000/login.php`

For production, use Apache or Nginx with PHP-FPM.

## Credentials

Default test user (created by schema.sql):

- **Email:** admin@stmarys.ug
- **Password:** password123

## Project Structure

```
├── config.php              # Database connection & session management
├── login.php               # Authentication form
├── dashboard.php           # Main dashboard with sidebar
├── logout.php              # Session termination
├── schema.sql              # Database schema
├── .env.example            # Environment variables template
└── README.md               # This file
```

## Security Features

### Multi-Tenant Isolation

Every table has a `tenant_id` column. All queries filter by current tenant:

```php
$stmt = $pdo->prepare('SELECT * FROM students WHERE tenant_id = ? AND id = ?');
$stmt->execute([$tenantId, $studentId]);
```

### Session Validation

Protected pages verify authentication at the top:

```php
if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}
```

### Password Security

Passwords are hashed using `password_hash()` (bcrypt):

```php
$hash = password_hash($password, PASSWORD_DEFAULT);
```

Verification uses `password_verify()`:

```php
if (password_verify($password, $user['password_hash'])) {
    // Login successful
}
```

### SQL Injection Prevention

All database queries use prepared statements with bound parameters:

```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
```

## API / Database Schema

### Schools Table (Tenants)

```sql
id, name, slug, logo_url, website, email, phone, address, city, country
```

### Users Table

```sql
id, tenant_id, name, email, password_hash, role, status, created_at
```

Roles: `admin`, `staff`, `teacher`, `student`

### Students Table

```sql
id, tenant_id, user_id, student_id_number, first_name, last_name, 
email, phone, date_of_birth, gender, enrollment_date, status
```

### Courses Table

```sql
id, tenant_id, name, code, description, credits, status
```

### Requirements Table

```sql
id, tenant_id, student_id, course_id, requirement_type, title, 
description, due_date, status, grade, max_grade, feedback
```

## Extending the Platform

### Adding New Pages

1. Create a new PHP file
2. Start with authentication check:

```php
<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$tenantId = getCurrentTenantId();
// Your logic here
?>
```

3. Use Tailwind classes for styling
4. Implement dark mode support

### Adding Database Tables

1. Ensure every table has `tenant_id` column
2. Add foreign key: `FOREIGN KEY (tenant_id) REFERENCES schools(id)`
3. Always filter queries by tenant:

```php
$stmt = $pdo->prepare('SELECT * FROM your_table WHERE tenant_id = ?');
$stmt->execute([$tenantId]);
```

## Light/Dark Mode

The application includes a complete light/dark mode toggle:

- Stored in localStorage
- Respects system preferences
- Applied via Tailwind's dark mode
- Available on login and dashboard pages

## Troubleshooting

### Database Connection Error

- Verify MySQL is running
- Check credentials in `.env`
- Ensure database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### Session Not Persisting

- Check that PHP sessions directory is writable
- Verify `session_start()` is called in `config.php`
- Browser must have cookies enabled

### Logo Not Displaying

- Verify image path in `schools.logo_url`
- Ensure public write permissions for uploads directory
- Use absolute paths or relative to web root

## License

Proprietary - EduBridge Uganda

## Support

For issues or feature requests, contact development team.
