<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<?php

$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 50; 
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$search_condition = "WHERE p.delete_flag = 0";
if(!empty($search)) {
    $search_condition .= " AND (p.name LIKE '%{$search}%' OR p.brand LIKE '%{$search}%' OR p.description LIKE '%{$search}%')";
}

$count_result = $conn->query("SELECT COUNT(*) as total FROM product_list p {$search_condition}");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
?>

<style>
	.product-img{
		width:3em;
		height:3em;
		object-fit:cover;
		object-position:center center;
	}
	.form-badge {
		display: inline-block;
		margin: 1px;
		padding: 3px 8px;
		background-color: #17a2b8;
		color: white;
		border-radius: 12px;
		font-size: 11px;
		font-weight: 500;
	}
	.forms-cell {
		max-width: 120px;
		word-wrap: break-word;
	}
	.search-box { max-width: 300px; }
	.pagination-info { font-size: 0.9em; color: #6c757d; }
</style>
<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">List of Products <span class="badge badge-secondary" data-live-count><?= number_format($total_records) ?> total</span></h3>
		<div class="card-tools">
		    <div class="d-inline-block mr-2">
		        <div class="input-group input-group-sm search-box">
		            <div class="input-group-prepend">
		                <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
		            </div>
		            <input type="text" name="search" class="form-control border-left-0" placeholder="Type to search products..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
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
        <col width="5%">
        <col width="12%">
        <col width="10%">
        <col width="15%">
        <col width="15%">
        <col width="10%">
        <col width="10%">
        <col width="10%">
        <col width="13%">
    </colgroup>
    <thead>
        <tr>
            <th>#</th>
            <th>Date Created</th>
            <th>Image</th>
            <th>Brand</th>
            <th>Name</th>
            <th>Form</th>
            <th>Price</th>
            <th>Unit</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $i = $offset + 1;
        
        $qry = $conn->query("
            SELECT p.*, 
                   f.name as form_name
            FROM `product_list` p 
            LEFT JOIN `form_list` f ON p.form_id = f.id
            {$search_condition}
            ORDER BY p.date_created DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
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
                <td class="forms-cell">
                    <?php if(!empty($row['form_name'])): ?>
                        <span class="form-badge"><?= htmlspecialchars($row['form_name']) ?></span>
                    <?php else: ?>
                        <small class="text-muted">No form</small>
                    <?php endif; ?>
                </td>
                <td class="text-right"><?= format_num($row['price'],2) ?></td>
                <td class="text-center">
                    <span class="badge badge-info px-2 rounded-pill"><?= $row['unit'] ?></span>
                </td>
                
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

            
            <?php if($total_pages > 1): ?>
            <nav aria-label="Product pagination" class="mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="pagination-info">
                        Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $limit, $total_records)) ?> of <?= number_format($total_records) ?> products
                    </div>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=products&p=1<?= !empty($search) ? '&search='.urlencode($search) : '' ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=products&p=<?= $page - 1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Prev</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for($p = $start_page; $p <= $end_page; $p++):
                        ?>
                        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=products&p=<?= $p ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=products&p=<?= $page + 1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=products&p=<?= $total_pages ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Last</a>
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
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this product permanently?","delete_product",[$(this).attr('data-id')])
		})
		$('#create_new').click(function(){
			uni_modal("Add New Product","products/manage_product.php",'mid-large')
		})
		$('.view_data').click(function(){
			uni_modal("Product Details","products/view_product.php?id="+$(this).attr('data-id'),'mid-large')
		})
		$('.edit_data').click(function(){
			uni_modal("Update Product Details","products/manage_product.php?id="+$(this).attr('data-id'),'mid-large')
		})
		
		
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
					if (xhr && xhr.responseText) {
						var r = JSON.parse(xhr.responseText);
						if (r && (r.msg || r.error)) msg = r.msg || r.error;
					}
				} catch(e) {}
				console.log("Delete product error:", xhr ? xhr.responseText : err);
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