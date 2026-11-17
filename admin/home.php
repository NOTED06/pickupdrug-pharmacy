<h1>Welcome, <?php echo $_settings->userdata('firstname')." ".$_settings->userdata('lastname') ?>!</h1>
<hr>
<?php

$today = date("Y-m-d");

$category_count = $conn->query("SELECT COUNT(*) as cnt FROM category_list WHERE delete_flag = 0")->fetch_assoc()['cnt'];

$products_count = $conn->query("SELECT COUNT(*) as cnt FROM product_list")->fetch_assoc()['cnt'];

$branches_count = $conn->query("SELECT COUNT(*) as cnt FROM branch_list")->fetch_assoc()['cnt'];

$customers_count = $conn->query("SELECT COUNT(*) as cnt FROM customer_list")->fetch_assoc()['cnt'];

$oos_count = $conn->query("
    SELECT COUNT(*) as cnt FROM (
        SELECT p.id,
            COALESCE(s.total_stock, 0) - COALESCE(o.total_ordered, 0) as available
        FROM product_list p
        LEFT JOIN (
            SELECT product_id, SUM(quantity) as total_stock 
            FROM stock_list 
            WHERE expiration IS NULL OR DATE(expiration) > '{$today}'
            GROUP BY product_id
        ) s ON p.id = s.product_id
        LEFT JOIN (
            SELECT product_id, SUM(quantity) as total_ordered 
            FROM order_items 
            GROUP BY product_id
        ) o ON p.id = o.product_id
        WHERE p.delete_flag = 0
        HAVING available <= 0
    ) as oos_products
")->fetch_assoc()['cnt'];

$exp_count = $conn->query("
    SELECT COUNT(*) as cnt FROM stock_list s 
    INNER JOIN product_list p ON s.product_id = p.id 
    WHERE p.delete_flag = 0 AND s.expiration IS NOT NULL AND DATE(s.expiration) <= CURDATE()
")->fetch_assoc()['cnt'];

$order_stats = $conn->query("
    SELECT 
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as packed,
        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as ready,
        SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as completed
    FROM order_list
")->fetch_assoc();
?>
<style>
    .info-box-link {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .info-box-link:hover {
        text-decoration: none;
        color: inherit;
    }
    .info-box-link:hover .info-box {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .info-box {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }
</style>
<div class="row">
    <?php if($_settings->userdata('type') == 2 || $_settings->userdata('type') == 1): ?>
    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=categories" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-th-list"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Categories</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($category_count) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
        
    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=products" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-dark elevation-1"><i class="fas fa-boxes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Products</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($products_count) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
        <?php endif; ?>
    <?php if($_settings->userdata('type') == 3 || $_settings->userdata('type') == 1): ?>

    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=customers" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-user"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Customers</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($customers_count) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
        <?php endif; ?>
    <?php if($_settings->userdata('type') == 2 || $_settings->userdata('type') == 1): ?>
    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=inventory/out_of_stock" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Out of Stock</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($oos_count) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=inventory/expired" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-danger elevation-1"><i class="fas fa-calendar-times"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Expired Stock</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($exp_count) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
        <?php endif; ?>
    <?php if($_settings->userdata('type') == 5 || $_settings->userdata('type') == 4 || $_settings->userdata('type') == 1): ?>
    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=orders&status=0" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-secondary elevation-1"><i class="fas fa-hourglass-half"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Orders</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($order_stats['pending']) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=orders&status=1" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-box"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Packed Orders</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($order_stats['packed']) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=orders&status=2" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-warning elevation-1"><i class="fas fa-shopping-bag"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Ready for Pick-up</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($order_stats['ready']) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-4 col-md-4">
        <a href="./?page=orders&status=3" class="info-box-link">
            <div class="info-box">
                <span class="info-box-icon bg-gradient-teal elevation-1"><i class="fas fa-clipboard-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Completed Orders</span>
                    <span class="info-box-number text-right h5">
                        <?= format_num($order_stats['completed']) ?>
                    </span>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>
<div class="container">
    <?php 
        $files = array();
          $fopen = scandir(base_app.'uploads/banner');
          foreach($fopen as $fname){
            if(in_array($fname,array('.','..')))
              continue;
            $files[]= validate_image('uploads/banner/'.$fname);
          }
    ?>
    <div id="tourCarousel"  class="carousel slide" data-ride="carousel" data-interval="3000">
          <div class="carousel-inner h-100">
              <?php foreach($files as $k => $img): ?>
              <div class="carousel-item  h-100 <?php echo $k == 0? 'active': '' ?>">
                  <img class="d-block w-100  h-100" style="object-fit:contain" src="<?php echo $img ?>" alt="">
              </div>
              <?php endforeach; ?>
          </div>
          <a class="carousel-control-prev" href="#tourCarousel" role="button" data-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="sr-only">Previous</span>
          </a>
          <a class="carousel-control-next" href="#tourCarousel" role="button" data-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="sr-only">Next</span>
          </a>
    </div>
</div>