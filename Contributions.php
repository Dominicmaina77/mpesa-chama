<?php
/*
 * Contributions Management Page for Chama Management System
 *
 * This page displays contribution information based on user role.
 * Admins and treasurers see all contributions, members see only their own.
 * Includes summary statistics for financial tracking.
 *
 * Features:
 * - Role-based contribution visibility
 * - Contribution status tracking (pending/confirmed)
 * - Summary statistics calculation
 * - Payment method categorization
 * - Secure data access based on user roles
 *
 * @author ChamaSys Development Team
 * @version 1.0
 * @since 2025
 */

// Start session to maintain user state across pages
session_start();

// Include the DatabaseClass which provides OOP database functionality
require_once 'DatabaseClass.php';

// Check if user is logged in by verifying session variable exists
if (!isset($_SESSION['user'])) {
    // Redirect unauthorized users to login page
    header('Location: Login.php');
    // Exit script to prevent further execution
    exit();
}

// Store current user data and role for use in the page
$user = $_SESSION['user'];
$role = $user['role'];

// Get contributions based on user role - implement role-based access control
$contribution = new Contribution();
if ($role === 'admin' || $role === 'treasurer') {
    // Admins and treasurers can see all contributions in the system
    $contributions = $contribution->getAllContributions();
} else {
    // Regular members can only see their own contributions
    $db = new Database();
    $db->query('SELECT id FROM members WHERE user_id = :user_id');
    $db->bind(':user_id', $user['id']);
    $member_result = $db->single();

    if ($member_result) {
        // Get member ID and retrieve only their contributions
        $member_id = $member_result['id'];
        $contributions = $contribution->getMemberContributions($member_id);
    } else {
        // Initialize empty array if member record not found
        $contributions = [];
    }
}

// Calculate summary data for display in summary cards
$total_contributions = 0;           // Total of all contributions (pending + confirmed)
$confirmed_contributions = 0;       // Total of confirmed contributions only
$pending_contributions = 0;         // Total of pending contributions only

