<?php
/*
 * Approval Update Handler for Chama Management System
 *
 * This page handles approval status updates for various pending items:
 * - Loan applications (approve/reject/disburse/pay)
 * - Contributions (confirm pending payments)
 * - Fines (mark as paid/waived)
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
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user has admin role - only admins can update approval statuses
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit();
}

// Set content type to JSON for proper response
header('Content-Type: application/json');

// Get current user
$user = $_SESSION['user'];

// Get the approval type and action from the request
$approval_type = $_POST['type'] ?? $_GET['type'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$item_id = $_POST['id'] ?? $_GET['id'] ?? '';

if (empty($approval_type) || empty($action) || empty($item_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    switch ($approval_type) {
        case 'loan':
            // Handle loan status updates
            $loan = new Loan();
            
            // Validate the action
            $valid_loan_actions = ['approve', 'reject', 'disburse', 'pay'];
            if (!in_array($action, $valid_loan_actions)) {
                throw new Exception('Invalid loan action');
            }
            
            // Map actions to statuses
            $status_map = [
                'approve' => 'approved',
                'reject' => 'rejected',
                'disburse' => 'disbursed',
                'pay' => 'paid'
            ];
            
            $status = $status_map[$action];
            
            // Update the loan status
            $result = $loan->updateLoanStatus($item_id, $status, $user['id']);
            
            if ($result) {
                // If loan is approved, create repayment schedule
                if ($action === 'approve') {
                    createLoanRepaymentSchedule($item_id);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Loan status updated successfully',
                    'new_status' => $status
                ]);
            } else {
                throw new Exception('Failed to update loan status');
            }
            break;

        case 'contribution':
            // Handle contribution status updates
            $db = new Database();

            // Validate the action
            $valid_contribution_actions = ['confirm', 'cancel'];
            if (!in_array($action, $valid_contribution_actions)) {
                throw new Exception('Invalid contribution action');
            }

            // Map actions to statuses
            $status_map = [
                'confirm' => 'confirmed',
                'cancel' => 'canceled'
            ];

            $status = $status_map[$action];

            // Update the contribution status
            $db->query("UPDATE contributions SET status = :status WHERE id = :contribution_id");
            $db->bind(':status', $status);
            $db->bind(':contribution_id', $item_id);
            $result = $db->execute();

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Contribution status updated successfully',
                    'new_status' => $status
                ]);
            } else {
                throw new Exception('Failed to update contribution status');
            }
            break;

        case 'fine':
            // Handle fine status updates
            $db = new Database();

            // Validate the action
            $valid_fine_actions = ['pay', 'waive'];
            if (!in_array($action, $valid_fine_actions)) {
                throw new Exception('Invalid fine action');
            }

            if ($action === 'pay') {
                // Mark fine as paid
                $db->query("UPDATE fines SET status = 'paid', paid_date = CURDATE() WHERE id = :fine_id");
                $db->bind(':fine_id', $item_id);
                $result = $db->execute();

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Fine marked as paid successfully',
                        'new_status' => 'paid'
                    ]);
                } else {
                    throw new Exception('Failed to mark fine as paid');
                }
            } elseif ($action === 'waive') {
                // Mark fine as waived
                $db->query("UPDATE fines SET status = 'waived', paid_date = CURDATE() WHERE id = :fine_id");
                $db->bind(':fine_id', $item_id);
                $result = $db->execute();

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Fine waived successfully',
                        'new_status' => 'waived'
                    ]);
                } else {
                    throw new Exception('Failed to waive fine');
                }
            }
            break;

        default:
            throw new Exception('Invalid approval type');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Create repayment schedule for an approved loan
 */
function createLoanRepaymentSchedule($loan_id) {
    $db = new Database();
    
    // Get loan details
    $db->query("SELECT * FROM loans WHERE id = :loan_id");
    $db->bind(':loan_id', $loan_id);
    $loan = $db->single();
    
    if (!$loan) {
        return false;
    }
    
    // Calculate monthly repayment amount
    $monthly_amount = $loan['total_repayment'] / $loan['duration_months'];
    
    // Create repayment schedule
    $current_date = new DateTime($loan['date_applied']);
    
    for ($i = 1; $i <= $loan['duration_months']; $i++) {
        $current_date->modify('+1 month');
        $due_date = $current_date->format('Y-m-d');
        
        // Insert repayment record
        $db->query("
            INSERT INTO loan_repayments (loan_id, amount_due, due_date, status, member_id) 
            VALUES (:loan_id, :amount_due, :due_date, 'pending', :member_id)
        ");
        $db->bind(':loan_id', $loan_id);
        $db->bind(':amount_due', round($monthly_amount, 2));
        $db->bind(':due_date', $due_date);
        $db->bind(':member_id', $loan['member_id']);
        
        $db->execute();
    }
    
    return true;
}

/**
 * Update contribution status
 */
function updateContributionStatus($contribution_id, $status) {
    $db = new Database();
    
    $db->query("UPDATE contributions SET status = :status WHERE id = :contribution_id");
    $db->bind(':status', $status);
    $db->bind(':contribution_id', $contribution_id);
    
    return $db->execute();
}

/**
 * Update fine status
 */
function updateFineStatus($fine_id, $status) {
    $db = new Database();
    
    $db->query("UPDATE fines SET status = :status, paid_date = CURDATE() WHERE id = :fine_id");
    $db->bind(':status', $status);
    $db->bind(':fine_id', $fine_id);
    
    return $db->execute();
}
/**
 * Create repayment schedule for an approved loan
 */
function createLoanRepaymentSchedule($loan_id) {
    $db = new Database();

    // Get loan details
    $db->query("SELECT * FROM loans WHERE id = :loan_id");
    $db->bind(':loan_id', $loan_id);
    $loan = $db->single();

    if (!$loan) {
        return false;
    }

    // Calculate monthly repayment amount
    $monthly_amount = $loan['total_repayment'] / $loan['duration_months'];

    // Create repayment schedule
    $current_date = new DateTime($loan['date_applied']);

    for ($i = 1; $i <= $loan['duration_months']; $i++) {
        $current_date->modify('+1 month');
        $due_date = $current_date->format('Y-m-d');

        // Insert repayment record
        $db->query("
            INSERT INTO loan_repayments (loan_id, amount_due, due_date, status, member_id)
            VALUES (:loan_id, :amount_due, :due_date, 'pending', :member_id)
        ");
        $db->bind(':loan_id', $loan_id);
        $db->bind(':amount_due', round($monthly_amount, 2));
        $db->bind(':due_date', $due_date);
        $db->bind(':member_id', $loan['member_id']);

        $db->execute();
    }

    return true;
}
?>