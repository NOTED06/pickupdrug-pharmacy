

<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    
    $current_date = date("Y-m-d");
    $stmt = $conn->prepare("
        SELECT p.*, 
               c.name as `category`,
               (COALESCE((SELECT SUM(quantity) FROM `stock_list` WHERE product_id = p.id AND (expiration IS NULL OR date(expiration) > ?)), 0) 
                - COALESCE((SELECT SUM(quantity) FROM `order_items` WHERE product_id = p.id), 0)) as `available` 
        FROM `product_list` p 
        INNER JOIN `category_list` c ON p.category_id = c.id 
        WHERE p.id = ? AND p.delete_flag = 0
    ");
    $stmt->bind_param('si', $current_date, $_GET['id']);
    $stmt->execute();
    $qry = $stmt->get_result();
    
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>

<style>
	#product-img{
		max-width:100%;
		max-height:12em;
		object-fit:scale-down;
		object-position:center center;
	}
	
	.branch-stock-card {
		border: 1px solid #e9ecef;
		border-radius: 4px;
		padding: 8px;
		margin-bottom: 8px;
		transition: all 0.3s;
	}
	
	.branch-stock-card:hover {
		border-color: #007bff;
		box-shadow: 0 2px 4px rgba(0,123,255,0.2);
	}
	
	.branch-header {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: white;
		padding: 5px 10px;
		border-radius: 4px;
		margin-bottom: 5px;
		font-size: 0.9em;
	}
	
	.warehouse-header {
		background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
		color: white;
		padding: 5px 10px;
		border-radius: 4px;
		margin-bottom: 5px;
		font-size: 0.9em;
	}
	
	.stock-summary {
		display: flex;
		justify-content: space-around;
		padding: 5px;
		background: #f8f9fa;
		border-radius: 6px;
		margin-top: 5px;
	}
	
	.stock-summary-item {
		text-align: center;
	}
	
	.stock-summary-number {
		font-size: 1.2em;
		font-weight: bold;
		color: #007bff;
	}
	
	.location-badge-large {
		font-size: 0.85em;
		padding: 3px 8px;
		border-radius: 10px;
	}
	
	.branch-filter-toolbar {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: white;
		padding: 8px 12px;
		border-radius: 4px;
		margin-bottom: 10px;
		box-shadow: 0 2px 6px rgba(0,0,0,0.15);
	}
	
	.filter-select-white {
		background: white;
		border: 1px solid white;
		color: #333;
		font-weight: 500;
		padding: 4px 8px;
		border-radius: 3px;
		transition: all 0.3s;
		font-size: 0.85em;
	}
	
	.filter-select-white:focus {
		border-color: #764ba2;
		box-shadow: 0 0 0 0.2rem rgba(118, 75, 162, 0.25);
	}
	
	.stats-badge {
		background: rgba(255, 255, 255, 0.2);
		padding: 4px 10px;
		border-radius: 15px;
		display: inline-block;
		margin: 2px;
		font-size: 0.85em;
	}
	
	.stats-number {
		font-size: 1.1em;
		font-weight: bold;
		margin-right: 3px;
	}
	
	.transfer-btn {
		padding: 2px 6px;
		font-size: 0.8em;
	}
	
	
	.expired-row {
		background-color: #ffebee !important;
	}
	
	.warehouse-row {
		background-color: #f3e5f5 !important;
	}
	
	.branch-row {
		background-color: #e3f2fd !important;
	}

    
    .card-header { padding: 0.5rem 1rem; }
    .card-body { padding: 0.75rem; }
    dl { margin-bottom: 0; }
    dt { font-size: 0.9em; }
    dd { font-size: 0.9em; margin-bottom: 0.2rem; }
    h6 { font-size: 0.95rem; }
</style>

