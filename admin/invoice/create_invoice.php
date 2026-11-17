<style>	

.product-autocomplete {
    position: relative;
}

.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    z-index: 9999; 
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: none;
    border-radius: 0 0 4px 4px;
}

.table-responsive {
    overflow: visible !important; 
}

.card-body {
    overflow: visible !important;
}

#items-table {
    overflow: visible !important;
}

.autocomplete-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
    font-size: 13px;
    transition: background-color 0.2s ease;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-item:hover,
.autocomplete-item.active {
    background-color: #f8f9fa;
}

.autocomplete-item strong {
    font-weight: 600;
    color: #333;
}

.autocomplete-item small {
    margin-top: 2px;
    display: block;
}

.autocomplete-item small.text-muted {
    color: #6c757d !important;
}

.autocomplete-item small.text-success {
    color: #28a745 !important;
    font-weight: 500;
}

.autocomplete-item small.text-info {
    color: #17a2b8 !important;
    font-weight: 500;
}

.loading-spinner {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    color: #007bff;
    z-index: 10000;
}

.border-success {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
}

.item-row {
    vertical-align: middle;
}

.item-row input {
    border-radius: 0.25rem;
}

.item-row .btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    padding: 0.25rem 0.5rem;
}

.item-row .btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

.unit-display {
    font-size: 0.8em;
    color: #6c757d;
    font-weight: 500;
    margin-left: 2px;
}

#items-table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    position: relative; 
}

#items-table tbody td {
    vertical-align: middle;
    position: relative; 
}

.table-responsive {
    overflow: visible !important;
}

.modal .table-responsive {
    overflow: visible !important;
}

.modal .card-body {
    overflow: visible !important;
}

#no-items-message {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    margin: 1rem;
}

#no-items-message .fa-shopping-cart {
    color: #6c757d;
}

#add-item-btn {
    background-color: #28a745;
    border-color: #28a745;
}

#add-item-btn:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

#clear-items-btn {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

#clear-items-btn:hover {
    background-color: #e0a800;
    border-color: #d39e00;
}

.card {
    overflow: visible !important;
}

.autocomplete-dropdown {
    z-index: 99999 !important;
}

.autocomplete-dropdown::-webkit-scrollbar {
    width: 6px;
}

.autocomplete-dropdown::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.autocomplete-dropdown::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.autocomplete-dropdown::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<?php 

