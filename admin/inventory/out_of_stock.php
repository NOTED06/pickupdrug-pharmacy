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
</style>
<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">Out of Stock Items</h3>
	</div>
	<div class="card-body">
        <div class="container-fluid">
			<div class="table-responsive">
			<table class="table table-hover table-striped table-bordered" id="list">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="10%">
					<col width="20%">
					<col width="20%">
					<col width="8%">
					<col width="12%">
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
					$i = 1;
						
						$qry = $conn->query("SELECT *, (COALESCE((SELECT SUM(quantity) FROM `stock_list` where product_id = product_list.id and (expiration IS NULL or date(expiration) > '".date("Y-m-d")."') ), 0) - COALESCE((SELECT SUM(quantity) FROM `order_items` where product_id = product_list.id), 0)) as `available` from `product_list` where delete_flag = 0  and (COALESCE((SELECT SUM(quantity) FROM `stock_list` where product_id = product_list.id and (expiration IS NULL or date(expiration) > '".date("Y-m-d")."') ), 0) - COALESCE((SELECT SUM(quantity) FROM `order_items` where product_id = product_list.id), 0)) <= 0 order by `brand` asc, `name` asc ");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
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
							<td class="text-right"><?= format_num($row['available'],0) ?></td>
							<td align="center">
								<button type="button" class="btn btn-flat p-1 btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
									Action
									<span class="sr-only">Toggle Dropdown</span>
								</button>
								<div class="dropdown-menu" role="menu">
									<a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
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
			_conf("Are you sure to delete this product permanently?","delete_product",[$(this).attr('data-id')])
		})
		$('.view_data').click(function(){
			uni_modal("Product Details","products/view_product.php?id="+$(this).attr('data-id'),'mid-large')
		})
		$('.edit_data').click(function(){
			uni_modal("Update Product Details","products/manage_product.php?id="+$(this).attr('data-id'),'mid-large')
		})
		$('.table').dataTable({
			columnDefs: [
					{ orderable: false, targets: [2,7] }
			],
			order:[0,'asc']
		});
		$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
	})
	function delete_product($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_product",
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
				if(typeof resp== 'object' && resp.status == 'success'){
					alert_toast(resp.msg || 'Product moved to Deleted Items.','success');
					setTimeout(function(){ location.href = './?page=maintenance'; }, 800);
				}else{
					alert_toast((resp && (resp.msg || resp.error)) || "An error occurred.", 'error');
					end_loader();
				}
			}
		});
	}
</script>