<?php 
if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM users where id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result();
    if($user->num_rows > 0){
        foreach($user->fetch_array() as $k =>$v){
            $meta[$k] = $v;
        }
    }
    $stmt->close();
}
?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline rounded-0 card-danger">
	<div class="card-body">
		<div class="container-fluid">
			<div id="msg"></div>
			<form action="" id="manage-user">
				<?php
				require_once(__DIR__ . '/../../app/helpers/SecurityHelper.php');
				echo SecurityHelper::csrfField();
				?>	
				<input type="hidden" name="id" value="<?= isset($meta['id']) ? $meta['id'] : '' ?>">
				<div class="form-group">
					<label for="name">First Name</label>
					<input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" required>
				</div>
				<div class="form-group">
					<label for="name">Middle Name</label>
					<input type="text" name="middlename" id="middlename" class="form-control" value="<?php echo isset($meta['middlename']) ? $meta['middlename']: '' ?>">
				</div>
				<div class="form-group">
					<label for="name">Last Name</label>
					<input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" required>
				</div>
				<div class="form-group">
					<label for="username">Username</label>
					<input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" required  autocomplete="off">
				</div>
				<div class="form-group">
					<label for="password"><?= isset($meta['id']) ? "New" : "" ?> Password</label>
					<input type="password" name="password" id="password" class="form-control" value="" autocomplete="off">
                    <?php if(isset($meta['id'])): ?>
					<small><i>Leave this blank if you dont want to change the password.</i></small>
                    <?php endif; ?>
				</div>
                <div class="form-group">
                    <label for="type" class="control-label">Type</label>
                    <select name="type" id="type" class="form-control form-control-sm rounded-0" required>
                    <option value="1" <?php echo isset($meta['type']) && $meta['type'] == 1 ? 'selected' : '' ?>>Administrator</option>
                    <option value="2" <?php echo isset($meta['type']) && $meta['type'] == 2 ? 'selected' : '' ?>>Inventory Staff</option>
					<option value="3" <?php echo isset($meta['type']) && $meta['type'] == 3 ? 'selected' : '' ?>>Customer Management</option>
					<option value="4" <?php echo isset($meta['type']) && $meta['type'] == 4 ? 'selected' : '' ?>>Biling Staff</option>
					<option value="5" <?php echo isset($meta['type']) && $meta['type'] == 5 ? 'selected' : '' ?>>Order Management</option>
                    </select>
                </div>
				<div class="form-group">
					<label for="" class="control-label">Avatar</label>
					<div class="custom-file">
		              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))" accept="image/png, image/jpeg, image/jpg, image/gif">
		              <label class="custom-file-label" for="customFile">Choose file</label>
		            </div>
		            <small class="text-muted">Accepted formats: JPG, PNG, GIF (Max: 5MB)</small>
				</div>
				<div class="form-group d-flex justify-content-center">
					<img src="<?php echo isset($meta['avatar']) && !empty($meta['avatar']) ? validate_image($meta['avatar']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMDAgNzBDODcuMjg3NiA3MCA3NyA4MC4yODc2IDc3IDkzQzc3IDEwNS43MTIgODcuMjg3NiAxMTYgMTAwIDExNkMxMTIuNzEyIDExNiAxMjMgMTA1LjcxMiAxMjMgOTNDMTIzIDgwLjI4NzYgMTEyLjcxMiA3MCAxMDAgNzBaIiBmaWxsPSIjOUM5Qzk3Ii8+CjxwYXRoIGQ9Ik02NiAxNTVDNjYgMTM2IDE4MS43NzggMTM2IDEzNCAxNTUiIHN0cm9rZT0iIzlDOUM5NyIgc3Ryb2tlLXdpZHRoPSI0IiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KPC9zdmc+' ?>" alt="Avatar" id="cimg" class="img-fluid img-thumbnail">
				</div>
			</form>
		</div>
	</div>
	<div class="card-footer">
			<div class="col-md-12">
				<div class="row">
					<button class="btn btn-sm btn-primary rounded-0 mr-3" form="manage-user">Save User Details</button>
					<a href="./?page=user/list" class="btn btn-sm btn-default border rounded-0" form="manage-user"><i class="fa fa-angle-left"></i> Cancel</a>
				</div>
			</div>
		</div>
</div>
<style>
	img#cimg{
		height: 15vh;
		width: 15vh;
		object-fit: cover;
		border-radius: 100% 100%;
	}
</style>
<script>
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }
	        reader.readAsDataURL(input.files[0]);
	    } else {
			$('#cimg').attr('src', '<?php echo isset($meta["avatar"]) && !empty($meta["avatar"]) ? validate_image($meta["avatar"]) : "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMDAgNzBDODcuMjg3NiA3MCA3NyA4MC4yODc2IDc3IDkzQzc3IDEwNS43MTIgODcuMjg3NiAxMTYgMTAwIDExNkMxMTIuNzEyIDExNiAxMjMgMTA1LjcxMiAxMjMgOTNDMTIzIDgwLjI4NzYgMTEyLjcxMiA3MCAxMDAgNzBaIiBmaWxsPSIjOUM5Qzk3Ii8+CjxwYXRoIGQ9Ik02NiAxNTVDNjYgMTM2IDE4MS43NzggMTM2IDEzNCAxNTUiIHN0cm9rZT0iIzlDOUM5NyIgc3Ryb2tlLXdpZHRoPSI0IiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KPC9zdmc+"; ?>');
		}
	}
	$('#manage-user').submit(function(e){
		e.preventDefault();
		start_loader()
		var formData = new FormData($(this)[0]);
		formData.append('csrf_token', _csrf_token_);
		$.ajax({
			url:_base_url_+'classes/Users.php?f=save',
			data: formData,
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success:function(resp){
				console.log("Server response:", resp); 
				
				if(resp == 1){
					location.href='./?page=user/list'
				} else if(resp == 2) {
					$('#msg').html('<div class="alert alert-danger">Username already exists</div>')
					end_loader()
				} else {
					
					if(typeof resp === 'string' && resp.indexOf('Error:') !== -1) {
						$('#msg').html('<div class="alert alert-danger">' + resp + '</div>')
					} else {
						try {
							var response = JSON.parse(resp);
							if(response.status == 'failed') {
								$('#msg').html('<div class="alert alert-danger">' + response.msg + '</div>')
							} else {
								location.href='./?page=user/list'
							}
						} catch(e) {
							
							$('#msg').html('<div class="alert alert-danger">Error: ' + resp + '</div>')
						}
					}
					end_loader()
				}
			},
			error: function(){
				$('#msg').html('<div class="alert alert-danger">Network error occurred</div>')
				end_loader()
			}
		})
	})

</script>