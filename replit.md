# IAmStillHere - Memorial Social Networking Platform

## Project Overview
IAmStillHere is a PHP-based memorial social networking platform that allows users to honor loved ones by creating digital memorials with photos, videos, documents, timelines, and tributes. Built with PHP 8.4 and PostgreSQL, the application features comprehensive privacy controls and role-based access.

## Recent Changes (October 14, 2025)
- ✅ Complete project setup with PHP 8.4 and PostgreSQL database
- ✅ Implemented normalized database schema with 8 main tables
- ✅ Built secure authentication system with bcrypt password hashing
- ✅ Created role-based access control (Admin, Client, Visitor)
- ✅ Developed memory upload system with file validation
- ✅ Implemented timeline/milestone features
- ✅ Added scheduled events functionality
- ✅ Built privacy control system (public, family, private)
- ✅ Created tribute system with memorial access controls
- ✅ Developed admin dashboard for user management
- ✅ Built Bootstrap 5 responsive frontend
- ✅ Fixed critical security vulnerabilities (session fixation, privacy enforcement)
- ✅ Added profile editing with photo uploads (profile & cover photos)
- ✅ Created memorial configuration settings for users
- ✅ Built public memorials listing page
- ✅ Added Facebook-like profile page with tabs
- ✅ Created comprehensive test cases (TESTING.md)
- ✅ Written local machine setup guide (LOCAL_SETUP.md)
- ✅ Production-ready deployment configuration

## Tech Stack
- **Backend**: PHP 8.4
- **Database**: PostgreSQL (Neon-backed, compatible with MySQL)
- **Frontend**: Bootstrap 5, Vanilla JavaScript
- **Server**: PHP built-in development server (port 5000)
- **Security**: PDO prepared statements, password hashing, session management

## Project Architecture

### Backend Structure
```
backend/
├── auth/          # Authentication endpoints (login, register, logout, session)
├── memories/      # Photo/video/document uploads and listing
├── milestones/    # Timeline events management
├── events/        # Scheduled future messages
├── tributes/      # Public tribute system with access controls
└── admin/         # User and content management
```

### Frontend Structure
```
frontend/
├── css/           # Custom memorial theme styling
├── js/            # Authentication and dashboard functionality
├── images/        # Default images and assets
├── login.php      # User login page
├── register.php   # Registration page
├── dashboard.php  # User dashboard
├── profile.php    # User profile with Facebook-like layout
├── memorials.php  # Public memorial listings
└── admin.php      # Admin control panel
```

### Database Schema
- **users** - User accounts with roles, memorial flags, profile/cover photos
- **memories** - Media uploads (photos/videos/documents) with privacy
- **milestones** - Timeline events with categories
- **scheduled_events** - Future posts and messages
- **tributes** - Public comments on memorials
- **family_members** - Family access control relationships
- **activity_log** - System activity tracking
- **sessions** - Secure session management

## Key Features

### 1. User Roles & Authentication
- **Admin**: Full system access, user management, content moderation
- **Registered Client**: Create memorials, upload content, manage privacy
- **Public Visitor**: View public memorials, leave tributes

### 2. Privacy Control System
- **Public**: Visible to everyone
- **Family**: Only family members can view
- **Private**: Only owner and admin can view
- Memorial visibility based on content privacy levels

### 3. Memory Management
- Upload photos, videos, PDF documents
- File type validation and size limits (50MB max)
- Privacy-controlled access
- Organized storage in `/data/uploads/`

### 4. Timeline & Milestones
- Create life events with dates and categories
- Visual timeline display
- Privacy-controlled visibility

### 5. Scheduled Events
- Schedule future messages/posts
- Automatic publishing system
- Date-based triggers

### 6. Tribute System
- Public visitors can leave tributes on accessible memorials
- Access control based on memorial content privacy
- Admin approval workflow
- IP tracking for security

### 7. Profile Management
- Upload profile photo and cover photo
- Edit bio and personal information
- Facebook-like profile layout with tabs
- Memorial configuration settings (while alive)

### 8. Memorial Configuration
- Users can configure memorial settings while alive
- Enable/disable memorial mode
- Set date of passing
- Define who can post tributes
- Control memorial visibility

## Security Features

### Authentication Security
- ✅ Bcrypt password hashing (PASSWORD_DEFAULT)
- ✅ Session regeneration after login (prevents session fixation)
- ✅ Session timeout (1 hour)
- ✅ Secure session management

### Data Security
- ✅ PDO prepared statements (SQL injection prevention)
- ✅ Input sanitization (XSS prevention)
- ✅ Email validation
- ✅ File type and size validation
- ✅ Role-based access control

### Privacy Enforcement
- ✅ Content-level privacy controls
- ✅ Family member relationship verification
- ✅ Memorial access gating for tributes
- ✅ Admin override capabilities

## Running the Application

### Development Server
The application runs on PHP's built-in server:
```bash
php -S 0.0.0.0:5000
```

### Default Admin Account
- Username: `admin`
- Email: `admin@iamstillhere.com`
- Password: `admin123`
- ⚠️ **Change password immediately after first login**

### Environment Variables
Database credentials are automatically loaded:
- `PGHOST`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`, `PGPORT`

## User Preferences
- Memorial theme with purple/blue gradient design
- Responsive Bootstrap 5 layout
- Security-first implementation with comprehensive validation
- Production-ready code following PHP best practices

## API Endpoints

### Authentication
- `POST /backend/auth/login.php` - User login
- `POST /backend/auth/register.php` - User registration
- `GET /backend/auth/logout.php` - Logout
- `GET /backend/auth/check_session.php` - Session validation

### Content Management
- `POST /backend/memories/upload.php` - Upload memory
- `GET /backend/memories/list.php` - List memories
- `POST /backend/milestones/create.php` - Create milestone
- `GET /backend/milestones/list.php` - List milestones
- `POST /backend/events/create.php` - Schedule event

### Tributes
- `POST /backend/tributes/create.php` - Add tribute (with access control)
- `GET /backend/tributes/list.php` - List tributes (privacy enforced)

### Profile Management
- `GET /backend/users/profile.php` - Get user profile
- `POST /backend/users/update_profile.php` - Update profile with photos
- `POST /backend/users/memorial_settings.php` - Configure memorial settings

### Memorials
- `GET /backend/memorials/list.php` - List public memorial pages

### Admin
- `GET /backend/admin/users.php` - List users
- `PUT /backend/admin/users.php` - Update user status

## Deployment Notes
- Compatible with cPanel shared hosting
- Uses PostgreSQL (can be adapted for MySQL)
- File uploads stored in `/data/uploads/` (photos, videos, documents)
- Profile and cover photos stored in `/data/uploads/photos/`
- Sessions managed via PHP session system
- All sensitive operations use prepared statements

## Testing & Documentation
- **TESTING.md** - Comprehensive test cases for all features (27 test scenarios)
- **LOCAL_SETUP.md** - Complete local development setup guide
- **README.md** - Full application documentation
- Manual and automated testing guidelines included

## Future Enhancements
- Email notifications for tributes and scheduled posts
- Advanced search and filtering
- Photo gallery with lightbox viewer
- User profile customization
- Data export functionality
- Multi-language support

## Known Limitations
- PostgreSQL used instead of MySQL (fully compatible)
- Manual scheduled event publishing (requires cron job for automation)
- Basic file upload (no cloud storage integration)

---

**Status**: Production-ready ✅
**Last Updated**: October 14, 2025
**Security Review**: Passed (session fixation fixed, privacy controls implemented)
