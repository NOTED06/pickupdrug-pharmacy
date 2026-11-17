<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<?php

$col_check = $conn->query("SHOW COLUMNS FROM `customer_list` LIKE 'deleted_at'");
if(!$col_check || $col_check->num_rows == 0){
	@$conn->query("ALTER TABLE `customer_list` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL");
}
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50; 
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$base_deleted = " (deleted_at IS NULL OR deleted_at = '') ";
$search_condition = " WHERE " . $base_deleted;
if(!empty($search)) {
    $search_condition .= " AND (firstname LIKE '%{$search}%' OR lastname LIKE '%{$search}%' OR email LIKE '%{$search}%' OR middlename LIKE '%{$search}%')";
}

$count_result = $conn->query("SELECT COUNT(*) as total FROM customer_list {$search_condition}");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
?>

<style>
    .customer-avatar{
        width:3rem;
        height:3rem;
        object-fit:scale-down;
        object-position:center center;
    }
    .age-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
        background-color: #e3f2fd;
        color: #1976d2;
        border: 1px solid #bbdefb;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .customer-row-inactive {
        opacity: 0.75;
        background-color: #fff3cd !important;
    }
    
    .btn-group-actions {
        display: flex;
        gap: 2px;
    }
    
    .btn-group-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .pagination-info {
        font-size: 0.9em;
        color: #6c757d;
    }
    
    .search-box {
        max-width: 300px;
    }
</style>

