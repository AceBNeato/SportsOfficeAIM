# Sports and Cultural Management System (2IT) Group 6

A comprehensive desktop-based Document Archiver for the Sports and Cultural Office of the University of Southeastern Philippines (USeP) Tagum-Mabini Campus. Built on Laravel 12 with Vite-powered assets, managing sports and cultural activities, document submissions, point-based evaluations, and equipment tracking.

## System Overview

The Sports and Cultural Management System serves as a digital solution for the USeP Tagum-Mabini Campus Sports and Cultural Office to:
- Manage student/alumni sports and cultural participation and achievements
- Handle document submissions and approval workflows with digital archiving
- Implement point-based evaluation system for awards and recognitions
- Provide secure multi-role user management (Admin and Student roles)
- Streamline administrative operations for sports and cultural activities
- Monitor sports equipment borrowing and returns with automated sanction system
- Facilitate fast data retrieval, filtering, and comparison capabilities

---

## 2. Key Features

### User Management
- **Two-Tier Role System**: Admin (Faculty/Staff) and Student users
- **Account Approval Workflow**: Students submit requests with documents for admin approval
- **Profile Management**: Users can update profiles and upload images
- **Session Management**: Secure login with timeout and CSRF protection
- **User Base Management**: Comprehensive user directory with role-based access control
- **Multi-Factor Authentication**: Enhanced security with MFA support

### Super Admin Features
- **System Administration**: Full system control and configuration
- **User Role Management**: Assign and manage user permissions across all roles
- **System Settings**: Configure application-wide settings and preferences
- **Audit Control**: Access to all system logs and administrative records
- **Emergency Controls**: System maintenance mode and emergency access controls

### Activity Logging & Monitoring
- **Comprehensive Audit Trail**: Log all user actions, system changes, and administrative activities
- **Real-time Monitoring**: Track user logins, file uploads, document approvals
- **Security Logs**: Monitor failed login attempts, permission changes, and security events
- **Administrative Actions**: Record all admin decisions, approvals, and rejections
- **Report Generation**: Generate detailed activity reports for compliance and auditing

### Document Management
- **Document Submission**: Students submit various document types (PDF, DOC, DOCX, JPG, PNG)
- **Approval Workflow**: Admins can approve/reject submissions with comments
- **File Storage**: Secure document storage with proper access controls
- **Download Functionality**: Secure file downloads with proper headers

### Achievement Tracking
- **Point-Based Evaluation System**: Scoring method for assessing student achievements in sports and cultural activities for awards and recognition
- **Campus Leaderboard**: Ranking system displaying student standings based on accumulated points
- **Total Points Tracking**: Cumulative score monitoring for individuals and teams
- **Award Recognition System**: Admins can record student achievements based on point accumulation
- **Multiple Award Types**: Championships, medals, certificates, scholarships
- **Document Attachment**: Supporting documents for achievements
- **Student Search**: Real-time student lookup for award assignment

### Administrative Features
- **Dashboard**: Comprehensive admin interface with multiple modules
- **User Management**: Add, edit, delete users
- **Reports and Analytics**: Student counts, submission statistics
- **Account Approvals**: Manage new account requests

### Sports Equipment Management
- **Equipment Inventory**: Complete catalog of sports supplies and tools with categorization
- **Borrowing System**: Track equipment checkouts with student identification and timestamps
- **Due Date Management**: Automatic tracking of return deadlines with configurable borrowing periods
- **Sanction System**: Automated penalty imposition for late returns including:
  - Warning notifications for first offenses
  - Temporary borrowing restrictions for repeat violations
  - Official sanctions recorded in student profiles
  - Escalating penalty tiers based on delay duration
- **Equipment Status**: Real-time availability tracking and maintenance scheduling
- **Borrowing History**: Complete audit trail of all equipment transactions
- **Student Accountability**: Link equipment borrowing to student records for comprehensive monitoring

---

## 3. Tech Stack

- **Backend:** PHP 8.2, Laravel 12
- **Frontend:** Vite, Vue 3, Inertia.js, TailwindCSS 4
- **Database:** SQLite (default) / MySQL / MariaDB
- **Runtime:** Node.js 18+, Composer 2+
- **Recommended Dev Environment:** Laragon on Windows (ships with required PHP extensions)

