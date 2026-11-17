<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM `product_list` WHERE id = ?");
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
<style>
	#cimg{
		max-width:100%;
		max-height:15em;
		object-fit:scale-down;
		object-position:center center;
	}
	.form-selection-container {
		max-height: 120px;
		overflow-y: auto;
		border: 1px solid #dee2e6;
		border-radius: 4px;
		padding: 8px;
		background: #f8f9fa;
	}
	.form-radio-item {
		display: flex;
		align-items: center;
		margin-bottom: 4px;
	}
	.form-radio-item label {
		font-weight: normal;
		margin-bottom: 0;
		cursor: pointer;
		width: 100%;
		padding: 6px 10px;
		border-radius: 4px;
		transition: background-color 0.2s;
		font-size: 0.9em;
	}
	.form-radio-item label:hover {
		background-color: #e9ecef;
	}
	.form-radio-item input[type="radio"]:checked + label {
		background-color: #d4edda;
		border: 1px solid #c3e6cb;
	}
	.no-forms-message {
		color: #6c757d;
		font-style: italic;
		text-align: center;
		padding: 10px;
		font-size: 0.9em;
	}
	.image-upload-note {
		font-size: 0.8em;
		color: #6c757d;
		margin-top: 3px;
	}
	.control-label {
		font-size: 0.9em;
		margin-bottom: 2px;
	}
	.form-group {
		margin-bottom: 0.5rem;
	}