<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">List of Customers <span class="badge badge-secondary" data-live-count><?= number_format($total_records) ?> total</span></h3>
		<div class="card-tools">
			<div class="d-inline-block mr-2">
			    <div class="input-group input-group-sm search-box">
			        <div class="input-group-prepend">
			            <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
			        </div>
			        <input type="text" name="search" class="form-control border-left-0" placeholder="Type to search customers..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
			    </div>
			</div>
			<a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
		</div>
	</div>
	<div class="card-body">
        <div class="container-fluid">
			<div class="table-responsive">
			<table class="table table-hover table-striped table-bordered" id="list">
				<colgroup>
					<col width="4%">
					<col width="10%">
					<col width="10%">
					<col width="16%">
					<col width="14%">
					<col width="10%">
					<col width="6%">
					<col width="8%">
					<col width="8%">
					<col width="14%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Date Updated</th>
						<th>Avatar</th>
						<th>Name</th>
						<th>Email</th>
						<th>Birthdate</th>
						<th>Age</th>
						<th>Gender</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = $offset + 1;
						
						$qry = $conn->query("SELECT *, concat(firstname,' ', coalesce(concat(middlename,' '), '') , lastname) as `name` 
						                     FROM `customer_list` 
						                     {$search_condition}
						                     ORDER BY `date_updated` DESC 
						                     LIMIT {$limit} OFFSET {$offset}");
						while($row = $qry->fetch_assoc()):
							
							$age = 'N/A';
							if(!empty($row['birthdate']) && $row['birthdate'] != '0000-00-00') {
								$birthDate = new DateTime($row['birthdate']);
								$today = new DateTime();
								$calculatedAge = $today->diff($birthDate)->y;
								$age = $calculatedAge . ' yrs';
							} elseif(isset($row['age']) && !empty($row['age'])) {
								$age = $row['age'] . ' yrs';
							}
							
							
							$status = isset($row['status']) ? $row['status'] : 1;
							$is_active = ($status == 1);
							$row_class = !$is_active ? 'customer-row-inactive' : '';
					?>
						<tr class="<?php echo $row_class; ?>">
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("Y-m-d H:i",strtotime($row['date_updated'])) ?></td>
							<td class="text-center">
                                <img src="<?= validate_image($row['avatar']) ?>" alt="" class="img-thumbnail rounded-circle customer-avatar">
                            </td>
							<td><?php echo $row['name'] ?></td>
							<td><?php echo $row['email'] ?></td>
							<td class="text-center">
								<?php 
								if(!empty($row['birthdate']) && $row['birthdate'] != '0000-00-00') {
									echo date("M d, Y", strtotime($row['birthdate']));
								} else {
									echo 'N/A';
								}
								?>
							</td>
							<td class="text-center">
								<span class="age-badge"><?php echo $age ?></span>
							</td>
							<td class="text-center">
								<span class="badge badge-<?php echo $row['gender'] == 'Male' ? 'primary' : 'info' ?>"><?php echo $row['gender'] ?></span>
							</td>
							<td class="text-center">
							<span class="status-badge <?php echo $is_active ? 'status-active' : 'status-inactive' ?>">
								<?php echo $is_active ? 'Active' : 'Deactivated' ?>
								</span>
							</td>
							<td align="center">
								<div class="btn-group-actions">
									<button type="button" class="btn btn-flat btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
										Action <span class="caret"></span>
									</button>
									<div class="dropdown-menu">
										<a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
											<span class="fa fa-edit text-primary"></span> Edit
										</a>
										<a class="dropdown-item view_details" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
											<span class="fa fa-eye text-info"></span> View Details
										</a>
										<div class="dropdown-divider"></div>
										
										<?php if($is_active): ?>
											<a class="dropdown-item deactivate_customer" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>" data-name="<?php echo $row['name'] ?>">
												<span class="fa fa-ban text-warning"></span> Deactivate
											</a>
										<?php else: ?>
											<a class="dropdown-item reactivate_customer" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>" data-name="<?php echo $row['name'] ?>">
												<span class="fa fa-check text-success"></span> Reactivate
											</a>
										<?php endif; ?>
										
										<div class="dropdown-divider"></div>
										<a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>" data-name="<?php echo $row['name'] ?>">
											<span class="fa fa-trash text-danger"></span> Delete Permanently
										</a>
									</div>
								</div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			</div>
			
			
			<?php if($total_pages > 1): ?>
			<nav aria-label="Customer pagination" class="mt-3">
			    <div class="d-flex justify-content-between align-items-center">
			        <div class="pagination-info">
			            Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $limit, $total_records)) ?> of <?= number_format($total_records) ?> customers
			        </div>
			        <ul class="pagination pagination-sm mb-0">
			            <?php if($page > 1): ?>
			            <li class="page-item">
			                <a class="page-link" href="?page=customers&p=1<?= !empty($search) ? '&search='.urlencode($search) : '' ?>">First</a>
			            </li>
			            <li class="page-item">
			                <a class="page-link" href="?page=customers&p=<?= $page - 1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Prev</a>
			            </li>
			            <?php endif; ?>
			            
			            <?php
			            $start_page = max(1, $page - 2);
			            $end_page = min($total_pages, $page + 2);
			            for($p = $start_page; $p <= $end_page; $p++):
			            ?>
			            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
			                <a class="page-link" href="?page=customers&p=<?= $p ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"><?= $p ?></a>
			            </li>
			            <?php endfor; ?>
			            
			            <?php if($page < $total_pages): ?>
			            <li class="page-item">
			                <a class="page-link" href="?page=customers&p=<?= $page + 1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Next</a>
			            </li>
			            <li class="page-item">
			                <a class="page-link" href="?page=customers&p=<?= $total_pages ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Last</a>
			            </li>
			            <?php endif; ?>
			        </ul>
			    </div>
			</nav>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="modal fade" id="customerDetailsModal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Customer Details</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="customer-details-content">
					<div class="text-center">
						<i class="fa fa-spinner fa-spin fa-2x"></i>
						<p>Loading customer details...</p>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
	
	$('.delete_data').click(function(){
		var customer_id = $(this).attr('data-id');
		var customer_name = $(this).attr('data-name');
		
		
		var message = "⚠️ PERMANENT DELETION WARNING ⚠️\n\n" +
					 "You are about to PERMANENTLY DELETE customer: '" + customer_name + "'\n\n" +
					 "This action:\n" +
					 "• Cannot be undone\n" +
					 "• Will be blocked if customer has existing transactions/orders\n" +
					 "• Will remove all customer data forever\n\n" +
					 "Consider using 'Deactivate' instead to preserve data.\n\n" +
					 "Are you absolutely sure you want to proceed?";
		
		if(confirm(message)) {
			delete_customer(customer_id);
		}
	});
	
	
	$('.deactivate_customer').click(function(){
		var customer_id = $(this).attr('data-id');
		var customer_name = $(this).attr('data-name');
		
		var message = "Deactivate customer '" + customer_name + "'?\n\n" +
					 "This will:\n" +
					 "• Keep all customer data\n" +
					 "• Mark account as inactive\n" +
					 "• Allow reactivation later\n\n" +
					 "Continue?";
		
		if(confirm(message)) {
			deactivate_customer(customer_id);
		}
	});
	
	
	$('.reactivate_customer').click(function(){
		var customer_id = $(this).attr('data-id');
		var customer_name = $(this).attr('data-name');
		
		var message = "Reactivate customer '" + customer_name + "'?";
		
		if(confirm(message)) {
			reactivate_customer(customer_id);
		}
	});

	
	$('#create_new').click(function(){
		uni_modal("Add New Customer","customers/manage_customer.php",'mid-large')
	})

	
	$('.edit_data').click(function(){
		uni_modal("Update Customer Details","customers/manage_customer.php?id="+$(this).attr('data-id'),'mid-large')
	})
	
	
	$('.view_details').click(function(){
		var customer_id = $(this).attr('data-id');
		view_customer_details(customer_id);
	});
	
	
	$('.table').dataTable({
		columnDefs: [
			{ orderable: false, targets: [9] } 
		],
		order: [0,'asc']
	});
	$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle');
});