---

## 4. System Requirements

### Hardware Requirements

| Component | Minimum Requirements | Recommended Requirements |
|-----------|---------------------|---------------------------|
| **Client Machine** | Intel i5 processor, 8GB RAM, 256GB SSD, Windows 10 or Linux OS | Intel i7 or higher, 16GB RAM, 512GB SSD, Windows 11 or latest Linux OS |
| **Server Machine** | Intel Xeon processor, 16GB RAM, 1TB HDD/SSD, Windows Server 2019 or Linux-based OS | Intel Xeon multi-core, 32GB RAM, 1TB SSD, Latest Windows Server or Linux OS |
| **Backup Storage** | External hard drives or cloud storage | Automated cloud backup with redundancy (e.g., AWS S3, Google Drive Enterprise) |

### Software Requirements

| Component | Minimum Requirements | Recommended Requirements |
|-----------|---------------------|---------------------------|
| **Database Management System** | MySQL 5.7 or Microsoft SQL Server 2016 | MySQL 8.0 or Microsoft SQL Server 2019 or newer |
| **Development Environment** | Visual Studio 2019, Eclipse, or similar IDE | Visual Studio 2022 or IntelliJ IDEA with full plugin support |
| **Web Server** | Apache 2.4, Nginx 1.18, or IIS 10 | Apache 2.4 or newer, Nginx latest stable, or IIS with optimized module configurations |
| **Security Software** | Basic antivirus (e.g., Windows Defender) and anti-malware | Enterprise-level endpoint protection (e.g., Bitdefender, Norton, or Malwarebytes Premium) |
| **Backup Software** | Manual or scheduled backup tools (e.g., Windows Backup, rsync) | Automated cloud-integrated backup solutions (e.g., Acronis, Veeam, or AWS Backup) |

### Network Requirements

| Component | Minimum Requirements | Recommended Requirements |
|-----------|---------------------|---------------------------|
| **Internet Connection** | Stable broadband (DSL/Fiber) with at least 10 Mbps download speed | High-speed fiber connection with 50 Mbps or higher, with redundancy/failover option |
| **Local Network** | Basic wired or wireless LAN supporting at least 100 Mbps | Gigabit Ethernet for wired connections; Wi-Fi 5/6 for wireless coverage |
| **Firewall** | Basic software firewall (e.g., Windows Firewall) | Dedicated hardware firewall (e.g., Cisco ASA, Fortinet) and enterprise-level software firewall (e.g., pfSense) |

### Other Facilities

- **User Training Facilities**: Designated room with computers, projector, and internet for hands-on training
- **Technical Support**: Helpdesk unit with ticketing system and trained IT personnel
- **Support Availability**: Peak hours support (8 AM - 5 PM weekdays) with emergency off-hours support
- **Support Channels**: Multichannel support (email, hotline, knowledge base integration)

> **Campus Restriction**: System is designed exclusively for USeP Tagum-Mabini Campus users and requires on-campus access or VPN connection.

---

## 5. Quick Start (Fresh Install)

```bash
# 1. Clone project
git clone <repository-url>
cd SOAPS

# 2. Install PHP dependencies
composer install

# 3. Environment + app key
cp .env.example .env
php artisan key:generate

# 4. Configure .env (optional - defaults work for development)
#   - DB_CONNECTION=sqlite (default) or mysql for production
#   - DB_DATABASE=soaps (for MySQL)
#   - DB_USERNAME=root
#   - DB_PASSWORD=your_password
#   - APP_URL=http://soaps.test (or your domain)

#   - MAIL_MAILER=smtp
#   - MAIL_HOST=smtp.gmail.com
#   - MAIL_PORT=587
#   - MAIL_USERNAME=your-email@gmail.com
#   - MAIL_PASSWORD=your-app-password
#   - MAIL_ENCRYPTION=tls
#   - MAIL_FROM_ADDRESS=your-email@gmail.com
#   - MAIL_FROM_NAME="${APP_NAME}"

# 5. Run migrations + seeders (creates default user)
php artisan migrate --seed

# 6. Link storage for uploaded files (REQUIRED)
php artisan storage:link

# 7. Install and compile assets
npm install
npm run build   # production assets
# or npm run dev for hot reload during development

# 8. Serve application
php artisan serve
# Visit: http://127.0.0.1:8000
```

