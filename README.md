# Missing Persons Platform

A comprehensive web application for reporting, managing, and tracking missing persons cases. Built with modern web technologies, this platform provides a robust solution for families, volunteers, and law enforcement to collaborate in finding missing individuals.

## 🚀 Overview

The Missing Persons Platform is a full-featured web application designed to streamline the process of reporting and managing missing persons cases. It provides a secure, user-friendly interface for families to report missing loved ones, for volunteers to submit sighting reports, and for administrators to manage and coordinate search efforts.

### Key Features

- **🔐 Multi-Role Authentication System** - Secure role-based access control (Family, Volunteer, Admin)
- **📝 Missing Person Reports** - Comprehensive reporting system with photo uploads and detailed information
- **👁️ Sighting Reports** - Real-time sighting submissions with location tracking
- **🗺️ Google Maps Integration** - Interactive maps with draggable markers and geolocation
- **📊 Admin Dashboard** - Comprehensive management panel with analytics
- **📈 Points & Rewards System** - Gamification to encourage community participation
- **🏆 Community Projects** - Collaborative search initiatives and volunteer coordination
- **📧 Email Notifications** - Automated email system for status updates
- **📱 Responsive Design** - Modern, mobile-friendly interface
- **📋 System Logging** - Complete audit trail and activity tracking
- **🔍 Advanced Search & Filtering** - Powerful search capabilities across all data
- **📄 PDF Generation** - Export reports and documents in PDF format

## 🛠️ Technology Stack

### Backend
- **Laravel 11** - PHP framework for robust backend development
- **MySQL/PostgreSQL** - Reliable database management
- **Laravel Sanctum** - API authentication
- **Laravel DomPDF** - PDF generation capabilities

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Inertia.js** - Modern monolith approach
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Fast build tool and development server

### External Services
- **Google Maps API** - Location services and mapping
- **SMTP Services** - Email delivery (Gmail, Mailgun, etc.)

## 📋 Prerequisites

Before you begin, ensure you have the following installed on your system:

### Required Software
- **PHP** >= 8.1 with extensions:
  - BCMath PHP Extension
  - Ctype PHP Extension
  - cURL PHP Extension
  - DOM PHP Extension
  - Fileinfo PHP Extension
  - JSON PHP Extension
  - Mbstring PHP Extension
  - OpenSSL PHP Extension
  - PCRE PHP Extension
  - PDO PHP Extension
  - Tokenizer PHP Extension
  - XML PHP Extension
- **Composer** >= 2.0
- **Node.js** >= 16.0
- **npm** >= 8.0
- **MySQL** >= 8.0 or **PostgreSQL** >= 13.0
- **Git** >= 2.0

### Optional but Recommended
- **Redis** - For caching and session storage
- **Supervisor** - For queue management in production
- **Nginx/Apache** - Web server for production deployment

## 🚀 Installation Guide

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd missing-persons-platform
```

### Step 2: Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

For development:
```bash
composer install
```

### Step 3: Install Node.js Dependencies

```bash
npm install
```

### Step 4: Environment Configuration

Copy the environment file and configure your settings:

```bash
cp .env.example .env
```

Edit the `.env` file with your configuration:

```env
# Application Settings
APP_NAME="Missing Persons Platform"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=missing_persons_platform
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Google Maps API (Required for map functionality)
GOOGLE_MAPS_API_KEY=your_google_maps_api_key

# File Storage
FILESYSTEM_DISK=public

