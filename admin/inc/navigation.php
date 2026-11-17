<style>
  [class*="sidebar-light-"] .nav-treeview > .nav-item > .nav-link.active, [class*="sidebar-light-"] .nav-treeview > .nav-item > .nav-link.active:hover {
      color: #ffffff !important;
  }
  
  
  .nav-sidebar .nav-link .nav-icon {
    width: 1.6rem;
    text-align: center;
    margin-right: 0.5rem;
    font-size: 1rem;
  }
  
  
  .nav-sidebar .nav-link .fa-prescription {
    font-size: 0.9rem;
    width: 1.6rem;
    text-align: center;
  }
  
  
  .nav-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 600;
  }
  .nav-badge-danger {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    animation: pulse-badge 2s infinite;
  }
  .nav-badge-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
  }
  .nav-badge-info {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
  }
  .nav-badge-success {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
  }
  .nav-badge-primary {
    background: linear-gradient(135deg, #0d9488, #0f766e);
    color: white;
  }
  .nav-badge-secondary {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
  }
  @keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
  }
</style>
<?php

$nav_counts = [
    'pending_rx' => 0,
    'pending_orders' => 0,
    'out_of_stock' => 0,
    'expired_stock' => 0,
    'total_customers' => 0,
    'today_sales' => 0
];

$rx_result = $conn->query("SELECT COUNT(*) as cnt FROM customer_prescriptions WHERE status = 'pending'");
if($rx_result) $nav_counts['pending_rx'] = $rx_result->fetch_assoc()['cnt'];

$nav_counts['orders_0'] = 0;
$nav_counts['orders_1'] = 0;
$nav_counts['orders_2'] = 0;
$nav_counts['orders_3'] = 0;
$nav_counts['orders_4'] = 0;
$nav_counts['orders_total'] = 0;
$nav_counts['orders_active'] = 0;