---

## 6. Scope and Limitations

### Scope

**Primary Users:**
- **Students**: Enrolled athletes and artists participating in university-approved sports and cultural events
- **Admins**: Faculty designated to manage the Sports and Cultural Office operations

**Functions:**
- Digital document submission, validation, and archiving
- Automated evaluation for awards and recognitions based on a point system
- Search and filter functionalities for quick access to stored data
- Sports equipment borrowing and return tracking with sanction system
- Campus leaderboard and point accumulation monitoring

**Access:**
- Limited to on-campus users at USeP Tagum-Mabini Campus
- VPN access available for authorized remote connections

### Limitations

- **Exclusion of Academic Records**: The system focuses solely on sports and cultural documents and does not handle academic records or achievements
- **Restricted User Base**: Designed exclusively for USeP Tagum-Mabini students, alumni and faculty; not accessible to external users
- **Dependency on Hardware and Internet Connection**: Requires compatible desktop computers and consistent power supply to function effectively
- **Manual Inputs for Validation**: Some document validation processes may still require manual review to ensure accuracy and compliance

## 7. Target Users

### Admins (Faculty/Staff)
- Complete system administration and configuration
- Manage student accounts and approval workflows
- Record and track student achievements using point-based evaluation
- Generate reports and analytics from dashboard
- Oversee equipment borrowing and return processes
- Monitor and enforce sanction policies for late returns
- Process document submissions and maintain digital archives

### Students/Alumni
- Submit sports and cultural-related documents and applications
- Track personal achievements and participation records
- Monitor accumulated points and campus leaderboard standings
- Update profiles and manage account information
- View approval status of submitted documents
- Browse available sports equipment and supplies
- Submit equipment borrowing requests with specified return dates
- Monitor borrowing history and current equipment status
- Receive notifications for upcoming due dates and sanctions

---

## 8. System Benefits

### Tangible Benefits
- **Reduction in operational costs**: Decreased need for physical storage and printed documents, saving on paper and printing expenses
- **Time savings**: Faster document processing and retrieval for faculty and students, leading to more timely participation in events
- **Improved accuracy**: Minimized manual errors through automated validation and processing

### Intangible Benefits
- **Improved user satisfaction**: Students face less frustration with simplified submission and tracking processes
- **Improved productivity**: Faculty can focus on strategic tasks instead of administrative burdens
- **Data security**: Better protection of sensitive student information with a controlled and digital system

---

## 9. Security Features

### Information Security Mechanisms

**User Authentication:**
- **Secure Login System**: Unique usernames and passwords for both students and faculty/admins
- **Multi-Factor Authentication (MFA)**: Enhanced security during the login process
- **Session Management**: Timeout protection and session regeneration

**Access Control:**
- **Role-Based Access Control (RBAC)**: Users can only access information and functionalities relevant to their roles
- **Regular Audits**: Periodic review of user access rights to ensure compliance and security

**Data Encryption:**
- **In Transit Protection**: SSL/TLS protocols for secure data transmission
- **At Rest Protection**: AES encryption for sensitive data storage
- **Secure Connections**: All document uploads and downloads conducted over encrypted channels

**Audit Trails:**
- **Activity Logging**: Comprehensive logs of user activities within the system
- **Access Tracking**: Monitor access and modifications to sensitive data
- **Regular Reviews**: Periodic audit log analysis to identify unauthorized access or anomalies

**Data Privacy Compliance:**
- **Regulatory Compliance**: Adherence to relevant data protection regulations (e.g., GDPR, local data privacy laws)
- **Data Retention Policies**: Responsible management of personal data with defined retention and deletion policies

### Technical Security Features
- **Password Hashing**: Secure password storage using PHP's password_hash()
- **CSRF Protection**: Cross-site request forgery protection with tokens
- **Input Validation**: Comprehensive input sanitization and validation
- **File Security**: MIME type verification and secure file handling
- **SQL Injection Prevention**: Prepared statements and parameterized queries

---

