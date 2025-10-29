# IAmStillHere - Memorial Social Networking Platform

A digital memorial platform built with PHP and PostgreSQL that allows users to honor loved ones by preserving memories, creating timelines, and sharing tributes.

## 🎯 Features

### User Authentication & Roles
- **Registration & Login** with secure password hashing (bcrypt)
- **Three User Roles:**
  - **Admin**: Full access to manage users, content, and view activity reports
  - **Registered Client**: Create memorials, upload content, manage privacy
  - **Public Visitor**: View public memorials and leave tributes

### Core Functionality

#### 1. Memory Management
- Upload photos, videos, and documents
- Add titles, descriptions, and dates
- Privacy controls (Public, Family Only, Private)
- File type validation and size limits
- Organized storage in `/data/uploads/`

#### 2. Timeline & Milestones
- Create life event milestones
- Categorize events (Birth, Education, Career, etc.)
- Visual timeline display
- Privacy-controlled visibility

#### 3. Scheduled Events
- Schedule future messages and posts
- Set specific dates and times
- Automatic publishing system
- Privacy level settings

#### 4. Tributes & Memorial Wall
- Public visitors can leave tributes
- Comment moderation system
- Memorial page with all content
- Family and friends can share memories

#### 5. Privacy Control System
- **Public**: Visible to everyone
- **Family**: Only visible to designated family members
- **Private**: Only visible to content owner

#### 6. Admin Dashboard
- User management (suspend/activate accounts)
- Content moderation
- Activity log tracking
- System statistics

## 🛠️ Tech Stack

- **Backend**: PHP 8.4
- **Database**: PostgreSQL (compatible with MySQL)
- **Frontend**: Bootstrap 5, Vanilla JavaScript
- **Security**: PDO prepared statements, password hashing, input sanitization

## 📁 Project Structure

```
IAmStillHere/
├── backend/
│   ├── auth/              # Authentication (login, register, logout, session)
│   ├── memories/          # Memory upload and listing
│   ├── milestones/        # Timeline and milestone management
│   ├── events/            # Scheduled events
│   ├── tributes/          # Public tributes
│   └── admin/             # Admin functions
├── frontend/
│   ├── css/
│   │   └── style.css      # Custom memorial theme
│   ├── js/
│   │   ├── auth.js        # Authentication helpers
│   │   └── dashboard.js   # Dashboard functionality
│   ├── login.php          # Login page
│   ├── register.php       # Registration page
│   ├── dashboard.php      # User dashboard
│   └── admin.php          # Admin panel
├── config/
│   ├── database.php       # Database connection
│   └── config.php         # App configuration
├── data/
│   └── uploads/           # File uploads (photos/videos/documents)
├── index.php              # Homepage
├── schema.sql             # Database schema
└── README.md

```

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.x
- PostgreSQL 5.7+ (or MySQL 5.7+)
- Web server (Apache/Nginx) or PHP built-in server

### Step 1: Clone Repository
```bash
git clone <repository-url>
cd IAmStillHere
```

### Step 2: Database Setup

The database schema is already created and configured. The application uses PostgreSQL with environment variables:
- `PGHOST`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`, `PGPORT`

If you need to recreate the database:
```bash
# Execute schema (if needed)
psql -h $PGHOST -p $PGPORT -U $PGUSER -d $PGDATABASE -f schema.sql
```

### Step 3: Configure Environment

Database credentials are automatically loaded from environment variables. No additional configuration needed.

### Step 4: Set Permissions
```bash
chmod 755 -R data/uploads/
```

### Step 5: Start Server

**Development (PHP Built-in Server):**
```bash
php -S 0.0.0.0:5000
```

**Production (Apache/Nginx):**
Configure your virtual host to point to the project root directory.

### Step 6: Access Application
- Homepage: `http://localhost:5000/`
- Login: `http://localhost:5000/frontend/login.php`

**⚠️ IMPORTANT: Change the default admin password after first login!**

## 📊 Database Schema

### Main Tables
- **users** - User accounts and profiles
- **memories** - Photos, videos, documents
- **milestones** - Timeline events
- **scheduled_events** - Future messages/posts
- **tributes** - Public comments and tributes
- **family_members** - Family access control
- **activity_log** - System activity tracking
- **sessions** - Session management

## 🔒 Security Features

1. **Password Security**
   - bcrypt password hashing
   - Minimum 8 character requirement
   - Secure session management

2. **Input Validation**
   - Sanitized inputs (htmlspecialchars, strip_tags)
   - Email validation
   - File type and size validation

3. **SQL Injection Prevention**
   - PDO prepared statements throughout
   - Parameterized queries only

4. **Access Control**
   - Role-based permissions
   - Privacy level enforcement
   - Session timeout (1 hour)

## 🌐 API Endpoints

### Authentication
- `POST /backend/auth/login.php` - User login
- `POST /backend/auth/register.php` - User registration
- `GET /backend/auth/logout.php` - User logout
- `GET /backend/auth/check_session.php` - Check login status

### Memories
- `POST /backend/memories/upload.php` - Upload memory
- `GET /backend/memories/list.php?user_id={id}` - List memories

### Milestones
- `POST /backend/milestones/create.php` - Create milestone
- `GET /backend/milestones/list.php?user_id={id}` - List milestones

### Events
- `POST /backend/events/create.php` - Schedule event

### Tributes
- `POST /backend/tributes/create.php` - Add tribute
- `GET /backend/tributes/list.php?memorial_user_id={id}` - List tributes

### Admin
- `GET /backend/admin/users.php` - List all users
- `PUT /backend/admin/users.php` - Update user status

## 🎨 Customization

### Theme Colors
Edit `/frontend/css/style.css`:
```css
:root {
    --memorial-purple: #9b59b6;
    --memorial-blue: #3498db;
}
```

### Upload Limits
Edit `/config/config.php`:
```php
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
```

## 📱 Responsive Design

The application is fully responsive using Bootstrap 5:
- Mobile-friendly navigation
- Adaptive card layouts
- Touch-optimized controls
- Responsive tables and forms

## 🔧 Troubleshooting

### File Upload Issues
1. Check folder permissions: `chmod 755 -R data/uploads/`
2. Verify `upload_max_filesize` in php.ini
3. Check `post_max_size` in php.ini

### Database Connection Issues
1. Verify environment variables are set
2. Check PostgreSQL service is running
3. Verify credentials in config

### Session Issues
1. Ensure sessions are enabled in php.ini
2. Check session save path permissions
3. Verify SESSION_SECRET environment variable

## 🚀 Deployment (cPanel Shared Hosting)

1. **Upload Files**
   - Upload all files to public_html or subdirectory

2. **Database Setup**
   - Create PostgreSQL/MySQL database via cPanel
   - Update config/database.php with credentials
   - Import schema.sql via phpMyAdmin

3. **Set Permissions**
   ```bash
   chmod 755 data/uploads
   chmod 644 config/config.php
   ```

4. **Configure .htaccess** (Apache)
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   ```

## 📄 License

This project is developed for memorial and tribute purposes. Please use respectfully.

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section
2. Review error logs in your server
3. Ensure all dependencies are installed

## 🎯 Future Enhancements

- Email notifications for tributes
- Advanced search and filtering
- Photo gallery with lightbox
- User profile customization
- Data export functionality
- Email integration for scheduled posts

---

**IAmStillHere** - Honoring Lives, Preserving Memories Forever 💜