</style>
<div class="container-fluid">
	<form action="" id="product-form" enctype="multipart/form-data">
		<?php
		require_once('../../app/helpers/SecurityHelper.php');
		echo SecurityHelper::csrfField();
		?>
		<input type="hidden" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
		
		<div class="row">
			<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<label for="brand" class="control-label">Brand</label>
				<input type="text" name="brand" id="brand" class="form-control form-control-sm rounded-0" value="<?php echo isset($brand) ? $brand : ''; ?>"  required/>
			</div>
			<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<label for="name" class="control-label">Name</label>
				<input type="text" name="name" id="name" class="form-control form-control-sm rounded-0" value="<?php echo isset($name) ? $name : ''; ?>"  required/>
			</div>
			<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<label for="dose" class="control-label">Dose</label>
				<input type="text" name="dose" id="dose" class="form-control form-control-sm rounded-0" value="<?php echo isset($dose) ? $dose : ''; ?>"  required/>
			</div>
		</div>

		<div class="row">
			<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
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
			<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<label for="price" class="control-label">Price</label>
				<input type="number" step="any" name="price" id="price" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($price) ? $price : ''; ?>"  required/>
			</div>
			<div class="form-group col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<label for="unit" class="control-label">Unit</label>
				<select name="unit" id="unit" class="form-control form-control-sm rounded-0" required="required">
					<option value="Pc" <?= isset($unit) && $unit == 'Pc' ? 'selected' : '' ?>>Pc</option>
					<option value="Bottle" <?= isset($unit) && $unit == 'Bottle' ? 'selected' : '' ?>>Bottle</option>
					<option value="Pad" <?= isset($unit) && $unit == 'Pad' ? 'selected' : '' ?>>Pad</option>
					<option value="Box" <?= isset($unit) && $unit == 'Box' ? 'selected' : '' ?>>Box</option>
					<option value="Pack" <?= isset($unit) && $unit == 'Pack' ? 'selected' : '' ?>>Pack</option>
					<option value="Dozen" <?= isset($unit) && $unit == 'Dozen' ? 'selected' : '' ?>>Dozen</option>
					<option value="Sachet" <?= isset($unit) && $unit == 'Sachet' ? 'selected' : '' ?>>Sachet</option>
					<option value="Can" <?= isset($unit) && $unit == 'Can' ? 'selected' : '' ?>>Can</option>
					<option value="Bar" <?= isset($unit) && $unit == 'Bar' ? 'selected' : '' ?>>Bar</option>
				</select>
			</div>
		</div>

		
		<div class="row">
			<div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<label class="control-label">Prescription Required</label>
				<div class="d-flex mt-2">
					<div class="custom-control custom-radio mr-4">
						<input class="custom-control-input" type="radio" id="rx_no" name="is_prescription" value="0" <?= !isset($is_prescription) || $is_prescription == 0 ? 'checked' : '' ?>>
						<label class="custom-control-label" for="rx_no">
							<i class="fas fa-store text-success mr-1"></i> OTC (Over-the-Counter)
						</label>
					</div>
					<div class="custom-control custom-radio">
						<input class="custom-control-input" type="radio" id="rx_yes" name="is_prescription" value="1" <?= isset($is_prescription) && $is_prescription == 1 ? 'checked' : '' ?>>
						<label class="custom-control-label" for="rx_yes">
							<i class="fas fa-prescription text-danger mr-1"></i> Rx Required
						</label>
					</div>
				</div>
			</div>
			<div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<label class="control-label">FDA Status</label>
				<div class="d-flex mt-2">
					<div class="custom-control custom-radio mr-4">
						<input class="custom-control-input" type="radio" id="fda_yes" name="fda_approved" value="1" <?= !isset($fda_approved) || $fda_approved == 1 ? 'checked' : '' ?>>
						<label class="custom-control-label" for="fda_yes">
							<i class="fas fa-check-circle text-success mr-1"></i> FDA Approved
						</label>
					</div>
					<div class="custom-control custom-radio">
						<input class="custom-control-input" type="radio" id="fda_no" name="fda_approved" value="0" <?= isset($fda_approved) && $fda_approved == 0 ? 'checked' : '' ?>>
						<label class="custom-control-label" for="fda_no">
							<i class="fas fa-times-circle text-warning mr-1"></i> Not FDA Approved
						</label>
					</div>
				</div>
			</div>
		</div>

		
		<div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" id="forms-section" style="display: none;">
			<label class="control-label">Available Forms (Optional)</label>
			<div class="form-selection-container" id="forms-container">
				<div class="no-forms-message">Please select a category first to view available forms.</div>
			</div>
		</div>

		<div class="row">
			<div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<label for="description" class="control-label">Description</label>
				<textarea rows="2" name="description" id="description" class="form-control form-control-sm rounded-0" required><?php echo isset($description) ? $description : ''; ?></textarea>
			</div>
		</div>
		
		<div class="row">
			<div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<label for="customFile1" class="control-label">Product Image</label>
				<div class="custom-file custom-file-sm">
					<input type="file" class="custom-file-input rounded-0" id="customFile1" name="img" onchange="displayImg(this)" accept="image/*">
					<label class="custom-file-label" for="customFile1">Choose file</label>
				</div>
				<div class="image-upload-note">
					Supported formats: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)
				</div>
			</div>
			<div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<label class="control-label">Preview</label><br>
				<img src="<?php echo validate_image(isset($image_path) ? $image_path : '') ?>" alt="Product Image" id="cimg" class="img-fluid img-thumbnail">
			</div>
		</div>
	</form>
</div>
<script>
function displayImg(input) {
    if (input.files && input.files[0]) {
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            input.value = '';
            $(input).siblings('.custom-file-label').html('Choose file');
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            $('#cimg').attr('src', e.target.result);
            $(input).siblings('.custom-file-label').html(input.files[0].name);
        }

        reader.readAsDataURL(input.files[0]);
    } else {
        $('#cimg').attr('src', "<?php echo validate_image(isset($image_path) ? $image_path : '') ?>");
        $(input).siblings('.custom-file-label').html('Choose file');
    }
}