# Queue Configuration (for background jobs)
QUEUE_CONNECTION=database
```

### Step 5: Generate Application Key

```bash
php artisan key:generate
```

### Step 6: Database Setup

Create the database and run migrations:

```bash
php artisan migrate
```

For development, you can seed the database with sample data:

```bash
php artisan db:seed
```

### Step 7: Storage Configuration

Create the storage link for file uploads:

```bash
php artisan storage:link
```

### Step 8: Build Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### Step 9: Start the Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## 🔧 Configuration

### Google Maps API Setup

1. **Create Google Cloud Project**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or select an existing one

2. **Enable Required APIs**
   - Maps JavaScript API
   - Places API
   - Geocoding API
   - Directions API

3. **Create API Key**
   - Navigate to "Credentials" in the Google Cloud Console
   - Click "Create Credentials" → "API Key"
   - Copy the generated API key

4. **Configure API Key**
   - Add the API key to your `.env` file:
   ```
   GOOGLE_MAPS_API_KEY=your_api_key_here
   ```

5. **Set API Key Restrictions** (Recommended for production)
   - Restrict the API key to your domain
   - Enable only the required APIs

### Email Configuration

Configure your email service in the `.env` file:

#### Gmail Example:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

#### Mailgun Example:
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your_domain.com
MAILGUN_SECRET=your_mailgun_secret
```

## 📊 Database Schema

### Core Tables

#### Users Table
```sql
- id (Primary Key)
- name (VARCHAR) - User's full name
- email (VARCHAR, Unique) - Email address
- password (VARCHAR) - Hashed password
- role (ENUM) - User role (family, volunteer, admin)
- phone (VARCHAR) - Phone number
- ic_number (VARCHAR) - IC/ID number
- created_at, updated_at (TIMESTAMP)
```

#### Missing Reports Table
```sql
- id (Primary Key)
- user_id (Foreign Key) - Reporter's user ID
- full_name (VARCHAR) - Missing person's full name
- ic_number (VARCHAR) - IC/ID number
- nickname (VARCHAR) - Nickname or alias
- age (INTEGER) - Age
- gender (ENUM) - Gender (Male, Female, Other)
- height_cm (INTEGER) - Height in centimeters
- weight_kg (DECIMAL) - Weight in kilograms
- physical_description (TEXT) - Physical description
- last_seen_date (DATE) - Date last seen
- last_seen_location (VARCHAR) - Last known location
- last_seen_clothing (TEXT) - Clothing description
- photo_paths (JSON) - Array of photo file paths
- police_report_path (VARCHAR) - Police report file path
- reporter_name (VARCHAR) - Reporter's name
- reporter_ic_number (VARCHAR) - Reporter's IC number
- reporter_relationship (VARCHAR) - Relationship to missing person
- reporter_phone (VARCHAR) - Reporter's phone
- reporter_email (VARCHAR) - Reporter's email
- additional_notes (TEXT) - Additional information
- case_status (ENUM) - Status (Pending, Approved, Rejected, Missing, Found, Closed)
- rejection_reason (TEXT) - Reason for rejection if applicable
- created_at, updated_at (TIMESTAMP)
```

#### Sighting Reports Table
```sql
- id (Primary Key)
- user_id (Foreign Key) - Reporter's user ID
- missing_report_id (Foreign Key) - Related missing report
- location (VARCHAR) - Sighting location
- latitude (DECIMAL) - GPS latitude
- longitude (DECIMAL) - GPS longitude
- description (TEXT) - Sighting description
- sighted_at (DATETIME) - Date/time of sighting
- photo_paths (JSON) - Array of photo file paths
- reporter_name (VARCHAR) - Reporter's name
- reporter_phone (VARCHAR) - Reporter's phone
- reporter_email (VARCHAR) - Reporter's email
- status (ENUM) - Status (Pending, Approved, Rejected)
- created_at, updated_at (TIMESTAMP)
```

#### System Logs Table
```sql
- id (Primary Key)
- user_id (Foreign Key) - User who performed the action
- action (VARCHAR) - Action performed
- description (TEXT) - Detailed description
- data (JSON) - Additional data
- ip_address (VARCHAR) - User's IP address
- user_agent (TEXT) - Browser user agent
- created_at, updated_at (TIMESTAMP)
```

### Additional Tables

#### User Points Table
```sql
- id (Primary Key)
- user_id (Foreign Key) - User ID
- points (INTEGER) - Points earned
- source (VARCHAR) - Source of points
- description (TEXT) - Description of points
- created_at, updated_at (TIMESTAMP)
```

#### Rewards Table
```sql
- id (Primary Key)
- name (VARCHAR) - Reward name
- description (TEXT) - Reward description
- points_required (INTEGER) - Points needed
- category_id (Foreign Key) - Reward category
- is_active (BOOLEAN) - Active status
- created_at, updated_at (TIMESTAMP)
```

