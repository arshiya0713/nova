# Database Setup Instructions

## Prerequisites
- XAMPP installed and running (MySQL/Apache)
- PHP support enabled

## Setup Steps

### 1. Start XAMPP
- Open XAMPP Control Panel
- Click "Start" for Apache
- Click "Start" for MySQL

### 2. Run Database Setup
- Navigate to: `http://localhost/timer/nova/db_setup.php`
- This will create the database and table automatically
- You should see: "Database setup complete! You can now delete this file."
- Delete the `db_setup.php` file after setup is complete

### 3. Verify Database (Optional)
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Login (default: username=root, password is blank)
- Look for `nova_agency` database
- The `contacts` table should be there with columns: id, name, phone, email, address, message, created_at

# Database Setup Instructions

## Prerequisites
- XAMPP installed and running (MySQL/Apache)
- PHP support enabled

## Setup Steps

### 1. Start XAMPP
- Open XAMPP Control Panel
- Click "Start" for Apache
- Click "Start" for MySQL

### 2. Run Database Setup
- Navigate to: `http://localhost/timer/nova/db_setup.php`
- This will create the database and table automatically
- You should see: "Database setup complete! You can now delete this file."
- Delete the `db_setup.php` file after setup is complete

### 3. Verify Database (Optional)
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Login (default: username=root, password is blank)
- Look for `nova_agency` database
- The `contacts` table should have columns: id, ticket_id, name, phone, email, address, message, created_at, updated_at

## How It Works (Ticket-Based System)

### Contact Form Fields
- **Name**: Only letters and spaces allowed
- **Phone**: Exactly 10 digits
- **Email**: Valid email format required (used as unique identifier)
- **Address**: Any text allowed
- **Message**: Any text allowed

### Database Storage - Ticket System
When a user submits the form:
1. JavaScript validates the data on the frontend
2. Data is sent to `contact_handler.php` via AJAX
3. PHP checks if the email already exists in the database
4. **If email exists**: Updates the existing ticket with new message (same row), returns existing ticket_id
5. **If email doesn't exist**: Creates new ticket with unique ticket_id
6. User receives: "Message recorded successfully! Ticket ID: [TICKET-XXXXXXXX]"
7. On resubmission with same email: "Message updated successfully! Ticket ID: [TICKET-XXXXXXXX]"

### Key Features
- **Unique Email Constraint**: One email = one ticket throughout conversation
- **Automatic Ticket ID**: Generated as TICKET-XXXXXXXX format
- **Timestamps**: 
  - `created_at`: When the ticket was first created
  - `updated_at`: Updates automatically when message is changed
- **Message History**: Latest message is stored; previous messages are replaced (ticket row stays the same)

## Troubleshooting

**Error: "Database connection failed"**
- Make sure MySQL is running in XAMPP
- Check username and password in `contact_handler.php`

**Error: "Error connecting to server"**
- Ensure Apache is running in XAMPP
- Check that `contact_handler.php` is in the correct directory

**Form doesn't submit**
- Open browser Developer Tools (F12)
- Check Console for errors
- Check Network tab to see if request was sent to `contact_handler.php`
