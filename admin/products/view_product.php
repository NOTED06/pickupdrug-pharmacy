<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    
    $stmt = $conn->prepare("SELECT p.*, c.name as `category` FROM `product_list` p INNER JOIN `category_list` c ON p.category_id = c.id WHERE p.id = ? AND p.delete_flag = 0");
    $stmt->bind_param('i', $_GET['id']);
    $stmt->execute();
    $qry = $stmt->get_result();
    
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }

    
    $form_data = null;
    if(!empty($form_id)) {
        $form_stmt = $conn->prepare("SELECT * FROM `form_list` WHERE id = ? AND delete_flag = 0");
        $form_stmt->bind_param('i', $form_id);
        $form_stmt->execute();
        $form_query = $form_stmt->get_result();
        
        if($form_query && $form_query->num_rows > 0) {
            $form_data = $form_query->fetch_assoc();
        }
    }
    
    
    $branches = array();
    if(!empty($id)) {
        $branch_stmt = $conn->prepare("
            SELECT b.* 
            FROM `product_branches` pb
            INNER JOIN `branch_list` b ON pb.branch_id = b.id
            WHERE pb.product_id = ? 
            AND b.status = 1
            ORDER BY b.branch_name ASC
        ");
        $branch_stmt->bind_param('i', $id);
        $branch_stmt->execute();
        $branch_query = $branch_stmt->get_result();
        
        if($branch_query) {
            while($branch = $branch_query->fetch_assoc()) {
                $branches[] = $branch;
            }
        }
    }
}
?>
<style>
	#product-img{
		max-width:100%;
		max-height:35em;
		object-fit:scale-down;
		object-position:center center;
	}
	.form-badge {
		display: inline-block;
		margin: 2px;
		padding: 8px 15px;
		background-color: #17a2b8;
		color: white;
		border-radius: 20px;
		font-size: 14px;
		font-weight: 500;
	}
	.branch-badge {
		display: inline-block;
		margin: 4px;
		padding: 8px 15px;
		background-color: #28a745;
		color: white;
		border-radius: 20px;
		font-size: 14px;
		font-weight: 500;
	}
	.branch-badge-all {
		display: inline-block;
		margin: 4px;
		padding: 8px 15px;
		background-color: #17a2b8;
		color: white;
		border-radius: 20px;
		font-size: 14px;
		font-weight: 500;
	}
	.no-form, .no-branch {
		color: #6c757d;
		font-style: italic;
		font-size: 14px;
	}
	.branches-container {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
		margin-top: 8px;
	}
	.branch-detail {
		background: #f8f9fa;
		border: 1px solid #dee2e6;
		border-radius: 8px;
		padding: 10px 15px;
		margin-bottom: 8px;
	}
	.branch-detail strong {
		display: block;
		color: #333;
	}
	.branch-detail small {
		color: #666;
	}
</style>
<div class="container-fluid">
	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label text-muted">Brand</label>
				<div class="pl-4"><?= isset($brand) ? $brand : '' ?></div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label text-muted">Name</label>
				<div class="pl-4"><?= isset($name) ? $name : '' ?></div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label text-muted">Dose</label>
				<div class="pl-4"><?= isset($dose) ? $dose : '' ?></div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label text-muted">Category</label>
				<div class="pl-4"><?= isset($category) ? $category : '' ?></div>
			</div>
		</div>
	</div>
	
	
	<div class="row">
		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label text-muted">Form</label>
				<div class="pl-4">
					<?php if(!empty($form_data)): ?>
						<span class="form-badge">
							<?= htmlspecialchars($form_data['name']) ?>
						</span>
						<?php if(!empty($form_data['description'])): ?>
							<div class="mt-1 text-muted small">
								<?= htmlspecialchars($form_data['description']) ?>
							</div>
						<?php endif; ?>
					<?php else: ?>
						<span class="no-form">No form selected</span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	
	
	<div class="row">
		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label text-muted">Available at Branches</label>
				<div class="pl-4">
					<?php if(empty($branches)): ?>
						<span class="branch-badge-all">
							<i class="fa fa-globe"></i> All Branches
						</span>
						<div class="mt-1 text-muted small">This product is available at all store locations.</div>
					<?php else: ?>
						<div class="branches-container">
							<?php foreach($branches as $branch): ?>
								<div class="branch-detail w-100">
									<strong><i class="fa fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($branch['branch_name']) ?></strong>
									<small><?= htmlspecialchars($branch['branch_address']) ?></small>
									<?php if(!empty($branch['branch_phone'])): ?>
										<br><small><i class="fa fa-phone"></i> <?= htmlspecialchars($branch['branch_phone']) ?></small>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label text-muted">Description</label>
				<div class="pl-4"><?= isset($description) ? str_replace(["\n\r", "\n", "\r"],"<br>", $description) : '' ?></div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label text-muted">Price</label>
				<div class="pl-4"><?= isset($price) ? format_num($price, 2) : '' ?></div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label text-muted">Unit</label>
				<div class="pl-4"><?= isset($unit) ? $unit : '' ?></div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label text-muted">Image</label>
				<div class="pl-4">
					<img src="<?= validate_image(isset($image_path) ? $image_path : '') ?>" alt="Product Image" id="product-img" class="img-fluid img-thumbnail bg-gradient-dark border-dark">
				</div>
			</div>
		</div>
	</div>
</div>
<div class="text-right">
    <button class="btn btn-primary btn-sm bg-gradient-primary rounded-0" type="button" id="edit_data"><i class="fa fa-edit"></i> Edit</button>
    <button class="btn btn-secondary btn-sm bg-gradient-secondary rounded-0" type="button" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
</div>
<script>
    $(function(){
        $('#edit_data').click(function(){
            uni_modal("Update Product Details","products/manage_product.php?id=<?= isset($id) ? $id : '' ?>",'mid-large')
        })
    })
</script>
<script>
    $(function(){
		$('#delete_data').click(function(){
			_conf("Are you sure to delete this product permanently?","delete_product", ["<?= isset($id) ? $id :'' ?>"])
		})
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