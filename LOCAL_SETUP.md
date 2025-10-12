# IAmStillHere - Local Machine Setup Guide

Complete step-by-step instructions to run IAmStillHere on your local development machine.

---

## Prerequisites

### Required Software
- **PHP 8.0 or higher** - [Download](https://www.php.net/downloads)
- **PostgreSQL 12+ or MySQL 5.7+** - [PostgreSQL](https://www.postgresql.org/download/) | [MySQL](https://dev.mysql.com/downloads/)
- **Composer** (optional, for dependencies) - [Download](https://getcomposer.com/download/)
- **Git** - [Download](https://git-scm.com/downloads)
- **Text Editor** - VS Code, Sublime, or your preferred editor

### System Requirements
- **OS**: Windows 10+, macOS 10.14+, or Linux
- **RAM**: 4GB minimum, 8GB recommended
- **Disk Space**: 500MB for application + 2GB for uploads
- **Browser**: Chrome, Firefox, Safari, or Edge (latest version)

---

## Step 1: Install PHP

### Windows
1. Download PHP from [windows.php.net](https://windows.php.net/download/)
2. Extract to `C:\php`
3. Add `C:\php` to system PATH
4. Verify installation:
   ```bash
   php -v
   ```

### macOS
```bash
# Using Homebrew
brew install php@8.2

# Verify
php -v
```

### Linux (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-pgsql php8.2-mbstring php8.2-xml

# Verify
php -v
```

---

## Step 2: Install Database

### Option A: PostgreSQL (Recommended)

#### Windows
1. Download installer from [postgresql.org](https://www.postgresql.org/download/windows/)
2. Run installer, set password for `postgres` user
3. Remember port (default: 5432)

#### macOS
```bash
brew install postgresql@16
brew services start postgresql@16
```

#### Linux
```bash
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

#### Create Database
```bash
# Login to PostgreSQL
sudo -u postgres psql

# In psql prompt:
CREATE DATABASE iamstillhere;
CREATE USER iamstillhere_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE iamstillhere TO iamstillhere_user;
\q
```

### Option B: MySQL

#### Install MySQL
```bash
# macOS
brew install mysql
brew services start mysql

# Linux
sudo apt install mysql-server
sudo systemctl start mysql
```

#### Create Database
```bash
mysql -u root -p

# In MySQL prompt:
CREATE DATABASE iamstillhere;
CREATE USER 'iamstillhere_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON iamstillhere.* TO 'iamstillhere_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Step 3: Clone Repository

```bash
# Navigate to your web directory
cd ~/Documents  # or C:\xampp\htdocs on Windows

# Clone the repository
git clone https://github.com/yourusername/iamstillhere.git

# Or download ZIP and extract
# Download from: https://github.com/yourusername/iamstillhere/archive/main.zip

# Navigate to project
cd iamstillhere
```

---

## Step 4: Configure Environment Variables

### Create .env file (if not using environment variables)

```bash
# Copy example environment file
cp .env.example .env
```

### Edit config/database.php

For **PostgreSQL**:
```php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "iamstillhere";
    private $username = "iamstillhere_user";
    private $password = "your_secure_password";
    private $port = "5432";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "pgsql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->db_name;
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            die("Database connection failed.");
        }
        return $this->conn;
    }
}
```

For **MySQL**:
```php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "iamstillhere";
    private $username = "iamstillhere_user";
    private $password = "your_secure_password";
    private $port = "3306";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->db_name . 
                   ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            die("Database connection failed.");
        }
        return $this->conn;
    }
}
```

---

## Step 5: Create Database Schema

### PostgreSQL
```bash
psql -h localhost -U iamstillhere_user -d iamstillhere -f schema.sql
# Enter password when prompted
```

### MySQL
```bash
mysql -u iamstillhere_user -p iamstillhere < schema.sql
# Enter password when prompted
```

### Verify Tables Created
```bash
# PostgreSQL
psql -h localhost -U iamstillhere_user -d iamstillhere -c "\dt"