if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM invoice_list WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $invoice = $stmt->get_result();
    if($invoice->num_rows > 0) {
        foreach($invoice->fetch_array() as $k => $v){
            if(!is_numeric($k))
                $$k = $v;
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
	<div class="card-header">
		<h3 class="card-title"><?= isset($id) ? "Update Invoice" : "Create New Invoice" ?></h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<form action="" id="invoice-form">
				<input type="hidden" name="id" value="<?= isset($id) ? $id : '' ?>">
				
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="invoice_code" class="control-label">Invoice Code *</label>
							<input type="text" class="form-control form-control-sm rounded-0" name="invoice_code" id="invoice_code" 
       							value="<?= isset($invoice_code) ? $invoice_code : '' ?>" readonly required>
						</div>
						
						<div class="form-group">
							<label for="customer_id" class="control-label">Customer *</label>
							<select class="custom-select custom-select-sm rounded-0" name="customer_id" id="customer_id" required>
								<option value="">Select Customer</option>
								<?php 
								$customers = $conn->query("SELECT *, CONCAT(firstname,' ', COALESCE(CONCAT(middlename,' '), ''), lastname) as name FROM customer_list ORDER BY firstname ASC");
								while($row = $customers->fetch_assoc()):
								?>
									<option value="<?= $row['id'] ?>" <?= isset($customer_id) && $customer_id == $row['id'] ? 'selected' : '' ?>>
										<?= $row['name'] ?> (<?= $row['email'] ?>)
									</option>
								<?php endwhile; ?>
							</select>
						</div>
						
						<div class="form-group">
							<label for="order_id" class="control-label">Linked Order</label>
							<select class="custom-select custom-select-sm rounded-0" name="order_id" id="order_id">
								<option value="">No Order (Manual Invoice)</option>
								<?php 
								
								
								
								$orders = $conn->query("SELECT o.*, c.firstname, c.lastname, o.total_amount as order_total 
														FROM order_list o 
														LEFT JOIN customer_list c ON o.customer_id = c.id 
														WHERE o.status != 3 
														ORDER BY o.date_created DESC");
								while($row = $orders->fetch_assoc()):
								?>
									<option value="<?= $row['id'] ?>" 
										data-customer="<?= $row['customer_id'] ?>"
										data-total="<?= $row['order_total'] ?>"
										<?= isset($order_id) && $order_id == $row['id'] ? 'selected' : '' ?>>
										<?= $row['code'] ?> - <?= $row['firstname'].' '.$row['lastname'] ?> (₱<?= number_format($row['order_total'], 2) ?>)
									</option>
								<?php endwhile; ?>
							</select>
						</div>
						
						<div class="form-group">
							<label for="invoice_date" class="control-label">Invoice Date *</label>
							<input type="date" class="form-control form-control-sm rounded-0" name="invoice_date" id="invoice_date" 
								value="<?= isset($invoice_date) ? $invoice_date : date('Y-m-d') ?>" required>
						</div>
						
						<div class="form-group">
							<label for="due_date" class="control-label">Due Date *</label>
							<input type="date" class="form-control form-control-sm rounded-0" name="due_date" id="due_date" 
								value="<?= isset($due_date) ? $due_date : '' ?>" required>
						</div>
					</div>
					
					<div class="col-md-6">
						<div class="form-group">
							<label for="status" class="control-label">Status</label>
							<select class="custom-select custom-select-sm rounded-0" name="status" id="status">
								<option value="0" <?= isset($status) && $status == '0' ? 'selected' : '' ?>>Pending</option>
								<option value="1" <?= isset($status) && $status == '1' ? 'selected' : '' ?>>Paid</option>
								<option value="2" <?= isset($status) && $status == '2' ? 'selected' : '' ?>>Cancelled</option>
							</select>
						</div>
						
						<div class="form-group">
							<label for="payment_method" class="control-label">Payment Method</label>
							<select class="custom-select custom-select-sm rounded-0" name="payment_method" id="payment_method">
								<option value="">Select Method</option>
								<option value="Cash" <?= isset($payment_method) && $payment_method == 'Cash' ? 'selected' : '' ?>>Cash</option>
								<option value="Check" <?= isset($payment_method) && $payment_method == 'Check' ? 'selected' : '' ?>>Check</option>
								<option value="Bank Transfer" <?= isset($payment_method) && $payment_method == 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
								<option value="Credit Card" <?= isset($payment_method) && $payment_method == 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
								<option value="Online Payment" <?= isset($payment_method) && $payment_method == 'Online Payment' ? 'selected' : '' ?>>Online Payment</option>
							</select>
						</div>
						
						<div class="form-group">
							<label for="tax_rate" class="control-label">Tax Rate (%)</label>
							<input type="number" step="0.01" class="form-control form-control-sm rounded-0" name="tax_rate" id="tax_rate" 
								value="<?= isset($tax_rate) ? $tax_rate : '2' ?>" min="0" max="100">
							<small class="text-muted">Pharmacy products: 0% (VAT-exempt for prescription drugs)</small>
						</div>

						<div class="form-group">
							<label for="notes" class="control-label">Notes</label>
							<textarea class="form-control form-control-sm rounded-0" name="notes" id="notes" rows="3"><?= isset($notes) ? $notes : '' ?></textarea>
						</div>
					</div>
				</div>
				
				
<div class="card mt-3">
	<div class="card-header bg-light">
		<h5>Invoice Items</h5>
		<div class="float-right">
			<button type="button" class="btn btn-sm btn-success" id="add-item-btn">
				<i class="fa fa-plus"></i> Add Item
			</button>
			<button type="button" class="btn btn-sm btn-warning ml-2" id="clear-items-btn">
				<i class="fa fa-trash"></i> Clear All Items
			</button>
		</div>
	</div>
	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-sm" id="items-table">
				<thead>
					<tr>
						<th width="25%">Product/Item Name</th>
						<th width="8%">Qty</th>
						<th width="8%">Unit</th>
						<th width="12%">Unit Price</th>
						<th width="15%">Total</th>
						<th width="5%">Action</th>
					</tr>
				</thead>
				<tbody id="items-tbody">
					<?php if(isset($id)): ?>
						<?php 
						$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = '$id'");
						while($item = $items->fetch_assoc()):
						?>
						<tr class="item-row">
							<td>
								<div class="product-autocomplete">
									<input type="text" class="form-control form-control-sm product-name-input" 
										   name="item_name[]" value="<?= $item['item_name'] ?>" required>
									<input type="hidden" class="product-id" name="product_id[]" value="">
									<div class="autocomplete-dropdown"></div>
								</div>
							</td>
							
							<td>
								<input type="number" step="0.01" class="form-control form-control-sm item-qty" 
									   name="item_qty[]" value="<?= $item['quantity'] ?>" min="0.01" required>
							</td>
							<td>
								<input type="text" class="form-control form-control-sm item-unit" 
									   name="item_unit[]" value="<?= isset($item['unit']) ? $item['unit'] : 'pc' ?>" placeholder="pc">
							</td>
							<td>
								<input type="number" step="0.01" class="form-control form-control-sm item-price" 
									   name="item_price[]" value="<?= $item['unit_price'] ?>" min="0" required>
							</td>
							<td>
								<input type="number" step="0.01" class="form-control form-control-sm item-total" 
									   name="item_total[]" value="<?= $item['total_price'] ?>" readonly>
							</td>
							<td>
								<button type="button" class="btn btn-sm btn-danger remove-item">
									<i class="fa fa-trash"></i>
								</button>
							</td>
						</tr>
						<?php endwhile; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<div id="no-items-message" style="display: none;" class="text-center text-muted py-3">
				<i class="fa fa-shopping-cart fa-3x mb-2"></i>
				<p>No items added yet. Select an order or add items manually.</p>
			</div>
		</div>
	</div>
</div>
				
				
				<div class="row mt-3">
					<div class="col-md-8"></div>
					<div class="col-md-4">
						<div class="card">
							<div class="card-body">
								<table class="table table-sm">
									<tr>
										<td><strong>Subtotal:</strong></td>
										<td class="text-right">
											<input type="number" step="0.01" class="form-control-plaintext text-right" name="subtotal" id="subtotal" 
												value="<?= isset($subtotal) ? $subtotal : '0.00' ?>" readonly>
										</td>
									</tr>
									<tr>
										<td><strong>Tax:</strong></td>
										<td class="text-right">
											<input type="number" step="0.01" class="form-control-plaintext text-right" name="tax_amount" id="tax_amount" 
												value="<?= isset($tax_amount) ? $tax_amount : '0.00' ?>" readonly>
										</td>
									</tr>
									<tr class="bg-light">
										<td><strong>Total Amount:</strong></td>
										<td class="text-right">
											<input type="number" step="0.01" class="form-control-plaintext text-right font-weight-bold" name="total_amount" id="total_amount" 
												value="<?= isset($total_amount) ? $total_amount : '0.00' ?>" readonly>
										</td>
									</tr>
									<tr>
										<td><strong>Paid Amount:</strong></td>
										<td class="text-right">
											<input type="number" step="0.01" class="form-control form-control-sm text-right" name="paid_amount" id="paid_amount" 
												value="<?= isset($paid_amount) ? $paid_amount : '0.00' ?>">
										</td>
									</tr>
									<tr>
										<td><strong>Balance:</strong></td>
										<td class="text-right">
											<input type="number" step="0.01" class="form-control-plaintext text-right" name="balance_amount" id="balance_amount" 
												value="<?= isset($balance_amount) ? $balance_amount : '0.00' ?>" readonly>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
	<div class="card-footer">
		<div class="col-md-12">
			<div class="row">
				<button class="btn btn-sm btn-primary rounded-0 mr-3" form="invoice-form">
					<?= isset($id) ? 'Update Invoice' : 'Save Invoice' ?>
				</button>
				<a href="./?page=invoice/manage_invoice" class="btn btn-sm btn-default border rounded-0">
					<i class="fa fa-angle-left"></i> Cancel
				</a>
			</div>
		</div>
	</div>
</div>

<script>
let currentRow = null;
let autocompleteTimeout = null;

$(document).ready(function(){
	
	<?php if(!isset($id)): ?>
	generateInvoiceCode();
	
	
	setDefaultDueDate();
	<?php endif; ?>
	
	
	$('#add-item-btn').click(function(){
		addInvoiceItem();
	});
	
	
	$(document).on('click', '.remove-item', function(){
		$(this).closest('tr').remove();
		checkNoItemsMessage();
		calculateTotals();
	});
	
	
	$('#clear-items-btn').click(function(){
		if(confirm('Are you sure you want to clear all items?')) {
			$('#items-tbody').empty();
			$('#order_id').val('');
			checkNoItemsMessage();
			calculateTotals();
		}
	});
	
	
	$(document).on('input', '.product-name-input', function(){
		const input = $(this);
		const searchTerm = input.val();
		currentRow = input.closest('tr');
		
		if(autocompleteTimeout) {
			clearTimeout(autocompleteTimeout);
		}
		
		if(searchTerm.length >= 2) {
			autocompleteTimeout = setTimeout(function() {
				searchProducts(searchTerm, input);
			}, 300);
		} else {
			hideAutocomplete(input);
		}
	});
	
	$('#customer_id').change(function(){
    var customerId = $(this).val();
    var orderSelect = $('#order_id');
    
    if(!customerId) {
        
        orderSelect.find('option').show();
        orderSelect.val('');
        return;
    }
    
    
    orderSelect.prop('disabled', true);
    orderSelect.html('<option value="">Loading orders...</option>');
    
    
    $.ajax({
        url: _base_url_ + "classes/Invoices.php?f=get_customer_orders",
        method: 'POST',
        data: { customer_id: customerId },
        dataType: 'json',
        success: function(resp){
            orderSelect.prop('disabled', false);
            
            if(resp.status == 'success' && resp.orders && resp.orders.length > 0) {
                
                var optionsHtml = '<option value="">No Order (Manual Invoice)</option>';
                
                resp.orders.forEach(function(order) {
                    optionsHtml += '<option value="' + order.id + '" ' +
                        'data-customer="' + order.customer_id + '" ' +
                        'data-total="' + order.total_amount + '">' +
                        order.code + ' - ₱' + parseFloat(order.total_amount).toFixed(2) +
                        ' (' + order.status_text + ')' +
                        '</option>';
                });
                
                orderSelect.html(optionsHtml);
                
                alert_toast("Found " + resp.orders.length + " order(s) for this customer", 'success');
            } else {
                orderSelect.html('<option value="">No Order (Manual Invoice)</option>');
                alert_toast("No orders found for this customer", 'info');
            }
        },
        error: function(xhr, status, error) {
            orderSelect.prop('disabled', false);
            orderSelect.html('<option value="">No Order (Manual Invoice)</option>');
            console.log('Error fetching customer orders:', error);
            alert_toast("Error loading customer orders", 'error');
        }
    });
});

$('#customer_id').on('change', function(e){
    var itemCount = $('#items-tbody tr').length;
    var orderId = $('#order_id').val();
    
    if(itemCount > 0 && orderId) {
        if(!confirm('Changing the customer will clear the current order and items. Continue?')) {
            e.preventDefault();
            
            $(this).val($(this).data('previous-value'));
            return false;
        } else {
            
            $('#order_id').val('');
            $('#items-tbody').empty();
            checkNoItemsMessage();
            calculateTotals();
        }
    }
    
    
    $(this).data('previous-value', $(this).val());
});

$('#customer_id').data('previous-value', $('#customer_id').val());

$('#order_id').on('change.customerValidation', function(){
    var orderId = $(this).val();
    var orderCustomerId = $(this).find('option:selected').data('customer');
    var selectedCustomerId = $('#customer_id').val();
    
    if(orderId && orderCustomerId) {
        
        if(selectedCustomerId && orderCustomerId != selectedCustomerId) {
            alert_toast("This order belongs to a different customer!", 'warning');
            $(this).val('');
            return false;
        }
        
        
        if(!selectedCustomerId) {
            $('#customer_id').val(orderCustomerId);
            
        }
    }
});

	
	$(document).on('keydown', '.product-name-input', function(e){
		const dropdown = $(this).siblings('.autocomplete-dropdown');
		const items = dropdown.find('.autocomplete-item');
		const activeItem = items.filter('.active');
		
		if(!dropdown.is(':visible')) return;
		
		switch(e.keyCode) {
			case 38: 
				e.preventDefault();
				if(activeItem.length && activeItem.index() > 0) {
					activeItem.removeClass('active');
					activeItem.prev().addClass('active');
				} else {
					items.removeClass('active');
					items.last().addClass('active');
				}
				break;
				
			case 40: 
				e.preventDefault();
				if(activeItem.length && activeItem.index() < items.length - 1) {
					activeItem.removeClass('active');
					activeItem.next().addClass('active');
				} else {
					items.removeClass('active');
					items.first().addClass('active');
				}
				break;
				
			case 13: 
				e.preventDefault();
				if(activeItem.length) {
					activeItem.click();
				}
				break;
				
			case 27: 
				hideAutocomplete($(this));
				break;
		}
	});

	
	$(document).on('click', function(e){
		if(!$(e.target).closest('.product-autocomplete').length) {
			$('.autocomplete-dropdown').hide();
		}
	});

	
	$(document).on('click', '.autocomplete-item[data-product-id]', function(){
		const productId = $(this).data('product-id');
		const productName = $(this).find('strong').text();
		const productUnit = $(this).data('unit') || 'pc';
		const input = $(this).closest('.product-autocomplete').find('.product-name-input');
		const row = input.closest('tr');
		
		
		input.val(productName);
		row.find('.product-id').val(productId);
		row.find('.item-unit').val(productUnit);
		
		
		hideAutocomplete(input);
		
		
		loadProductDetails(productId, row);
	});
	
	
	$(document).on('input', '.item-qty, .item-price', function(){
		var row = $(this).closest('tr');
		var qty = parseFloat(row.find('.item-qty').val()) || 0;
		var price = parseFloat(row.find('.item-price').val()) || 0;
		var total = qty * price;
		row.find('.item-total').val(total.toFixed(2));
		calculateTotals();
	});
	
	
	$('#tax_rate, #paid_amount').on('input', function(){
    calculateTotals();
});
	
	
	$('#invoice_date').change(function(){
		if(!$('#due_date').val()) {
			setDefaultDueDate();
		}
	});
	
	
	$('#order_id').change(function(){
		var orderId = $(this).val();
		var customerId = $(this).find('option:selected').data('customer');
		
		if(orderId && customerId) {
			
			$('#customer_id').val(customerId);
			
			
			loadOrderItems(orderId);
		} else {
			
			if(!orderId) {
				$('#items-tbody').empty();
				checkNoItemsMessage();
				calculateTotals();
			}
		}
	});
	
	
	<?php if(isset($id)): ?>
	calculateTotals();
	<?php endif; ?>
	
	
	checkNoItemsMessage();
});

function addInvoiceItem() {
	var newRow = `
		<tr class="item-row">
			<td>
				<div class="product-autocomplete">
					<input type="text" class="form-control form-control-sm product-name-input" 
						   name="item_name[]" placeholder="Type product name..." required>
					<input type="hidden" class="product-id" name="product_id[]" value="">
					<div class="autocomplete-dropdown"></div>
				</div>
			</td>
			<td>
				<input type="number" step="0.01" class="form-control form-control-sm item-qty" 
					   name="item_qty[]" value="1" min="0.01" required>
			</td>
			<td>
				<input type="text" class="form-control form-control-sm item-unit" 
					   name="item_unit[]" value="pc" placeholder="pc">
			</td>
			<td>
				<input type="number" step="0.01" class="form-control form-control-sm item-price" 
					   name="item_price[]" value="0.00" min="0" required>
			</td>
			<td>
				<input type="number" step="0.01" class="form-control form-control-sm item-total" 
					   name="item_total[]" value="0.00" readonly>
			</td>
			<td>
				<button type="button" class="btn btn-sm btn-danger remove-item" title="Remove Item">
					<i class="fa fa-trash"></i>
				</button>
			</td>
		</tr>
	`;
	$('#items-tbody').append(newRow);
	checkNoItemsMessage();
	
	
	$('#items-tbody tr:last-child .product-name-input').focus();
}

function searchProducts(searchTerm, input) {
	const container = input.closest('.product-autocomplete');
	const dropdown = container.find('.autocomplete-dropdown');
	
	
	showLoading(input);
	
	$.ajax({
		url: _base_url_ + 'classes/Invoices.php?f=get_products',
		method: 'POST',
		data: { search: searchTerm },
		dataType: 'json',
		success: function(resp) {
			hideLoading(input);
			
			if(resp.status === 'success' && resp.products && resp.products.length > 0) {
				let html = '';
				resp.products.forEach(function(product) {
					const unit = product.unit || 'pc';
					const unitDisplay = unit !== 'pc' ? unit : 'piece';
					
					html += `
						<div class="autocomplete-item" data-product-id="${product.id}" data-unit="${unit}">
							<strong>${product.product_name}</strong>
							${product.sku ? '<small class="text-muted">SKU: ' + product.sku + '</small>' : ''}
							<small class="text-info">Unit: ${unitDisplay}</small>
							<small class="text-success">₱${parseFloat(product.price).toFixed(2)} per ${unitDisplay}</small>
						</div>
					`;
				});
				
				dropdown.html(html).show();
			} else {
				dropdown.html('<div class="autocomplete-item text-muted">No products found</div>').show();
			}
		},
		error: function() {
			hideLoading(input);
			dropdown.html('<div class="autocomplete-item text-danger">Error searching products</div>').show();
		}
	});
}

function loadProductDetails(productId, row) {
	showLoading(row.find('.product-name-input'));
	
	$.ajax({
		url: _base_url_ + 'classes/Invoices.php?f=get_product_details',
		method: 'POST',
		data: { product_id: productId },
		dataType: 'json',
		success: function(resp) {
			hideLoading(row.find('.product-name-input'));
			
			if(resp.status === 'success' && resp.product) {
				const product = resp.product;
				
				
				row.find('.item-description').val(product.description || '');
				row.find('.item-price').val(parseFloat(product.price).toFixed(2));
				row.find('.item-unit').val(product.unit || 'pc');
				
				
				const qty = parseFloat(row.find('.item-qty').val()) || 1;
				const price = parseFloat(product.price) || 0;
				row.find('.item-total').val((qty * price).toFixed(2));
				
				calculateTotals();
				
				
				row.find('.product-name-input').addClass('border-success');
				setTimeout(function() {
					row.find('.product-name-input').removeClass('border-success');
				}, 2000);
				
				alert_toast("Product details loaded successfully", 'success');
			}
		},
		error: function() {
			hideLoading(row.find('.product-name-input'));
			alert_toast("Error loading product details", 'error');
		}
	});
}

function showLoading(input) {
	const container = input.closest('.product-autocomplete');
	if(!container.find('.loading-spinner').length) {
		container.append('<i class="fas fa-spinner fa-spin loading-spinner"></i>');
	}
}

function hideLoading(input) {
	const container = input.closest('.product-autocomplete');
	container.find('.loading-spinner').remove();
}

function hideAutocomplete(input) {
	input.closest('.product-autocomplete').find('.autocomplete-dropdown').hide();
}

function generateInvoiceCode() {
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=generate_invoice_code",
		method: 'GET',
		dataType: 'json',
		success: function(resp){
			if(resp.status == 'success'){
				$('#invoice_code').val(resp.code);
			}
		}
	});
}

function setDefaultDueDate() {
	var invoiceDate = new Date($('#invoice_date').val() || new Date());
	invoiceDate.setDate(invoiceDate.getDate() + 2);
	$('#due_date').val(invoiceDate.toISOString().split('T')[0]);
}

function loadOrderItems(orderId) {
	start_loader();
	
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=get_order_items",
		method: 'POST',
		data: { order_id: orderId },
		dataType: 'json',
		success: function(resp){
			if(resp.status == 'success' && resp.items && resp.items.length > 0) {
				
				$('#items-tbody').empty();
				
				
				resp.items.forEach(function(item) {
					var productName = item.product_name || item.item_name || item.name || 'Product';
					var description = item.description || item.product_description || '';
					var quantity = parseFloat(item.quantity || item.qty || 1);
					var price = parseFloat(item.price || item.unit_price || item.amount || 0);
					var total = parseFloat(item.total || item.total_amount || (quantity * price));
					var unit = item.unit || item.product_unit || 'pc';
					
					var itemRow = `
						<tr class="item-row">
							<td>
								<div class="product-autocomplete">
									<input type="text" class="form-control form-control-sm product-name-input" 
										   name="item_name[]" value="${productName}" required>
									<input type="hidden" class="product-id" name="product_id[]" value="${item.product_id || ''}">
									<div class="autocomplete-dropdown"></div>
								</div>
							</td>
							<td>
								<input type="number" step="0.01" class="form-control form-control-sm item-qty" 
									   name="item_qty[]" value="${quantity}" min="0.01" required>
							</td>
							<td>
								<input type="text" class="form-control form-control-sm item-unit" 
									   name="item_unit[]" value="${unit}" placeholder="pc">
							</td>
							<td>
								<input type="number" step="0.01" class="form-control form-control-sm item-price" 
									   name="item_price[]" value="${price}" min="0" required>
							</td>
							<td>
								<input type="number" step="0.01" class="form-control form-control-sm item-total" 
									   name="item_total[]" value="${total.toFixed(2)}" readonly>
							</td>
							<td>
								<button type="button" class="btn btn-sm btn-danger remove-item" title="Remove Item">
									<i class="fa fa-trash"></i>
								</button>
							</td>
						</tr>
					`;
					$('#items-tbody').append(itemRow);
				});
				
				checkNoItemsMessage();
				calculateTotals();
				
				alert_toast("Order items loaded successfully (" + resp.items.length + " items)", 'success');
			} else {
				alert_toast(resp.msg || "No items found for this order", 'info');
				
				addInvoiceItem();
			}
			end_loader();
		},
		error: function(xhr, status, error) {
			console.log('AJAX Error:', xhr.responseText);
			alert_toast("Error loading order items: " + error, 'error');
			
			addInvoiceItem();
			end_loader();
		}
	});
}

