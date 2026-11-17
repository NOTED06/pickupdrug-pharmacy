<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT f.*, c.name as category_name, c.id as category_id 
                         FROM `form_list` f 
                         LEFT JOIN `category_list` c ON f.category_id = c.id 
                         WHERE f.id = '{$_GET['id']}' AND f.delete_flag = 0 ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<div class="content py-5 px-3 bg-gradient-danger">
	<h2><b>Form Details</b></h2>
</div>
<div class="row mt-lg-n4 mt-md-n4 justify-content-center">
	<div class="col-lg-10 col-md-10 col-sm-12 col-xs-12">
		<div class="card rounded-0">
			<div class="card-body">
                <div class="container-fluid">
                    <dl>
                        <dt class="text-muted">Form Name</dt>
                        <dd class="pl-4"><?= isset($form_name) ? $form_name : "" ?></dd>
                        
                        <dt class="text-muted">Category</dt>
                        <dd class="pl-4">
                            <?php if(isset($category_name) && !empty($category_name)): ?>
                                <span class="badge badge-info px-3 rounded-pill"><?= $category_name ?></span>
                            <?php else: ?>
                                <span class="text-muted">No category assigned</span>
                            <?php endif; ?>
                        </dd>
                        
                        <dt class="text-muted">Description</dt>
                        <dd class="pl-4"><?= isset($description) ? str_replace(["\n\r", "\n", "\r"],"<br>", $description) : '' ?></dd>
                        
                        <dt class="text-muted">Status</dt>
                        <dd class="pl-4">
                            <?php if($status == 1): ?>
                                <span class="badge badge-success px-3 rounded-pill">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger px-3 rounded-pill">Inactive</span>
                            <?php endif; ?>
                        </dd>
                        
                        <dt class="text-muted">Date Created</dt>
                        <dd class="pl-4"><?= isset($date_created) ? date("M d, Y h:i A", strtotime($date_created)) : "" ?></dd>
                        
                        <?php if(isset($date_updated) && !empty($date_updated)): ?>
                        <dt class="text-muted">Last Updated</dt>
                        <dd class="pl-4"><?= date("M d, Y h:i A", strtotime($date_updated)) ?></dd>
                        <?php endif; ?>
                    </dl>
                    
                    <?php if(isset($form_fields) && !empty($form_fields)): ?>
                    <hr>
                    <div class="mt-3">
                        <h5 class="text-muted mb-3">Form Preview</h5>
                        <div class="card border-secondary">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><?= $form_name ?></h6>
                                <?php if(!empty($description)): ?>
                                <small class="text-muted"><?= $description ?></small>
                                <?php endif; ?>
                                <?php if(isset($category_name) && !empty($category_name)): ?>
                                <div class="mt-1">
                                    <small class="text-info">Category: <?= $category_name ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div id="form-preview-container">
                                    
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Form Statistics</h6>
                                    <div class="bg-light p-3 rounded">
                                        <div id="form-stats">
                                            
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Form Fields Configuration</h6>
                                    <div class="bg-light p-3 rounded">
                                        <pre id="json-display" style="background: transparent; border: none; font-size: 0.9em; max-height: 300px; overflow-y: auto;"><?= htmlspecialchars($form_fields) ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <hr>
                    <div class="mt-3">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> No form fields have been configured for this form yet. 
                            <a href="./?page=forms/manage_form&id=<?= isset($id) ? $id : '' ?>" class="alert-link">Click here to add fields</a>.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
			<div class="card-footer py-1 text-center">
				<button class="btn btn-danger btn-sm bg-gradient-danger rounded-0" type="button" id="delete_data"><i class="fa fa-trash"></i> Delete</button>
				<a class="btn btn-primary btn-sm bg-gradient-primary rounded-0" href="./?page=forms/manage_form&id=<?= isset($id) ? $id : '' ?>"><i class="fa fa-edit"></i> Edit</a>
				<a class="btn btn-light btn-sm bg-gradient-light border rounded-0" href="./?page=forms"><i class="fa fa-angle-left"></i> Back to List</a>
			</div>
		</div>
	</div>
</div>