#### Community Projects Table
```sql
- id (Primary Key)
- title (VARCHAR) - Project title
- description (TEXT) - Project description
- missing_report_id (Foreign Key) - Related missing report
- status (ENUM) - Project status
- start_date (DATE) - Project start date
- end_date (DATE) - Project end date
- created_at, updated_at (TIMESTAMP)
```

## 👥 User Roles & Permissions

### Family Members
**Capabilities:**
- Create and manage missing person reports
- Upload photos and documents
- Track report status and updates
- Receive email notifications
- View sighting reports for their cases
- Participate in community projects
- Earn points for contributions

**Access Restrictions:**
- Can only view their own missing person reports in user profile
- Cannot access admin features
- Cannot approve/reject sighting reports

### Volunteers
**Capabilities:**
- Create missing person reports
- View all approved missing person reports
- Submit sighting reports with location data
- Upload photos and detailed descriptions
- Earn points for contributions
- Participate in community projects
- Access volunteer dashboard

**Access Restrictions:**
- Cannot access admin features
- Cannot approve/reject reports

### Administrators
**Capabilities:**
- Full access to all platform features
- Manage missing person reports (approve/reject/edit)
- Review and approve/reject sighting reports
- Manage user accounts and roles
- View system logs and analytics
- Manage community projects
- Configure system settings
- Generate reports and exports

**Special Features:**
- Admin dashboard with comprehensive analytics
- Bulk operations for report management
- System monitoring and maintenance
- User management and role assignment

## 🎯 Key Features Explained

### Missing Person Reports

The platform provides a comprehensive system for creating and managing missing person reports:

- **Detailed Information Collection**: Name, age, gender, physical characteristics, last seen details
- **Photo Upload**: Multiple photo uploads with automatic optimization
- **Document Upload**: Police reports and other supporting documents
- **Status Tracking**: Real-time status updates (Pending, Approved, Rejected, Missing, Found, Closed)
- **Reporter Information**: Complete contact information for follow-up

### Sighting Reports

Volunteers can submit detailed sighting reports:

- **Location Services**: Interactive Google Maps integration with GPS coordinates
- **Photo Evidence**: Upload photos from the sighting location
- **Detailed Descriptions**: Comprehensive description of the sighting
- **Contact Information**: Reporter's contact details for verification
- **Status Management**: Admin review and approval process

### Admin Dashboard

Comprehensive management interface for administrators:

- **Analytics Overview**: System statistics and key metrics
- **Report Management**: Bulk operations for missing person reports
- **Sighting Review**: Approve/reject sighting reports with reasons
- **User Management**: Manage user accounts and roles
- **System Monitoring**: View logs and system health
- **Community Projects**: Manage collaborative search initiatives

### Points & Rewards System

Gamification features to encourage community participation:

- **Point Earning**: Users earn points for various activities
- **Reward Categories**: Different types of rewards available
- **Redemption System**: Users can redeem points for rewards
- **Leaderboards**: Community engagement tracking

### Community Projects

Collaborative search initiatives:

- **Project Creation**: Admins can create search projects
- **Volunteer Coordination**: Organize search efforts
- **Progress Tracking**: Monitor project status and outcomes
- **Communication Tools**: Keep participants informed

## 🔍 Search & Filtering

### Advanced Search Capabilities

- **Full-Text Search**: Search across all report fields
- **Location-Based Search**: Search by geographic area
- **Date Range Filtering**: Filter by date ranges
- **Status Filtering**: Filter by report status
- **Category Filtering**: Filter by various categories

### Search Features

- **Real-time Search**: Instant search results
- **Saved Searches**: Save frequently used search criteria
- **Export Results**: Export search results in various formats
- **Search History**: Track previous searches

## 📧 Email Notifications

### Automated Email System

The platform includes a comprehensive email notification system:

- **Status Updates**: Notify users of report status changes
- **Sighting Alerts**: Alert families of new sighting reports
- **Project Updates**: Keep project participants informed
- **System Notifications**: Important system announcements

