# Academic Performance Monitoring System

A web-based system for monitoring and managing academic performance in educational institutions.

## Project Structure

```
academicperfsystem/
├── config/               # Configuration files
│   ├── config.php       # Main configuration
│   └── db.php          # Database configuration
├── auth/                # Authentication related files
│   ├── login.php
│   ├── register.php
│   └── ...
├── admin/               # Admin module
│   ├── dashboard.php
│   └── ...
├── teacher/             # Teacher module
│   ├── dashboard.php
│   └── ...
├── student/             # Student module
│   ├── dashboard.php
│   └── ...
├── api/                 # API endpoints
├── includes/            # Shared components
├── assets/              # Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── database/           # Database related files
│   ├── migrations/     # SQL migration files
│   └── backups/        # Database backups
├── logs/               # System logs
├── uploads/            # User uploads
└── vendor/             # Dependencies
```

## Setup Instructions

1. Clone the repository
2. Configure your web server (Apache/Nginx) to point to the project directory
3. Create a MySQL database
4. Import the database structure:
   ```bash
   mysql -u your_username -p your_database < database/migrations/complete_setup.sql
   ```
5. Copy `config/config.example.php` to `config/config.php` and update with your settings
6. Ensure the following directories are writable by the web server:
   - logs/
   - uploads/
   - database/backups/

## Default Accounts

### Admin
- Email: admin@school.edu
- Password: admin123

### Teachers
- Email: [firstname.lastname]@school.edu
- Password: password123

### Students
- Email: Various test accounts available
- Password: student123

## Features

- User authentication and authorization
- Role-based access control (Admin, Teacher, Student)
- Grade management
- Performance monitoring
- Subject management
- Student enrollment
- Reporting system

## Security Notes

- All passwords are hashed using bcrypt
- Session management implemented
- Input validation and sanitization in place
- CSRF protection enabled

## Contributing

1. Create a new branch for your feature
2. Make your changes
3. Submit a pull request

## License

[Your License Here] 