function loadFormsByCategory(categoryId) {
    if(!categoryId) {
        $('#forms-section').hide();
        $('#forms-container').html('<div class="no-forms-message">Please select a category first to view available forms.</div>');
        return;
    }

    $('#forms-container').html('<div class="no-forms-message"><i class="fa fa-spinner fa-spin"></i> Loading forms...</div>');
    $('#forms-section').show();

    $.ajax({
        url: _base_url_ + "classes/Master.php?f=get_forms_by_category",
        method: "POST",
        data: {category_id: categoryId},
        dataType: "json",
        error: function(err) {
            console.log("Error loading forms:", err);
            $('#forms-container').html('<div class="no-forms-message text-danger">Error loading forms. Please try again.</div>');
        },
        success: function(resp) {
            console.log("Forms response:", resp);
            if(resp.status === 'success' && resp.data) {
                let formsHtml = '<div class="form-selection-note mb-3"><small class="text-info"><i class="fa fa-info-circle"></i> Select a form for this product:</small></div>';
                
                
                formsHtml += `
                    <div class="form-radio-item">
                        <input type="radio" id="form_none" name="form_id" value="" class="mr-2" checked>
                        <label for="form_none">
                            <strong>None</strong>
                            <br><small class="text-muted">No specific form</small>
                        </label>
                    </div>
                `;
                
                if(resp.data.length > 0) {
                    resp.data.forEach(function(form) {
                        formsHtml += `
                            <div class="form-radio-item">
                                <input type="radio" id="form_${form.id}" name="form_id" value="${form.id}" class="mr-2">
                                <label for="form_${form.id}">
                                    <strong>${form.name}</strong>
                                    ${form.description ? '<br><small class="text-muted">' + form.description + '</small>' : ''}
                                </label>
                            </div>
                        `;
                    });
                }
                
                $('#forms-container').html(formsHtml);
                
                if(!$('input[name="form_id"]:checked').length) {
                    $('#form_none').prop('checked', true);
                }
            } else {
                console.log("No forms found or error:", resp);
                $('#forms-container').html('<div class="no-forms-message">No forms available for this category.</div><input type="hidden" name="form_id" value="">');
            }
        }
    });
}

function loadProductForm(productId) {
    if(!productId) return;

    console.log("Loading product form for product ID:", productId);

    $.ajax({
        url: _base_url_ + "classes/Master.php?f=get_product_forms",
        method: "POST",
        data: {product_id: productId},
        dataType: "json",
        error: function(err) {
            console.log("Error loading product form:", err);
        },
        success: function(resp) {
            console.log("Product form response:", resp);
            if(resp.status === 'success' && resp.data && resp.data.length > 0) {
                const formId = resp.data[0].id;
                $(`input[name="form_id"][value="${formId}"]`).prop('checked', true);
            } else {
                $('#form_none').prop('checked', true);
            }
        }
    });
}

$(document).ready(function(){
    
    $('#category_id').on('change', function() {
        const categoryId = $(this).val();
        console.log("Category changed to:", categoryId);
        loadFormsByCategory(categoryId);
    });

    
    <?php if(isset($category_id) && $category_id > 0): ?>
    console.log("Loading forms for category:", <?= $category_id ?>);
    loadFormsByCategory(<?= $category_id ?>);
    
    <?php if(isset($id) && $id > 0): ?>
    setTimeout(function() {
        console.log("Loading product form for product:", <?= $id ?>);
        loadProductForm(<?= $id ?>);
    }, 1000);
    <?php endif; ?>
    <?php endif; ?>

    
    $('#product-form').submit(function(e){
        e.preventDefault();
        var _this = $(this);
        $('.err-msg').remove();
        start_loader();

        console.log("Form submitted");

        var formData = new FormData(this);
        
        
        var selectedFormId = $('input[name="form_id"]:checked').val();
        console.log("Selected form ID:", selectedFormId);
        
        if(selectedFormId === undefined || selectedFormId === null || selectedFormId === '0') {
            selectedFormId = '';
        }
        formData.set('form_id', selectedFormId);

        
        console.log("FormData contents:");
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }

        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_product",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            dataType: 'json',
            error: function(err){
                console.log("AJAX Error:", err);
                alert_toast("An error occurred", 'error');
                end_loader();
            },
            success: function(resp){
                console.log("Response:", resp);
                if(typeof resp == 'object' && resp.status == 'success'){
                    alert_toast(resp.msg, 'success');
                    setTimeout(function(){
                        location.reload();
                    }, 2000);
                } else if(resp.status == 'failed' && !!resp.msg){
                    var el = $('<div>');
                    el.addClass("alert alert-danger err-msg").text(resp.msg);
                    _this.prepend(el);
                    el.show('slow');
                    $("html, body").scrollTop(0);
                    end_loader();
                } else {
                    console.log("Unexpected response:", resp);
                    alert_toast("An error occurred", 'error');
                    end_loader();
                }
            }
        });
    });
});
</script>