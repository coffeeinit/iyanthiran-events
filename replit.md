# Event Registration & Admin Management System

## Overview

This is a PHP-based event management system designed for shared hosting environments. The system provides public event listing and registration functionality with admin management capabilities. It's built with plain PHP 8.x, MySQL, and integrates with Razorpay for payments and PHPMailer for email notifications.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Technology**: Plain PHP with server-side rendering
- **Styling**: Custom CSS with CSS variables for theming
- **JavaScript**: Vanilla JavaScript for client-side interactions
- **Responsive Design**: Bootstrap-inspired responsive grid system
- **User Interface**: Separate styling for public frontend (`style.css`) and admin panel (`admin.css`)

### Backend Architecture
- **Language**: PHP 8.x
- **Database**: MySQL (Production: Hostinger srv1642.hstgr.io)
- **Email Service**: PHP mail() function with SMTP configuration
- **Payment Processing**: Razorpay Live API integration (Production keys configured)
- **Session Management**: PHP sessions for admin authentication
- **File Structure**: Modular PHP files with clear separation of concerns

### Production Configuration
- **Database**: u184589548_eventsphp on Hostinger MySQL
- **Domain**: events.iyanthiran.com
- **Razorpay**: Live keys configured for real payment processing
- **Email**: SMTP configured for info@iyanthiran.com

### Hosting Strategy
- **Environment**: Shared hosting (Hostinger)
- **Deployment**: Standard PHP files with simple .php extensions
- **URL Structure**: Uses standard query parameters (event.php?id=1) for maximum compatibility
- **Domain Replication**: System designed to be replicated across multiple client domains

### Recent Changes (July 24, 2025)
- ✅ Migrated from PostgreSQL to MySQL production database
- ✅ Fixed database schema with missing columns (slug, otp_verified, payment fields)
- ✅ Simplified URL routing - removed complex router, uses standard .php URLs
- ✅ Fixed event viewing and registration functionality  
- ✅ Updated all navigation links to use event.php?id= format
- ✅ Both free and paid event registration flows working with real Razorpay integration

## Key Components

### Public Frontend Components
- **Homepage** (`index.php`): Landing page with featured events
- **Events Listing** (`events.php`): Complete event catalog with filtering
- **Event Details** (`event.php`): Individual event pages with slug-based URLs
- **Registration Handler** (`register.php`): Event registration processing
- **OTP Verification** (`verify_otp.php`): Email verification system
- **Gallery** (`gallery.php`): Event-specific photo galleries

### Admin Management Components
- **Dashboard**: Real-time analytics and system overview
- **Event Management**: CRUD operations for events
- **Registration Management**: User registration oversight
- **Analytics**: UTM campaign tracking and reporting
- **Gallery Management**: Photo upload and organization
- **CSV Export**: Data export functionality

### Authentication & Security
- **Admin Authentication**: Session-based login system
- **OTP Verification**: Email-based user verification
- **Role-Based Access**: Simple admin/visitor role structure
- **Input Validation**: Server-side validation for all forms

## Data Flow

### User Registration Flow
1. User browses events on public pages
2. User initiates registration for free/paid events
3. System generates OTP and sends via PHPMailer
4. User verifies email with OTP
5. For paid events, Razorpay payment processing occurs
6. Registration confirmation and admin notification

### Admin Management Flow
1. Admin authenticates via login system
2. Admin manages events through CRUD interface
3. System logs all admin actions
4. Analytics data collected and displayed
5. CSV export functionality for reporting

### Analytics & Tracking
1. UTM parameters captured on page visits
2. YouTube video interactions tracked
3. Event card clicks monitored
4. All data stored in MySQL for reporting

## External Dependencies

### Payment Processing
- **Razorpay**: Payment gateway for paid events
- **Integration**: Server-side PHP SDK for secure transactions

### Email Services
- **PHPMailer**: SMTP email sending library
- **Use Cases**: OTP verification, registration confirmations, admin notifications

### Frontend Libraries
- **Custom CSS Framework**: Responsive design system
- **Vanilla JavaScript**: No external JS frameworks
- **Font Integration**: System fonts (Segoe UI family)

## Deployment Strategy

### File Organization
- **Public Files**: Served directly from `public_html/`
- **Admin Files**: Protected in subdirectories
- **Assets**: CSS and JS files organized in `assets/` directory
- **Configuration**: Environment-specific config files

### Database Management
- **MySQL**: Relational database design
- **Schema**: Event management, user registrations, analytics tables
- **Backup Strategy**: Regular database exports via admin panel

### Multi-Domain Support
- **Replication**: System designed for easy deployment across multiple domains
- **Configuration**: Domain-specific settings management
- **Client Isolation**: Each deployment operates independently

### Security Considerations
- **Input Sanitization**: All user inputs filtered and validated
- **Session Security**: Secure session handling for admin access
- **File Upload Security**: Safe file handling for gallery uploads
- **Database Security**: Prepared statements to prevent SQL injection

### Performance Optimization
- **Lightweight Stack**: Minimal dependencies for fast loading
- **Shared Hosting Optimization**: Designed for resource-constrained environments
- **Caching Strategy**: Basic PHP output caching where applicable
- **Image Optimization**: Efficient gallery image handling