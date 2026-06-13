# Field Practical Training Application System - Setup Guide

## 🚀 **System Overview**

This is a comprehensive web-based platform designed to facilitate the process of students from various colleges and universities applying for field practical training positions at different organizations and companies.

## 📋 **Features**

### ✅ **Student Module**
- Registration & Profile Management
- Search and Apply for Practical Training
- Application Management with notifications

### ✅ **Organization/Company Module**
- Registration & Profile Management
- Field Practical Position Management
- Application Review with feedback

### ✅ **Admin Module**
- User Management (approve/reject registrations)
- Field Practical Listings Management
- Reports and Analytics with interactive charts
- Communication Management (system announcements)

### ✅ **Advanced Features**
- Multi-source Notification System (Admin, Organization, System)
- Real-time AJAX interactions
- Professional HESLB government-style UI
- Comprehensive search and filtering
- Email verification and password reset

## 🛠 **Installation & Setup**

### **Prerequisites**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx)

### **1. Install Dependencies & Initialize**
```bash
composer install
php init
# Choose: 0 = Development
```

### **2. Database Setup**
```bash
# Run migrations to create database tables
php yii migrate --interactive=0

# Create sample data for LOCAL TESTING ONLY (never run in production)
php yii sample-data
```

### **3. Configuration**
1. Edit `common/config/main-local.php` — database connection (created by `php init`)
2. Copy `common/config/params-local.example.php` → `common/config/params-local.php`
3. Add OAuth, SMTP, or Tesseract paths locally — **never commit secrets**
4. See `.env.example` for production environment variable names (Render/Railway)

### **4. Web Server Configuration**
Point your web server to:
- Frontend: `frontend/web/`
- Backend: `backend/web/`

## 👥 **Login Credentials (Development / Sample Data Only)**

> **Security:** The passwords below are created only by `php yii sample-data`.
> Do **not** run sample-data on production. Change all default passwords before any public deployment.

### **Admin Access**
- **URL**: `http://your-domain/backend/web/`
- **Username**: `admin`
- **Password**: `admin123`

### **Student Access**
- **URL**: `http://your-domain/frontend/web/`
- **Usernames**: `student1`, `student2`, `student3`
- **Password**: `password123`

### **Organization Access**
- **URL**: `http://your-domain/frontend/web/`
- **Usernames**: `org1`, `org2`, `org3`
- **Password**: `password123`

## 🎯 **User Guide**

### **For Students**
1. **Register/Login**: Create account or login with provided credentials
2. **Complete Profile**: Add university, field of study, CV, personal statement
3. **Search Positions**: Use filters to find suitable training opportunities
4. **Apply**: Submit applications to organizations
5. **Track Applications**: Monitor application status and receive notifications

### **For Organizations**
1. **Register/Login**: Create organization account or login
2. **Complete Profile**: Add organization details and description
3. **Post Positions**: Create field practical training opportunities
4. **Review Applications**: View and manage student applications
5. **Send Feedback**: Accept/reject applications with feedback

### **For Administrators**
1. **Login**: Access admin panel with admin credentials
2. **User Management**: Approve/reject user registrations
3. **Position Management**: Review and approve training positions
4. **Analytics**: View comprehensive reports and statistics
5. **Communications**: Send system-wide announcements

## 🔧 **Technical Stack**

- **Frontend**: HTML5, Bootstrap 5, jQuery, Font Awesome
- **Backend**: PHP 7.4+, Yii2 Framework
- **Database**: MySQL/MariaDB
- **Features**: AJAX, Real-time notifications, Email integration

## 📊 **Database Structure**

### **Core Tables**
- `user` - User accounts and authentication
- `student` - Student profiles and information
- `organization` - Organization profiles
- `position` - Training opportunities
- `application` - Student applications
- `notification` - System notifications
- `admin` - Admin accounts

### **Key Relationships**
- Students can apply to multiple positions
- Organizations can post multiple positions
- Notifications link to users, admins, and organizations
- Applications track student-position relationships

## 🚀 **Getting Started**

### **Development Server Setup**
To start the development server locally:

**For Windows (PowerShell):**
```powershell
# Navigate to project directory
cd C:\xampp\htdocs\my_project

# Start development server
php yii serve --port=8080
```

**For Windows (Command Prompt):**
```cmd
# Navigate to project directory
cd C:\xampp\htdocs\my_project

# Start development server
php yii serve --port=8080
```

**For Linux/Mac:**
```bash
# Navigate to project directory
cd /path/to/my_project

# Start development server
php yii serve --port=8080
```

**Access URLs:**
- **Frontend**: `http://localhost:8080`
- **Backend**: `http://localhost:8080/backend/web/`

### **Production Setup**
1. `composer install --no-dev --optimize-autoloader`
2. `php init --env=Production --overwrite=All`
3. Set environment variables (see `.env.example`) on your host
4. `php yii migrate --interactive=0`
5. Ensure `runtime/`, `web/assets/`, and `common/runtime/` are writable
6. Point web roots to `frontend/web/` and `backend/web/` only
7. **Do not** run `php yii sample-data` in production

See `documentation/Developer_Guide.md` §10 for the full deployment checklist.

## 📞 **Support**

For technical support or questions:
- Check the documentation in each module
- Review the code comments for implementation details
- Test with the provided sample data

## 🎉 **System Status**

✅ Feature-complete Field Training Platform  
✅ Yii2 Advanced structure (frontend / backend / common / console)  
✅ Security helpers, RBAC, and gitignored local config  
✅ Production config templates under `environments/prod/`

Before going live: rotate any OAuth keys that were ever stored in git, set production env vars, disable debug/gii, and run through `documentation/Developer_Guide.md` deployment checklist. 