$order_status_result = $conn->query("
    SELECT status, COUNT(*) as cnt 
    FROM order_list 
    WHERE (deleted_at IS NULL OR deleted_at = '') 
    GROUP BY status
");
if($order_status_result) {
    while($row = $order_status_result->fetch_assoc()) {
        $st = (int)$row['status'];
        $c = (int)$row['cnt'];
        $nav_counts['orders_' . $st] = $c;
        $nav_counts['orders_total'] += $c;
        if(in_array($st, [0, 1, 2])) {
            $nav_counts['orders_active'] += $c;
        }
    }
}
$nav_counts['pending_orders'] = $nav_counts['orders_0'];

$oos_result = $conn->query("
    SELECT COUNT(*) as cnt FROM (
        SELECT p.id,
            COALESCE(s.total_stock, 0) - COALESCE(o.total_ordered, 0) as available
        FROM product_list p
        LEFT JOIN (SELECT product_id, SUM(quantity) as total_stock FROM stock_list WHERE expiration IS NULL OR DATE(expiration) > CURDATE() GROUP BY product_id) s ON p.id = s.product_id
        LEFT JOIN (SELECT product_id, SUM(quantity) as total_ordered FROM order_items GROUP BY product_id) o ON p.id = o.product_id
        WHERE p.delete_flag = 0 HAVING available <= 0
    ) as oos
");
if($oos_result) $nav_counts['out_of_stock'] = $oos_result->fetch_assoc()['cnt'];

$exp_result = $conn->query("SELECT COUNT(*) as cnt FROM stock_list s INNER JOIN product_list p ON s.product_id = p.id WHERE p.delete_flag = 0 AND s.expiration IS NOT NULL AND DATE(s.expiration) <= CURDATE()");
if($exp_result) $nav_counts['expired_stock'] = $exp_result->fetch_assoc()['cnt'];

$cust_result = $conn->query("SELECT COUNT(*) as cnt FROM customer_list");
if($cust_result) $nav_counts['total_customers'] = $cust_result->fetch_assoc()['cnt'];

$sales_result = $conn->query("SELECT COUNT(*) as cnt FROM order_list WHERE status = 3 AND DATE(date_created) = CURDATE()");
if($sales_result) $nav_counts['today_sales'] = $sales_result->fetch_assoc()['cnt'];
?>

      <aside class="main-sidebar sidebar-light-teal navbar-light elevation-4 sidebar-no-expand">
        
        <a href="<?php echo base_url ?>admin" class="brand-link bg-teal text-sm">
        <img src="<?php echo validate_image($_settings->info('logo'))?>" alt="Store Logo" class="brand-image img-circle elevation-3" style="opacity: .8;width: 1.5rem;height: 1.5rem;max-height: unset">
        <span class="brand-text font-weight-light"><?php echo $_settings->info('short_name') ?></span>
        </a>
        
        <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-transition os-host-scrollbar-horizontal-hidden">
          <div class="os-resize-observer-host observed">
            <div class="os-resize-observer" style="left: 0px; right: auto;"></div>
          </div>
          <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
            <div class="os-resize-observer"></div>
          </div>
          <div class="os-content-glue" style="margin: 0px -8px; width: 249px; height: 646px;"></div>
          <div class="os-padding">
            <div class="os-viewport os-viewport-native-scrollbars-invisible" style="overflow-y: scroll;">
              <div class="os-content" style="padding: 0px 8px; height: 100%; width: 100%;">
                
                <div class="clearfix"></div>
                
                <nav class="mt-4">
                   <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
                   <li class="nav-item dropdown">
                      <a href="./" class="nav-link nav-home">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                          Dashboard
                        </p>
                      </a>
                    </li> 
                    <?php if($_settings->userdata('type') == 2 || $_settings->userdata('type') == 1): ?>
                    <li class="nav-item dropdown">
                      <a href="./?page=categories" class="nav-link nav-categories">
                        <i class="nav-icon fas fa-th-list"></i>
                        <p>
                          Category List
                        </p>
                      </a>
                    </li> 
                    <li class="nav-item dropdown">
                      <a href="./?page=products" class="nav-link nav-products">
                        <i class="nav-icon fas fa-pills"></i>
                        <p>
                          Product List
                        </p>
                      </a>
                    </li> 
                   <li class="nav-item has-treeview">
                      <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-clipboard"></i>
                        <p>
                          Inventory
                          <?php if($nav_counts['out_of_stock'] + $nav_counts['expired_stock'] > 0): ?>
                          <span class="badge nav-badge nav-badge-danger right"><?= $nav_counts['out_of_stock'] + $nav_counts['expired_stock'] ?></span>
                          <?php endif; ?>
                          <i class="right fas fa-angle-left"></i>
                        </p>
                      </a>
                      <ul class="nav nav-treeview">
                        <li class="nav-item">
                          <a href="./?page=inventory" class="nav-link tree-item nav-inventory">
                            <i class="fas fa-box-open nav-icon"></i>
                            <p>Products</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="./?page=inventory/out_of_stock" class="nav-link tree-item nav-inventory_out_of_stock">
                            <i class="fas fa-ban nav-icon"></i>
                            <p>Out Of Stocks
                              <?php if($nav_counts['out_of_stock'] > 0): ?>
                              <span class="badge nav-badge nav-badge-danger right"><?= $nav_counts['out_of_stock'] ?></span>
                              <?php endif; ?>
                            </p>
                          </a>
                        </li>
                         <li class="nav-item">
                          <a href="./?page=inventory/expired_stock" class="nav-link tree-item nav-inventory_expired_stock">
                            <i class="fas fa-clock nav-icon"></i>
                            <p>Expired
                              <?php if($nav_counts['expired_stock'] > 0): ?>
                              <span class="badge nav-badge nav-badge-warning right"><?= $nav_counts['expired_stock'] ?></span>
                              <?php endif; ?>
                            </p>
                          </a>
                        </li>
                      </ul>
                    </li>
                    <?php endif; ?>
                    
                    
                    <?php if($_settings->userdata('type') == 1 || $_settings->userdata('type') == 5): ?>
                    <li class="nav-item">
                      <a href="./?page=prescriptions" class="nav-link nav-prescriptions">
                        <i class="nav-icon fas fa-file-prescription"></i>
                        <p>
                          Prescriptions
                          <?php if($nav_counts['pending_rx'] > 0): ?>
                          <span class="badge nav-badge nav-badge-warning right"><?= $nav_counts['pending_rx'] ?></span>
                          <?php endif; ?>
                        </p>
                      </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if($_settings->userdata('type') == 5 || $_settings->userdata('type') == 4 || $_settings->userdata('type') == 1): ?>
                    <li class="nav-item has-treeview">
                      <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-table"></i>
                        <p>
                          Orders
                          <?php if($nav_counts['orders_active'] > 0): ?>
                          <span class="badge nav-badge nav-badge-info right"><?= $nav_counts['orders_active'] ?></span>
                          <?php endif; ?>
                          <i class="right fas fa-angle-left"></i>
                        </p>
                      </a>
                      <ul class="nav nav-treeview" style="display: none;">
                        <?php if($_settings->userdata('type') == 4 || $_settings->userdata('type') == 5 || $_settings->userdata('type') == 1): ?>
                        <li class="nav-item">
                          <a href="./?page=orders" class="nav-link tree-item nav-orders">
                            <i class="fas fa-list nav-icon"></i>
                            <p>List All
                              <?php if($nav_counts['orders_total'] > 0): ?>
                              <span class="badge nav-badge nav-badge-secondary right"><?= $nav_counts['orders_total'] ?></span>
                              <?php endif; ?>
                            </p>
                          </a>
                        </li>
                        <?php endif; ?>
                        <?php if($_settings->userdata('type') == 5 || $_settings->userdata('type') == 1): ?>
                        <li class="nav-item">
                          <a href="./?page=orders&status=0" class="nav-link tree-item nav-orders_0">
                            <i class="fas fa-hourglass-half nav-icon"></i>
                            <p>Pending
                              <?php if($nav_counts['orders_0'] > 0): ?>
                              <span class="badge nav-badge nav-badge-info right"><?= $nav_counts['orders_0'] ?></span>
                              <?php endif; ?>
                            </p>
                          </a>
                        </li>
                        <?php endif; ?>
                        <?php if($_settings->userdata('type') == 5 || $_settings->userdata('type') == 1): ?>
                        <li class="nav-item">
                          <a href="./?page=orders&status=1" class="nav-link tree-item nav-orders_1">
                            <i class="fas fa-box nav-icon"></i>
                            <p>Packed
                              <?php if($nav_counts['orders_1'] > 0): ?>
                              <span class="badge nav-badge nav-badge-warning right"><?= $nav_counts['orders_1'] ?></span>
                              <?php endif; ?>
                            </p>
                          </a>
                        </li>
                        <?php endif; ?>
                        <?php if($_settings->userdata('type') == 5 || $_settings->userdata('type') == 1): ?>
                        <li class="nav-item">
                          <a href="./?page=orders&status=2" class="nav-link tree-item nav-orders_2">
                            <i class="fas fa-box-open nav-icon"></i>
                            <p>Ready for Pick-up
                              <?php if($nav_counts['orders_2'] > 0): ?>
                              <span class="badge nav-badge nav-badge-success right"><?= $nav_counts['orders_2'] ?></span>
                              <?php endif; ?>
                            </p>
                          </a>
                        </li>
                        <?php endif; ?>
                        <?php if($_settings->userdata('type') == 5 || $_settings->userdata('type') == 1): ?>
                        <li class="nav-item">
                          <a href="./?page=orders&status=3" class="nav-link tree-item nav-orders_3">
                            <i class="fas fa-check-circle nav-icon"></i>
                            <p>Done
                              <?php if($nav_counts['orders_3'] > 0): ?>
                              <span class="badge nav-badge nav-badge-secondary right"><?= $nav_counts['orders_3'] ?></span>
                              <?php endif; ?>
                            </p>
                          </a>
                        </li>
                        <?php endif; ?>
                     <?php if($_settings->userdata('type') == 5 || $_settings->userdata('type') == 1): ?>
                        <li class="nav-item">
                          <a href="./?page=orders&status=4" class="nav-link tree-item nav-orders_4">
                            <i class="fas fa-times-circle nav-icon"></i>
                            <p>Cancelled
                              <?php if($nav_counts['orders_4'] > 0): ?>
                              <span class="badge nav-badge nav-badge-danger right"><?= $nav_counts['orders_4'] ?></span>
                              <?php endif; ?>
                            </p>
                          </a>
                        </li>
                        <?php endif; ?>
                      </ul>
                    </li>
                    <?php endif; ?>
                    
                     <?php if($_settings->userdata('type') == 4 || $_settings->userdata('type') == 1): ?>
                    <li class="nav-item has-treeview">
                      <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>
                          Invoice
                          <i class="right fas fa-angle-left"></i>
                        </p>
                      </a>
                      <ul class="nav nav-treeview">
                        <li class="nav-item">
                          <a href="./?page=invoice/create_invoice" class="nav-link tree-item nav-invoice_create_invoice">
                            <i class="fas fa-plus-circle nav-icon"></i>
                            <p>Create Invoice</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="./?page=invoice/manage_invoice" class="nav-link tree-item nav-invoice_manage_invoice">
                            <i class="fas fa-tasks nav-icon"></i>
                            <p>Manage Invoice</p>
                          </a>
                        </li>
                      </ul>
                    </li>
                    <?php endif; ?>
                    <?php if($_settings->userdata('type') == 3 || $_settings->userdata('type') == 1): ?>
                    <li class="nav-item dropdown">
                      <a href="./?page=customers" class="nav-link nav-customers">
                        <i class="nav-icon fas fa-user-friends"></i>
                        <p>
                          Customer List
                          <span class="badge nav-badge nav-badge-primary right"><?= number_format($nav_counts['total_customers']) ?></span>
                        </p>
                      </a>
                    </li> 
                     <?php endif; ?>
                    <?php if($_settings->userdata('type') == 1): ?>
                    <li class="nav-header">Sales Report</li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=reports" class="nav-link nav-reports">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>
                          Daily Report
                          <?php if($nav_counts['today_sales'] > 0): ?>
                          <span class="badge nav-badge nav-badge-success right"><?= $nav_counts['today_sales'] ?></span>
                          <?php endif; ?>
                        </p>
                      </a>
                    </li>
                    <li class="nav-header">Maintenance</li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=maintenance" class="nav-link nav-maintenance">
                        <i class="nav-icon fas fa-trash-restore"></i>
                        <p>
                          Deleted Items
                          <?php 
                          $deleted_count = 0;
                          
                          $table_check = $conn->query("SHOW TABLES LIKE 'deleted_items'");
                          if($table_check && $table_check->num_rows > 0){
                              $deleted_qry = $conn->query("SELECT COUNT(*) as cnt FROM deleted_items");
                              if($deleted_qry) $deleted_count = $deleted_qry->fetch_assoc()['cnt'];
                          }
                          if($deleted_count > 0): ?>
                          <span class="badge nav-badge nav-badge-warning right"><?= $deleted_count ?></span>
                          <?php endif; ?>
                        </p>
                      </a>
                    </li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=user/list" class="nav-link nav-user_list">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>
                          User List
                        </p>
                      </a>
                    </li>
                    
                    
                    <li class="nav-item has-treeview">
                      <a href="#" class="nav-link nav-is-tree nav-settings">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                          Settings Center
                          <i class="right fas fa-angle-left"></i>
                        </p>
                      </a>
                      <ul class="nav nav-treeview" style="display: none;">
                        <li class="nav-item">
                          <a href="<?php echo base_url ?>admin/?page=settings&tab=api" class="nav-link tree-item nav-settings_api">
                            <i class="fas fa-key nav-icon"></i>
                            <p>API Keys</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="<?php echo base_url ?>admin/?page=settings&tab=smtp" class="nav-link tree-item nav-settings_smtp">
                            <i class="fas fa-envelope nav-icon"></i>
                            <p>SMTP / Email</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="<?php echo base_url ?>admin/?page=settings&tab=sms" class="nav-link tree-item nav-settings_sms">
                            <i class="fas fa-sms nav-icon"></i>
                            <p>SMS Gateway</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="<?php echo base_url ?>admin/?page=settings&tab=reminders" class="nav-link tree-item nav-settings_reminders">
                            <i class="fas fa-bell nav-icon"></i>
                            <p>Medication Reminders</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="<?php echo base_url ?>admin/?page=settings&tab=email_verification" class="nav-link tree-item nav-settings_email_verification">
                            <i class="fas fa-user-check nav-icon"></i>
                            <p>Email Verification</p>
                          </a>
                        </li>
                        <li class="nav-item">
                          <a href="<?php echo base_url ?>admin/?page=settings&tab=security" class="nav-link tree-item nav-settings_security">
                            <i class="fas fa-shield-alt nav-icon"></i>
                            <p>Security</p>
                          </a>
                        </li>
                      </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=system_info/contact_info" class="nav-link nav-system_info_contact_info">
                        <i class="nav-icon fas fa-phone-square-alt"></i>
                        <p>
                          Contact Info
                        </p>
                      </a>
                    </li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=system_info" class="nav-link nav-system_info">
                        <i class="nav-icon fas fa-tools"></i>
                        <p>
                          System Settings
                        </p>
                      </a>
                    </li>
                    <li class="nav-item dropdown">
                      <a href="<?php echo base_url ?>admin/?page=chatbot" class="nav-link nav-chatbot">
                        <i class="nav-icon fab fa-facebook-messenger"></i>
                        <p>
                          Facebook Chatbot
                        </p>
                      </a>
                    </li>
                    <?php endif; ?>
                  </ul>
                </nav>
                
              </div>
            </div>
          </div>
          <div class="os-scrollbar os-scrollbar-horizontal os-scrollbar-unusable os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
              <div class="os-scrollbar-handle" style="width: 100%; transform: translate(0px, 0px);"></div>
            </div>
          </div>
          <div class="os-scrollbar os-scrollbar-vertical os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
              <div class="os-scrollbar-handle" style="height: 55.017%; transform: translate(0px, 0px);"></div>
            </div>
          </div>
          <div class="os-scrollbar-corner"></div>
        </div>
        
      </aside>
      <script>
    $(document).ready(function(){
      var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
      var status = '<?php echo isset($_GET['status']) ? $_GET['status'] : '' ?>';
      page = page.replace(/\
      page = status != '' ? page + "_" + status : page;
      console.log($('.nav-link.nav-'+page)[0])
      if($('.nav-link.nav-'+page).length > 0){
        var $activeLink = $('.nav-link.nav-'+page);
        $activeLink.addClass('active');
        if($activeLink.hasClass('tree-item')){
          var $treeview = $activeLink.closest('.nav-treeview');
          $treeview.show();
          $treeview.parent().addClass('menu-open');
          $treeview.parent().children('.nav-link').first().addClass('active');
        }
        if($activeLink.hasClass('nav-is-tree')){
          $activeLink.parent().addClass('menu-open');
        }
      }
      $('.nav-link.active').addClass('bg-gradient-teal')
    })
  </script>