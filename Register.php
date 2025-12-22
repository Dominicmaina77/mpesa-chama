
<?php
/*
 * Registration Page for Chama Management System
 *
 * This page handles user registration for the Chama system.
 * It implements secure registration functionality with comprehensive validation.
 *
 * Features:
 * - User input validation
 * - Unique email checking
 * - Password confirmation
 * - Terms and conditions agreement
 * - Role selection during registration
 * - SQL injection prevention via DatabaseClass
 *
 * @author ChamaSys Development Team
 * @version 1.0
 * @since 2025
 */

// Include the DatabaseClass which provides OOP database functionality
require_once 'DatabaseClass.php';

// Initialize error and success message variables to empty strings
$error = '';
$success = '';

// Process form submission when POST data is received
if ($_POST) {
    // Sanitize and extract user input data from the form
    $full_name = trim($_POST['name']);                 // Remove whitespace from full name
    $email = trim($_POST['email']);                    // Remove whitespace from email
    $phone_number = trim($_POST['number']);            // Remove whitespace from phone number
    $id_number = trim($_POST['id']);                   // Remove whitespace from ID number
    $role = $_POST['register'];                        // User's selected role (admin, treasurer, member)
    $password = $_POST['password'];                    // Password (will be hashed)
    $confirm_password = $_POST['confirm_password'];    // Password confirmation
    $terms_agreed = isset($_POST['terms']) ? $_POST['terms'] : '';  // Terms agreement (1 if checked)

    // Initialize array to collect validation errors
    $errors = [];

    // Validate each input field individually

    if (empty($full_name)) {
        // Check if full name is provided
        $errors[] = 'Full name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Validate email format using PHP's built-in email validator
        $errors[] = 'Valid email is required.';
    }

    if (empty($phone_number)) {
        // Check if phone number is provided
        $errors[] = 'Phone number is required.';
    }

    if (empty($id_number)) {
        // Check if National ID number is provided
        $errors[] = 'ID number is required.';
    }

    // Validate Kenyan ID number format (8-10 digits)
    if (!empty($id_number) && !preg_match('/^\d{8,10}$/', $id_number)) {
        $errors[] = 'ID number must be 8-10 digits.';
    }

    if (empty($password) || strlen($password) < 6) {
        // Check if password is at least 6 characters long
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirm_password) {
        // Verify that both password fields match
        $errors[] = 'Passwords do not match.';
    }

    if (empty($terms_agreed)) {
        // Verify that user agreed to terms and conditions
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy.';
    }

    // Check if the email already exists in the system to prevent duplicates
    $user = new User();
    if ($user->findByEmail($email)) {
        $errors[] = 'Email already exists. Please use a different email.';
    }

    // Check if the ID number already exists in the system to prevent duplicates
    $db = new Database();
    $db->query("SELECT id FROM users WHERE id_number = :id_number");
    $db->bind(':id_number', $id_number);
    $existing_user = $db->single();
    if ($existing_user) {
        $errors[] = 'ID number already exists. Please use a different ID number.';
    }

    // Process registration if no validation errors were found
    if (empty($errors)) {
        // Attempt to register the user using the DatabaseClass User method
        if ($user->register($full_name, $email, $phone_number, $id_number, $password, $role)) {
            // Successful registration message
            $success = 'Registration successful! You can now log in.';
        } else {
            // Error message if registration failed at the database level
            $error = 'Registration failed. Please try again.';
        }
    } else {
        // Combine all validation errors into a single message separated by line breaks
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Chama Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #00A651, #008542);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #00A651, #008542);
        }

        h1 {
            text-align: center;
            color: #00A651;
            margin-bottom: 10px;
            font-size: 2.2rem;
        }

        p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }

        input, select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #00A651;
            box-shadow: 0 0 0 3px rgba(0, 166, 81, 0.1);
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .input-group {
            flex: 1;
        }

        .checkbox-container {
            display: flex;
            align-items: flex-start;
            margin: 20px 0;
        }

        .checkbox-container input {
            width: auto;
            margin-right: 10px;
            margin-top: 5px;
        }

        .checkbox-container label {
            margin-bottom: 0;
            color: #555;
            font-size: 0.9rem;
        }

        .checkbox-container a {
            color: #00A651;
            text-decoration: none;
        }

        .checkbox-container a:hover {
            text-decoration: underline;
        }

        .mybutton {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00A651, #008542);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .mybutton:hover {
            background: linear-gradient(135deg, #008542, #006431);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 166, 81, 0.3);
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #c62828;
            font-size: 0.95rem;
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #2e7d32;
            font-size: 0.95rem;
        }

        footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        footer p {
            margin: 5px 0;
            color: #666;
            font-size: 0.9rem;
        }

        footer a {
            color: #00A651;
            text-decoration: none;
            font-weight: 500;
        }

        footer a:hover {
            text-decoration: underline;
        }

        .id-info {
            font-size: 0.8rem;
            color: #888;
            margin-top: 3px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Account</h1>
        <p>Join your Chama management system</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <script>
                // Redirect to login after successful registration
                setTimeout(function() {
                    window.location.href = 'Login.php';
                }, 3000);
            </script>
        <?php endif; ?>

        <form action="" method="post">
            <div class="input-group">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    required
                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                />
            </div>

            <div class="input-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="yourname@gmail.com"
                    required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                />
            </div>

            <div class="form-row">
                <div class="input-group">
                    <label for="number">Phone Number</label>
                    <input
                        type="tel"
                        id="number"
                        name="number"
                        placeholder="254XXXXXXXXX"
                        required
                        value="<?php echo isset($_POST['number']) ? htmlspecialchars($_POST['number']) : ''; ?>"
                    />
                </div>

                <div class="input-group">
                    <label for="id">ID Number <span class="id-info">(8-10 digits)</span></label>
                    <input
                        type="tel"
                        id="id"
                        name="id"
                        placeholder="12345678"
                        required
                        pattern="[0-9]{8,10}"
                        value="<?php echo isset($_POST['id']) ? htmlspecialchars($_POST['id']) : ''; ?>"
                    />
                </div>
            </div>

            <div class="input-group">
                <label for="register">Register As</label>
                <select name="register" id="register" required>
                    <option value="member" <?php echo (isset($_POST['register']) && $_POST['register'] === 'member') ? 'selected' : ''; ?>>Member</option>
                    <option value="treasurer" <?php echo (isset($_POST['register']) && $_POST['register'] === 'treasurer') ? 'selected' : ''; ?>>Treasurer</option>
                    <option value="admin" <?php echo (isset($_POST['register']) && $_POST['register'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="form-row">
                <div class="input-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    />
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        required
                    />
                </div>
            </div>

            <div class="checkbox-container">
                <input type="checkbox" name="terms" id="terms" value="1" required />
                <label for="terms">
                    I agree to the <a href="#">Terms of Service</a> and
                    <a href="#">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="mybutton">Create Account</button>
        </form>

        <footer>
            <p>Already have an account? <a href="Login.php">Sign In</a></p>
            <p>
                <small>
                    Secure Chama Management System <br />
                    Powered by M-Pesa Integration
                </small>
            </p>
        </footer>
    </div>
</body>
</html>
