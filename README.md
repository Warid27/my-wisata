# Event Ticket Booking System

A web-based event ticket booking system built with PHP Native, MySQL, and Bootstrap 5. This system allows users to browse events, purchase tickets, and manage their bookings, while administrators can manage venues, events, tickets, vouchers, and view comprehensive reports.

## Features

### Core Features
- **User Authentication**: Role-based login system (Admin/User)
- **Event Management**: CRUD operations for venues, events, and tickets
- **Ticket Booking**: Complete booking flow with cart functionality
- **Voucher System**: Discount vouchers with quota management
- **Payment Processing**: Simulated payment with order status tracking
- **Ticket Generation**: Unique ticket codes with QR code support
- **Check-in System**: Digital check-in using ticket codes
- **Dashboard & Reports**: Comprehensive analytics and reporting

### Advanced Features
- **HOTS Analysis**: Higher Order Thinking Skills analysis for:
  - Quota prevention mechanism
  - Sales analytics per event
  - User purchase history
  - Voucher impact analysis
- **PDF Export**: Export reports to PDF format
- **Responsive Design**: Mobile-friendly interface
- **Real-time Updates**: Live quota checking and availability

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional, for PDF export)

### Setup Instructions

1. **Clone/Download the project** to your web server directory

2. **Database Setup**:
   ```sql
   Create a new database named `event_tiket`
   Import the SQL file: `sql/database.sql`
   ```

3. **Configuration**:
   - Edit `config/database.php` to match your database credentials
   - Update `config/config.php` for base URL configuration

4. **File Permissions**:
   ```bash
   chmod 755 assets/images/
   chmod 644 assets/images/*
   ```

5. **Access the Application**:
   - Open your browser and navigate to `http://localhost/my-wisata-pecut/`
   - Default admin login: `admin@event.com` / `password`

## Project Structure

```
my-wisata-pecut/
├── config/                 # Configuration files
│   ├── database.php       # Database connection
│   └── config.php         # Global settings & functions
├── includes/              # Reusable components
│   ├── header.php         # HTML header
│   ├── footer.php         # HTML footer
│   ├── auth.php           # Authentication middleware
│   └── functions.php      # Helper functions
├── admin/                 # Admin panel
│   ├── index.php          # Admin dashboard
│   ├── venue/             # Venue management
│   ├── event/             # Event management
│   ├── tiket/             # Ticket management
│   ├── voucher/           # Voucher management
│   ├── orders.php         # Order management
│   ├── checkin.php        # Check-in system
│   ├── reports.php        # Sales reports
│   └── hots_analysis.php  # HOTS analysis
├── user/                  # User panel
│   ├── index.php          # User dashboard
│   ├── login.php          # User login
│   ├── register.php       # User registration
│   ├── events.php         # Event listing
│   ├── event_detail.php   # Event details
│   ├── order.php          # Order processing
│   ├── payment.php        # Payment page
│   ├── my_tickets.php     # User tickets
│   └── history.php        # Purchase history
├── api/                   # API endpoints
│   ├── order_detail.php   # Order detail API
│   └── export_pdf.php     # PDF export
├── assets/                # Static assets
│   ├── css/style.css      # Custom styles
│   └── images/            # Event images
├── sql/                   # Database scripts
│   └── database.sql       # Database schema
├── index.php              # Landing page
└── logout.php             # Logout handler
```

## Database Schema

The system uses 9 interconnected tables:

1. **users** - User accounts and authentication
2. **venue** - Event venues
3. **event** - Event information
4. **tiket** - Ticket types and pricing
5. **voucher** - Discount vouchers
6. **orders** - Order headers
7. **order_detail** - Order line items
8. **attendee** - Generated tickets

## Usage Guide

### For Users
1. Register an account or login
2. Browse available events
3. Select event and choose tickets
4. Apply voucher codes (if available)
5. Complete payment process
6. Receive e-tickets with QR codes
7. Check-in at event using ticket code

### For Administrators
1. Login with admin credentials
2. Manage venues, events, and tickets
3. Create and manage discount vouchers
4. Monitor orders and payments
5. Process check-ins at events
6. View analytics and reports
7. Export reports to PDF/Excel

## Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- CSRF protection for forms
- Session security configuration
- Role-based access control

## Technical Implementation

### Quota Prevention
The system uses database transactions with `SELECT FOR UPDATE` to prevent overselling:
```php
$db->beginTransaction();
$query = "SELECT kuota FROM tiket WHERE id_tiket = ? FOR UPDATE";
```

### Ticket Code Generation
Unique ticket codes are generated using:
```php
function generate_ticket_code($length = 10) {
    return 'TKT' . date('Ymd') . bin2hex(random_bytes($length/2));
}
```

### Voucher System
Vouchers include:
- Unique codes
- Discount amounts
- Usage quotas
- Status management (active/inactive)

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is for educational purposes as part of the UK assignment.

## Support

For issues or questions:
- Check the HOTS Analysis page for advanced insights
- Review the database schema for understanding relationships
- Consult the code comments for implementation details

## Changelog

### Version 1.0.0 (2024-04-14)
- Initial release with all core features
- Complete CRUD operations
- Booking and payment system
- Check-in functionality
- HOTS analysis implementation
- PDF export feature
- Responsive design implementation
