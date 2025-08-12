# Missing Persons Platform

A comprehensive web application for reporting and managing missing persons cases, built with Laravel, Inertia.js, and Vue.js.

## Features

- **User Authentication & Authorization** - Role-based access (Family, Volunteer, Admin)
- **Missing Person Reports** - Create and manage missing person cases
- **Sighting Reports** - Report sightings with location tracking
- **Google Maps Integration** - Interactive maps with draggable markers
- **Admin Dashboard** - Comprehensive management panel
- **System Logging** - Complete activity tracking and audit trail
- **Responsive Design** - Modern UI with Tailwind CSS

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.1
- **Composer** >= 2.0
- **Node.js** >= 16.0
- **npm** >= 8.0
- **MySQL** >= 8.0 or **PostgreSQL** >= 13.0
- **Git**

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd missing-persons-platform
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Setup

Copy the environment file and configure your database:

```bash
cp .env.example .env
```

Edit `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=missing_persons_platform
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Google Maps API Key (Required for map functionality)
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Create Storage Link

```bash
php artisan storage:link
```

### 8. Build Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### 9. Start the Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Required Dependencies

### PHP Dependencies (composer.json)

```json
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "inertiajs/inertia-laravel": "^1.0",
        "barryvdh/laravel-dompdf": "^2.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.26",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.1",
        "phpunit/phpunit": "^11.1",
        "spatie/laravel-ignition": "^2.4"
    }
}
```

### Node.js Dependencies (package.json)

```json
{
    "dependencies": {
        "@inertiajs/vue3": "^1.0.0",
        "@vitejs/plugin-vue": "^5.0.0",
        "autoprefixer": "^10.4.16",
        "axios": "^1.6.0",
        "laravel-vite-plugin": "^1.0.0",
        "postcss": "^8.4.32",
        "tailwindcss": "^3.4.0",
        "vite": "^5.0.0",
        "vue": "^3.4.0"
    },
    "devDependencies": {
        "@tailwindcss/forms": "^0.5.7"
    }
}
```

## Google Maps API Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the following APIs:
   - Maps JavaScript API
   - Places API
   - Geocoding API
4. Create credentials (API Key)
5. Add the API key to your `.env` file:
   ```
   GOOGLE_MAPS_API_KEY=your_api_key_here
   ```

## Database Schema

### Users Table
- `id` - Primary key
- `name` - User's full name
- `email` - Email address (unique)
- `password` - Hashed password
- `role` - User role (family, volunteer, admin)
- `phone` - Phone number
- `email_verified_at` - Email verification timestamp
- `created_at`, `updated_at` - Timestamps

### Missing Reports Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `name` - Missing person's name
- `age` - Age
- `gender` - Gender
- `height` - Height
- `weight` - Weight
- `last_seen_location` - Last known location
- `last_seen_date` - Date last seen
- `description` - Physical description
- `photo_path` - Photo file path
- `police_report_path` - Police report file path
- `status` - Report status
- `created_at`, `updated_at` - Timestamps

### Sighting Reports Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `missing_report_id` - Foreign key to missing_reports table
- `location` - Sighting location
- `description` - Sighting description
- `sighted_at` - Date/time of sighting
- `photo_paths` - JSON array of photo paths
- `reporter_name` - Reporter's name
- `reporter_phone` - Reporter's phone
- `reporter_email` - Reporter's email
- `status` - Report status (Pending, Approved, Rejected)
- `created_at`, `updated_at` - Timestamps

### System Logs Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `action` - Action performed
- `description` - Detailed description
- `ip_address` - User's IP address
- `user_agent` - Browser user agent
- `metadata` - JSON metadata
- `created_at`, `updated_at` - Timestamps

## Usage

### User Roles

1. **Family** - Can create missing person reports and view their own reports
2. **Volunteer** - Can view all missing person reports and submit sighting reports
3. **Admin** - Full access to all features including user management and system logs

### Key Features

#### Missing Person Reports
- Create detailed missing person reports
- Upload photos and police reports
- Track report status

#### Sighting Reports
- Submit sighting reports for specific missing persons
- Use interactive Google Maps for location selection
- Include photos and contact information

#### Admin Dashboard
- View system statistics
- Manage missing person reports
- Review and approve/reject sighting reports
- Monitor system logs
- Manage users and roles

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

### Database Seeding

```bash
php artisan db:seed
```

## Deployment

### Production Build

1. Set environment to production:
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

2. Build frontend assets:
   ```bash
   npm run build
   ```

3. Optimize Laravel:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Server Requirements

- PHP >= 8.1
- MySQL >= 8.0 or PostgreSQL >= 13.0
- Web server (Apache/Nginx)
- SSL certificate (recommended)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support and questions, please contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- User authentication and authorization
- Missing person reports
- Sighting reports with Google Maps integration
- Admin dashboard
- System logging
- Responsive design
