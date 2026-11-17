<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<style>
	.product-img{
		width:3em;
		height:3em;
		object-fit:cover;
		object-position:center center;
	}
	
	
	.low-stock-alert {
		background: linear-gradient(135deg, #ff6b6b, #ee5a52);
		color: white;
		border-radius: 8px;
		padding: 15px 20px;
		margin-bottom: 20px;
		box-shadow: 0 4px 8px rgba(255, 107, 107, 0.3);
		animation: pulse 2s infinite;
	}
	
	@keyframes pulse {
		0% { box-shadow: 0 4px 8px rgba(255, 107, 107, 0.3); }
		50% { box-shadow: 0 4px 15px rgba(255, 107, 107, 0.5); }
		100% { box-shadow: 0 4px 8px rgba(255, 107, 107, 0.3); }
	}
	
	.status-icon {
		font-size: 1.2em;
		margin-right: 8px;
	}
	
	.low-stock-count {
		font-size: 2em;
		font-weight: bold;
		margin-left: 10px;
	}
	
	
	.critical-stock {
		background-color: #ffebee !important;
		border-left: 4px solid #f44336 !important;
	}
	
	.low-stock {
		background-color: #fff3e0 !important;
		border-left: 4px solid #ff9800 !important;
	}
	
	.out-of-stock {
		background-color: #fce4ec !important;
		border-left: 4px solid #e91e63 !important;
	}
	
	
	.stock-critical {
		background-color: #f44336 !important;
		color: white;
		font-weight: bold;
		animation: blink 1.5s infinite;
	}
	
	.stock-low {
		background-color: #ff9800 !important;
		color: white;
		font-weight: bold;
	}
	
	.stock-out {
		background-color: #e91e63 !important;
		color: white;
		font-weight: bold;
	}
	
	.stock-good {
		background-color: #4caf50 !important;
		color: white;
		font-weight: bold;
	}
	
	@keyframes blink {
		0%, 50% { opacity: 1; }
		51%, 100% { opacity: 0.7; }
	}
	
	.status-summary {
		display: flex;
		justify-content: space-around;
		margin-top: 10px;
		flex-wrap: wrap;
	}
	
	.status-item {
		background: rgba(255, 255, 255, 0.2);
		padding: 10px 15px;
		border-radius: 5px;
		text-align: center;
		margin: 5px;
		min-width: 120px;
	}
	
	.status-number {
		font-size: 1.5em;
		font-weight: bold;
		display: block;
	}
	
	.status-label {
		font-size: 0.9em;
		opacity: 0.9;
	}
</style>

<?php 

$page_num = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50;
$offset = ($page_num - 1) * $limit;

$stock_stats_sql = "
    SELECT 
        SUM(CASE WHEN available = 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN available > 0 AND available <= 3 THEN 1 ELSE 0 END) as critical_stock,
        SUM(CASE WHEN available > 3 AND available <= 10 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN available <= 10 THEN 1 ELSE 0 END) as total_low_stock
    FROM (
        SELECT p.id, 
        (COALESCE((SELECT SUM(quantity) FROM `stock_list` s 
                  WHERE s.product_id = p.id 
                  AND (s.expiration IS NULL OR DATE(s.expiration) > '".date("Y-m-d")."')), 0) - 
         COALESCE((SELECT SUM(oi.quantity) FROM `order_items` oi
                  WHERE oi.product_id = p.id), 0)) as `available` 
        FROM `product_list` p WHERE p.delete_flag = 0
    ) as inventory_data
";
$stock_stats_qry = $conn->query($stock_stats_sql);
$stock_stats = $stock_stats_qry->fetch_assoc();
?>

<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">Inventory</h3>
		<div class="card-tools">
		    <div class="input-group input-group-sm" style="width: 220px;">
		        <div class="input-group-prepend">
		            <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
		        </div>
		        <input type="text" name="search" class="form-control border-left-0" placeholder="Type to search inventory..." autocomplete="off">
		    </div>
		</div>
	</div>
	<div class="card-body">
        <div class="container-fluid">
        
			<?php if($stock_stats['total_low_stock'] > 0): ?>
			
			<div class="low-stock-alert">
				<div class="d-flex align-items-center">
					<i class="fas fa-exclamation-triangle status-icon"></i>
					<div class="flex-grow-1">
						<h5 class="mb-1">
							<strong>Low Stock Alert!</strong>
							<span class="low-stock-count"><?= $stock_stats['total_low_stock'] ?></span> 
							item<?= $stock_stats['total_low_stock'] != 1 ? 's' : '' ?> need attention
						</h5>
						<p class="mb-0">Items with stock level of 10 or below require immediate restocking</p>
					</div>
				</div>
				
				<div class="status-summary">
					<?php if($stock_stats['out_of_stock'] > 0): ?>
					<div class="status-item">
						<span class="status-number"><?= $stock_stats['out_of_stock'] ?></span>
						<span class="status-label">Out of Stock</span>
					</div>
					<?php endif; ?>
					
					<?php if($stock_stats['critical_stock'] > 0): ?>
					<div class="status-item">
						<span class="status-number"><?= $stock_stats['critical_stock'] ?></span>
						<span class="status-label">Critical (1-3)</span>
					</div>
					<?php endif; ?>
					
					<?php if($stock_stats['low_stock'] > 0): ?>
					<div class="status-item">
						<span class="status-number"><?= $stock_stats['low_stock'] ?></span>
						<span class="status-label">Low (4-10)</span>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
			
			<div class="table-responsive">
			<table class="table table-hover table-striped table-bordered" id="list">
				<colgroup>
					<col width="5%">
					<col width="12%">
					<col width="10%">
					<col width="20%">
					<col width="20%">
					<col width="10%">
					<col width="13%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Date Created</th>
						<th>Image</th>
						<th>Brand</th>
						<th>Name</th>
						<th>Unit</th>
						<th>Stock</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					
					$count_sql = "SELECT COUNT(*) as total FROM `product_list` WHERE delete_flag = 0";
					$total_records = $conn->query($count_sql)->fetch_assoc()['total'];
					$total_pages = ceil($total_records / $limit);
					
					$i = $offset + 1;
					
					$inventory_sql = "
						SELECT p.*, 
						COALESCE(s.total_stock, 0) - COALESCE(o.total_ordered, 0) as `available`
						FROM `product_list` p 
						LEFT JOIN (
						    SELECT product_id, SUM(quantity) as total_stock 
						    FROM `stock_list` 
						    WHERE (expiration IS NULL OR DATE(expiration) > '".date("Y-m-d")."')
						    GROUP BY product_id
						) s ON p.id = s.product_id
						LEFT JOIN (
						    SELECT oi.product_id, SUM(oi.quantity) as total_ordered 
						    FROM `order_items` oi
						    GROUP BY oi.product_id
						) o ON p.id = o.product_id
						WHERE p.delete_flag = 0  
						ORDER BY `available` ASC, p.brand ASC, p.name ASC
						LIMIT {$limit} OFFSET {$offset}
					";
					$qry = $conn->query($inventory_sql);
					while($row = $qry->fetch_assoc()):
						
						$product_branches = [];
						$product_id_val = intval($row['id']);
						$branch_stock_qry = $conn->query("
							SELECT b.branch_name, 
								   (COALESCE(SUM(s.quantity), 0) - COALESCE(o.total_ordered, 0)) as `stock_qty`
							FROM `branch_list` b
							INNER JOIN `stock_list` s ON s.branch_id = b.id
							LEFT JOIN (
								SELECT ol.branch_id, oi.product_id, SUM(oi.quantity) as total_ordered
								FROM `order_items` oi
								INNER JOIN `order_list` ol ON oi.order_id = ol.id
								WHERE oi.product_id = {$product_id_val}
								GROUP BY ol.branch_id
							) o ON o.branch_id = b.id
							WHERE s.product_id = {$product_id_val}
							AND b.status = 1
							AND (s.expiration IS NULL OR DATE(s.expiration) > '" . date("Y-m-d") . "')
							GROUP BY b.id, b.branch_name
							HAVING `stock_qty` > 0
							ORDER BY b.branch_name ASC
						");
						if ($branch_stock_qry) {
							while ($branch_row = $branch_stock_qry->fetch_assoc()) {
								$product_branches[] = $branch_row;
							}
						}
						
						
						$stock_class = '';
						$stock_badge_class = 'stock-good';
						if($row['available'] == 0) {
							$stock_class = 'out-of-stock';
							$stock_badge_class = 'stock-out';
						} elseif($row['available'] <= 3) {
							$stock_class = 'critical-stock';
							$stock_badge_class = 'stock-critical';
						} elseif($row['available'] <= 10) {
							$stock_class = 'low-stock';
							$stock_badge_class = 'stock-low';
						}
					?>
						<tr class="<?= $stock_class ?>">
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
							<td class="text-center">
								<img src="<?= validate_image($row['image_path']) ?>" alt="" class="img-thumbnail p-0 border product-img">
							</td>
							<td class=""><?= $row['brand'] ?></td>
							<td class="">
								<div style="line-height:1em">
									<div><?= $row['name'] ?></div>
									<div><small class="text-muted"><?= $row['dose'] ?></small></div>
								</div>
							</td>
							<td class="text-center">
								<span class="badge badge-info px-2 rounded-pill"><?= isset($row['unit']) ? $row['unit'] : 'Pcs' ?></span>
							</td>
							<td class="text-right">
								<span class="badge <?= $stock_badge_class ?> px-2 rounded-pill">
									<?= format_num($row['available'],0) ?>
									<?php if($row['available'] == 0): ?>
									<i class="fas fa-times ml-1"></i>
									<?php elseif($row['available'] <= 3): ?>
									<i class="fas fa-exclamation-triangle ml-1"></i>
									<?php elseif($row['available'] <= 10): ?>
									<i class="fas fa-exclamation ml-1"></i>
									<?php else: ?>
									<i class="fas fa-check ml-1"></i>
									<?php endif; ?>
								</span>
							</td>
							<td class="text-center">
								<?php 
								$branch_count = count($product_branches);
								if($branch_count > 0):
									$branches_shown = 0;
									foreach($product_branches as $branch_stock):
										if($branches_shown < 2):
								?>
								<span class="branch-badge" title="Stock: <?= format_num($branch_stock['stock_qty'], 0) ?>">
									<i class="fa fa-map-marker-alt"></i> <?= $branch_stock['branch_name'] ?>
								</span><br>
								<?php 
										$branches_shown++;
										endif;
									endforeach;
									if($branch_count > 2):
								?>
								<small class="text-muted">+<?= ($branch_count - 2) ?> more</small>
								<?php endif; ?>
								<?php else: ?>
								<small class="text-muted">No stock</small>
								<?php endif; ?>
							</td>
							<td align="center">
                                <button type="button" class="btn btn-sm btn-flat btn-light bg-gradient-light border view_inventory" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</button>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			</div>
			
			
            <?php if($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $limit, $total_records)) ?> of <?= number_format($total_records) ?> products
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if($page_num > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=inventory&p=1">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=inventory&p=<?= $page_num - 1 ?>">&laquo;</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page_num - 2);
                        $end_page = min($total_pages, $page_num + 2);
                        for($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                            <a class="page-link" href="?page=inventory&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if($page_num < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=inventory&p=<?= $page_num + 1 ?>">&raquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=inventory&p=<?= $total_pages ?>">Last</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.table').on('click','.view_inventory',function(){
			uni_modal("Inventory Details","inventory/view_inventory.php?id="+$(this).attr('data-id'),'mid-large')
		})
		
		$('.table').dataTable({
			paging: false,
			info: false,
			columnDefs: [
					{ orderable: false, targets: [2,7] }
			],
			order:[6,'asc'] 
		});
		$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
	})
</script>