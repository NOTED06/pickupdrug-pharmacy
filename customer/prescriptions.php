<?php

if($_settings->userdata('id') == '' || $_settings->userdata('login_type') != 2){
    echo "<script>alert('You dont have access for this page'); location.replace('./');</script>";
    exit;
}

$customer_id = $_settings->userdata('id');
$page_title = "My Medication Schedules";

$query = "
    SELECT 
        o.id as order_id,
        o.code as order_code,
        o.date_created as order_date,
        o.pickup_date,
        o.pickup_time,
        o.status,
        COUNT(oi.product_id) as total_items,
        SUM(CASE WHEN p.is_prescription = 1 THEN 1 ELSE 0 END) as rx_items
    FROM order_list o
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN product_list p ON oi.product_id = p.id
    WHERE o.customer_id = {$customer_id}
    AND o.status = 3
    AND p.is_prescription = 1
    GROUP BY o.id
    ORDER BY o.date_created DESC
";

$orders_result = $conn->query($query);
?>
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">
<style>

.medication-page {
    background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
    min-height: 100vh;
    padding: 20px;
    padding-top: 100px; 
    margin-top: 20px;
}

.medication-header {
    background: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border-left: 5px solid #0d9488;
}

.medication-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0f766e;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.medication-header p {
    color: #64748b;
    margin: 0;
    font-size: 14px;
}

.order-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border: 2px solid #e5e7eb;
}

.order-card:hover {
    box-shadow: 0 4px 20px rgba(13,148,136,0.15);
    border-color: #0d9488;
}

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f9ff;
    margin-bottom: 15px;
}

.order-info h3 {
    font-size: 18px;
    font-weight: 600;
    color: #0f766e;
    margin: 0 0 5px 0;
}

.order-meta {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    font-size: 13px;
    color: #64748b;
}

.order-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.badge-rx {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.rx-products-list {
    display: grid;
    gap: 15px;
}

.rx-product-item {
    background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 100%);
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid #0d9488;
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 15px;
}

.rx-product-info {
    flex: 1;
}

.rx-product-name {
    font-size: 16px;
    font-weight: 600;
    color: #0f766e;
    margin: 0 0 5px 0;
}

.rx-product-details {
    font-size: 13px;
    color: #64748b;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 5px;
}

.medication-schedule {
    background: white;
    padding: 15px;
    border-radius: 10px;
    border: 2px solid #0d9488;
    min-width: 280px;
    max-width: 100%;
}

.schedule-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 2px solid #ccfbf1;
}

.schedule-header h4 {
    font-size: 14px;
    font-weight: 600;
    color: #0d9488;
    margin: 0;
    flex: 1;
}