## 10. Seeded Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin (Faculty/Staff) | admin@scms.usep.edu.ph | admin123 |
| Admin (Backup) | sports.admin@scms.usep.edu.ph | admin123 |
| Student | student@scms.usep.edu.ph | student123 |
| Test User | test@example.com | password |

> **Important:** Update all default credentials immediately in production. The Admin accounts have full system access including user management, document processing, and system configuration. Additional users can be created through the application interface or by modifying `database/seeders/DatabaseSeeder.php`.

---

## 11. Useful Artisan & npm Commands

| Command | Purpose |
|---------|---------|
| `php artisan migrate:fresh --seed` | Reset database and reseed demo data |
| `php artisan storage:link` | **Important:** expose `storage/app/public` via `public/storage` |
| `php artisan queue:work` | Run queued jobs (if using notifications or async tasks) |
| `php artisan optimize:clear` | Clear config, route, view caches |
| `php artisan config:clear && php artisan cache:clear` |
| `npm run dev` | Watch + hot reload assets |
| `npm run build` | Compile & minify production assets |
| `npm run build:ssr` | Build with server-side rendering support |

### Composer Scripts

- `composer run dev` ⇒ boots Laravel server, queue listener, and Vite concurrently (if configured)
- `composer run setup` ⇒ full install workflow (composer + key + migrate + npm build) (if configured)

---

## 12. Environment Tips

- **File Uploads:** uploaded files live in `storage/app/public`. Missing images usually mean `php artisan storage:link` was skipped.
- **Sessions:** configured via `config/session.php`; adjust timeout/security as needed.
- **Mail & Queues:** set `MAIL_*` + queue driver in `.env` before enabling email alerts or background jobs.
- **Database:** Default uses SQLite for easy setup. Switch to MySQL/MariaDB for production by updating `DB_CONNECTION` and related settings.
- **Logging:** check `storage/logs/laravel.log` when debugging.

---

## 13. Project Map

```
app/
├── Http/
│   ├── Controllers/        # Web controllers
│   ├── Middleware/         # Custom middleware
│   └── Kernel.php          # Middleware registration
├── Models/                 # Eloquent models (User, etc.)
└── Services/               # Business logic services

database/
├── migrations/             # Schema definition
└── seeders/                # Default data seeding

resources/
├── views/                  # Blade templates
├── js/                     # Vue.js components and entry points
│   ├── app.ts             # Main application entry
│   └── ssr.ts             # Server-side rendering entry
└── css/                    # TailwindCSS styles

public/
├── storage/ -> ../storage/app/public (symlink)
└── build/                  # Vite compiled assets
```

---

## 14. Testing & Quality

```bash
# Run automated tests
php artisan test

# Code formatting (if Pint is installed)
./vendor/bin/pint

# Lint JavaScript/TypeScript (if configured)
npm run lint
```

---

## 15. Common Issues & Solutions

### Vite Manifest Not Found Error
If you encounter `ViteManifestNotFoundException`, run:
```bash
npm run build
```
This generates the missing `public/build/manifest.json` file.

### Storage Link Issues
If uploaded files are not displaying:
```bash
php artisan storage:link
```

### Cache Issues
After environment changes, clear caches:
```bash
php artisan optimize:clear
```

---

## 16. Maintenance Checklist

- Clear caches after env/config changes:
  ```bash
  php artisan optimize:clear
  ```
- Update dependencies regularly:
  ```bash
  composer update
  npm update
  ```
- Back up `.env` and database before deployment.
- Configure HTTPS and trusted proxies when deploying behind load balancers.
- For production, switch from SQLite to MySQL/MariaDB and configure proper database credentials.

---

## 17. Development Workflow

1. **Setup:** Follow the Quick Start guide above
2. **Development:** Use `npm run dev` for hot reload during frontend changes
3. **Backend:** Use `php artisan serve` for the Laravel development server
4. **Testing:** Run `php artisan test` before committing changes
5. **Deployment:** Use `npm run build` to compile production assets

---

## 18. License & Attribution

This project is developed for the University of Southeastern Philippines (USeP) Tagum-Mabini Campus Sports and Cultural Office by Group 6 of the 2IT program.

<div align="center">
  <sub>Maintained by the Sports and Cultural Management System development team for USeP Tagum-Mabini Campus.</sub>
</div>