### Email Templates

- **Welcome Emails**: New user registration
- **Status Change Notifications**: Report status updates
- **Sighting Alerts**: New sighting reports
- **Project Updates**: Community project notifications
- **Password Reset**: Account recovery

## 📱 Responsive Design

### Mobile-First Approach

The platform is designed with a mobile-first approach:

- **Responsive Layout**: Adapts to all screen sizes
- **Touch-Friendly Interface**: Optimized for touch devices
- **Fast Loading**: Optimized for mobile networks
- **Offline Capabilities**: Basic functionality without internet

### Design Features

- **Modern UI**: Clean, professional interface
- **Accessibility**: WCAG compliant design
- **Cross-Browser Compatibility**: Works on all modern browsers
- **Performance Optimized**: Fast loading and smooth interactions

## 🔒 Security Features

### Authentication & Authorization

- **Multi-Factor Authentication**: Enhanced security for admin accounts
- **Role-Based Access Control**: Granular permissions system
- **Session Management**: Secure session handling
- **Password Policies**: Strong password requirements

### Data Protection

- **Data Encryption**: Sensitive data encryption
- **File Upload Security**: Secure file handling
- **SQL Injection Prevention**: Parameterized queries
- **XSS Protection**: Cross-site scripting prevention
- **CSRF Protection**: Cross-site request forgery protection

### Privacy Compliance

- **GDPR Compliance**: European privacy regulation compliance
- **Data Retention Policies**: Automatic data cleanup
- **User Consent Management**: Explicit consent tracking
- **Data Export/Deletion**: User data control

## 🚀 Development

### Development Environment Setup

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd missing-persons-platform
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

4. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Start Development Servers**
   ```bash
   # Terminal 1: Laravel development server
   php artisan serve
   
   # Terminal 2: Vite development server
   npm run dev
   ```

### Code Quality

#### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=MissingReportTest

# Run tests with coverage
php artisan test --coverage
```

#### Code Style
```bash
# Fix code style issues
./vendor/bin/pint

# Check code style
./vendor/bin/pint --test
```

#### Static Analysis
```bash
# Run PHPStan
./vendor/bin/phpstan analyse

# Run Larastan
./vendor/bin/larastan analyse
```

### Database Management

#### Migrations
```bash
# Create new migration
php artisan make:migration create_new_table

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Reset database
php artisan migrate:fresh --seed
```

#### Seeding
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder

# Create seeder
php artisan make:seeder NewSeeder
```

### Frontend Development

#### Vue.js Components
- **Component Structure**: Organized component hierarchy
- **Props & Events**: Clean component communication
- **Composition API**: Modern Vue 3 patterns
- **Reactive Data**: Responsive data management

#### Styling
- **Tailwind CSS**: Utility-first styling
- **Custom Components**: Reusable UI components
- **Responsive Design**: Mobile-first approach
- **Dark Mode**: Optional dark theme support

## 🚀 Deployment

### Production Deployment Checklist

#### Pre-Deployment
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up SSL certificate
- [ ] Configure email services
- [ ] Set up Google Maps API key

#### Deployment Steps
1. **Build Assets**
   ```bash
   npm run build
   ```