.schedule-times {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.schedule-time {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    background: #f0fdfa;
    border-radius: 8px;
}

.time-badge {
    background: #0d9488;
    color: white;
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
    min-width: 80px;
    text-align: center;
}

.time-label {
    font-size: 12px;
    color: #64748b;
}

.no-schedule {
    text-align: center;
    padding: 20px;
    color: #94a3b8;
    font-size: 14px;
}

.no-schedule i {
    font-size: 40px;
    margin-bottom: 10px;
    color: #cbd5e1;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.empty-state i {
    font-size: 80px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #334155;
    margin-bottom: 10px;
}

.empty-state p {
    color: #94a3b8;
    font-size: 14px;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-completed {
    background: #dcfce7;
    color: #166534;
}

@media (max-width: 768px) {
    .rx-product-item {
        flex-direction: column;
    }
    
    .medication-schedule {
        min-width: 100%;
    }
    
    .order-card-header {
        flex-direction: column;
        align-items: start;
        gap: 10px;
    }
    
    .medication-page {
        padding: 12px;
        padding-top: 90px;
    }
}

@media (max-width: 480px) {
    .medication-page {
        padding: 8px;
        padding-top: 80px;
    }
    
    .medication-page .container {
        padding: 0;
    }
    
    .order-card {
        border-radius: 10px;
    }
    
    .order-card-header h3 {
        font-size: 1rem;
    }
    
    .rx-product-item {
        padding: 10px;
        gap: 8px;
    }
    
    .medication-schedule {
        padding: 10px;
    }
    
    .medication-schedule h4 {
        font-size: 1rem;
    }
    
    .page-header-banner {
        padding: 18px 14px;
        border-radius: 12px;
    }
    
    .page-header-banner h1 {
        font-size: 1.25rem;
    }
}

@media (max-width: 360px) {
    .medication-page {
        padding: 6px;
        padding-top: 75px;
    }
    
    .order-card-header {
        padding: 10px;
    }
}
</style>

<div class="medication-page">
    <div class="container">
        
        
        <div class="medication-header">
            <h1>
                <i class="fas fa-pills"></i>
                My Medication Schedules
            </h1>
            <p>View and manage medication schedules from your completed prescription orders</p>
        </div>
        
        
        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
            <?php while ($order = $orders_result->fetch_assoc()): ?>
                <div class="order-card">
                    
                    <div class="order-card-header">
                        <div class="order-info">
                            <h3>
                                <i class="fas fa-receipt"></i>
                                Order #<?= htmlspecialchars($order['order_code']) ?>
                            </h3>
                            <div class="order-meta">
                                <span>
                                    <i class="far fa-calendar"></i>
                                    <?= date('M d, Y', strtotime($order['order_date'])) ?>
                                </span>
                                <span>
                                    <i class="fas fa-box"></i>
                                    <?= $order['rx_items'] ?> RX Item<?= $order['rx_items'] > 1 ? 's' : '' ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="badge-rx">
                                <i class="fas fa-prescription"></i>
                                Prescription Order
                            </span>
                        </div>
                    </div>
                    
                    
                    <div class="rx-products-list">
                        <?php
                        
                        $products_query = "
                            SELECT 
                                p.id as product_id,
                                p.name as product_name,
                                p.brand,
                                p.dose,
                                oi.quantity,
                                oi.price,
                                mr.id as reminder_id,
                                mr.medicine_name,
                                mr.dosage,
                                mr.frequency,
                                mr.reminder_times,
                                mr.start_date,
                                mr.end_date,
                                mr.instructions,
                                mr.status as reminder_status,
                                DATEDIFF(mr.end_date, mr.start_date) as duration_days
                            FROM order_items oi
                            INNER JOIN product_list p ON oi.product_id = p.id
                            LEFT JOIN medication_reminders mr ON (
                                mr.customer_id = {$customer_id}
                                AND mr.created_from_order = {$order['order_id']}
                            )
                            WHERE oi.order_id = {$order['order_id']}
                            AND p.is_prescription = 1
                            ORDER BY p.name
                        ";
                        
                        $products_result = $conn->query($products_query);
                        
                        while ($product = $products_result->fetch_assoc()):
                        ?>
                            <div class="rx-product-item">
                                <div class="rx-product-info">
                                    <h4 class="rx-product-name">
                                        <i class="fas fa-prescription-bottle-alt"></i>
                                        <?= htmlspecialchars($product['product_name']) ?>
                                        <?php if (!empty($product['brand'])): ?>
                                            <span style="font-weight: 400; color: #64748b; font-size: 14px;">(<?= htmlspecialchars($product['brand']) ?>)</span>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="rx-product-details">
                                        <?php if (!empty($product['dose'])): ?>
                                            <span><i class="fas fa-capsules"></i> <?= htmlspecialchars($product['dose']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-shopping-cart"></i> Qty: <?= $product['quantity'] ?></span>
                                        <span><i class="fas fa-peso-sign"></i> ₱<?= number_format($product['price'], 2) ?></span>
                                    </div>
                                </div>
                                
                                
                                <div class="medication-schedule">
                                    <?php if ($product['reminder_id']): ?>
                                        
                                        <div class="schedule-header">
                                            <h4>
                                                <i class="fas fa-clock"></i>
                                                Medication Schedule
                                            </h4>
                                            <?php if ($product['reminder_status'] == 'active'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">
                                                    <i class="fas fa-bell"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">
                                                    <i class="fas fa-bell-slash"></i> Paused
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="schedule-times">
                                            <?php
                                            
                                            $times = json_decode($product['reminder_times'], true);
                                            if (!$times && $product['reminder_times']) {
                                                $times = explode(',', $product['reminder_times']);
                                            }
                                            if ($times):
                                                foreach ($times as $time):
                                                    $time = trim($time);
                                            ?>
                                                <div class="schedule-time">
                                                    <span class="time-badge">
                                                        <i class="far fa-clock"></i>
                                                        <?= date('g:i A', strtotime($time)) ?>
                                                    </span>
                                                    <span class="time-label">
                                                        <?= htmlspecialchars($product['dosage'] ?? 'Take medication') ?>
                                                    </span>
                                                </div>
                                            <?php 
                                                endforeach;
                                            endif;
                                            ?>
                                        </div>
                                        
                                        <?php if (!empty($product['instructions'])): ?>
                                            <div style="margin-top: 10px; padding: 10px; background: #fff7ed; border-radius: 6px; border-left: 3px solid #f59e0b;">
                                                <small style="color: #92400e; font-weight: 600; display: block; margin-bottom: 3px;">
                                                    <i class="fas fa-info-circle"></i> Instructions:
                                                </small>
                                                <small style="color: #78350f; line-height: 1.5;">
                                                    <?= nl2br(htmlspecialchars($product['instructions'])) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div style="margin-top: 12px; display: flex; gap: 8px; justify-content: space-between; align-items: center;">
                                            <small style="color: #64748b;">
                                                <i class="fas fa-calendar-check"></i>
                                                Started: <?= date('M d, Y', strtotime($product['start_date'])) ?>
                                            </small>
                                            <small style="color: #64748b;">
                                                <i class="fas fa-hourglass-half"></i>
                                                <?= $product['duration_days'] ?> days
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        
                                        <div class="no-schedule">
                                            <i class="fas fa-calendar-times"></i>
                                            <p>No medication schedule set yet</p>
                                            <small>Please contact pharmacy to set up your medication reminder</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            
            <div class="empty-state">
                <i class="fas fa-prescription-bottle"></i>
                <h3>No Prescription Orders Yet</h3>
                <p>When you complete an order containing prescription medications, your medication schedules will appear here.</p>
                <a href="?p=customer/home" class="btn btn-primary" style="margin-top: 20px; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); border: none; padding: 12px 30px; border-radius: 10px;">
                    <i class="fas fa-shopping-cart"></i>
                    Browse Products
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    console.log('Medication Schedule page loaded');
});
</script>