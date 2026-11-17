<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<style>
    .user-avatar{
        width:3rem;
        height:3rem;
        object-fit:scale-down;
        object-position:center center;
    }
</style>
<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">List of Users</h3>
		<div class="card-tools">
			<a href="./?page=user/manage_user" id="create_new" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
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
					<col width="25%">
					<col width="15%">
					<col width="10%">
					<col width="15%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Date Updated</th>
						<th>Avatar</th>
						<th>Name</th>
						<th>Username</th>
						<th>Type</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					
					$col_check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'delete_flag'");
					if(!$col_check || $col_check->num_rows == 0){
						@$conn->query("ALTER TABLE `users` ADD COLUMN `delete_flag` TINYINT(1) NOT NULL DEFAULT 0");
					}
					$qry = $conn->query("SELECT *, concat(firstname,' ', coalesce(concat(middlename,' '), '') , lastname) as `name` from `users` where id != '{$_settings->userdata('id')}' AND (COALESCE(delete_flag,0) = 0) order by concat(firstname,' ', lastname) asc ");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("Y-m-d H:i",strtotime($row['date_updated'])) ?></td>
							<td class="text-center">
                                <img src="<?= validate_image($row['avatar']) ?>" alt="" class="img-thumbnail rounded-circle user-avatar">
                            </td>
							<td><?php echo $row['name'] ?></td>
							<td><?php echo $row['username'] ?></td>
							<td class="text-center">
                                <?php if($row['type'] == 1): ?>
                                    Administrator
                                <?php elseif($row['type'] == 2): ?>
                                    Inventory Staff
									<?php elseif($row['type'] == 3): ?>
									Customer Management
									<?php elseif($row['type'] == 4): ?>
									Biling Staff
									<?php elseif($row['type'] == 5): ?>
									Order Management
                                <?php else: ?>
									N/A
                                <?php endif; ?>
                            </td>
							<td align="center">
								 <button type="button" class="btn btn-flat p-1 btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		Action
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
				                    <a class="dropdown-item" href="./?page=user/manage_user&id=<?= $row['id'] ?>"><span class="fa fa-edit text-dark"></span> Edit</a>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
				                  </div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this User permanently?","delete_user",[$(this).attr('data-id')])
		})
		$('.table').dataTable({
			columnDefs: [
					{ orderable: false, targets: [6] }
			],
			order:[0,'asc']
		});
		$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
	})
	function delete_user($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Users.php?f=delete",
			method:"POST",
			data:{id: $id, csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''},
			dataType:"json",
			error:function(xhr, status, err){
				var msg = "An error occurred.";
				try {
					if (xhr && xhr.responseText) { var r = JSON.parse(xhr.responseText); if (r && (r.msg || r.error)) msg = r.msg || r.error; }
				} catch(e) {}
				alert_toast(msg, 'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp === 'object' && resp.status === 'success'){
					alert_toast(resp.msg || 'User moved to Deleted Items.', 'success');
					setTimeout(function(){ location.href = './?page=maintenance'; }, 800);
				}else{
					alert_toast((resp && resp.msg) || "An error occurred.", 'error');
					end_loader();
				}
			}
		});
	}
</script>