foreach ($contributions as $c) {
    // Add to total contribution amount
    $total_contributions += $c['amount'];

    // Categorize contributions by status for summary statistics
    if ($c['status'] === 'confirmed') {
        // Add to confirmed contributions total
        $confirmed_contributions += $c['amount'];
    } elseif ($c['status'] === 'pending') {
        // Add to pending contributions total
        $pending_contributions += $c['amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contributions Management - Chama Management System</title>
    <link rel="stylesheet" href="dashboard.css" />
  </head>
  <body>
    <aside class="sidebar">
      <h2 class="logo">Integrated</h2>

      <nav>
        <?php if ($role === 'admin'): ?>
          <a href="Admin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'Admin.php') ? 'active' : ''; ?>">Dashboard</a>
          <a href="Admin.php">Manage Members</a>
        <?php elseif ($role === 'treasurer'): ?>
          <a href="Treasurer.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'Treasurer.php') ? 'active' : ''; ?>">Dashboard</a>
          <a href="Treasurer.php">Record Contributions</a>
        <?php else: ?>
          <a href="Members.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'Members.php') ? 'active' : ''; ?>">Dashboard</a>
          <a href="payment.php">Make Payment</a>
          <a href="loan_repayment.php">Loan Repayment</a>
        <?php endif; ?>
        <a href="Loans.php">Loans</a>
        <a href="Contributions.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'Contributions.php') ? 'active' : ''; ?>">Contributions</a>
        <?php if ($role === 'admin' || $role === 'treasurer'): ?>
        <a href="#record-contribution">Record Contribution</a>
        <?php endif; ?>
        <a href="Fines.php">Fines</a>
        <a href="logout.php">Logout</a>
      </nav>
    </aside>

    <main class="main">
      <header class="topbar">
        <h1>Contributions Dashboard</h1>

        <div class="top-actions">
          <button id="themeToggle" onclick="toggleTheme()">☀️</button>
          <span class="user">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
        </div>
      </header>

      <!-- cards -->
      <section class="cards">
        <div class="card">
          <h3>Total Contributions</h3>
          <p class="amount success">KES <?php echo number_format($total_contributions, 2); ?></p>
        </div>

        <div class="card">
          <h3>Confirmed Contributions</h3>
          <p class="amount success">KES <?php echo number_format($confirmed_contributions, 2); ?></p>
        </div>

        <div class="card">
          <h3>Pending Contributions</h3>
          <p class="amount warning">KES <?php echo number_format($pending_contributions, 2); ?></p>
        </div>
      </section>

      <?php if ($role === 'admin' || $role === 'treasurer'): ?>
      <!-- Record Contribution Form -->
      <section class="content" id="record-contribution">
        <h2>Record New Contribution</h2>

        <?php
        $message = '';
        if ($_POST && isset($_POST['record_contribution'])) {
            $member_id = (int)$_POST['member_id'];
            $amount = (float)$_POST['amount'];
            $payment_method = $_POST['payment_method'];
            $mpesa_code = !empty($_POST['mpesa_code']) ? $_POST['mpesa_code'] : null;

            $contribution = new Contribution();
            if ($contribution->addContribution($member_id, $amount, $payment_method, $mpesa_code)) {
                $message = "Contribution recorded successfully!";
                // Refresh contributions after adding new one
                $contributions = $contribution->getAllContributions();

                // Recalculate summary data
                $total_contributions = 0;
                $confirmed_contributions = 0;
                $pending_contributions = 0;

                foreach ($contributions as $c) {
                    $total_contributions += $c['amount'];
                    if ($c['status'] === 'confirmed') {
                        $confirmed_contributions += $c['amount'];
                    } elseif ($c['status'] === 'pending') {
                        $pending_contributions += $c['amount'];
                    }
                }
            } else {
                $message = "Failed to record contribution.";
            }
        }
        ?>

        <?php if (!empty($message)): ?>
        <div style="padding: 10px; margin-bottom: 15px; border-radius: 4px;
                    <?php echo strpos($message, 'successfully') ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <form method="post" style="margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div>
                    <label for="member_id" style="display: block; margin-bottom: 5px; font-weight: bold;">Select Member:</label>
                    <select name="member_id" id="member_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select a member</option>
                        <?php
                        $user_obj = new User();
                        $members = $user_obj->getAllUsersByRole('member');
                        foreach ($members as $member):
                        ?>
                        <option value="<?php echo $member['id']; ?>">
                            <?php echo htmlspecialchars($member['full_name'] . ' (' . ($member['member_number'] ?? 'N/A') . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="amount" style="display: block; margin-bottom: 5px; font-weight: bold;">Amount (KES):</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" required
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>

                <div>
                    <label for="payment_method" style="display: block; margin-bottom: 5px; font-weight: bold;">Payment Method:</label>
                    <select name="payment_method" id="payment_method" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="mpesa">M-Pesa</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <div>
                    <label for="mpesa_code" style="display: block; margin-bottom: 5px; font-weight: bold;">M-Pesa Code (if applicable):</label>
                    <input type="text" name="mpesa_code" id="mpesa_code"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>

            <button type="submit" name="record_contribution" style="background: linear-gradient(135deg, #00A651, #008542); color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 500;">
                Record Contribution
            </button>
        </form>
      </section>
      <?php endif; ?>

      <!-- tables -->
      <section class="content">
        <h2>Recent Transactions</h2>

        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Member</th>
              <th>Amount (KES)</th>
              <th>Payment Method</th>
              <th>Status</th>
            </tr>
          </thead>

          <tbody>
            <?php if (!empty($contributions)): ?>
              <?php foreach ($contributions as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars($c['contribution_date']); ?></td>
                <td><?php echo htmlspecialchars($c['full_name'] ?? $user['full_name']); ?></td>
                <td>KES <?php echo number_format($c['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($c['payment_method']); ?></td>
                <td class="status-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5">No contributions found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>

    <script>
      // Theme toggle functionality
      function toggleTheme() {
        const html = document.documentElement;
        const button = document.getElementById('themeToggle');

        if (html.getAttribute('data-theme') === 'dark') {
          html.removeAttribute('data-theme');
          button.innerHTML = '☀️';
        } else {
          html.setAttribute('data-theme', 'dark');
          button.innerHTML = '🌙';
        }
      }

      // Smooth scrolling for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
          anchor.addEventListener('click', function (e) {
              e.preventDefault();

              const targetId = this.getAttribute('href');
              const targetElement = document.querySelector(targetId);

              if (targetElement) {
                  window.scrollTo({
                      top: targetElement.offsetTop - 100,
                      behavior: 'smooth'
                  });
              }
          });
      });
    </script>
  </body>
</html>
