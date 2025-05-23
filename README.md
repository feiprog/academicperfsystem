# Academic Performance Monitoring System

A comprehensive system for monitoring and managing academic performance in educational institutions.

## Features

- User Management (Admin, Teachers, Students)
- Grade Management
- Performance Tracking
- Attendance Monitoring
- Report Generation
- Real-time Notifications
- Secure Authentication
- Activity Logging

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx Web Server
- XAMPP (recommended for easy setup)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/academicperfsystem.git
```

2. Set up the database:
- Create a new MySQL database named 'academicperfsystem'
- Import the SQL files from the 'sql' directory in this order:
  - admin_setup.sql
  - create_login_history.sql
  - update_schema.sql

3. Configure the system:
- Copy config.php to config.local.php
- Update database credentials in config.local.php
- Ensure proper permissions for logs/, uploads/, and backups/ directories

4. Default Credentials:
- Admin: username "admin" / password "admin123"
- Teachers: usernames "mramos", "sabina", "jagudo", "jsabalo" / password "password123"
- Students: Test accounts available with password "password123"

## Directory Structure

```
academicperfsystem/
├── api/              # API endpoints
├── includes/         # Core system files
├── sql/             # Database scripts
├── css/             # Stylesheets
├── images/          # System images
├── uploads/         # User uploads
├── logs/            # System logs
└── backups/         # Database backups
```

## Security Features

- Session Management
- Password Hashing
- SQL Injection Prevention
- XSS Protection
- CSRF Protection
- Activity Logging
- Error Handling

## Maintenance

The system includes several maintenance tools:
- verify_system.php - System health check
- backup_database.php - Database backup
- update_database.php - Database updates

## Updates and Upgrades

1. Pull the latest changes:
```bash
git pull origin main
```

2. Run database updates:
```bash
php update_database.php
```

3. Verify system:
```bash
php verify_system.php
```

## Troubleshooting

Common issues and solutions:
1. Database Connection:
   - Verify database credentials in config.php
   - Ensure MySQL service is running

2. File Permissions:
   - Ensure write permissions for logs/, uploads/, and backups/
   - Check PHP has proper permissions

3. Session Issues:
   - Verify PHP session configuration
   - Clear browser cookies and cache

## Support

For issues and support:
1. Check the troubleshooting guide
2. Review error logs in logs/ directory
3. Submit an issue on GitHub

## License

[Your License Type] - See LICENSE file for details 