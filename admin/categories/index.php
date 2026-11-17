<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<style>
	.category-logo{
		width:3em;
		height:3em;
		object-fit:cover;
		object-position:center center;
	}
	
	
	.nav-tabs .nav-link {
		border-radius: 0;
		border: 1px solid #dee2e6;
		border-bottom: 0;
		margin-right: 2px;
	}
	.nav-tabs .nav-link.active {
		background-color: #dc3545;
		color: white;
		border-color: #dc3545;
	}
	.nav-tabs .nav-link:hover:not(.active) {
		background-color: #f8f9fa;
		border-color: #dee2e6;
	}
	.tab-content {
		border: 1px solid #dee2e6;
		border-top: 0;
	}
	
	.modal-header.bg-primary {
		background-color: #007bff !important;
		color: white;
	}
	.modal-header.bg-success {
		background-color: #28a745 !important;
		color: white;
	}
	.modal-header.bg-info {
		background-color: #17a2b8 !important;
		color: white;
	}
	.modal-header.bg-danger {
		background-color: #dc3545 !important;
		color: white;
	}
	.form-group label {
		font-weight: 600;
	}
	.readonly-field {
		background-color: #f8f9fa;
		border: 1px solid #e9ecef;
	}
	.field-preview {
		background-color: #f8f9fa;
		border: 1px solid #dee2e6;
		border-radius: 4px;
		padding: 10px;
		margin: 5px 0;
		max-height: 200px;
		overflow-y: auto;
	}
</style>

