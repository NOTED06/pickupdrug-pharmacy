<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * from `form_list` where id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $qry = $stmt->get_result();
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
    $stmt->close();
}
?>
<div class="content py-5 px-3 bg-gradient-danger">
	<h2><b><?= isset($id) ? "Update Form Details" : "New Form Entry" ?></b></h2>
</div>
<div class="row mt-lg-n4 mt-md-n4 justify-content-center">
	<div class="col-lg-10 col-md-10 col-sm-12 col-xs-12">
		<div class="card rounded-0">
			<div class="card-body">
				<div class="container-fluid">
					<form action="" id="form-form">
						<?php
						require_once('../../app/helpers/SecurityHelper.php');
						echo SecurityHelper::csrfField();
						?>
						<input type="hidden" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
						
						<div class="row">
							<div class="form-group col-lg-8 col-md-8 col-sm-12 col-xs-12">
								<label for="form_name" class="control-label">Form Name</label>
								<input type="text" name="form_name" id="form_name" class="form-control form-control-sm rounded-0" value="<?php echo isset($form_name) ? $form_name : ''; ?>" required/>
							</div>
							<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
								<label for="status" class="control-label">Status</label>
								<select name="status" id="status" class="form-control form-control-sm rounded-0" required="required">
									<option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>Active</option>
									<option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>Inactive</option>
								</select>
							</div>
						</div>
						
						<div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
							<label for="description" class="control-label">Description</label>
							<textarea rows="3" name="description" id="description" class="form-control form-control-sm rounded-0" required><?php echo isset($description) ? $description : ''; ?></textarea>
						</div>
						
						<div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<label for="category_id" class="control-label">Category</label>
							<select name="category_id" id="category_id" class="form-control form-control-sm rounded-0" required="required">
								<option value="" disabled <?= !isset($category_id) ? 'selected' : '' ?>>Select Category</option>
								<?php 
								$categories = $conn->query("SELECT * FROM category_list where delete_flag = 0 and `status` = 1 ".(isset($category_id) && $category_id > 0 ? " or id = '{$category_id}' " : '')." order by `name` asc");
								while($row = $categories->fetch_assoc()):
								?>
								<option value="<?= $row['id'] ?>" <?= isset($category_id) && $row['id'] == $category_id ? 'selected' : '' ?>><?= $row['name'] ?></option>
								<?php endwhile; ?>
							</select>
						</div>
						
						
					</form>
				</div>
			</div>
			<div class="card-footer py-1 text-center">
				<button class="btn btn-primary btn-sm bg-gradient-primary btn-flat" form="form-form"><i class="fa fa-save"></i> Save</button>
				<a class="btn btn-light btn-sm bg-gradient-light border btn-flat" href="./?page=forms"><i class="fa fa-times"></i> Cancel</a>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="previewModalLabel">Form Preview</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="form-preview-container">
					
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
		$('#form-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			$('.err-msg').remove();
			
			
			var formName = $('#form_name').val().trim();
			var description = $('#description').val().trim();
			var categoryId = $('#category_id').val();
			
			if(!formName) {
				alert_toast("Form name is required", 'warning');
				return;
			}
			
			if(!description) {
				alert_toast("Description is required", 'warning');
				return;
			}
			
			if(!categoryId) {
				alert_toast("Please select a category", 'warning');
				return;
			}
		}	
	}	
</script>