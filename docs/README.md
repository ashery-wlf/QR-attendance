# QR Attendance System

A web-based attendance management system using QR codes for events. Track event attendance in real-time with automatic QR code generation and scanning capabilities.

## Features

✅ **User Management**
- User registration and login
- Session-based authentication
- User profile management

✅ **Event Management**
- Create and manage events
- Set event dates, times, and venues
- Support for public and private events
- Event registration modes (self-registration, access codes)

✅ **Attendance Tracking**
- Real-time QR code scanning
- Automatic attendance recording
- Device information logging
- Attendance history and statistics

✅ **Reporting & Analytics**
- Attendance reports by event
- Device statistics
- Attendance tracking dashboard

✅ **Responsive Design**
- Desktop and mobile-friendly interface
- Touch-optimized QR scanning
- Responsive sidebar navigation

## Quick Start

### Prerequisites
- XAMPP (Apache, MySQL, PHP)
- Web browser (Chrome, Firefox, Safari, Edge)
- PHP 7.x or higher

### Installation

1. **Extract to XAMPP**
   ```
   Extract project to: C:\xampp\htdocs\QR attendance\
   ```

2. **Start XAMPP**
   - Start Apache
   - Start MySQL

3. **Initialize Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create new database: `qr_attendance`
   - Import SQL schema: `includes/database-structure.sql`

4. **Access Application**
   ```
   http://localhost/QR%20attendance/login.php
   ```

### Default Routes

| URL | Purpose |
|-----|---------|
| `/login.php` | Login page |
| `/registrer.php` | User registration |
| `/dashboard.php` | Main dashboard |
| `/events.php` | Event listing |
| `/create-event.php` | Create new event |
| `/event.php?id=X` | Event details |
| `/scan.php` | QR code scanner |
| `/attendance.php` | Attendance records |
| `/report.php` | Reports & analytics |

## User Guide

### For Event Organizers

1. **Create Event**
   - Navigate to "Create Event"
   - Fill in event details (name, date, time, venue)
   - Set registration mode (self-register or code-based)
   - Event is automatically created

2. **Monitor Attendance**
   - Go to "Reports" section
   - View real-time attendance statistics
   - Track device information
   - Export attendance data if needed

### For Attendees

1. **Register & Login**
   - Go to registration page
   - Enter name, email, phone, password
   - Login with credentials

2. **Join Event**
   - Browse available events in "Events" section
   - Click to join event
   - Wait for event to start

3. **Mark Attendance**
   - During event, open QR scanner
   - Allow camera access
   - Scan QR code displayed by organizer
   - Attendance automatically recorded

4. **View History**
   - Go to "Attendance" section
   - See all attended events
   - Track attendance records

## System Architecture

See [ARCHITECTURE-FLOW.md](ARCHITECTURE-FLOW.md) for detailed system architecture and data flow diagrams.

## File Structure

```
QR attendance/
├── login.php                 # Authentication
├── registrer.php             # User registration
├── dashboard.php             # Main dashboard
├── events.php                # Event browser
├── create-event.php          # Event creator
├── event.php                 # Event details
├── scan.php                  # QR scanner
├── attendance.php            # Attendance records
├── report.php                # Reports & analytics
├── generate-qr.php           # QR generation API
├── save-attendance.php       # Attendance API
├── logout.php                # Session logout
├── logo.png                  # Application logo
├── includes/
│   ├── app.php              # App utilities & UI shell
│   ├── auth.php             # Authentication middleware
│   ├── db.php               # Database connection
│   ├── style.css            # Global styles
│   ├── database-structure.sql # Database schema
│   └── users-table-update.sql # DB migrations
├── uploads/                  # Event images directory
└── .vscode/                  # VS Code configuration
```

## Database Setup

### Tables
1. **users** - User accounts and authentication
2. **events** - Event information
3. **participants** - Event registrations
4. **attendance** - Attendance records with timestamps

### Key Columns
- Event registration modes: `self` (open), `code` (access code required)
- Event types: `public`, `private`
- Attendance times with device information

## Security Notes

⚠️ **Important**
- Passwords are hashed using PHP's PASSWORD_DEFAULT algorithm
- Sessions required for protected pages
- User authorization checks on all operations
- Input validation and sanitization implemented

## Troubleshooting

### Can't Access Application
- Verify Apache and MySQL are running in XAMPP
- Check if database is created and imported
- Clear browser cache and cookies

### QR Scanner Not Working
- Check browser permissions for camera access
- Ensure HTTPS or localhost access (camera access requires secure context)
- Try different browser

### Attendance Not Recording
- Verify QR code is valid
- Check database connection
- Ensure user is logged in

## API Endpoints

### QR Generation
```
GET /generate-qr.php?event_id=X&token=ABC
Returns: QR code image
```

### Attendance Recording
```
POST /save-attendance.php
Body: {event_id, token}
Returns: JSON response
```

## Timezone Configuration
- System timezone: Africa/Dar_es_Salaam
- All dates/times stored in UTC
- Automatic conversion for display

## Support & Maintenance