<script>
    $(function(){
		$('#delete_data').click(function(){
			_conf("Are you sure to delete this form permanently?","delete_form", ["<?= isset($id) ? $id :'' ?>"])
		})
		
		
		generateFormPreview();
		
		
		formatJSONDisplay();
		
		
		generateFormStats();
    })
    
    function delete_form($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_form",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.replace("./?page=forms");
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
	
	function generateFormPreview() {
		var formFields = <?= json_encode($form_fields ?? '') ?>;
		
		if(!formFields) {
			$('#form-preview-container').html('<p class="text-muted">No form fields configured.</p>');
			return;
		}
		
		try {
			var fields = JSON.parse(formFields);
			var html = '<form>';
			
			fields.forEach(function(field, index) {
				html += '<div class="form-group">';
				html += '<label class="font-weight-semibold">' + field.label;
				if(field.required) html += ' <span class="text-danger">*</span>';
				html += '</label>';
				
				switch(field.type) {
					case 'text':
					case 'email':
					case 'tel':
					case 'number':
					case 'password':
						html += '<input type="' + field.type + '" class="form-control" placeholder="Enter ' + field.label.toLowerCase() + '" disabled>';
						break;
					case 'textarea':
						var rows = field.rows || 3;
						html += '<textarea class="form-control" rows="' + rows + '" placeholder="Enter ' + field.label.toLowerCase() + '" disabled></textarea>';
						break;
					case 'select':
						html += '<select class="form-control" disabled>';
						html += '<option value="">Choose ' + field.label + '</option>';
						if(field.options) {
							field.options.forEach(function(option) {
								html += '<option value="' + option + '">' + option + '</option>';
							});
						}
						html += '</select>';
						break;
					case 'radio':
						if(field.options) {
							field.options.forEach(function(option, optIndex) {
								html += '<div class="form-check">';
								html += '<input class="form-check-input" type="radio" name="preview_' + field.name + '" value="' + option + '" id="preview_' + field.name + '_' + optIndex + '" disabled>';
								html += '<label class="form-check-label" for="preview_' + field.name + '_' + optIndex + '">' + option + '</label>';
								html += '</div>';
							});
						}
						break;
					case 'checkbox':
						if(field.options) {
							field.options.forEach(function(option, optIndex) {
								html += '<div class="form-check">';
								html += '<input class="form-check-input" type="checkbox" name="preview_' + field.name + '[]" value="' + option + '" id="preview_' + field.name + '_' + optIndex + '" disabled>';
								html += '<label class="form-check-label" for="preview_' + field.name + '_' + optIndex + '">' + option + '</label>';
								html += '</div>';
							});
						} else {
							html += '<div class="form-check">';
							html += '<input class="form-check-input" type="checkbox" name="preview_' + field.name + '" id="preview_' + field.name + '" disabled>';
							html += '<label class="form-check-label" for="preview_' + field.name + '">' + field.label + '</label>';
							html += '</div>';
						}
						break;
					case 'date':
					case 'datetime-local':
					case 'time':
						html += '<input type="' + field.type + '" class="form-control" disabled>';
						break;
					case 'file':
						html += '<input type="file" class="form-control-file" disabled>';
						break;
					default:
						html += '<input type="text" class="form-control" placeholder="Enter ' + field.label.toLowerCase() + '" disabled>';
				}
				
				
				if(field.description) {
					html += '<small class="form-text text-muted">' + field.description + '</small>';
				}
				
				html += '</div>';
			});
			
			html += '<button type="button" class="btn btn-primary" disabled>Submit (Preview Mode)</button>';
			html += '</form>';
			
			$('#form-preview-container').html(html);
			
		} catch (e) {
			$('#form-preview-container').html('<div class="alert alert-warning">Error generating preview: Invalid form configuration</div>');
		}
	}
	
	function generateFormStats() {
		var formFields = <?= json_encode($form_fields ?? '') ?>;
		
		if(!formFields) {
			$('#form-stats').html('<p class="text-muted mb-0">No statistics available</p>');
			return;
		}
		
		try {
			var fields = JSON.parse(formFields);
			var stats = {
				total: fields.length,
				required: 0,
				optional: 0,
				types: {}
			};
			
			fields.forEach(function(field) {
				if(field.required) {
					stats.required++;
				} else {
					stats.optional++;
				}
				
				if(stats.types[field.type]) {
					stats.types[field.type]++;
				} else {
					stats.types[field.type] = 1;
				}
			});
			
			var html = '<div class="row">';
			html += '<div class="col-6"><strong>Total Fields:</strong><br><span class="text-primary">' + stats.total + '</span></div>';
			html += '<div class="col-6"><strong>Required:</strong><br><span class="text-danger">' + stats.required + '</span></div>';
			html += '</div>';
			html += '<div class="row mt-2">';
			html += '<div class="col-6"><strong>Optional:</strong><br><span class="text-success">' + stats.optional + '</span></div>';
			html += '<div class="col-6"><strong>Field Types:</strong><br>';
			
			var typeList = [];
			for(var type in stats.types) {
				typeList.push(type + ' (' + stats.types[type] + ')');
			}
			html += '<small>' + typeList.join(', ') + '</small>';
			html += '</div></div>';
			
			$('#form-stats').html(html);
			
		} catch (e) {
			$('#form-stats').html('<p class="text-muted mb-0">Error calculating statistics</p>');
		}
	}
	
	function formatJSONDisplay() {
		var jsonText = $('#json-display').text();
		if(jsonText) {
			try {
				var parsed = JSON.parse(jsonText);
				var formatted = JSON.stringify(parsed, null, 2);
				$('#json-display').text(formatted);
			} catch (e) {
				
			}
		}
	}
</script>