function delete_customer(customer_id){
	start_loader();
	$.ajax({
		url: _base_url_+"classes/Users.php?f=delete_customer",
		method: "POST",
		data: {id: customer_id, csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''},
		dataType: 'json',
		error: function(xhr, status, err){
			var msg = "An error occurred while processing the request.";
			try {
				if (xhr && xhr.responseText) { var r = JSON.parse(xhr.responseText); if (r && (r.msg || r.error)) msg = r.msg || r.error; }
			} catch(e) {}
			console.log("Delete customer error:", xhr ? xhr.responseText : err);
			alert_toast(msg, 'error');
			end_loader();
		},
		success: function(resp){
			if(resp.status == 'success'){
				alert_toast(resp.msg, 'success');
				setTimeout(function(){ location.href = './?page=maintenance'; }, 800);
			} else {
				alert_toast(resp.msg || "Failed to delete customer.", 'error');
				end_loader();
			}
		}
	});
}

function deactivate_customer(customer_id){
	start_loader();
	$.ajax({
		url: _base_url_+"classes/Users.php?f=deactivate_customer",
		method: "POST",
		data: {id: customer_id, csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''},
		dataType: 'json',
		error: function(err){
			console.log(err);
			alert_toast("An error occurred while deactivating customer.", 'error');
			end_loader();
		},
		success: function(resp){
			if(resp.status == 'success'){
				alert_toast(resp.msg, 'success');
				setTimeout(function(){
					location.reload();
				}, 1500);
			} else {
				alert_toast(resp.msg || "Failed to deactivate customer.", 'error');
				end_loader();
			}
		}
	});
}

function reactivate_customer(customer_id){
	start_loader();
	$.ajax({
		url: _base_url_+"classes/Users.php?f=reactivate_customer",
		method: "POST",
		data: {id: customer_id, csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''},
		dataType: 'json',
		error: function(err){
			console.log(err);
			alert_toast("An error occurred while reactivating customer.", 'error');
			end_loader();
		},
		success: function(resp){
			if(resp.status == 'success'){
				alert_toast(resp.msg, 'success');
				setTimeout(function(){
					location.reload();
				}, 1500);
			} else {
				alert_toast(resp.msg || "Failed to reactivate customer.", 'error');
				end_loader();
			}
		}
	});
}