<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">Categories Management</h3>
	</div>
	<div class="card-body">
		
		<ul class="nav nav-tabs" id="categoryTabs" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" id="categories-tab" data-toggle="tab" data-target="#categories" type="button" role="tab" aria-controls="categories" aria-selected="true">
					<i class="fas fa-list"></i> Categories List
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="forms-tab" data-toggle="tab" data-target="#forms" type="button" role="tab" aria-controls="forms" aria-selected="false">
					<i class="fas fa-wpforms"></i> Forms Management
				</button>
			</li>
		</ul>

		
		<div class="tab-content" id="categoryTabContent">
			
			<div class="tab-pane fade show active" id="categories" role="tabpanel" aria-labelledby="categories-tab">
				<div class="p-3">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="mb-0">List of Categories</h5>
						<div class="d-flex align-items-center">
						    <div class="input-group input-group-sm mr-2" style="width: 200px;">
						        <div class="input-group-prepend">
						            <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
						        </div>
						        <input type="text" name="search" class="form-control border-left-0" placeholder="Search categories..." autocomplete="off">
						    </div>
						    <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-primary">
							    <span class="fas fa-plus"></span> Create New Category
						    </a>
						</div>
					</div>
					
					<div class="container-fluid">
						<div class="table-responsive">
						<table class="table table-hover table-striped table-bordered" id="list">
							<colgroup>
								<col width="5%">
								<col width="20%">
								<col width="20%">
								<col width="30%">
								<col width="15%">
								<col width="10%">
							</colgroup>
							<thead>
								<tr>
									<th>#</th>
									<th>Date Created</th>
									<th>Name</th>
									<th>Description</th>
									<th>Status</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php 
								$i = 1;
									$qry = $conn->query("SELECT * from `category_list` where delete_flag = 0 order by `name` asc ");
									while($row = $qry->fetch_assoc()):
								?>
									<tr>
										<td class="text-center"><?php echo $i++; ?></td>
										<td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
										<td class=""><?= $row['name'] ?></td>
										<td><p class="mb-0 truncate-1"><?php echo ($row['description']) ?></p></td>
										<td class="text-center">
											<?php if($row['status'] == 1): ?>
												<span class="badge badge-success px-3 rounded-pill">Active</span>
											<?php else: ?>
												<span class="badge badge-danger px-3 rounded-pill">Inactive</span>
											<?php endif; ?>
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
					</div>
				</div>
			</div>

			
			<div class="tab-pane fade" id="forms" role="tabpanel" aria-labelledby="forms-tab">
				<div class="p-3">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="mb-0">Forms Management</h5>
						<div>
							<button class="btn btn-flat btn-info btn-sm" id="refresh_forms_btn" title="Refresh Forms List">
								<span class="fas fa-sync-alt"></span>
							</button>
							<button class="btn btn-flat btn-success" id="create_form_btn">
								<span class="fas fa-plus"></span> Create New Form
							</button>
						</div>
					</div>
					
					
					<div class="row mb-3">
						<div class="col-md-4">
							<div class="form-group mb-0">
								<label for="category_filter" class="form-label">Filter by Category:</label>
								<select class="form-control" id="category_filter">
									<option value="">All Categories</option>
									<?php 
									$categories = $conn->query("SELECT * from `category_list` where delete_flag = 0 order by `name` asc ");
									while($cat = $categories->fetch_assoc()):
									?>
									<option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
									<?php endwhile; ?>
								</select>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group mb-0">
								<label for="status_filter" class="form-label">Filter by Status:</label>
								<select class="form-control" id="status_filter">
									<option value="">All Status</option>
									<option value="1">Active</option>
									<option value="0">Inactive</option>
								</select>
							</div>
						</div>
						<div class="col-md-4 d-flex align-items-end">
							<div class="form-group mb-0 w-100">
								<button class="btn btn-outline-secondary btn-block" id="clear_filters_btn">
									<span class="fas fa-times"></span> Clear Filters
								</button>
							</div>
						</div>
					</div>
					
					<div class="container-fluid">
						<div class="table-responsive">
						<table class="table table-hover table-striped table-bordered" id="forms_list">
							<colgroup>
								<col width="5%">
								<col width="15%">
								<col width="20%">
								<col width="15%">
								<col width="25%">
								<col width="10%">
								<col width="10%">
							</colgroup>
							<thead>
								<tr>
									<th>#</th>
									<th>Date Created</th>
									<th>Form Name</th>
									<th>Category</th>
									<th>Description</th>
									<th>Status</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								
								<tr>
									<td colspan="7" class="text-center text-muted">Loading forms...</td>
								</tr>
							</tbody>
						</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="formModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content rounded-0">
			<div class="modal-header bg-primary rounded-0" id="formModalHeader">
				<h5 class="modal-title" id="formModalLabel">Add New Form</h5>
				<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form id="formModalForm">
				<div class="modal-body">
					<?php echo SecurityHelper::csrfField(); ?>
					<input type="hidden" id="form_id" name="id">
					
					<div class="form-group">
						<label for="form_name" class="control-label">Form Name <span class="text-danger">*</span></label>
						<input type="text" class="form-control form-control-sm rounded-0" id="form_name" name="form_name" required>
					</div>
					
					<div class="form-group">
						<label for="category_id" class="control-label">Category <span class="text-danger">*</span></label>
						<select class="form-control form-control-sm rounded-0" id="category_id" name="category_id" required>
							<option value="">Select Category</option>
							<?php 
							$categories = $conn->query("SELECT * from `category_list` where delete_flag = 0 and status = 1 order by `name` asc ");
							while($cat = $categories->fetch_assoc()):
							?>
							<option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
							<?php endwhile; ?>
						</select>
					</div>
					
					<div class="form-group">
						<label for="description" class="control-label">Description <span class="text-danger">*</span></label>
						<textarea class="form-control form-control-sm rounded-0" id="description" name="description" rows="3" required></textarea>
					</div>
					
					<div class="form-group">
						<label for="status" class="control-label">Status</label>
						<select class="form-control form-control-sm rounded-0" id="status" name="status">
							<option value="1">Active</option>
							<option value="0">Inactive</option>
						</select>
					</div>
				</div>
				<div class="modal-footer rounded-0">
					<button type="button" class="btn btn-secondary btn-sm rounded-0" data-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-primary btn-sm rounded-0" id="form_submit_btn">Save Form</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="deleteFormModal" tabindex="-1" role="dialog" aria-labelledby="deleteFormModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content rounded-0">
			<div class="modal-header bg-danger rounded-0">
				<h5 class="modal-title text-white" id="deleteFormModalLabel">Delete Form</h5>
				<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<p>Are you sure you want to delete the form "<span id="delete_form_name" class="font-weight-bold"></span>"?</p>
				<p class="text-muted">This action cannot be undone.</p>
			</div>
			<div class="modal-footer rounded-0">
				<button type="button" class="btn btn-secondary btn-sm rounded-0" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-danger btn-sm rounded-0" id="confirm_delete_form">Delete Form</button>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
	
	$('#create_new').click(function(){
		uni_modal("Add New Category","categories/manage_category.php")
	})
	$('.edit_data').click(function(){
		uni_modal("Update Category Details","categories/manage_category.php?id="+$(this).attr('data-id'))
	})
	$('.view_data').click(function(){
		uni_modal("Category Details","categories/view_category.php?id="+$(this).attr('data-id'))
	})
	$('.delete_data').click(function(){
		_conf("Are you sure to delete this category permanently?","delete_category",[$(this).attr('data-id')])
	})
	
	
	
	$('#list td, #list th').addClass('py-1 px-2 align-middle');
	
	
	var formsTable = null;
	
	$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle');
	
	
	var currentFormId = null;
	var formMode = 'add'; 
	
	
	$('#create_form_btn').click(function(){
		openFormModal('add');
	});
	
	
	$('#refresh_forms_btn').click(function(){
		$(this).find('.fas').addClass('fa-spin');
		forceRefreshFormsData();
		setTimeout(function() {
			$('#refresh_forms_btn .fas').removeClass('fa-spin');
		}, 2000);
	});
	
	
	$('button[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var target = $(e.target).attr("data-target");
		
		if (target === '#forms') {
			if (formsTable === null) {
				loadFormsData();
			} else {
				
				$.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
				setTimeout(function() {
					initializeCategoryFilter();
				}, 100);
			}
		} else {
			
			$.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
		}
	});
	
	
	$('#formModalForm').on('submit', function(e) {
		e.preventDefault();
		
		if (formMode === 'view') {
			$('#formModal').modal('hide');
			return;
		}
		
		
		$('#form_submit_btn').prop('disabled', true).text('Saving...');
		
		var formData = $(this).serialize();
		formData += '&csrf_token=' + (typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : '');
		console.log('Submitting form data:', formData);
		
		$.ajax({
			url: _base_url_ + "classes/Master.php?f=save_form",
			method: "POST",
			data: formData,
			dataType: "json",
			timeout: 10000,
			success: function(resp) {
				console.log('Save response:', resp);
				if (resp && resp.status === 'success') {
					alert_toast(resp.msg, 'success');
					$('#formModal').modal('hide');
					
					
					directRefreshTable();
					
				} else {
					alert_toast(resp.msg || 'An error occurred while saving the form', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', xhr.responseText);
				alert_toast("Failed to save form: " + error, 'error');
			},
			complete: function() {
				$('#form_submit_btn').prop('disabled', false).text(formMode === 'edit' ? 'Update Form' : 'Save Form');
			}
		});
	});

	
	$('#confirm_delete_form').click(function() {
		if (!currentFormId) return;
		
		$(this).prop('disabled', true).text('Deleting...');
		
		$.ajax({
			url: _base_url_ + "classes/Master.php?f=delete_form",
			method: "POST",
			data: {id: currentFormId, csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''},
			dataType: "json",
			success: function(resp) {
				console.log('Delete response:', resp);
				if (resp && resp.status === 'success') {
					alert_toast(resp.msg, 'success');
					
					
					$('#deleteFormModal').modal('hide');
					
					
					$('#deleteFormModal').on('hidden.bs.modal', function(e) {
						console.log('Delete modal closed, refreshing data...');
						$(this).off('hidden.bs.modal'); 
						forceRefreshFormsData();
					});
					
				} else {
					alert_toast(resp.msg || 'Failed to delete form', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('Delete error:', xhr.responseText);
				alert_toast("An error occurred: " + error, 'error');
			},
			complete: function() {
				$('#confirm_delete_form').prop('disabled', false).text('Delete Form');
			}
		});
	});
	
	
	function initializeCategoryFilter() {
		console.log('Initializing category filter...');
		
		
		$('#category_filter').off('change').on('change', function() {
    var selectedCategoryId = $(this).val();
    var selectedCategoryName = $(this).find('option:selected').text();
    
    console.log('Category filter changed:', selectedCategoryId, selectedCategoryName);
    
    if (formsTable && $.fn.DataTable.isDataTable('#forms_list')) {
        if (selectedCategoryId === '') {
            
            formsTable.column(3).search('').draw();
        } else {
            
            formsTable.column(3).search(selectedCategoryName, false, true).draw();
        }
    }
});
		
		
		$('#status_filter').off('change').on('change', function() {
			var selectedStatus = $(this).val();
			console.log('Status filter changed:', selectedStatus);
			
			if (formsTable && $.fn.DataTable.isDataTable('#forms_list')) {
				if (selectedStatus === '') {
					
					formsTable.column(5).search('').draw();
				} else {
					var statusText = selectedStatus === '1' ? 'Active' : 'Inactive';
					formsTable.column(5).search(statusText, true, false).draw();
				}
			}
		});
		
		
		$('#clear_filters_btn').off('click').on('click', function() {
			console.log('Clearing filters...');
			$('#category_filter').val('');
			$('#status_filter').val('');
			
			if (formsTable && $.fn.DataTable.isDataTable('#forms_list')) {
				formsTable.search('').columns().search('').draw();
			}
		});
	}
	
	
	function forceRefreshFormsData() {
		console.log('Force refreshing forms data...');
		
		
		if (formsTable && $.fn.DataTable.isDataTable('#forms_list')) {
			try {
				formsTable.destroy();
				$('#forms_list').empty();
			} catch (e) {
				console.log('Error destroying DataTable:', e);
			}
			formsTable = null;
		}
		
		
		var tableHtml = `
			<table class="table table-hover table-striped table-bordered" id="forms_list">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="20%">
					<col width="15%">
					<col width="25%">
					<col width="10%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Date Created</th>
						<th>Form Name</th>
						<th>Category</th>
						<th>Description</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="7" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Refreshing forms...</td>
					</tr>
				</tbody>
			</table>
		`;
		
		$('#forms_list').replaceWith(tableHtml);
		
		
		var timestamp = new Date().getTime();
		
		$.ajax({
			url: _base_url_ + "classes/Master.php?f=get_forms&_=" + timestamp,
			method: "GET",
			dataType: "json",
			cache: false,
			timeout: 15000,
			success: function(forms) {
				console.log('Forms refreshed:', forms);
				populateFormsTable(forms);
			},
			error: function(xhr, status, error) {
				console.error("Error refreshing forms:", {
					status: status,
					error: error,
					responseText: xhr.responseText
				});
				
				$('#forms_list tbody').html(`
					<tr>
						<td colspan="7" class="text-center text-danger">
							Error refreshing forms: ${error}
							<br><button class="btn btn-sm btn-outline-primary mt-2" onclick="forceRefreshFormsData()">Try Again</button>
						</td>
					</tr>
				`);
			}
		});
	}
	
	
	function populateFormsTable(forms) {
		var tbody = $('#forms_list tbody');
		tbody.empty();
		
		if (forms && Array.isArray(forms) && forms.length > 0) {
			forms.forEach(function(form, index) {
				var statusBadge = form.status == 1 ? 
					'<span class="badge badge-success px-3 rounded-pill">Active</span>' : 
					'<span class="badge badge-danger px-3 rounded-pill">Inactive</span>';
				
				var dateCreated = form.date_created ? new Date(form.date_created).toLocaleString() : 'N/A';
				var description = form.description && form.description.length > 80 ? 
					form.description.substring(0, 80) + '...' : (form.description || '');
				var categoryName = form.category_name || 'No Category';
				var formName = form.form_name || form.name || 'Unnamed Form';
				
				var row = `
					<tr>
						<td class="text-center">${index + 1}</td>
						<td>${dateCreated}</td>
						<td><strong>${formName}</strong></td>
						<td><span class="badge badge-info">${categoryName}</span></td>
						<td><p class="mb-0 truncate-1">${description}</p></td>
						<td class="text-center">${statusBadge}</td>
						<td align="center">
							<button type="button" class="btn btn-flat p-1 btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
								Action
								<span class="sr-only">Toggle Dropdown</span>
							</button>
							<div class="dropdown-menu" role="menu">
								<a class="dropdown-item view_form_data" href="javascript:void(0)" data-id="${form.id}">
									<span class="fa fa-eye text-info"></span> View
								</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item edit_form_data" href="javascript:void(0)" data-id="${form.id}">
									<span class="fa fa-edit text-primary"></span> Edit
								</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item delete_form_data" href="javascript:void(0)" data-id="${form.id}" data-name="${formName}">
									<span class="fa fa-trash text-danger"></span> Delete
								</a>
							</div>
						</td>
					</tr>
				`;
				tbody.append(row);
			});
		} else {
			tbody.html('<tr><td colspan="7" class="text-center text-muted">No forms available. Click "Create New Form" to add one.</td></tr>');
		}
		
		
		setTimeout(function() {
			try {
				formsTable = $('#forms_list').DataTable({
					columnDefs: [
						{ orderable: false, targets: [6] }
					],
					order: [0, 'asc'],
					destroy: true,
					paging: true,
					searching: true,
					info: true,
					responsive: true,
					drawCallback: function() {
						$('#forms_list td, #forms_list th').addClass('py-1 px-2 align-middle');
						bindFormActions();
					}
				});
				
				bindFormActions();
				
				
				setTimeout(function() {
					initializeCategoryFilter();
					console.log('Forms table and filters initialized successfully');
				}, 100);
				
			} catch (e) {
				console.error('Error initializing DataTable:', e);
			}
		}, 200);
	}

	
	function directRefreshTable() {
		console.log('Direct table refresh started...');
		
		
		if ($.fn.DataTable.isDataTable('#forms_list')) {
			$('#forms_list').DataTable().destroy();
		}
		
		$('#forms_list tbody').html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Refreshing...</td></tr>');
		
		
		setTimeout(function() {
			$.ajax({
				url: _base_url_ + "classes/Master.php?f=get_forms",
				method: "GET",
				dataType: "json",
				cache: false,
				success: function(forms) {
					console.log('Forms fetched for direct refresh:', forms);
					populateFormsTable(forms); 
				},
				error: function(xhr, status, error) {
					console.error('Direct refresh failed:', error);
					$('#forms_list tbody').html(`
						<tr><td colspan="7" class="text-center text-danger">
							Failed to refresh: ${error}
							<br><button class="btn btn-sm btn-primary mt-2" onclick="directRefreshTable()">Retry</button>
						</td></tr>
					`);
				}
			});
		}, 800); 
	}
	
	function openFormModal(mode, formId = null) {
		formMode = mode;
		currentFormId = formId;
		
		
		$('#formModalForm')[0].reset();
		$('#form_id').val('');
		
		
		switch (mode) {
			case 'add':
				$('#formModalHeader').removeClass().addClass('modal-header bg-success');
				$('#formModalLabel').text('Add New Form');
				$('#form_submit_btn').text('Save Form').show();
				$('#formModalForm input, #formModalForm select, #formModalForm textarea').prop('readonly', false).prop('disabled', false);
				break;
				
			case 'edit':
				$('#formModalHeader').removeClass().addClass('modal-header bg-primary');
				$('#formModalLabel').text('Edit Form');
				$('#form_submit_btn').text('Update Form').show();
				$('#formModalForm input, #formModalForm select, #formModalForm textarea').prop('readonly', false).prop('disabled', false);
				if (formId) loadFormData(formId);
				break;
				
			case 'view':
				$('#formModalHeader').removeClass().addClass('modal-header bg-info');
				$('#formModalLabel').text('View Form');
				$('#form_submit_btn').hide();
				$('#formModalForm input, #formModalForm select, #formModalForm textarea').prop('readonly', true).prop('disabled', true);
				if (formId) loadFormData(formId, true);
				break;
		}
		
		$('#formModal').modal('show');
	}
	
	function loadFormData(formId, isViewMode = false) {
		console.log('Loading form data for ID:', formId);
		
		$.ajax({
			url: _base_url_ + "classes/Master.php?f=get_form",
			method: "POST",
			data: {id: formId, csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''},
			dataType: "json",
			success: function(resp) {
				console.log('Form data loaded:', resp);
				
				if (resp && resp.status === 'success' && resp.data) {
					var form = resp.data;
					
					$('#form_id').val(form.id);
					$('#form_name').val(form.form_name || form.name);
					$('#category_id').val(form.category_id);
					$('#description').val(form.description);
					$('#status').val(form.status);
				} else {
					alert_toast(resp.msg || 'Failed to load form data', 'error');
					$('#formModal').modal('hide');
				}
			},
			error: function(xhr, status, error) {
				console.error('Load form data error:', xhr.responseText);
				alert_toast("Failed to load form data: " + error, 'error');
				$('#formModal').modal('hide');
			}
		});
	}
	
	function loadFormsData() {
		console.log('Loading forms data...');
		
		
		$('#forms_list tbody').html('<tr><td colspan="7" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading forms...</td></tr>');
		
		
		if ($.fn.DataTable.isDataTable('#forms_list')) {
			$('#forms_list').DataTable().destroy();
			formsTable = null;
		}
		
		$.ajax({
			url: _base_url_ + "classes/Master.php?f=get_forms",
			method: "GET",
			dataType: "json",
			timeout: 15000,
			cache: false,
			success: function(forms) {
				console.log('Forms loaded:', forms);
				populateFormsTable(forms);
			},
			error: function(xhr, status, error) {
				console.error("Error loading forms:", {
					status: status,
					error: error,
					responseText: xhr.responseText
				});
				
				var errorMsg = "Error loading forms: ";
				if (status === 'timeout') {
					errorMsg += "Request timed out";
				} else if (status === 'parsererror') {
					errorMsg += "Invalid response format";
				} else {
					errorMsg += error;
				}
				
				$('#forms_list tbody').html(`<tr><td colspan="7" class="text-center text-danger">${errorMsg}</td></tr>`);
			}
		});
	}
	
	function bindFormActions() {
		
		$(document).off('click', '.view_form_data');
		$(document).off('click', '.edit_form_data');
		$(document).off('click', '.delete_form_data');
		
		
		$(document).on('click', '.view_form_data', function(e) {
			e.preventDefault();
			var formId = $(this).attr('data-id');
			if(formId) {
				console.log('View form:', formId);
				openFormModal('view', formId);
			}
		});
		
		$(document).on('click', '.edit_form_data', function(e) {
			e.preventDefault();
			var formId = $(this).attr('data-id');
			if(formId) {
				console.log('Edit form:', formId);
				openFormModal('edit', formId);
			}
		});
		
		$(document).on('click', '.delete_form_data', function(e) {
			e.preventDefault();
			var formId = $(this).attr('data-id');
			var formName = $(this).attr('data-name');
			
			if(formId && formName) {
				console.log('Delete form:', formId, formName);
				currentFormId = formId;
				$('#delete_form_name').text(formName);
				$('#deleteFormModal').modal('show');
			}
		});
	}
});

function delete_category($id){
	start_loader();
	$.ajax({
		url:_base_url_+"classes/Master.php?f=delete_category",
		method:"POST",
		data:{id: $id, csrf_token: typeof _csrf_token_ !== 'undefined' ? _csrf_token_ : ''},
		dataType:"json",
		error:function(err){
			console.log(err);
			alert_toast("An error occurred.",'error');
			end_loader();
		},
		success:function(resp){
			if(typeof resp== 'object' && resp.status == 'success'){
				alert_toast(resp.msg || 'Category moved to Deleted Items.','success');
				setTimeout(function(){ location.href = './?page=maintenance'; }, 800);
			}else{
				alert_toast((resp && (resp.msg || resp.error)) || "An error occurred.",'error');
				end_loader();
			}
		}
	});
}
</script>