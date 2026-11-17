<?php

if (isset($_POST['approve_prescription'])) {
    $prescription_id = $_POST['prescription_id'];
    $customer_id = $_POST['customer_id'];
    $expires_at = $_POST['expires_at'] ?? '';
    $expiry = DateTime::createFromFormat('Y-m-d', $expires_at);
    if (!$expiry || $expiry->format('Y-m-d') !== $expires_at || $expires_at < date('Y-m-d')) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'A valid prescription expiry date is required.'];
        return;
    }
    
    
    $update_stmt = $conn->prepare("UPDATE customer_prescriptions SET status = 'approved', approved_at = NOW(), expires_at = ? WHERE id = ?");
    $update_stmt->bind_param('si', $expires_at, $prescription_id);
    
    if ($update_stmt->execute()) {
        
        require_once(__DIR__ . '/../customer/classes/EmailIntegration.php');
        EmailIntegration::sendPrescriptionEmail($customer_id, $prescription_id, 'approved');
        
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Prescription approved and customer notified via email.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to approve prescription.'];
    }
}

if (isset($_POST['reject_prescription'])) {
    $prescription_id = $_POST['prescription_id'];
    $customer_id = $_POST['customer_id'];
    $reason = $_POST['rejection_reason'] ?? 'Not specified';
    
    
    $update_stmt = $conn->prepare("UPDATE customer_prescriptions SET status = 'rejected', rejection_reason = ? WHERE id = ?");
    $update_stmt->bind_param('si', $reason, $prescription_id);
    
    if ($update_stmt->execute()) {
        
        require_once(__DIR__ . '/../customer/classes/EmailIntegration.php');
        EmailIntegration::sendPrescriptionEmail($customer_id, $prescription_id, 'rejected');
        
        $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Prescription rejected and customer notified via email.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to reject prescription.'];
    }
}
?>

<div class="prescription-actions">
    <form method="POST" style="display: inline;">
        <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
        <input type="hidden" name="customer_id" value="<?php echo $prescription['customer_id']; ?>">
        <input type="date" name="expires_at" min="<?php echo date('Y-m-d'); ?>" required aria-label="Prescription valid until">
        <button type="submit" name="approve_prescription" class="btn btn-success">
            <i class="fas fa-check"></i> Approve & Email Customer
        </button>
    </form>
    
    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal<?php echo $prescription['id']; ?>">
        <i class="fas fa-times"></i> Reject & Email Customer
    </button>
</div>

<div class="modal fade" id="rejectModal<?php echo $prescription['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Prescription</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $prescription['customer_id']; ?>">
                    
                    <div class="form-group">
                        <label for="rejection_reason">Reason for Rejection:</label>
                        <textarea class="form-control" name="rejection_reason" rows="3" required
                                  placeholder="Please provide a reason for rejecting this prescription..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="reject_prescription" class="btn btn-danger">
                        Reject & Email Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
