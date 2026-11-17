<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php 
$table_filter = isset($_GET['table']) ? $_GET['table'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$table_check = $conn->query("SHOW TABLES LIKE 'deleted_items'");
if($table_check->num_rows == 0){
	$conn->query("CREATE TABLE IF NOT EXISTS `deleted_items` (
		`id` INT(11) NOT NULL AUTO_INCREMENT,
		`table_name` VARCHAR(100) NOT NULL,
		`item_id` INT(11) NOT NULL,
		`item_data` TEXT DEFAULT NULL,
		`deleted_by` INT(11) DEFAULT NULL,
		`deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		INDEX `idx_table_item` (`table_name`, `item_id`),
		INDEX `idx_deleted_at` (`deleted_at`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$conn->query("DELETE FROM `deleted_items` WHERE `deleted_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$auto_purged = $conn->affected_rows;

$count_qry = $conn->query("SELECT COUNT(*) as total FROM deleted_items");
$total_deleted = $count_qry ? $count_qry->fetch_assoc()['total'] : 0;

$expiring_qry = $conn->query("SELECT COUNT(*) as cnt FROM deleted_items WHERE `deleted_at` < DATE_SUB(NOW(), INTERVAL 23 DAY)");
$expiring_soon = $expiring_qry ? $expiring_qry->fetch_assoc()['cnt'] : 0;

$table_counts = array();
$counts_qry = $conn->query("SELECT table_name, COUNT(*) as cnt FROM deleted_items GROUP BY table_name");
if($counts_qry){
	while($row = $counts_qry->fetch_assoc()){
		$table_counts[$row['table_name']] = $row['cnt'];
	}
}
?>
<div class="card card-outline rounded-0 card-teal">
	<div class="card-header">
		<h3 class="card-title"><i class="fas fa-trash-restore mr-2"></i>Maintenance - Deleted Items <span class="badge badge-secondary"><?= number_format($total_deleted) ?> total</span>
			<?php if($auto_purged > 0): ?><span class="badge badge-danger ml-2"><?= $auto_purged ?> expired items permanently deleted</span><?php endif; ?>
			<?php if($expiring_soon > 0): ?><span class="badge badge-warning ml-2"><?= $expiring_soon ?> expiring within 7 days</span><?php endif; ?>
		</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-sm btn-danger mr-2" id="purge_expired" title="Permanently delete all expired items">
				<i class="fas fa-skull-crossbones"></i> Purge All Expired
			</button>
			<div class="d-inline-block">
				<div class="input-group input-group-sm" style="max-width: 280px;">
					<div class="input-group-prepend">
						<span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
					</div>
					<input type="text" name="search" class="form-control border-left-0" placeholder="Search deleted items..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
				</div>
			</div>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			
			<ul class="nav nav-pills mb-3" id="filterTabs" role="tablist">
				<li class="nav-item">
					<a class="nav-link <?= empty($table_filter) ? 'active' : '' ?>" href="./?page=maintenance">All <span class="badge badge-secondary"><?= $total_deleted ?></span></a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= $table_filter == 'order_list' ? 'active' : '' ?>" href="./?page=maintenance&table=order_list">Orders <span class="badge badge-info"><?= isset($table_counts['order_list']) ? $table_counts['order_list'] : 0 ?></span></a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= $table_filter == 'product_list' ? 'active' : '' ?>" href="./?page=maintenance&table=product_list">Products <span class="badge badge-primary"><?= isset($table_counts['product_list']) ? $table_counts['product_list'] : 0 ?></span></a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= $table_filter == 'category_list' ? 'active' : '' ?>" href="./?page=maintenance&table=category_list">Categories <span class="badge badge-success"><?= isset($table_counts['category_list']) ? $table_counts['category_list'] : 0 ?></span></a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= $table_filter == 'users' ? 'active' : '' ?>" href="./?page=maintenance&table=users">Users <span class="badge badge-warning"><?= isset($table_counts['users']) ? $table_counts['users'] : 0 ?></span></a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= $table_filter == 'customer_list' ? 'active' : '' ?>" href="./?page=maintenance&table=customer_list">Customers <span class="badge badge-danger"><?= isset($table_counts['customer_list']) ? $table_counts['customer_list'] : 0 ?></span></a>
				</li>
			</ul>
			
			<div class="table-responsive">
			<table class="table table-hover table-striped table-bordered" id="list">
				<colgroup>
					<col width="4%">
					<col width="12%">
					<col width="22%">
					<col width="22%">
					<col width="14%">
					<col width="16%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th class="p-1 text-center">#</th>
						<th class="p-1 text-center">Type</th>
						<th class="p-1 text-center">Item Details</th>
						<th class="p-1 text-center">Deleted Info</th>
						<th class="p-1 text-center">Date Deleted</th>
						<th class="p-1 text-center">Auto-Delete In</th>
						<th class="p-1 text-center">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$where = "1=1";
					if(!empty($table_filter)){
						$where .= " AND d.table_name = '" . $conn->real_escape_string($table_filter) . "'";
					}
					if(!empty($search)){
						$search_escaped = $conn->real_escape_string($search);
						$where .= " AND (d.item_data LIKE '%{$search_escaped}%' OR d.table_name LIKE '%{$search_escaped}%')";
					}
					
					$i = 1;
					$qry = $conn->query("SELECT d.*, u.username as deleted_by_name 
					                     FROM `deleted_items` d 
					                     LEFT JOIN users u ON d.deleted_by = u.id 
					                     WHERE {$where} 
					                     ORDER BY d.deleted_at DESC 
					                     LIMIT 500");
					
					if($qry && $qry->num_rows > 0):
						while($row = $qry->fetch_assoc()):
							$item_data = json_decode($row['item_data'], true);
							$item_name = '';
							$item_id_display = $row['item_id'];
							
							
							if($row['table_name'] == 'order_list' && $item_data){
								$item_name = isset($item_data['code']) ? $item_data['code'] : 'Order #' . $item_id_display;
								if(isset($item_data['total_amount'])){
									$item_name .= ' - ' . format_num($item_data['total_amount'], 2);
								}
							} elseif($row['table_name'] == 'product_list' && $item_data){
								$item_name = (isset($item_data['brand']) ? $item_data['brand'] . ' ' : '') . (isset($item_data['name']) ? $item_data['name'] : 'Product #' . $item_id_display);
							} elseif($row['table_name'] == 'category_list' && $item_data){
								$item_name = isset($item_data['name']) ? $item_data['name'] : 'Category #' . $item_id_display;
							} elseif($row['table_name'] == 'users' && $item_data){
								$item_name = isset($item_data['username']) ? $item_data['username'] : (trim(($item_data['firstname'] ?? '') . ' ' . ($item_data['lastname'] ?? '')) ?: 'User #' . $item_id_display);
							} elseif($row['table_name'] == 'customer_list' && $item_data){
								$item_name = trim(($item_data['firstname'] ?? '') . ' ' . ($item_data['lastname'] ?? '')) ?: (isset($item_data['email']) ? $item_data['email'] : 'Customer #' . $item_id_display);
							} else {
								$item_name = ucfirst(str_replace('_list', '', $row['table_name'])) . ' #' . $item_id_display;
							}
							
							$table_label = $row['table_name'] == 'users' ? 'User' : ucfirst(str_replace('_list', '', $row['table_name']));
					?>
						<tr>
							<td class="p-1 align-middle text-center"><?= $i++ ?></td>
							<td class="p-1 align-middle">
								<?php 
								$badge_class = 'badge-secondary';
								if($row['table_name'] == 'order_list') $badge_class = 'badge-info';
								elseif($row['table_name'] == 'product_list') $badge_class = 'badge-primary';
								elseif($row['table_name'] == 'category_list') $badge_class = 'badge-success';
								elseif($row['table_name'] == 'users') $badge_class = 'badge-warning';
								elseif($row['table_name'] == 'customer_list') $badge_class = 'badge-danger';
								?>
								<span class="badge <?= $badge_class ?>"><?= $table_label ?></span>
							</td>
							<td class="p-1 align-middle">
								<strong><?= htmlspecialchars($item_name) ?></strong>
								<?php if($item_data && $row['table_name'] == 'order_list'): ?>
									<br><small class="text-muted">
										<?php if(isset($item_data['status'])): 
											$status_labels = ['Pending', 'Packed', 'Ready for Pick-up', 'Done', 'Cancelled'];
											$status = intval($item_data['status']);
											echo 'Status: ' . (isset($status_labels[$status]) ? $status_labels[$status] : 'Unknown');
										endif; ?>
									</small>
								<?php endif; ?>
							</td>
							<td class="p-1 align-middle">
								<small class="text-muted">
									<?php if($row['deleted_by_name']): ?>
										Deleted by: <strong><?= htmlspecialchars($row['deleted_by_name']) ?></strong><br>
									<?php endif; ?>
									ID: <?= $item_id_display ?>
								</small>
							</td>
							<td class="p-1 align-middle">
								<?= date("M d, Y<br>h:i A", strtotime($row['deleted_at'])) ?>
							</td>
							<td class="p-1 align-middle text-center">
								<?php
									$deleted_date = new DateTime($row['deleted_at']);
									$expiry_date = clone $deleted_date;
									$expiry_date->modify('+30 days');
									$now = new DateTime();
									$diff = $now->diff($expiry_date);
									$days_left = $diff->invert ? 0 : $diff->days;
									
									if($days_left <= 3){
										$badge_color = 'danger';
									} elseif($days_left <= 7){
										$badge_color = 'warning';
									} else {
										$badge_color = 'info';
									}
								?>
								<span class="badge badge-<?= $badge_color ?>" style="font-size: 0.85em;">
									<i class="fas fa-clock mr-1"></i><?= $days_left ?> day<?= $days_left != 1 ? 's' : '' ?> left
								</span>
								<br><small class="text-muted">Expires: <?= $expiry_date->format('M d, Y') ?></small>
							</td>
							<td class="p-1 align-middle text-center">
								<div class="btn-group">
									<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<i class="fas fa-ellipsis-v"></i>
									</button>
									<div class="dropdown-menu dropdown-menu-right shadow-sm">
										<a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
											<i class="fa fa-eye text-info mr-2"></i> View Details
										</a>
										<a class="dropdown-item restore_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
											<i class="fa fa-undo text-primary mr-2"></i> Restore Item
										</a>
										<div class="dropdown-divider"></div>
										<a class="dropdown-item text-danger permanent_delete" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
											<i class="fa fa-trash mr-2"></i> Permanently Delete
										</a>
									</div>
								</div>
							</td>
						</tr>
					<?php 
						endwhile;
					else:
					?>
						<tr>
							<td colspan="7" class="text-center py-5">
								<h5 class="text-muted">No deleted items found</h5>
								<p class="text-muted">Items that are deleted from the system will appear here.</p>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="view_modal" role="dialog">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Deleted Item Details</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="view_details">
				<p class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading...</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<script>
	$(function(){
		
		$('input[name="search"]').on('keypress', function(e){
			if(e.which == 13){
				var search = $(this).val();
				var table = '<?= $table_filter ?>';
				var url = './?page=maintenance';
				if(table) url += '&table=' + table;
				if(search) url += '&search=' + encodeURIComponent(search);
				location.href = url;
			}
		});
		
		
		$('.view_data').click(function(){
			var id = $(this).attr('data-id');
			start_loader();
			$.ajax({
				url: _base_url_ + 'classes/Master.php?f=get_deleted_items',
				method: 'GET',
				data: {},
				dataType: 'json',
				error: err => {
					console.log(err);
					alert_toast("An error occurred", 'error');
					end_loader();
				},
				success: function(resp){
					end_loader();
					if(Array.isArray(resp)){
						var item = resp.find(i => i.id == id);
						if(item){
							var item_data = item.parsed_data || {};
							var html = '<div class="container-fluid">';
							html += '<div class="row">';
							html += '<div class="col-12"><h6><strong>Item Type:</strong> ' + item.table_name.replace('_list', '').replace('_', ' ').toUpperCase() + '</h6></div>';
							html += '<div class="col-12"><h6><strong>Item ID:</strong> ' + item.item_id + '</h6></div>';
							if(item.deleted_by_name){
								html += '<div class="col-12"><h6><strong>Deleted By:</strong> ' + item.deleted_by_name + '</h6></div>';
							}
							html += '<div class="col-12"><h6><strong>Deleted At:</strong> ' + new Date(item.deleted_at).toLocaleString() + '</h6></div>';
							html += '<div class="col-12 mt-3"><h6><strong>Item Data:</strong></h6>';
							html += '<pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">' + JSON.stringify(item_data, null, 2) + '</pre>';
							html += '</div></div></div>';
							$('#view_details').html(html);
							$('#view_modal').modal('show');
						}
					}
				}
			});
		});
		
		
		$('.restore_data').click(function(){
			var id = $(this).attr('data-id');
			_conf("Are you sure to restore this item?","restore_item", [id]);
		});
		
		
		$('.permanent_delete').click(function(){
			var id = $(this).attr('data-id');
			_conf("Are you sure you want to PERMANENTLY DELETE this item? This action cannot be undone!","permanent_delete_item", [id]);
		});
		
		
		$('#purge_expired').click(function(){
			_conf("Are you sure you want to permanently delete ALL expired items (older than 30 days)? This cannot be undone!","purge_expired_items");
		});
	});
	
	function restore_item(id){
		start_loader();
		$.ajax({
			url: _base_url_ + "classes/Master.php?f=restore_item",
			method: "POST",
			data: {deleted_id: id, csrf_token: _csrf_token_},
			dataType: "json",
			error: err => {
				console.log(err);
				alert_toast("An error occurred", 'error');
				end_loader();
			},
			success: function(resp){
				if(typeof resp == 'object' && resp.status == 'success'){
					alert_toast("Item restored successfully", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				}else{
					alert_toast(resp.msg || "An error occurred", 'error');
					end_loader();
				}
			}
		});
	}
	
	function permanent_delete_item(id){
		start_loader();
		$.ajax({
			url: _base_url_ + "classes/Master.php?f=permanent_delete_item",
			method: "POST",
			data: {deleted_id: id, csrf_token: _csrf_token_},
			dataType: "json",
			error: err => {
				console.log(err);
				alert_toast("An error occurred", 'error');
				end_loader();
			},
			success: function(resp){
				if(typeof resp == 'object' && resp.status == 'success'){
					alert_toast("Item permanently deleted", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				}else{
					alert_toast(resp.msg || "An error occurred", 'error');
					end_loader();
				}
			}
		});
	}
	
	function purge_expired_items(){
		start_loader();
		$.ajax({
			url: _base_url_ + "classes/Master.php?f=purge_expired_items",
			method: "POST",
			data: {csrf_token: _csrf_token_},
			dataType: "json",
			error: err => {
				console.log(err);
				alert_toast("An error occurred", 'error');
				end_loader();
			},
			success: function(resp){
				if(typeof resp == 'object' && resp.status == 'success'){
					alert_toast(resp.msg || "Expired items purged", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				}else{
					alert_toast(resp.msg || "An error occurred", 'error');
					end_loader();
				}
			}
		});
	}
</script>
