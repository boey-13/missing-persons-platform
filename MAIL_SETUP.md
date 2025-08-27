# Email Configuration for FindMe

## Overview
FindMe sends welcome emails to new users upon successful registration. This document explains how to configure email functionality.

## Current Configuration
- **Default Mailer**: `log` (emails are logged to storage/logs/laravel.log)
- **From Address**: `noreply@findme.com`
- **From Name**: `FindMe`

## Email Features
1. **Welcome Email**: Sent to new users after registration
2. **Password Reset**: Already configured for password reset functionality

## Configuration Options

### For Development (Current Setup)
Emails are logged to `storage/logs/laravel.log` for testing purposes.

### For Production
To send actual emails, update your `.env` file with one of these configurations:

#### Option 1: Gmail SMTP
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="FindMe"
```

#### Option 2: Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-mailgun-secret
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="FindMe"
```

#### Option 3: SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="FindMe"
```

## Testing Email Functionality

### Test Command
Use the provided test command to verify email functionality:
```bash
php artisan test:welcome-email your-email@example.com
```

### Check Logs
If using the `log` driver, check the email content in:
```bash
tail -f storage/logs/laravel.log
```

## Email Templates
- **Welcome Email**: `resources/views/emails/welcome.blade.php`
- **Password Reset**: Uses Laravel's default password reset templates

## Troubleshooting

### Common Issues
1. **Emails not sending**: Check mail configuration in `.env`
2. **Authentication errors**: Verify SMTP credentials
3. **Template errors**: Check email template syntax

### Debug Mode
Enable debug mode to see detailed error messages:
```env
APP_DEBUG=true
```

## Security Notes
- Never commit email credentials to version control
- Use environment variables for sensitive information
- Consider using email service providers for production
