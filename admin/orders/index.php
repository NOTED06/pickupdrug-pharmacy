<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php 
$status = isset($_GET['status']) ? $_GET['status'] : '';

$stat_arr = ['Pending Orders', 'Packed Orders', 'Ready for Pick Up', 'Completed Order', 'Cancelled Orders'];

$page_num = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50; 
$offset = ($page_num - 1) * $limit;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where = "";
$where_conditions = array();

$where_conditions[] = "(o.deleted_at IS NULL OR o.deleted_at = '')";

if($status !== '' && is_numeric($status)) {
    $where_conditions[] = "o.status = " . intval($status);
}

if(!empty($search)) {
    $where_conditions[] = "(o.code LIKE '%{$search}%' OR c.firstname LIKE '%{$search}%' OR c.lastname LIKE '%{$search}%')";
}

if(!empty($where_conditions)) {
    $where = "WHERE " . implode(" AND ", $where_conditions);
}

$count_result = $conn->query("SELECT COUNT(*) as total FROM order_list o INNER JOIN customer_list c ON o.customer_id = c.id {$where}");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
?>
<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">List of <?= isset($stat_arr[$status]) ? $stat_arr[$status] : 'All Orders' ?> <span class="badge badge-secondary" data-live-count><?= number_format($total_records) ?> total</span></h3>
		<div class="card-tools">
		    <div class="d-inline-block">
		        <div class="input-group input-group-sm" style="max-width: 280px;">
		            <div class="input-group-prepend">
		                <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
		            </div>
		            <input type="text" name="search" class="form-control border-left-0" placeholder="Type to search orders..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
		        </div>
		    </div>
		</div>
	</div>
	<div class="card-body">
        <div class="container-fluid">
			<div class="table-responsive">
			<table class="table table-hover table-striped table-bordered" id="list">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="15%">
					<col width="20%">
					<col width="15%">
					<col width="15%">
					<col width="15%">
				</colgroup>
				<thead>
					<tr>
						<th class="p-1 text-center">#</th>
						<th class="p-1 text-center">Date Ordered</th>
						<th class="p-1 text-center">Code</th>
						<th class="p-1 text-center">Customer</th>
						<th class="p-1 text-center">Total Amount</th>
						<th class="p-1 text-center">Status</th>
						<th class="p-1 text-center">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = $offset + 1;
					
					$qry = $conn->query("SELECT o.*, CONCAT(c.firstname, ' ', COALESCE(concat(c.middlename, ' '), ''), c.lastname) as customer 
					                     FROM `order_list` o 
					                     INNER JOIN customer_list c ON o.customer_id = c.id 
					                     {$where} 
					                     ORDER BY o.date_created DESC 
					                     LIMIT {$limit} OFFSET {$offset}");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="p-1 align-middle text-center"><?= $i++ ?></td>
							<td class="p-1 align-middle"><?= date("Y-m-d H:i", strtotime($row['date_created'])) ?></td>
							<td class="p-1 align-middle"><?= $row['code'] ?><?php if(!empty($row['is_urgent'])): ?> <span class="badge badge-danger" style="font-size:0.7em;"><i class="fas fa-bolt"></i> URGENT</span><?php endif; ?></td>
							<td class="p-1 align-middle"><?= $row['customer'] ?></td>
							<td class="p-1 align-middle text-right"><?= format_num($row['total_amount'],2) ?></td>
							<td class="p-1 align-middle text-center">
								<?php 
								switch($row['status']){
									case 0:
										echo '<span class="badge badge-secondary bg-gradient-secondary px-3 rounded-pill">Pending</span>';
										break;
									case 1:
										echo '<span class="badge badge-primary bg-gradient-primary px-3 rounded-pill">Packed</span>';
										break;
									case 2:
										echo '<span class="badge badge-warning bg-gradient-warning px-3 rounded-pill">Ready for Pick Up</span>';
										break;
									case 3:
										echo '<span class="badge badge-teal bg-gradient-teal px-3 rounded-pill">Done</span>';
										break;
									case 4:
										echo '<span class="badge badge-teal bg-gradient-danger px-3 rounded-pill">Cancelled</span>';
										break;
								}
								?>
							</td>
							<td class="p-1 align-middle text-center">
								<button type="button" class="btn btn-flat btn-sm btn-light border-gradient-light border view_order" data-id="<?= $row['id'] ?>"><i class="fa fa-eye text-dark"></i> View</button>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			</div>
			
			
			<?php if($total_pages > 1): ?>
			<nav aria-label="Order pagination" class="mt-3">
			    <div class="d-flex justify-content-between align-items-center">
			        <div class="pagination-info" style="font-size: 0.9em; color: #6c757d;">
			            Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $limit, $total_records)) ?> of <?= number_format($total_records) ?> orders
			        </div>
			        <ul class="pagination pagination-sm mb-0">
			            <?php if($page_num > 1): ?>
			            <li class="page-item">
			                <a class="page-link" href="?page=orders&p=1<?= $status !== '' ? '&status='.$status : '' ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">First</a>
			            </li>
			            <li class="page-item">
			                <a class="page-link" href="?page=orders&p=<?= $page_num - 1 ?><?= $status !== '' ? '&status='.$status : '' ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Prev</a>
			            </li>
			            <?php endif; ?>
			            
			            <?php
			            $start_page = max(1, $page_num - 2);
			            $end_page = min($total_pages, $page_num + 2);
			            for($p = $start_page; $p <= $end_page; $p++):
			            ?>
			            <li class="page-item <?= $p == $page_num ? 'active' : '' ?>">
			                <a class="page-link" href="?page=orders&p=<?= $p ?><?= $status !== '' ? '&status='.$status : '' ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"><?= $p ?></a>
			            </li>
			            <?php endfor; ?>
			            
			            <?php if($page_num < $total_pages): ?>
			            <li class="page-item">
			                <a class="page-link" href="?page=orders&p=<?= $page_num + 1 ?><?= $status !== '' ? '&status='.$status : '' ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Next</a>
			            </li>
			            <li class="page-item">
			                <a class="page-link" href="?page=orders&p=<?= $total_pages ?><?= $status !== '' ? '&status='.$status : '' ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Last</a>
			            </li>
			            <?php endif; ?>
			        </ul>
			    </div>
			</nav>
			<?php endif; ?>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.table').on('click','.view_order',function(){
			uni_modal("Order Details","orders/view_order.php?id="+$(this).attr('data-id'),'large')
		})
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this request permanently?","delete_request",[$(this).attr('data-id')])
		})
		
		$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')

		
		var urlParams = new URLSearchParams(window.location.search);
		var viewOrderId = urlParams.get('view_order');
		if(viewOrderId) {
			if (typeof uni_modal === 'function') {
				uni_modal("Order Details", "orders/view_order.php?id=" + viewOrderId, 'large');
			}
		}
	})
	
</script>