# MySQL
mysql -u iamstillhere_user -p iamstillhere -e "SHOW TABLES;"
```

You should see:
- users
- memories
- milestones
- scheduled_events
- tributes
- family_members
- activity_log
- sessions

---

## Step 6: Set Up Upload Directories

```bash
# Create upload directories
mkdir -p data/uploads/{photos,videos,documents}

# Set permissions (Linux/macOS)
chmod -R 755 data/uploads/

# Windows: Right-click folders → Properties → Security → Give Full Control to your user
```

---

## Step 7: Configure PHP Settings

### Check Current Settings
```bash
php -i | grep upload_max_filesize
php -i | grep post_max_size
php -i | grep max_execution_time
```

### Update php.ini (if needed)

Find php.ini location:
```bash
php --ini
```

Edit php.ini and update:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```

---

## Step 8: Start Development Server

### Option A: PHP Built-in Server (Easiest)

```bash
# Navigate to project root
cd /path/to/iamstillhere

# Start server on port 5000
php -S localhost:5000

# Or bind to all interfaces
php -S 0.0.0.0:5000
```

**Access the application:**
- Homepage: http://localhost:5000
- Login: http://localhost:5000/frontend/login.php
- Register: http://localhost:5000/frontend/register.php

### Option B: Using XAMPP/WAMP/MAMP

1. Copy project to web root:
   - **XAMPP**: `C:\xampp\htdocs\iamstillhere`
   - **WAMP**: `C:\wamp64\www\iamstillhere`
   - **MAMP**: `/Applications/MAMP/htdocs/iamstillhere`

2. Start Apache and MySQL/PostgreSQL from control panel

3. Access: http://localhost/iamstillhere

### Option C: Using Apache VirtualHost

Create `/etc/apache2/sites-available/iamstillhere.conf`:
```apache
<VirtualHost *:80>
    ServerName iamstillhere.local
    DocumentRoot /path/to/iamstillhere
    
    <Directory /path/to/iamstillhere>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/iamstillhere_error.log
    CustomLog ${APACHE_LOG_DIR}/iamstillhere_access.log combined
</VirtualHost>
```

Enable and restart:
```bash
sudo a2ensite iamstillhere
sudo systemctl restart apache2

# Add to /etc/hosts
echo "127.0.0.1 iamstillhere.local" | sudo tee -a /etc/hosts
```

Access: http://iamstillhere.local

---

## Step 9: Initial Login

### Default Admin Account
- **Username**: `admin`
- **Email**: `admin@iamstillhere.com`
- **Password**: `admin123`

### ⚠️ IMPORTANT: Change Admin Password
1. Login with default credentials
2. Go to profile settings
3. Change password immediately
4. Update email to your own

---

## Step 10: Test Installation

### Basic Functionality Checklist

✅ **Homepage loads** - http://localhost:5000
```bash
curl http://localhost:5000
```

✅ **Login works**
- Navigate to Login page
- Enter admin/admin123
- Should redirect to admin dashboard

✅ **Register new user**
- Navigate to Register page
- Create test account
- Verify in database:
  ```sql
  SELECT * FROM users WHERE username='testuser';
  ```

✅ **Upload memory**
- Login as user
- Go to Dashboard
- Upload a test image
- Check file exists in `data/uploads/photos/`

✅ **Create milestone**
- Add a timeline event
- Verify in Timeline tab

✅ **View memorials**
- Navigate to /frontend/memorials.php
- Should see list of memorial pages

---

## Troubleshooting

### Issue: "Database connection failed"
**Solution:**
1. Verify database is running:
   ```bash
   # PostgreSQL
   sudo systemctl status postgresql
   
   # MySQL
   sudo systemctl status mysql
   ```
2. Check credentials in config/database.php
3. Test connection manually:
   ```bash
   psql -h localhost -U iamstillhere_user -d iamstillhere
   ```

### Issue: "Permission denied" on uploads
**Solution:**
```bash
# Linux/macOS
chmod -R 777 data/uploads/

# Or set proper ownership
sudo chown -R www-data:www-data data/uploads/
```

