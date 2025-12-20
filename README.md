# M-Pesa Chama Management System

A comprehensive web-based platform for managing Chamas (savings groups) with secure M-Pesa integration.

## Features

- **User Authentication**: Role-based access for Admin, Treasurer, and Members
- **M-Pesa Integration**: Automatic contributions via STK Push and C2B payments
- **Loan Management**: Apply, approve, and track loans with interest calculations
- **Financial Tracking**: Contributions, fines, and transaction management
- **Dashboard Views**: Customized dashboards for each user role
- **Reporting**: Generate accurate monthly and annual financial reports

## Technologies Used

- PHP (Object-Oriented Programming)
- MySQL Database
- HTML5 & CSS3
- JavaScript
- PDO for database operations

## Installation

1. Clone the repository:
```bash
git clone https://github.com/your-username/mpesa-chama.git
```

2. Set up your web server (Apache/Nginx) with PHP support

3. Create a MySQL database named `chama_db`

4. Run the database setup:
```bash
php setup_database.php
```

5. Add sample data (optional):
```bash
php sample_data.php
```

6. Update database connection in `DatabaseClass.php` if needed

## Database Schema

The system includes 8 core tables:
- users: User authentication and profile information
- members: Detailed member information
- contributions: Tracking member contributions
- loans: Loan applications and management
- loan_repayments: Tracking loan repayment schedules
- fines: Tracking member fines
- mpesa_transactions: M-Pesa payment tracking
- settings: System configuration

## User Roles

- **Admin**: Full system access, user management
- **Treasurer**: Financial management, record contributions and fines
- **Member**: View personal financial data, apply for loans

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements to prevent SQL injection
- Session-based authentication
- Role-based access control

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open source and available under the [MIT License](LICENSE).