function checkNoItemsMessage() {
	var itemCount = $('#items-tbody tr').length;
	if(itemCount === 0) {
		$('#no-items-message').show();
		$('#items-table').hide();
	} else {
		$('#no-items-message').hide();
		$('#items-table').show();
	}
}

function calculateTotals() {
    var subtotal = 0;
    
    
    $('.item-total').each(function(){
        subtotal += parseFloat($(this).val()) || 0;
    });
    
    var taxRate = parseFloat($('#tax_rate').val()) || 2.00; 
    var paidAmount = parseFloat($('#paid_amount').val()) || 0;
    
    var taxAmount = (subtotal * taxRate) / 100;
    var totalAmount = subtotal + taxAmount;
    var balanceAmount = totalAmount - paidAmount;
    
    
    $('#subtotal').val(subtotal.toFixed(2));
    $('#tax_amount').val(taxAmount.toFixed(2));
    $('#total_amount').val(totalAmount.toFixed(2));
    $('#balance_amount').val(balanceAmount.toFixed(2));
    
    
    if(balanceAmount <= 0 && totalAmount > 0) {
        $('#status').val('1'); 
    } else if(paidAmount > 0 && balanceAmount > 0) {
        $('#status').val('2'); 
    } else if(balanceAmount > 0) {
        $('#status').val('0'); 
    }
}

$('#invoice-form').submit(function(e){
	e.preventDefault();
	start_loader();
	
	
	if($('#items-tbody tr').length === 0) {
		alert_toast("Please add at least one item to the invoice", 'error');
		end_loader();
		return;
	}
	
	
	var hasEmptyUnits = false;
	$('.item-unit').each(function(){
		if(!$(this).val().trim()) {
			hasEmptyUnits = true;
			$(this).focus();
			return false;
		}
	});
	
	if(hasEmptyUnits) {
		alert_toast("Please specify units for all items", 'error');
		end_loader();
		return;
	}
	
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=save_invoice",
		method: 'POST',
		data: new FormData($(this)[0]),
		dataType: 'json',
		cache: false,
		processData: false,
		contentType: false,
		error: err => {
			console.log(err)
			alert_toast("An error occurred", 'error');
			end_loader();
		},
		success: function(resp){
			if(resp.status == 'success'){
				alert_toast(resp.msg, 'success');
				setTimeout(function(){
					location.href = './?page=invoice/manage_invoice';
				}, 1500);
			} else {
				alert_toast(resp.msg, 'error');
			}
			end_loader();
		}
	})
});
</script>