2. **Optimize Laravel**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan storage:link
   ```

3. **Database Migration**
   ```bash
   php artisan migrate --force
   ```

4. **Queue Setup**
   ```bash
   # Start queue worker
   php artisan queue:work --daemon
   
   # Or use Supervisor for production
   ```

#### Server Requirements

**Minimum Requirements:**
- PHP >= 8.1
- MySQL >= 8.0 or PostgreSQL >= 13.0
- 2GB RAM
- 10GB Storage

**Recommended Requirements:**
- PHP >= 8.2
- MySQL >= 8.0 or PostgreSQL >= 14.0
- 4GB RAM
- 50GB Storage
- Redis for caching
- CDN for static assets

### Deployment Options

#### Traditional Server
- **Apache/Nginx**: Web server configuration
- **SSL Certificate**: HTTPS setup
- **Database Server**: MySQL/PostgreSQL setup
- **File Permissions**: Proper file ownership and permissions

#### Cloud Deployment
- **AWS**: EC2, RDS, S3, CloudFront
- **Google Cloud**: Compute Engine, Cloud SQL, Cloud Storage
- **DigitalOcean**: Droplets, Managed Databases
- **Heroku**: Platform as a Service

#### Docker Deployment
```dockerfile
# Example Dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www
```

## 📊 Monitoring & Maintenance

### System Monitoring

#### Performance Monitoring
- **Application Performance**: Response time monitoring
- **Database Performance**: Query optimization
- **Server Resources**: CPU, memory, disk usage
- **Error Tracking**: Error logging and alerting

#### Security Monitoring
- **Access Logs**: User access tracking
- **Security Events**: Suspicious activity detection
- **Vulnerability Scanning**: Regular security audits
- **Backup Monitoring**: Backup success/failure tracking

### Maintenance Tasks

#### Regular Maintenance
```bash
# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Optimize autoloader
composer dump-autoload --optimize
```

#### Database Maintenance
```bash
# Backup database
php artisan backup:run

# Clean old logs
php artisan log:clear

# Optimize database
php artisan db:optimize
```

## 🤝 Contributing

We welcome contributions to the Missing Persons Platform! Here's how you can help:

### Contribution Guidelines

1. **Fork the Repository**
   - Fork the project on GitHub
   - Clone your fork locally

2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```

3. **Make Your Changes**
   - Write clean, well-documented code
   - Follow the existing code style
   - Add tests for new features
   - Update documentation as needed

4. **Test Your Changes**
   ```bash
   php artisan test
   npm run test
   ```

5. **Submit a Pull Request**
   - Provide a clear description of your changes
   - Include any relevant issue numbers
   - Ensure all tests pass

### Development Standards

#### Code Style
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Keep functions small and focused

#### Testing
- Write unit tests for new features
- Ensure test coverage for critical paths
- Use descriptive test names
- Mock external dependencies

#### Documentation
- Update README for new features
- Document API changes
- Include usage examples
- Maintain changelog

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Getting Help

If you need help with the Missing Persons Platform:

1. **Documentation**: Check this README and inline code comments
2. **Issues**: Search existing issues on GitHub
3. **Discussions**: Use GitHub Discussions for questions
4. **Contact**: Reach out to the development team

### Reporting Issues

When reporting issues, please include:

- **Environment**: OS, PHP version, database type
- **Steps to Reproduce**: Clear, step-by-step instructions
- **Expected Behavior**: What should happen
- **Actual Behavior**: What actually happens
- **Screenshots**: Visual evidence if applicable
- **Error Logs**: Relevant error messages

### Feature Requests

We welcome feature requests! When suggesting new features:

- **Describe the Problem**: What problem does this solve?
- **Propose a Solution**: How should it work?
- **Consider Impact**: How does this affect existing users?
- **Provide Examples**: Show how it would be used

## 📈 Roadmap

### Upcoming Features

- **Mobile App**: Native mobile applications
- **AI Integration**: Machine learning for pattern recognition
- **Social Media Integration**: Automated social media posting
- **Advanced Analytics**: Enhanced reporting and insights
- **Multi-Language Support**: Internationalization
- **API Development**: Public API for third-party integrations

### Planned Improvements

- **Performance Optimization**: Faster loading times
- **Enhanced Security**: Additional security measures
- **Better UX**: Improved user experience
- **Scalability**: Support for larger deployments

## 🙏 Acknowledgments

- **Laravel Team**: For the amazing PHP framework
- **Vue.js Team**: For the progressive JavaScript framework
- **Tailwind CSS**: For the utility-first CSS framework
- **Google Maps API**: For location services
- **Open Source Community**: For the tools and libraries that make this possible

## 📞 Contact

- **Project Maintainer**: Boey
- **Email**: baoyee01@gmail.com
- **GitHub**: https://github.com/boey-13

---

**Note**: This platform is designed to help find missing persons and should be used responsibly. Always work with local law enforcement when dealing with missing persons cases.