### Issue: "File upload failed"
**Solution:**
1. Check php.ini settings
2. Verify upload_max_filesize and post_max_size
3. Restart PHP/Apache:
   ```bash
   sudo systemctl restart php8.2-fpm
   sudo systemctl restart apache2
   ```

### Issue: "Session not working"
**Solution:**
1. Check session save path:
   ```bash
   php -i | grep session.save_path
   ```
2. Create directory if missing:
   ```bash
   sudo mkdir -p /var/lib/php/sessions
   sudo chmod 777 /var/lib/php/sessions
   ```

### Issue: "404 Not Found" for CSS/JS
**Solution:**
1. Verify files exist in frontend/ directory
2. Check file paths in HTML (case-sensitive on Linux)
3. Clear browser cache (Ctrl+Shift+R)

### Issue: Port 5000 already in use
**Solution:**
```bash
# Use different port
php -S localhost:8000

# Or find and kill process using port 5000
# Linux/macOS
lsof -ti:5000 | xargs kill -9

# Windows
netstat -ano | findstr :5000
taskkill /PID <PID> /F
```

---

## Development Tools

### Enable Debug Mode

Edit `config/config.php`:
```php
// Development only - disable in production
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/php_errors.log');
```

### Database GUI Tools
- **PostgreSQL**: [pgAdmin](https://www.pgadmin.org/)
- **MySQL**: [phpMyAdmin](https://www.phpmyadmin.net/) or [MySQL Workbench](https://www.mysql.com/products/workbench/)

### API Testing
```bash
# Test login endpoint
curl -X POST http://localhost:5000/backend/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Test session check
curl http://localhost:5000/backend/auth/check_session.php
```

---

## Production Deployment (cPanel)

### 1. Export Database
```bash
# PostgreSQL
pg_dump -h localhost -U iamstillhere_user iamstillhere > iamstillhere_backup.sql

# MySQL
mysqldump -u iamstillhere_user -p iamstillhere > iamstillhere_backup.sql
```

### 2. Upload Files
- Use FileZilla or cPanel File Manager
- Upload all files to public_html/

### 3. Import Database
- Use cPanel phpMyAdmin or PostgreSQL tool
- Import iamstillhere_backup.sql

### 4. Update config/database.php
- Use cPanel database credentials
- Update host (usually localhost)

### 5. Set Permissions
```bash
chmod 755 -R public_html/iamstillhere
chmod 777 public_html/iamstillhere/data/uploads
```

### 6. Configure .htaccess (Apache)
```apache
RewriteEngine On
RewriteBase /

# Disable directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(sql|md|json)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## Maintenance

### Backup Database (Recommended: Daily)
```bash
# Automated backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
pg_dump -h localhost -U iamstillhere_user iamstillhere > backups/db_$DATE.sql
```

### Clean Old Sessions (Weekly)
```sql
DELETE FROM sessions WHERE expires_at < NOW();
```

### Monitor Disk Usage
```bash
du -sh data/uploads/*
```

---

## Support & Resources

- **Documentation**: README.md
- **Test Cases**: TESTING.md
- **Database Schema**: schema.sql
- **Issue Tracker**: GitHub Issues
- **Community**: Discord/Slack (if available)

---

## Quick Start Checklist

- [ ] Install PHP 8.0+
- [ ] Install PostgreSQL/MySQL
- [ ] Clone repository
- [ ] Create database and user
- [ ] Run schema.sql
- [ ] Configure config/database.php
- [ ] Create upload directories
- [ ] Start PHP server
- [ ] Login with admin/admin123
- [ ] Change admin password
- [ ] Create test user
- [ ] Upload test memory
- [ ] Run through TESTING.md checklist

**Congratulations! You're ready to develop!** 🎉

---

## Next Steps

1. Review TESTING.md for comprehensive test cases
2. Explore the codebase structure
3. Check out feature roadmap in README.md
4. Join the development community
5. Start building!

For questions or issues, please open an issue on GitHub or contact the development team.