function view_customer_details(customer_id){
	$('#customerDetailsModal').modal('show');
	
	$.ajax({
		url: _base_url_+"classes/Users.php?f=get_customer_details",
		method: "POST",
		data: {id: customer_id},
		dataType: 'json',
		success: function(resp){
			if(resp.status == 'success'){
				var customer = resp.data;
				var associations = resp.associations;
				
				var content = '<div class="row">';
				
				
				content += '<div class="col-md-6">';
				content += '<h5><i class="fa fa-user"></i> Customer Information</h5>';
				content += '<table class="table table-sm">';
				content += '<tr><td><strong>Name:</strong></td><td>' + (customer.firstname || '') + ' ' + (customer.middlename || '') + ' ' + (customer.lastname || '') + '</td></tr>';
				content += '<tr><td><strong>Email:</strong></td><td>' + (customer.email || 'N/A') + '</td></tr>';
				content += '<tr><td><strong>Contact:</strong></td><td>' + (customer.contact || 'N/A') + '</td></tr>';
				content += '<tr><td><strong>Gender:</strong></td><td>' + (customer.gender || 'N/A') + '</td></tr>';
				content += '<tr><td><strong>Birthdate:</strong></td><td>' + (customer.birthdate && customer.birthdate != '0000-00-00' ? new Date(customer.birthdate).toLocaleDateString() : 'N/A') + '</td></tr>';
				content += '<tr><td><strong>Age:</strong></td><td>' + (customer.age ? customer.age + ' years' : 'N/A') + '</td></tr>';
				content += '<tr><td><strong>Status:</strong></td><td>';
				
				if(customer.status == '1' || customer.status == 1) {
					content += '<span class="badge badge-success">Active</span>';
				} else {
					content += '<span class="badge badge-secondary">Inactive</span>';
				}
				content += '</td></tr>';
				content += '<tr><td><strong>Registered:</strong></td><td>' + new Date(customer.date_created).toLocaleString() + '</td></tr>';
				content += '<tr><td><strong>Last Updated:</strong></td><td>' + new Date(customer.date_updated).toLocaleString() + '</td></tr>';
				content += '</table>';
				content += '</div>';
				
				
				content += '<div class="col-md-6">';
				content += '<h5><i class="fa fa-link"></i> Associated Records</h5>';
				
				if(associations.has_associations) {
					content += '<div class="alert alert-warning">';
					content += '<strong>⚠️ Cannot Delete</strong><br>';
					content += 'This customer has associated records in:<br>';
					content += '<ul>';
					for(var i = 0; i < associations.association_types.length; i++) {
						content += '<li>' + associations.association_types[i] + '</li>';
					}
					content += '</ul>';
					content += 'Remove these records first or use deactivate option.';
					content += '</div>';
				} else {
					content += '<div class="alert alert-success">';
					content += '<strong>✅ Safe to Delete</strong><br>';
					content += 'No associated records found. Customer can be safely deleted if needed.';
					content += '</div>';
				}
				
				content += '</div>';
				content += '</div>';
				
				
				if(customer.avatar) {
					content += '<div class="row mt-3">';
					content += '<div class="col-12 text-center">';
					content += '<h5><i class="fa fa-image"></i> Profile Picture</h5>';
					content += '<img src="' + _base_url_ + customer.avatar + '" alt="Customer Avatar" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">';
					content += '</div>';
					content += '</div>';
				}
				
				$('#customer-details-content').html(content);
			} else {
				$('#customer-details-content').html('<div class="alert alert-danger">Failed to load customer details.</div>');
			}
		},
		error: function(err){
			console.log(err);
			$('#customer-details-content').html('<div class="alert alert-danger">An error occurred while loading customer details.</div>');
		}
	});
}

</script>