<?php

require_once('config.php');
require_once('classes/PayMongo.php');

$type = isset($_GET['type']) ? $_GET['type'] : '';
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$source_id = '';
if ($order_id > 0) {
    $src_stmt = $conn->prepare("SELECT paymongo_source_id FROM order_list WHERE id = ?");
    $src_stmt->bind_param('i', $order_id);
    $src_stmt->execute();
    $src_result = $src_stmt->get_result();
    if ($src_result->num_rows > 0) {
        $source_id = $src_result->fetch_assoc()['paymongo_source_id'] ?? '';
    }
    $src_stmt->close();
}

$paymongo = new PayMongo($conn);

switch ($type) {
    case 'success':
        handleSuccess($conn, $paymongo, $source_id, $order_id);
        break;
    
    case 'failed':
        handleFailed($conn, $source_id, $order_id);
        break;
    
    default:
        
        header('Location: index.php?p=customer/orders&error=invalid_callback');
        exit;
}

function handleSuccess($conn, $paymongo, $source_id, $order_id) {
    
    $source = $paymongo->getSource($source_id);
    
    if ($source['status'] !== 'success') {
        error_log("PayMongo callback: Failed to get source {$source_id}");
        header('Location: index.php?p=customer/orders&msg=payment_verification_failed&order_id=' . $order_id);
        exit;
    }
    
    $source_status = $source['source_status'];
    $metadata = $source['metadata'];
    
    
    if ($order_id === 0 && isset($metadata['order_id'])) {
        $order_id = intval($metadata['order_id']);
    }
    
    
    $order_check = $conn->prepare("SELECT id, code AS order_code, total_amount, payment_status FROM order_list WHERE id = ?");
    $order_check->bind_param('i', $order_id);
    $order_check->execute();
    $order_result = $order_check->get_result();
    
    if ($order_result->num_rows === 0) {
        error_log("PayMongo callback: Order {$order_id} not found");
        header('Location: index.php?p=customer/orders&error=order_not_found');
        exit;
    }
    
    $order = $order_result->fetch_assoc();
    
    
    if ($source_status === 'chargeable') {
        
        $payment_result = $paymongo->createPayment(
            $source_id, 
            $order['total_amount'],
            'Order #' . $order['order_code']
        );
        
        if ($payment_result['status'] === 'success' && $payment_result['payment_status'] === 'paid') {
            
            $update_stmt = $conn->prepare("UPDATE order_list SET payment_status = 'paid', paymongo_source_id = ?, paymongo_payment_id = ?, payment_verified_at = NOW() WHERE id = ?");
            $payment_id = $payment_result['payment_id'];
            $update_stmt->bind_param('ssi', $source_id, $payment_id, $order_id);
            $update_stmt->execute();
            
            error_log("PayMongo payment successful: Order #{$order['order_code']}, Payment ID: {$payment_id}");
            
            $_SESSION['payment_success'] = true;
            $_SESSION['payment_order_code'] = $order['order_code'];
            header('Location: index.php?p=customer/orders&payment=success&order_id=' . $order_id);
            exit;
        } else {
            
            error_log("PayMongo payment creation failed: " . json_encode($payment_result));
            cancelOrderOnPaymentFailure($conn, $order_id, $source_id, 'GCash payment could not be processed.');
        header('Location: index.php?p=customer/checkout&payment=failed');
            exit;
        }
    } elseif ($source_status === 'paid') {
        
        $update_stmt = $conn->prepare("UPDATE order_list SET payment_status = 'paid', paymongo_source_id = ? WHERE id = ?");
        $update_stmt->bind_param('si', $source_id, $order_id);
        $update_stmt->execute();
        
        $_SESSION['payment_success'] = true;
        $_SESSION['payment_order_code'] = $order['order_code'];
        header('Location: index.php?p=customer/orders&payment=success&order_id=' . $order_id);
        exit;
    } else {
        
        error_log("PayMongo source not chargeable: Status = {$source_status}, cancelling order {$order_id}");
        cancelOrderOnPaymentFailure($conn, $order_id, $source_id, 'Payment was ' . $source_status . '.');
        header('Location: index.php?p=customer/checkout&payment=failed');
        exit;
    }
}

function handleFailed($conn, $source_id, $order_id) {
    if ($order_id > 0) {
        cancelOrderOnPaymentFailure($conn, $order_id, $source_id, 'Payment was declined or cancelled.');
        error_log("PayMongo payment failed/expired: Order ID {$order_id} - Order cancelled.");
    }
    
    header('Location: index.php?p=customer/checkout&payment=failed');
    exit;
}

function cancelOrderOnPaymentFailure($conn, $order_id, $source_id, $reason = '') {
    $paymongo = new PayMongo($conn);
    $rollback = $paymongo->rollbackFailedPendingOrder($order_id, $reason);

    if (!$rollback['success']) {
        error_log("PayMongo payment rollback failed for order {$order_id}: {$rollback['msg']}");
    }

    return $rollback;
}