For issues or feature requests, review:
- System logs in error_log
- Database queries in developer console
- Browser console for JavaScript errors

## License
Proprietary - QR Attendance System

## Version
v1.0.0 - April 2026

Swali / Task	Uende page/file gani
Badili background ya login/register page	[includes/style.css (line 37)](C:/xampp/htdocs/QR-attendance/includes/style.css:37), halafu angalia [login.php (line 78)](C:/xampp/htdocs/QR-attendance/login.php:78) na [registrer.php (line 105)](C:/xampp/htdocs/QR-attendance/registrer.php:105)
Badili rangi ya dashboard sidebar	[includes/app.php (line 1836)](C:/xampp/htdocs/QR-attendance/includes/app.php:1836)
Badili rangi ya button kuu kama Create Event, Save, Login	Login/register: [includes/style.css (line 349)](C:/xampp/htdocs/QR-attendance/includes/style.css:349). Dashboard buttons: [includes/app.php (line 1820)](C:/xampp/htdocs/QR-attendance/includes/app.php:1820) au CSS ya page husika
Badili logo ya system	Kwa UI: [system-settings.php (line 332)](C:/xampp/htdocs/QR-attendance/system-settings.php:332). Kwa default logic: [includes/app.php (line 49)](C:/xampp/htdocs/QR-attendance/includes/app.php:49)
Badili system name kutoka “QR Attendance Event” kwenda jina la taasisi	[system-settings.php (line 323)](C:/xampp/htdocs/QR-attendance/system-settings.php:323), default iko [includes/app.php (line 47)](C:/xampp/htdocs/QR-attendance/includes/app.php:47)
Badili rangi ya topbar/mobile navigation	[includes/app.php (line 1962)](C:/xampp/htdocs/QR-attendance/includes/app.php:1962) kwa topbar, [includes/app.php (line 2057)](C:/xampp/htdocs/QR-attendance/includes/app.php:2057) kwa mobile nav
Badili card background au shadow kwenye dashboard	[dashboard.php (line 203)](C:/xampp/htdocs/QR-attendance/dashboard.php:203), hasa .stat-card, cards, panels
Badili rangi za badges kama Present, Late, Absent, Active, Disabled	Attendance badges: [attendance.php (line 724)](C:/xampp/htdocs/QR-attendance/attendance.php:724). Report badges: [report.php (line 466)](C:/xampp/htdocs/QR-attendance/report.php:466). Global badges: [includes/style.css (line 1649)](C:/xampp/htdocs/QR-attendance/includes/style.css:1649)
Badili table header color kwenye reports/attendance	Global table: [includes/style.css (line 1628)](C:/xampp/htdocs/QR-attendance/includes/style.css:1628). Attendance export/table: [attendance.php (line 119)](C:/xampp/htdocs/QR-attendance/attendance.php:119). Report export/table: [report.php (line 146)](C:/xampp/htdocs/QR-attendance/report.php:146)
Badili chart colors kwenye attendance.php au report.php	[attendance.php (line 1308)](C:/xampp/htdocs/QR-attendance/attendance.php:1308), [report.php (line 884)](C:/xampp/htdocs/QR-attendance/report.php:884)
Ongeza field mpya kwenye register/profile mfano College, Year of Study, Program	Register form: [registrer.php (line 135)](C:/xampp/htdocs/QR-attendance/registrer.php:135). Profile drawer: [includes/app.php (line 2195)](C:/xampp/htdocs/QR-attendance/includes/app.php:2195). Database/user schema: [includes/app.php (line 310)](C:/xampp/htdocs/QR-attendance/includes/app.php:310)
Ongeza filter kwenye attendance report mfano department, attendee type, date	[attendance.php (line 1)](C:/xampp/htdocs/QR-attendance/attendance.php:1) na [report.php (line 1)](C:/xampp/htdocs/QR-attendance/report.php:1)
Ongeza export option mfano PDF au Excel format nyingine	Attendance export: [attendance.php (line 109)](C:/xampp/htdocs/QR-attendance/attendance.php:109). Report export: [report.php (line 136)](C:/xampp/htdocs/QR-attendance/report.php:136)
Ongeza role mpya au badili menu kulingana na role	Role/session logic: [includes/auth.php (line 24)](C:/xampp/htdocs/QR-attendance/includes/auth.php:24). Menu logic: [includes/app.php (line 1696)](C:/xampp/htdocs/QR-attendance/includes/app.php:1696)
Ongeza/rekebisha QR scan verification mfano distance limit, time window, phone matching	Scan page: [scan.php (line 1)](C:/xampp/htdocs/QR-attendance/scan.php:1). Submit/save attendance: [attendance-submit.php (line 1)](C:/xampp/htdocs/QR-attendance/attendance-submit.php:1), [save-attendance.php (line 1)](C:/xampp/htdocs/QR-attendance/save-attendance.php:1)
Badili text za Help/FAQ ndani ya system-settings.php	UI form: [system-settings.php (line 340)](C:/xampp/htdocs/QR-attendance/system-settings.php:340). Default text: [includes/app.php (line 50)](C:/xampp/htdocs/QR-attendance/includes/app.php:50)