<div class="container-fluid">
	<div class="row mb-3">
		<div class="col-md-4 text-center">
			<img src="<?= validate_image(isset($image_path) ? $image_path : '') ?>" alt="<?= isset($name) ? $name : '' ?>" class="img-thumbnail p-0 border" id="product-img">
		</div>
		<div class="col-md-8">
			<div class="d-flex justify-content-between align-items-start">
				<h5><?= isset($brand) ? $brand : "" ?> - <?= isset($name) ? $name : "" ?> [<?= isset($dose) ? $dose : "" ?>]</h5>
				<button class="btn btn-primary btn-sm bg-gradient-primary rounded-0" type="button" id="new_entry">
					<i class="fa fa-plus-square"></i> New Stock
				</button>
			</div>
			<hr class="my-2">
			<div class="row">
				<div class="col-6">
					<dl>
						<dt class="text-muted">Category</dt>
						<dd><?= isset($category) ? $category : "" ?></dd>
						<dt class="text-muted">Price</dt>
						<dd><?= isset($price) ? format_num($price,2) : "" ?></dd>
					</dl>
				</div>
				<div class="col-6">
					<dl>
						<dt class="text-muted">Unit</dt>
						<dd><span class="badge badge-info px-2 rounded-pill"><?= isset($unit) ? $unit : "Pcs" ?></span></dd>
						<dt class="text-muted">Total Available</dt>
						<dd class="h6 text-success">
							<?= isset($available) ? format_num($available,0) : "" ?> units
						</dd>
					</dl>
				</div>
			</div>
		</div>
	</div>
	
	
	<div class="card rounded-0">
		<div class="card-header">
			<h6 class="card-title mb-0">
				<i class="fa fa-history"></i> Stock History
			</h6>
		</div>
		<div class="card-body">
			<div class="container-fluid p-0">
			
				<table class="table table-sm table-striped table-bordered" id="stock-history">
					<colgroup>
						<col width="15%">
						<col width="20%">
						<col width="15%">
						<col width="15%">
						<col width="10%">
						<col width="10%">
						<col width="15%">
					</colgroup>
					<thead>
						<tr>
							<th class="p-1 text-center">Date</th>
							<th class="p-1 text-center">Code</th>
							<th class="p-1 text-center">Qty</th>
							<th class="p-1 text-center">Expiry</th>
							<th class="p-1 text-center">Status</th>
							<th class="p-1 text-center">Value</th>
							<th class="p-1 text-center">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php if(isset($id)): ?>
						<?php 
						$stocks = $conn->query("
							SELECT s.* 
							FROM `stock_list` s 
							WHERE s.product_id = '{$id}' 
							ORDER BY abs(unix_timestamp(s.date_created)) DESC
						");
						
						while($row = $stocks->fetch_assoc()):
							$is_expired = !empty($row['expiration']) && (strtotime($row['expiration']) <= strtotime(date("Y-m-d")));
							$row_class = $is_expired ? 'expired-row' : '';
							$stock_value = isset($price) ? ($row['quantity'] * $price) : 0;
						?>
							<tr class="<?= $row_class ?> stock-row" data-quantity="<?= $row['quantity'] ?>">
								<td class="p-1 align-middle"><?= date("M d, Y", strtotime($row['date_created'])) ?></td>
								<td class="p-1 align-middle">
									<code><?= $row['code'] ?></code>
								</td>
								<td class="p-1 text-right align-middle">
									<strong><?= format_num($row['quantity'], 0) ?></strong>
									<?= $is_expired ? " <small class='text-danger'>(Exp)</small>" : "" ?>
								</td>
								<td class="p-1 align-middle">
									<?php if(!empty($row['expiration'])): 
										$exp_date = strtotime($row['expiration']);
										$days_until = ceil(($exp_date - time()) / (60*60*24));
										$exp_class = $days_until < 30 ? 'text-danger' : ($days_until < 90 ? 'text-warning' : 'text-success');
									?>
										<span class="<?= $exp_class ?>">
											<?= date("M d, Y", $exp_date) ?>
											<?php if($days_until > 0 && $days_until < 90): ?>
											<br><small>(<?= $days_until ?> days)</small>
											<?php endif; ?>
										</span>
									<?php else: ?>
										<span class="text-muted">No Exp</span>
									<?php endif; ?>
								</td>
								<td class="p-1 text-center align-middle">
									<?php if($is_expired): ?>
										<span class="badge badge-danger"><i class="fa fa-times-circle"></i> Exp</span>
									<?php else: ?>
										<span class="badge badge-success"><i class="fa fa-check-circle"></i> Active</span>
									<?php endif; ?>
								</td>
								<td class="p-1 text-right align-middle">
									<small class="text-muted">₱<?= format_num($stock_value, 2) ?></small>
								</td>
								<td class="p-1 text-center align-middle">
									<div class="btn-group btn-group-sm">
										<?php if($is_expired): ?>
											<button class="btn btn-secondary btn-xs" title="Expired - Read Only" type="button" disabled>
												<i class="fa fa-eye"></i>
											</button>
										<?php else: ?>
											<button class="btn btn-primary btn-xs edit_stock" title="Edit Stock" type="button" data-id='<?= $row['id'] ?>'>
												<i class="fa fa-edit"></i>
											</button>
											<button class="btn btn-danger btn-xs delete_stock" title="Delete Stock" type="button" data-code='<?= $row['code'] ?>' data-id='<?= $row['id'] ?>'>
												<i class="fa fa-trash"></i>
											</button>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endwhile; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
var dataTable;

$(function(){
	
	dataTable = $('#stock-history').dataTable({
		columnDefs: [
			{ orderable: false, targets: [6] }
		],
		order:[0,'desc'] 
	});
	
	$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
	
	
	$('#new_entry').click(function(){
		uni_modal('<i class="far fa-plus-square"></i> Add New Stock Entry', 'inventory/manage_stock.php?pid=<?= isset($id) ? $id : '' ?>')
	})
	
	
	$('.edit_stock').click(function(){
		uni_modal('<i class="fa fa-edit"></i> Edit Stock Entry Details', 'inventory/manage_stock.php?pid=<?= isset($id) ? $id : '' ?>&id='+$(this).attr('data-id'))
	})
	
	
	$('.delete_stock').click(function(){
		_conf("Are you sure to delete this <b>["+$(this).attr('data-code')+"]</b> Stock Entry permanently?","delete_stock", [$(this).attr('data-id')])
	})
})

function delete_stock($id){
	start_loader();
	$.ajax({
		url:_base_url_+"classes/Master.php?f=delete_stock",
		method:"POST",
		data:{id: $id},
		dataType:"json",
		error:err=>{
			console.log(err)
			alert_toast("An error occured.",'error');
			end_loader();
		},
		success:function(resp){
			if(typeof resp== 'object' && resp.status == 'success'){
				location.reload();
			}else{
				alert_toast("An error occured.",'error');
				end_loader();
			}
		}
	})
}
</script>