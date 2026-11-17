<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
    .invoice-status {
        padding: 10px 10px;
        border-radius: 15px;
        color: white;
        font-weight: bold;
        font-size: 12px;
    }
    .status-pending { background-color: #ffc107; }
    .status-paid { background-color: #28a745; }
    .status-partial { background-color: #17a2b8; }
    .status-overdue { background-color: #dc3545; }
    .status-cancelled { background-color: #6c757d; }
</style>

<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">Invoice Management</h3>
		<div class="card-tools">
			<a href="./?page=invoices/create_invoice" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Create Invoice</a>
			<a href="./?page=invoices/manage_invoice" class="btn btn-flat btn-success"><span class="fas fa-file-invoice"></span> New Invoice</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div class="row mb-3">
				<div class="col-md-3">
					<div class="card bg-info">
						<div class="card-body">
							<h5>Total Invoices</h5>
							<h3><?php echo $conn->query("SELECT id FROM invoice_list")->num_rows; ?></h3>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card bg-success">
						<div class="card-body">
							<h5>Paid Invoices</h5>
							<h3><?php echo $conn->query("SELECT id FROM invoice_list WHERE status = 1")->num_rows; ?></h3>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card bg-warning">
						<div class="card-body">
							<h5>Pending Invoices</h5>
							<h3><?php echo $conn->query("SELECT id FROM invoice_list WHERE status = 0")->num_rows; ?></h3>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card bg-danger">
						<div class="card-body">
							<h5>Overdue Invoices</h5>
							<h3><?php echo $conn->query("SELECT id FROM invoice_list WHERE status = 3")->num_rows; ?></h3>
						</div>
					</div>
				</div>
			</div>
			
			<div class="table-responsive">
			<table class="table table-hover table-striped table-bordered" id="list">
				<colgroup>
					<col width="5%">
					<col width="12%">
					<col width="15%">
					<col width="15%">
					<col width="10%">
					<col width="10%">
					<col width="12%">
					<col width="8%">
					<col width="13%">
				</colgroup>
				<thead>
					<tr class="bg-dark text-white">
						<th>#</th>
						<th>Invoice Code</th>
						<th>Customer</th>
						<th>Order Reference</th>
						<th>Invoice Date</th>
						<th>Due Date</th>
						<th>Total Amount</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					$qry = $conn->query("SELECT i.*, 
						CONCAT(c.firstname,' ', COALESCE(CONCAT(c.middlename,' '), ''), c.lastname) as customer_name,
						o.code as order_code
						FROM `invoice_list` i 
						LEFT JOIN `customer_list` c ON i.customer_id = c.id 
						LEFT JOIN `order_list` o ON i.order_id = o.id
						ORDER BY i.date_created DESC");
					
					while($row = $qry->fetch_assoc()):
						
						$status_class = '';
						$status_text = '';
						switch($row['status']) {
							case 0: $status_class = 'status-pending'; $status_text = 'Pending'; break;
							case 1: $status_class = 'status-paid'; $status_text = 'Paid'; break;
							case 2: $status_class = 'status-partial'; $status_text = 'Partial'; break;
							case 3: $status_class = 'status-overdue'; $status_text = 'Overdue'; break;
							case 4: $status_class = 'status-cancelled'; $status_text = 'Cancelled'; break;
						}
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><strong><?php echo $row['invoice_code'] ?></strong></td>
							<td><?php echo $row['customer_name'] ?></td>
							<td><?php echo $row['order_code'] ? $row['order_code'] : '<em>No Order</em>' ?></td>
							<td><?php echo date("M d, Y", strtotime($row['invoice_date'])) ?></td>
							<td><?php echo date("M d, Y", strtotime($row['due_date'])) ?></td>
							<td class="text-right">₱<?php echo number_format($row['total_amount'], 2) ?></td>
							<td class="text-center">
								<span class="invoice-status <?php echo $status_class ?>"><?php echo $status_text ?></span>
							</td>
							<td align="center">
								<button type="button" class="btn btn-flat p-1 btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
									Action
									<span class="sr-only">Toggle Dropdown</span>
								</button>
								<div class="dropdown-menu" role="menu">
									<a class="dropdown-item" href="./?page=invoice/view_invoice&id=<?= $row['id'] ?>">
										<span class="fa fa-eye text-primary"></span> View
									</a>
									<a class="dropdown-item" href="./?page=invoice/manage_invoice&id=<?= $row['id'] ?>">
										<span class="fa fa-edit text-dark"></span> Edit
									</a>
									<a class="dropdown-item print_invoice" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
										<span class="fa fa-print text-info"></span> Print
									</a>
									<?php if($row['status'] != 1): ?>
									<a class="dropdown-item record_payment" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>" data-balance="<?php echo $row['balance_amount'] ?>">
										<span class="fa fa-credit-card text-success"></span> Record Payment
									</a>
									<?php endif; ?>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
										<span class="fa fa-trash text-danger"></span> Delete
									</a>
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

<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Record Payment</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="payment-form">
                <div class="modal-body">
                    <input type="hidden" name="invoice_id" id="payment_invoice_id">
                    <div class="form-group">
                        <label>Balance Amount</label>
                        <input type="text" class="form-control" id="balance_display" readonly>
                    </div>
                    <div class="form-group">
                        <label>Payment Amount *</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="payment_amount" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Date *</label>
                        <input type="date" class="form-control" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method *</label>
                        <select class="form-control" name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Check">Check</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Online Payment">Online Payment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" class="form-control" name="reference_number">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
	$('.delete_data').click(function(){
		_conf("Are you sure to delete this invoice permanently?","delete_invoice",[$(this).attr('data-id')])
	})
	
	$('.record_payment').click(function(){
		$('#payment_invoice_id').val($(this).attr('data-id'));
		$('#balance_display').val('₱' + parseFloat($(this).attr('data-balance')).toFixed(2));
		$('#payment_amount').attr('max', $(this).attr('data-balance'));
		$('#paymentModal').modal('show');
	})
	
	$('.print_invoice').click(function(){
		var invoice_id = $(this).attr('data-id');
		var printWindow = window.open('./?page=invoices/print_invoice&id=' + invoice_id, '_blank');
		printWindow.print();
	})
	
	$('.table').dataTable({
		columnDefs: [
			{ orderable: false, targets: [8] }
		],
		order:[0,'desc']
	});
	$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
})

$('#payment-form').submit(function(e){
	e.preventDefault();
	start_loader();
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=record_payment",
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
					location.reload();
				}, 1500);
			} else {
				alert_toast(resp.msg, 'error');
			}
			end_loader();
		}
	})
})

function delete_invoice($id){
	start_loader();
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=delete_invoice",
		method: "POST",
		data: {id: $id},
		dataType: 'json',
		error: err => {
			console.log(err)
			alert_toast("An error occurred.", 'error');
			end_loader();
		},
		success: function(resp){
			if(resp.status == 'success'){
				location.reload();
			} else {
				alert_toast("An error occurred.", 'error');
				end_loader();
			}
		}
	})
}
</script>