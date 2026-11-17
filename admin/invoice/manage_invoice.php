<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<div class="card card-outline rounded-0 card-danger">
	<div class="card-header">
		<h3 class="card-title">Invoice Management</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-primary btn-sm" id="create-invoice">
				<i class="fas fa-plus"></i> New Invoice
			</button>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			
			<ul class="nav nav-tabs nav-justified mb-3" id="invoiceTab" role="tablist">
				<li class="nav-item" role="presentation">
					<a class="nav-link active" id="all-tab" data-toggle="tab" href="#all-invoices" role="tab" aria-controls="all-invoices" aria-selected="true">
						<i class="fas fa-list"></i> All Invoices
						<span class="badge badge-light ml-1" id="all-count">0</span>
					</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pending-tab" data-toggle="tab" href="#pending-invoices" role="tab" aria-controls="pending-invoices" aria-selected="false">
						<i class="fas fa-clock text-warning"></i> Pending
						<span class="badge badge-warning ml-1" id="pending-count">0</span>
					</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="paid-tab" data-toggle="tab" href="#paid-invoices" role="tab" aria-controls="paid-invoices" aria-selected="false">
						<i class="fas fa-check-circle text-success"></i> Paid
						<span class="badge badge-success ml-1" id="paid-count">0</span>
					</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="overdue-tab" data-toggle="tab" href="#overdue-invoices" role="tab" aria-controls="overdue-invoices" aria-selected="false">
						<i class="fas fa-exclamation-triangle text-danger"></i> Overdue
						<span class="badge badge-danger ml-1" id="overdue-count">0</span>
					</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="partially-paid-tab" data-toggle="tab" href="#partially-paid-invoices" role="tab" aria-controls="partially-paid-invoices" aria-selected="false">
						<i class="fas fa-coins text-info"></i> Partially Paid
						<span class="badge badge-info ml-1" id="partially-paid-count">0</span>
					</a>
				</li>
			</ul>

			
			<div class="tab-content" id="invoiceTabContent">
				
				<div class="tab-pane fade show active" id="all-invoices" role="tabpanel" aria-labelledby="all-tab">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="mb-0"><i class="fas fa-list text-primary"></i> All Invoices</h5>
						<div class="btn-group btn-group-sm" role="group">
							<button type="button" class="btn btn-outline-secondary" onclick="refreshTable('all')">
								<i class="fas fa-sync-alt"></i> Refresh
							</button>
							<button type="button" class="btn btn-outline-success" onclick="exportToExcel('all')">
								<i class="fas fa-file-excel"></i> Export
							</button>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table table-hover table-striped table-sm invoice-table" id="all-invoices-table">
							<thead>
								<tr>
									<th>#</th>
									<th>Invoice Code</th>
									<th>Customer</th>
									<th>Order Code</th>
									<th>Invoice Date</th>
									<th>Due Date</th>
									<th>Total Amount</th>
									<th>Balance</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php echo generateInvoiceRows('all'); ?>
							</tbody>
						</table>
					</div>
				</div>

				
				<div class="tab-pane fade" id="pending-invoices" role="tabpanel" aria-labelledby="pending-tab">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="mb-0"><i class="fas fa-clock text-warning"></i> Pending Invoices</h5>
						<div class="btn-group btn-group-sm" role="group">
							<button type="button" class="btn btn-outline-secondary" onclick="refreshTable('pending')">
								<i class="fas fa-sync-alt"></i> Refresh
							</button>
							<button type="button" class="btn btn-outline-warning" onclick="sendReminderEmails('pending')">
								<i class="fas fa-envelope"></i> Send Reminders
							</button>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table table-hover table-striped table-sm invoice-table" id="pending-invoices-table">
							<thead>
								<tr>
									<th>#</th>
									<th>Invoice Code</th>
									<th>Customer</th>
									<th>Order Code</th>
									<th>Invoice Date</th>
									<th>Due Date</th>
									<th>Total Amount</th>
									<th>Balance</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php echo generateInvoiceRows('pending'); ?>
							</tbody>
						</table>
					</div>
				</div>

				
				<div class="tab-pane fade" id="paid-invoices" role="tabpanel" aria-labelledby="paid-tab">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="mb-0"><i class="fas fa-check-circle text-success"></i> Paid Invoices</h5>
						<div class="btn-group btn-group-sm" role="group">
							<button type="button" class="btn btn-outline-secondary" onclick="refreshTable('paid')">
								<i class="fas fa-sync-alt"></i> Refresh
							</button>
							<button type="button" class="btn btn-outline-success" onclick="generateReport('paid')">
								<i class="fas fa-chart-line"></i> Generate Report
							</button>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table table-hover table-striped table-sm invoice-table" id="paid-invoices-table">
							<thead>
								<tr>
									<th>#</th>
									<th>Invoice Code</th>
									<th>Customer</th>
									<th>Order Code</th>
									<th>Invoice Date</th>
									<th>Due Date</th>
									<th>Total Amount</th>
									<th>Payment Date</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php echo generateInvoiceRows('paid'); ?>
							</tbody>
						</table>
					</div>
				</div>

				
				<div class="tab-pane fade" id="overdue-invoices" role="tabpanel" aria-labelledby="overdue-tab">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="mb-0"><i class="fas fa-exclamation-triangle text-danger"></i> Overdue Invoices</h5>
						<div class="btn-group btn-group-sm" role="group">
							<button type="button" class="btn btn-outline-secondary" onclick="refreshTable('overdue')">
								<i class="fas fa-sync-alt"></i> Refresh
							</button>
							<button type="button" class="btn btn-outline-danger" onclick="sendReminderEmails('overdue')">
								<i class="fas fa-exclamation"></i> Send Urgent Reminders
							</button>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table table-hover table-striped table-sm invoice-table" id="overdue-invoices-table">
							<thead>
								<tr>
									<th>#</th>
									<th>Invoice Code</th>
									<th>Customer</th>
									<th>Order Code</th>
									<th>Invoice Date</th>
									<th>Due Date</th>
									<th>Total Amount</th>
									<th>Days Overdue</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php echo generateInvoiceRows('overdue'); ?>
							</tbody>
						</table>
					</div>
				</div>

				
				<div class="tab-pane fade" id="partially-paid-invoices" role="tabpanel" aria-labelledby="partially-paid-tab">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="mb-0"><i class="fas fa-coins text-info"></i> Partially Paid Invoices</h5>
						<div class="btn-group btn-group-sm" role="group">
							<button type="button" class="btn btn-outline-secondary" onclick="refreshTable('partially-paid')">
								<i class="fas fa-sync-alt"></i> Refresh
							</button>
							<button type="button" class="btn btn-outline-info" onclick="sendReminderEmails('partially-paid')">
								<i class="fas fa-envelope"></i> Send Balance Reminders
							</button>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table table-hover table-striped table-sm invoice-table" id="partially-paid-invoices-table">
							<thead>
								<tr>
									<th>#</th>
									<th>Invoice Code</th>
									<th>Customer</th>
									<th>Order Code</th>
									<th>Invoice Date</th>
									<th>Due Date</th>
									<th>Total Amount</th>
									<th>Balance</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php echo generateInvoiceRows('partially-paid'); ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
function generateInvoiceRows($filter = 'all') {
	global $conn;
	
	$whereClause = '';
	switch($filter) {
		case 'pending':
			$whereClause = 'WHERE i.status = 0';
			break;
		case 'paid':
			$whereClause = 'WHERE i.status = 1';
			break;
		case 'overdue':
			$whereClause = 'WHERE i.status = 3 OR (i.status != 1 AND i.due_date < CURDATE())';
			break;
		case 'partially-paid':
			$whereClause = 'WHERE i.status = 2';
			break;
		case 'all':
		default:
			$whereClause = '';
			break;
	}
	
	$query = "SELECT i.id, i.invoice_code, i.customer_id, i.order_id, i.invoice_date, 
		i.due_date, i.total_amount, i.paid_amount, i.balance_amount, i.status, 
		i.date_created, i.date_updated,
		CONCAT(c.firstname,' ', COALESCE(CONCAT(c.middlename,' '), ''), c.lastname) as customer_name,
		c.email as customer_email,
		o.code as order_code,
		DATEDIFF(CURDATE(), i.due_date) as days_overdue
		FROM `invoice_list` i 
		LEFT JOIN `customer_list` c ON i.customer_id = c.id 
		LEFT JOIN `order_list` o ON i.order_id = o.id
		$whereClause
		ORDER BY i.date_created DESC";
	
	$invoices = $conn->query($query);
	
	
	if (!$invoices || $invoices->num_rows == 0) {
		return '';
	}
	
	$i = 1;
	$output = '';
	while($row = $invoices->fetch_assoc()):
		$status_class = '';
		$status_text = '';
		switch($row['status']) {
			case 0:
				$status_class = 'badge-warning';
				$status_text = 'Pending';
				break;
			case 1:
				$status_class = 'badge-success';
				$status_text = 'Paid';
				break;
			case 2:
				$status_class = 'badge-info';
				$status_text = 'Partially Paid';
				break;
			case 3:
				$status_class = 'badge-danger';
				$status_text = 'Overdue';
				break;
			case 4:
				$status_class = 'badge-dark';
				$status_text = 'Cancelled';
				break;
		}
		
		
		$is_overdue = strtotime($row['due_date']) < strtotime(date('Y-m-d')) && $row['status'] != 1;
		
		$output .= '<tr>';
		$output .= '<td>' . $i++ . '</td>';
		$output .= '<td><b class="text-primary">' . htmlspecialchars($row['invoice_code']) . '</b></td>';
		$output .= '<td>';
		$output .= '<div><strong>' . htmlspecialchars($row['customer_name']) . '</strong></div>';
		$output .= '<small class="text-muted d-block"><i class="fas fa-envelope"></i> ' . htmlspecialchars($row['customer_email']) . '</small>';
		$output .= '</td>';
		$output .= '<td>';
		$output .= $row['order_code'] ? '<span class="badge badge-info">' . htmlspecialchars($row['order_code']) . '</span>' : '<span class="badge badge-light text-muted">Manual</span>';
		$output .= '</td>';
		$output .= '<td>';
		$output .= '<small>' . date("M d, Y", strtotime($row['invoice_date'])) . '</small>';
		$output .= '</td>';
		$output .= '<td>';
		$due_date = !empty($row['due_date']) ? date("M d, Y", strtotime($row['due_date'])) : 'N/A';
		$output .= $is_overdue ? '<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> ' . $due_date . '</small>' : '<small>' . $due_date . '</small>';
		$output .= '</td>';
		$output .= '<td class="text-right">';
		$output .= '<strong>₱' . number_format($row['total_amount'], 2) . '</strong>';
		$output .= '</td>';
		
		
		if($filter == 'paid') {
			$output .= '<td>';
			$payment_date = !empty($row['date_updated']) ? date("M d, Y", strtotime($row['date_updated'])) : 'N/A';
			$output .= '<small>' . $payment_date . '</small>';
			$output .= '</td>';
		} elseif($filter == 'overdue') {
			$output .= '<td>';
			$output .= '<span class="badge badge-danger">' . max(0, $row['days_overdue']) . ' days</span>';
			$output .= '</td>';
		} else {
			$balance = $row['balance_amount'];
			$output .= '<td class="text-right">';
			$output .= $balance > 0 ? '<span class="text-danger font-weight-bold">₱' . number_format($balance, 2) . '</span>' : '<span class="text-success font-weight-bold">₱0.00</span>';
			$output .= '</td>';
		}
		
		$output .= '<td>';
		$output .= '<span class="badge ' . $status_class . '">' . $status_text . '</span>';
		$output .= '</td>';
		$output .= '<td>';
		$output .= '<div class="btn-group btn-group-sm d-flex" role="group">';
		$output .= '<button type="button" class="btn btn-outline-info view_invoice" data-id="' . $row['id'] . '" title="View Invoice">';
		$output .= '<i class="fas fa-eye"></i>';
		$output .= '</button>';
		$output .= '<button type="button" class="btn btn-outline-warning edit_invoice" data-id="' . $row['id'] . '" title="Edit Invoice">';
		$output .= '<i class="fas fa-edit"></i>';
		$output .= '</button>';
		$output .= '<button type="button" class="btn btn-outline-danger delete_invoice" data-id="' . $row['id'] . '" title="Delete Invoice">';
		$output .= '<i class="fas fa-trash-alt"></i>';
		$output .= '</button>';
		$output .= '</div>';
		$output .= '</td>';
		$output .= '</tr>';
	endwhile;
	
	return $output;
}
?>

<div class="modal fade" id="viewInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="viewInvoiceModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header bg-gradient-info text-white">
				<h5 class="modal-title" id="viewInvoiceModalLabel">
					<i class="fas fa-file-invoice-dollar"></i> Invoice Details
				</h5>
				<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body p-0">
				<div id="invoice-details" class="p-4">
					<div class="text-center py-5">
						<div class="spinner-border text-info" role="status">
							<span class="sr-only">Loading...</span>
						</div>
						<p class="mt-3 h5 text-muted">Loading invoice details...</p>
					</div>
				</div>
			</div>
			<div class="modal-footer bg-light">
				<button type="button" class="btn btn-info" id="print-from-modal">
					<i class="fas fa-print"></i> Print Invoice
				</button>
				<button type="button" class="btn btn-success" id="email-invoice">
					<i class="fas fa-envelope"></i> Send Email
				</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">
					<i class="fas fa-times"></i> Close
				</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="editInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="editInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-success text-white">
                    <h5 class="modal-title" id="editInvoiceModalLabel">
                        <i class="fas fa-edit"></i> Edit Invoice
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="edit-invoice-form" class="edit-form">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div id="edit-invoice-content">
                            <div class="text-center py-5">
                                <div class="spinner-border text-success" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-3 h5 text-muted">Loading invoice data...</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-success" id="save-invoice-changes">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<div class="modal fade" id="printPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header bg-gradient-primary text-white">
				<h5 class="modal-title">
					<i class="fas fa-print"></i> Print Preview
				</h5>
				<button type="button" class="close text-white" data-dismiss="modal">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body p-0" style="max-height: 70vh; overflow-y: auto;">
				<div id="print-content">
					<div class="text-center py-5">
						<div class="spinner-border text-primary" role="status">
							<span class="sr-only">Loading...</span>
						</div>
						<p class="mt-3 h5 text-muted">Preparing print preview...</p>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onclick="printInvoice()">
					<i class="fas fa-print"></i> Print Now
				</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">
					<i class="fas fa-times"></i> Cancel
				</button>
			</div>
		</div>
	</div>
</div>

<style>

.table-responsive {
	border-radius: 0.5rem;
	box-shadow: 0 2px 8px rgba(0,0,0,0.1);
	margin-bottom: 1rem;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
}

.table {
	width: 100%;
	margin-bottom: 0;
	min-width: 800px; 
}

.table th, 
.table td {
	padding: 0.75rem 0.5rem;
	vertical-align: middle;
	border-top: 1px solid #dee2e6;
	white-space: nowrap;
	min-width: fit-content;
}

.table th {
	background: linear-gradient(145deg, #f8f9fa, #e9ecef);
	font-weight: 600;
	color: #495057;
	border-top: none;
	position: sticky;
	top: 0;
	z-index: 10;
	font-size: 0.85rem;
}

.table td {
	font-size: 0.9rem;
}

.table-hover tbody tr:hover {
	background-color: rgba(0,123,255,0.05);
	transform: scale(1.001);
	transition: all 0.2s ease;
}

.table th:nth-child(1), 
.table td:nth-child(1) {
	width: 50px;
	min-width: 50px;
	text-align: center;
}

.table th:nth-child(2), 
.table td:nth-child(2) {
	min-width: 120px;
}

.table th:nth-child(3), 
.table td:nth-child(3) {
	min-width: 200px;
	max-width: 250px;
}

.table th:nth-child(4), 
.table td:nth-child(4) {
	min-width: 100px;
	text-align: center;
}

.table th:nth-child(5), 
.table td:nth-child(5),
.table th:nth-child(6), 
.table td:nth-child(6) {
	min-width: 100px;
	text-align: center;
}

.table th:nth-child(7), 
.table td:nth-child(7) {
	min-width: 120px;
	text-align: right;
}

.table th:nth-child(8), 
.table td:nth-child(8) {
	min-width: 100px;
	text-align: center;
}

.table th:nth-child(9), 
.table td:nth-child(9) {
	min-width: 100px;
	text-align: center;
}

.table th:nth-child(10), 
.table td:nth-child(10) {
	min-width: 150px;
	text-align: center;
}

.table td div,
.table td span {
	overflow: hidden;
	text-overflow: ellipsis;
}

.table td small {
	display: block;
	font-size: 0.75rem;
	margin-top: 2px;
}

.btn-group-sm > .btn {
	padding: 0.25rem 0.375rem;
	font-size: 0.75rem;
	border-radius: 0.15rem;
	margin: 0 1px;
}

.badge {
	font-size: 0.7em;
	padding: 0.4em 0.6em;
	border-radius: 0.25rem;
}

.modal-xl {
	max-width: 95%;
}

.nav-tabs .nav-link {
	border: 1px solid transparent;
	border-radius: 0.5rem 0.5rem 0 0;
	transition: all 0.3s ease;
	font-weight: 500;
	padding: 0.75rem 1.25rem;
	margin-bottom: -1px;
	background: linear-gradient(145deg, #f8f9fa, #e9ecef);
}

.nav-tabs .nav-link:hover {
	border-color: #e9ecef #e9ecef #dee2e6;
	background: linear-gradient(145deg, #ffffff, #f8f9fa);
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.nav-tabs .nav-link.active {
	background: linear-gradient(145deg, #ffffff, #f8f9fa);
	border-color: #dee2e6 #dee2e6 #fff;
	font-weight: 600;
	color: #495057;
	box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
}

.tab-content {
	border: 1px solid #dee2e6;
	border-top: none;
	border-radius: 0 0 0.5rem 0.5rem;
	background: #fff;
	box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.tab-pane {
	padding: 1.5rem;
	animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
	from { opacity: 0; transform: translateY(10px); }
	to { opacity: 1; transform: translateY(0); }
}

.nav-tabs .badge {
	font-size: 0.65rem;
	padding: 0.25rem 0.5rem;
	border-radius: 50px;
	animation: pulse 2s infinite;
}

.nav-tabs .badge-warning {
	background: linear-gradient(45deg, #ffc107, #ffca2c);
	animation: glow 2s infinite alternate;
}

.nav-tabs .badge-danger {
	background: linear-gradient(45deg, #dc3545, #e85868);
	animation: urgentPulse 1.5s infinite;
}

@keyframes glow {
	from { box-shadow: 0 0 5px rgba(255, 193, 7, 0.5); }
	to { box-shadow: 0 0 15px rgba(255, 193, 7, 0.8); }
}

@keyframes urgentPulse {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.1); }
}

.bg-gradient-info {
	background: linear-gradient(87deg, #11cdef 0, #1171ef 100%) !important;
}

.bg-gradient-primary {
	background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
}

.bg-gradient-success {
	background: linear-gradient(87deg, #2dce89 0, #2dcecc 100%) !important;
}

.btn {
	transition: all 0.15s ease-in-out;
}

.btn:hover {
	transform: translateY(-1px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}

.badge-warning {
	animation: pulse 2s infinite;
}

.badge-danger {
	animation: shake 3s infinite;
}

@keyframes pulse {
	0% { opacity: 1; }
	50% { opacity: 0.7; }
	100% { opacity: 1; }
}

@keyframes shake {
	0%, 100% { transform: translateX(0); }
	10%, 30%, 50%, 70%, 90% { transform: translateX(-1px); }
	20%, 40%, 60%, 80% { transform: translateX(1px); }
}

.table tbody tr:hover {
	background-color: rgba(0,123,255,0.05);
	transform: scale(1.001);
	transition: all 0.2s ease;
}

.spinner-border {
	width: 2rem;
	height: 2rem;
}

#invoice-details .card {
	box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
	border: 1px solid rgba(0, 0, 0, 0.125);
	border-radius: 0.375rem;
}

#invoice-details .table {
	margin-bottom: 0;
}

#invoice-details .table th {
	border-top: none;
	font-weight: 600;
	color: #495057;
	font-size: 0.875rem;
}

#invoice-details .badge {
	font-size: 0.75rem;
	padding: 0.375rem 0.75rem;
}

@media print {
	body * {
		visibility: hidden;
	}
	#print-content, #print-content * {
		visibility: visible;
	}
	#print-content {
		position: absolute;
		left: 0;
		top: 0;
		width: 100%;
	}
	.modal, .modal-backdrop {
		display: none !important;
	}
}

@media (max-width: 768px) {
	.modal-xl {
		max-width: 98%;
		margin: 0.5rem;
	}
	
	.nav-tabs .nav-link {
		padding: 0.5rem 0.75rem;
		font-size: 0.85rem;
	}
	
	.nav-tabs .badge {
		font-size: 0.6rem;
		padding: 0.2rem 0.4rem;
	}
	
	.table-responsive {
		border: none;
		box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		margin: 0 -15px;
		border-radius: 0;
	}
	
	.table {
		min-width: 1000px; 
		font-size: 0.8rem;
	}
	
	.table th, 
	.table td {
		padding: 0.5rem 0.3rem;
		font-size: 0.75rem;
	}
	
	.table th {
		font-size: 0.7rem;
	}
	
	
	.btn-group-sm {
		flex-direction: column;
		width: 60px;
	}
	
	.btn-group-sm > .btn {
		margin: 1px 0;
		border-radius: 0.15rem !important;
		padding: 0.15rem 0.25rem;
		font-size: 0.65rem;
	}
	
	
	.table td:nth-child(3) {
		min-width: 150px;
		max-width: 180px;
	}
	
	.table td:nth-child(3) div {
		font-size: 0.75rem;
		line-height: 1.2;
	}
	
	.table td:nth-child(3) small {
		font-size: 0.65rem;
	}
}

@media (max-width: 576px) {
	.table {
		min-width: 1200px; 
		font-size: 0.75rem;
	}
	
	.table th, 
	.table td {
		padding: 0.4rem 0.2rem;
		font-size: 0.7rem;
	}
	
	.table th {
		font-size: 0.65rem;
	}
	
	.btn-group-sm > .btn {
		padding: 0.1rem 0.2rem;
		font-size: 0.6rem;
	}
	
	.btn-group-sm > .btn i {
		font-size: 0.6rem;
	}
}

#invoice-details::-webkit-scrollbar,
#print-content::-webkit-scrollbar {
	width: 6px;
}

#invoice-details::-webkit-scrollbar-track,
#print-content::-webkit-scrollbar-track {
	background: #f1f1f1;
	border-radius: 3px;
}

#invoice-details::-webkit-scrollbar-thumb,
#print-content::-webkit-scrollbar-thumb {
	background: #c1c1c1;
	border-radius: 3px;
}

#invoice-details::-webkit-scrollbar-thumb:hover,
#print-content::-webkit-scrollbar-thumb:hover {
	background: #a8a8a8;
}

.tab-summary-cards {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 1rem;
	border-radius: 0.5rem;
	margin-bottom: 1rem;
}

.summary-card {
	background: rgba(255,255,255,0.1);
	backdrop-filter: blur(10px);
	border: 1px solid rgba(255,255,255,0.2);
	border-radius: 0.5rem;
	padding: 1rem;
	text-align: center;
	transition: transform 0.2s ease;
}

.summary-card:hover {
	transform: translateY(-2px);
	background: rgba(255,255,255,0.15);
}

.summary-card h4 {
	margin: 0;
	font-size: 1.5rem;
	font-weight: bold;
}

.summary-card small {
	opacity: 0.8;
}

</style>

<script>
$(document).ready(function(){
	
	initializeDataTables();
	
	
	updateTabCounts();
	
	
	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var target = $(e.target).attr("href");
		var tableId = target.substring(1) + '-table';
		
		
		if ($.fn.DataTable.isDataTable('#' + tableId)) {
			$('#' + tableId).DataTable().columns.adjust().draw();
		}
	});
	
	
	initializeTooltips();
});

function initializeTooltips() {
	$('[data-toggle="tooltip"]').tooltip();
	$('[title]').tooltip();
}

$(document).on('click', '.view_invoice', function(e){
	e.preventDefault();
	e.stopPropagation();
	
	var invoice_id = $(this).data('id');
	console.log('View invoice clicked. Invoice ID:', invoice_id);
	
	if (!invoice_id) {
		console.error('No invoice ID found');
		alert('Error: Invoice ID not found');
		return;
	}
	
	var btn = $(this);
	var originalHtml = btn.html();
	
	
	btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
	
	
	$('#invoice-details').html(`
		<div class="text-center py-5">
			<div class="spinner-border text-info" role="status">
				<span class="sr-only">Loading...</span>
			</div>
			<p class="mt-3 h5 text-muted">Loading invoice details...</p>
		</div>
	`);
	
	
	$('#viewInvoiceModal').modal('show');
	
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=get_invoice_details",
		method: 'POST',
		data: {id: invoice_id},
		success: function(resp){
			console.log('Invoice details loaded successfully');
			$('#invoice-details').html(resp);
			$('#print-from-modal, #edit-from-modal, #email-invoice').attr('data-id', invoice_id);
			initializeTooltips(); 
		},
		error: function(xhr, status, error) {
			console.error('Error loading invoice:', error);
			$('#invoice-details').html(`
				<div class="text-center py-5">
					<i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
					<h5 class="text-danger">Error Loading Invoice</h5>
					<p class="text-muted">Unable to load invoice details. Please try again.</p>
					<button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
						<i class="fas fa-redo"></i> Refresh Page
					</button>
				</div>
			`);
		},
		complete: function() {
			
			btn.prop('disabled', false).html(originalHtml);
		}
	});
});

$(document).on('click', '.print_invoice, #print-from-modal', function(e){
	e.preventDefault();
	e.stopPropagation();
	var invoice_id = $(this).data('id');
	if (invoice_id) {
		showPrintPreview(invoice_id);
	} else {
		alert('Error: Invoice ID not found');
	}
});

$(document).on('click', '.edit_invoice, #edit-from-modal', function(e) {
	e.preventDefault();
	e.stopPropagation();
	
	const invoiceId = $(this).data('id');
	console.log('Edit invoice clicked. Invoice ID:', invoiceId);
	
	if (!invoiceId) {
		alert('Invoice ID not found.');
		return;
	}
	
	
	$('#editInvoiceModal').modal('show');
	$('#edit-invoice-content').html(`
		<div class="text-center py-5">
			<div class="spinner-border text-success" role="status">
				<span class="sr-only">Loading...</span>
			</div>
			<p class="mt-3 h5 text-muted">Loading invoice data...</p>
		</div>
	`);
	
	
	$.ajax({
		url: _base_url_ + 'classes/Invoices.php?f=get_invoice',
		method: 'POST',
		data: { id: invoiceId },
		dataType: 'json',
		success: function(resp) {
			if (resp.status === 'success') {
				const invoice = resp.data;
				let formHtml = `
					<input type="hidden" name="id" value="${invoice.id}">
					<div class="row">
						<div class="col-md-4 form-group">
							<label for="invoice_code">Invoice Code</label>
							<input type="text" class="form-control form-control-sm" name="invoice_code" value="${invoice.invoice_code || ''}" readonly>
						</div>
						<div class="col-md-4 form-group">
							<label for="customer_id">Customer</label>
							<input type="text" class="form-control form-control-sm" value="${invoice.customer_name}" readonly>
							<input type="hidden" name="customer_id" value="${invoice.customer_id}">
						</div>
						<div class="col-md-4 form-group">
							<label for="status">Status</label>
							<select name="status" id="status" class="form-control form-control-sm">
								<option value="0" ${invoice.status == 0 ? 'selected' : ''}>Pending</option>
								<option value="1" ${invoice.status == 1 ? 'selected' : ''}>Paid</option>
								<option value="2" ${invoice.status == 2 ? 'selected' : ''}>Partially Paid</option>
								<option value="3" ${invoice.status == 3 ? 'selected' : ''}>Overdue</option>
								<option value="4" ${invoice.status == 4 ? 'selected' : ''}>Cancelled</option>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4 form-group">
							<label for="invoice_date">Invoice Date</label>
							<input type="date" class="form-control form-control-sm" name="invoice_date" value="${invoice.invoice_date}" required>
						</div>
						<div class="col-md-4 form-group">
							<label for="due_date">Due Date</label>
							<input type="date" class="form-control form-control-sm" name="due_date" value="${invoice.due_date}" required>
						</div>
						<div class="col-md-4 form-group">
							<label for="order_id">Order Code</label>
							<input type="text" class="form-control form-control-sm" name="order_code" value="${invoice.order_code || ''}" readonly>
							<input type="hidden" name="order_id" value="${invoice.order_id}">
						</div>
					</div>
					<hr>
					<h5>Invoice Items</h5>
					<table class="table table-bordered table-sm" id="invoice-items-table">
						<thead>
							<tr>
								<th>Item Name</th>
								<th>Description</th>
								<th width="10%">Qty</th>
								<th width="15%">Price</th>
								<th width="15%">Total</th>
							</tr>
						</thead>
						<tbody>
				`;
				
				invoice.items.forEach(item => {
					formHtml += `
						<tr>
							<td><input type="text" class="form-control form-control-sm" name="item_name[]" value="${item.item_name}" required></td>
							<td><input type="text" class="form-control form-control-sm" name="item_description[]" value="${item.description}"></td>
							<td><input type="number" step="any" class="form-control form-control-sm item-qty" name="item_qty[]" value="${item.quantity}" required></td>
							<td><input type="number" step="any" class="form-control form-control-sm item-price" name="item_price[]" value="${item.unit_price}" required></td>
							<td><input type="number" step="any" class="form-control form-control-sm item-total" name="item_total[]" value="${item.total_price}" readonly></td>
						</tr>
					`;
				});

				formHtml += `
						</tbody>
					</table>
					<button type="button" class="btn btn-sm btn-info" id="add-item"><i class="fas fa-plus"></i> Add Item</button>
					<hr>
					<div class="row justify-content-end">
						<div class="col-md-4">
							<div class="form-group">
								<label for="subtotal">Subtotal</label>
								<input type="text" class="form-control form-control-sm text-right" id="subtotal" name="subtotal" value="${invoice.subtotal}" readonly>
							</div>
							<div class="form-group">
								<label for="tax_rate">Tax Rate (%)</label>
								<input type="number" step="any" class="form-control form-control-sm" id="tax_rate" name="tax_rate" value="${invoice.tax_rate}">
							</div>
							<div class="form-group">
								<label for="tax_amount">Tax Amount</label>
								<input type="text" class="form-control form-control-sm text-right" id="tax_amount" name="tax_amount" value="${invoice.tax_amount}" readonly>
							</div>
							<div class="form-group">
								<label for="total_amount">Total Amount</label>
								<input type="text" class="form-control form-control-sm text-right font-weight-bold" id="total_amount" name="total_amount" value="${invoice.total_amount}" readonly>
							</div>
							<div class="form-group">
								<label for="paid_amount">Paid Amount</label>
								<input type="number" step="any" class="form-control form-control-sm" id="paid_amount" name="paid_amount" value="${invoice.paid_amount}">
							</div>
							<div class="form-group">
								<label for="balance_amount">Balance</label>
								<input type="text" class="form-control form-control-sm text-right" id="balance_amount" name="balance_amount" value="${invoice.balance_amount}" readonly>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label for="notes">Notes</label>
						<textarea name="notes" id="notes" class="form-control form-control-sm">${invoice.notes}</textarea>
					</div>
				`;
				$('#edit-invoice-content').html(formHtml);
				calculateTotals();
			} else {
				$('#edit-invoice-content').html(`<div class="alert alert-danger">${resp.msg}</div>`);
			}
		},
		error: function() {
			$('#edit-invoice-content').html(`<div class="alert alert-danger">An error occurred while fetching invoice data.</div>`);
		}
	});
});

$(document).on('input', '.item-qty, .item-price', function() {
	const row = $(this).closest('tr');
	const qty = parseFloat(row.find('.item-qty').val()) || 0;
	const price = parseFloat(row.find('.item-price').val()) || 0;
	const total = qty * price;
	row.find('.item-total').val(total.toFixed(2));
	calculateTotals();
});

$(document).on('input', '#tax_rate, #paid_amount', function() {
	calculateTotals();
});

$(document).on('click', '#add-item', function() {
	const newRow = `
		<tr>
			<td><input type="text" class="form-control form-control-sm" name="item_name[]" required></td>
			<td><input type="text" class="form-control form-control-sm" name="item_description[]"></td>
			<td><input type="number" step="any" class="form-control form-control-sm item-qty" name="item_qty[]" required></td>
			<td><input type="number" step="any" class="form-control form-control-sm item-price" name="item_price[]" required></td>
			<td><input type="number" step="any" class="form-control form-control-sm item-total" name="item_total[]" readonly></td>
		</tr>
	`;
	$('#invoice-items-table tbody').append(newRow);
});

$(document).on('click', '#save-invoice-changes', function(e) {
	e.preventDefault();
	const formData = $('#edit-invoice-form').serialize();
	
	
	if ($('input[name="customer_id"]').val() === '') {
		alert_toast('Customer is required.', 'error');
		return;
	}
	
	Swal.fire({
		title: 'Saving Invoice',
		text: 'Please wait...',
		allowOutsideClick: false,
		allowEscapeKey: false,
		showConfirmButton: false,
		didOpen: () => {
			Swal.showLoading()
		}
	});
	
	$.ajax({
		url: _base_url_ + 'classes/Invoices.php?f=save_invoice',
		method: 'POST',
		data: formData,
		dataType: 'json',
		success: function(resp) {
			if (resp.status === 'success') {
				Swal.fire({
					title: 'Success!',
					text: resp.msg,
					icon: 'success',
					confirmButtonColor: '#28a745'
				}).then(() => {
					location.reload();
				});
			} else {
				alert_toast(resp.msg, 'error');
			}
		},
		error: function(xhr, status, error) {
			console.error('Save Error:', error);
			alert_toast('An unexpected error occurred. Please try again.', 'error');
		}
	});
});

$(document).on('click', '.delete_invoice', function(e){
	e.preventDefault();
	e.stopPropagation();
	
	var invoice_id = $(this).data('id');
	var invoice_code = $(this).closest('tr').find('td:nth-child(2) b').text();
	
	console.log('Delete invoice clicked. Invoice ID:', invoice_id);
	
	if (!invoice_id || invoice_id === '') {
		alert_toast('Unable to identify the invoice to delete.', 'error');
		return;
	}
	
	Swal.fire({
		title: 'Delete Invoice',
		html: `Are you sure you want to delete invoice <strong>${invoice_code}</strong>?<br><br>
			   <small class="text-danger">This will permanently delete:</small><br>
			   <small>• Invoice record</small><br>
			   <small>• All associated invoice items</small><br>
			   <small>• All payment records</small><br><br>
			   <strong class="text-danger">This action cannot be undone!</strong>`,
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#dc3545',
		cancelButtonColor: '#6c757d',
		confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete Forever',
		cancelButtonText: '<i class="fas fa-times"></i> Cancel',
		reverseButtons: true,
		focusCancel: true
	}).then((result) => {
		if (result.isConfirmed) {
			delete_invoice(invoice_id);
		}
	});
});

$(document).on('click', '#email-invoice', function(e){
	e.preventDefault();
	var invoice_id = $(this).data('id');
	
	if (!invoice_id) {
		alert('Error: Invoice ID not found');
		return;
	}
	
	Swal.fire({
		title: 'Send Invoice via Email',
		text: 'Send this invoice to the customer email address?',
		icon: 'info',
		showCancelButton: true,
		confirmButtonColor: '#28a745',
		cancelButtonColor: '#6c757d',
		confirmButtonText: '<i class="fas fa-envelope"></i> Send Email',
		cancelButtonText: '<i class="fas fa-times"></i> Cancel'
	}).then((result) => {
		if (result.isConfirmed) {
			send_invoice_email(invoice_id);
		}
	});
});

$(document).on('click', '#create-invoice', function(e){
	e.preventDefault();
	window.location.href = _base_url_ + "admin/?page=invoice/create_invoice";
});

function initializeDataTables() {
	const tableOptions = {
		"responsive": true,
		"processing": true,
		"order": [[ 0, "desc" ]],
		"columnDefs": [
			{ "orderable": false, "targets": -1 },
			{ "searchable": false, "targets": [0, -1] },
			{ "responsivePriority": 1, "targets": [1, 2, -1] },
			{ "responsivePriority": 2, "targets": [6, 8] },
		],
		"pageLength": 25,
		"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
		"dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
			   "<'row'<'col-sm-12'tr>>" +
			   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
		"language": {
			"search": "Search invoices:",
			"lengthMenu": "Show _MENU_ invoices per page",
			"info": "Showing _START_ to _END_ of _TOTAL_ invoices",
			"emptyTable": "No invoices found for this status",
			"zeroRecords": "No matching invoices found",
			"paginate": {
				"first": "First",
				"last": "Last",
				"next": "Next",
				"previous": "Previous"
			}
		},
		"drawCallback": function(settings) {
			updateTabCounts();
			initializeTooltips();
		},
		"initComplete": function(settings, json) {
			updateTabCounts();
			initializeTooltips();
		}
	};
	
	
	$('.invoice-table').each(function() {
		try {
			
			if ($(this).find('thead th').length > 0 && $(this).find('tbody').length > 0) {
				if (!$.fn.DataTable.isDataTable(this)) {
					$(this).DataTable(tableOptions);
				}
			}
		} catch(e) {
			console.error('Error initializing DataTable:', e);
		}
	});
	
	
	setTimeout(updateTabCounts, 200);
}

function updateTabCounts() {
	$('#all-count').text('0');
	$('#pending-count').text('0');
	$('#paid-count').text('0');
	$('#overdue-count').text('0');
	$('#partially-paid-count').text('0');
	
	$('.invoice-table').each(function() {
		const $table = $(this);
		const tableId = $table.attr('id');
		const tabName = tableId.replace('-invoices-table', '').replace('-table', '');
		let badgeId = '';
		
		switch(tabName) {
			case 'all':
				badgeId = 'all-count';
				break;
			case 'pending':
				badgeId = 'pending-count';
				break;
			case 'paid':
				badgeId = 'paid-count';
				break;
			case 'overdue':
				badgeId = 'overdue-count';
				break;
			case 'partially-paid':
				badgeId = 'partially-paid-count';
				break;
		}
		
		if (badgeId) {
			let count = 0;
			
			if ($.fn.DataTable.isDataTable($table)) {
				const dataTable = $table.DataTable();
				count = dataTable.rows({ filter: 'applied' }).count();
				
				const hasNoDataRow = $table.find('tbody tr td').hasClass('dataTables_empty') || 
								   $table.find('tbody tr td:contains("No invoices found")').length > 0;
				
				if (hasNoDataRow) {
					count = 0;
				}
			} else {
				const rows = $table.find('tbody tr');
				count = rows.length;
				
				if (rows.length === 1 && (
					rows.find('td:contains("No invoices found")').length > 0 ||
					rows.find('td').attr('colspan')
				)) {
					count = 0;
				}
			}
			
			$('#' + badgeId).text(count);
			
			const $badge = $('#' + badgeId);
			if (count === 0) {
				$badge.removeClass('badge-light badge-warning badge-success badge-danger badge-info')
					.addClass('badge-secondary');
			} else {
				$badge.removeClass('badge-secondary');
				switch(tabName) {
					case 'all':
						$badge.addClass('badge-light');
						break;
					case 'pending':
						$badge.addClass('badge-warning');
						break;
					case 'paid':
						$badge.addClass('badge-success');
						break;
					case 'overdue':
						$badge.addClass('badge-danger');
						break;
					case 'partially-paid':
						$badge.addClass('badge-info');
						break;
				}
			}
		}
	});
}

function showPrintPreview(invoice_id) {
	$('#print-content').html(`
		<div class="text-center py-5">
			<div class="spinner-border text-primary" role="status">
				<span class="sr-only">Loading...</span>
			</div>
			<p class="mt-3 h5 text-muted">Preparing print preview...</p>
		</div>
	`);
	
	$('#printPreviewModal').modal('show');
	
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=get_invoice_print_content",
		method: 'POST',
		data: {id: invoice_id},
		success: function(resp){
			$('#print-content').html(resp);
		},
		error: function(xhr, status, error) {
			console.error('Error loading print preview:', error);
			$('#print-content').html(`
				<div class="text-center py-5">
					<i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
					<h5 class="text-danger">Error Loading Preview</h5>
					<p class="text-muted">Unable to generate print preview. Please try again.</p>
				</div>
			`);
		}
	});
}

function calculateTotals() {
	let subtotal = 0;
	$('.item-total').each(function() {
		subtotal += parseFloat($(this).val()) || 0;
	});
	
	const taxRate = parseFloat($('#tax_rate').val()) || 0;
	const taxAmount = subtotal * (taxRate / 100);
	const totalAmount = subtotal + taxAmount;
	const paidAmount = parseFloat($('#paid_amount').val()) || 0;
	const balance = totalAmount - paidAmount;

	$('#subtotal').val(subtotal.toFixed(2));
	$('#tax_amount').val(taxAmount.toFixed(2));
	$('#total_amount').val(totalAmount.toFixed(2));
	$('#balance_amount').val(balance.toFixed(2));
}

function printInvoice() {
	var printContent = document.getElementById('print-content').innerHTML;
	var originalContent = document.body.innerHTML;
	
	document.body.innerHTML = printContent;
	window.print();
	document.body.innerHTML = originalContent;
	location.reload();
}

function delete_invoice(id) {
	if (!id || id === '') {
		alert_toast('Invalid invoice ID.', 'error');
		return;
	}
	
	Swal.fire({
		title: 'Deleting Invoice',
		text: 'Please wait...',
		allowOutsideClick: false,
		allowEscapeKey: false,
		showConfirmButton: false,
		didOpen: () => {
			Swal.showLoading()
		}
	});
	
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=delete_invoice",
		method: 'POST',
		data: {id: id},
		dataType: 'json',
		timeout: 10000,
		success: function(resp){
			console.log('Delete response:', resp);
			
			if(resp && resp.status == 'success'){
				Swal.fire({
					title: 'Deleted!',
					text: resp.msg || 'Invoice has been deleted successfully.',
					icon: 'success',
					confirmButtonColor: '#28a745'
				}).then(() => {
					location.reload();
				});
			} else {
				alert_toast(resp.msg || 'An error occurred while deleting the invoice.', 'error');
				if(resp.error) console.error('Technical details:', resp.error);
			}
		},
		error: function(xhr, status, error) {
			console.error('Delete AJAX Error:', {
				status: status,
				error: error,
				responseText: xhr.responseText
			});
			
			let errorMsg = 'An unexpected error occurred.';
			if (status === 'timeout') {
				errorMsg = 'The request timed out. Please try again.';
			} else if (xhr.status === 404) {
				errorMsg = 'The invoice deletion endpoint was not found.';
			} else if (xhr.status === 500) {
				errorMsg = 'Server error occurred. Please check the server logs.';
			}
			
			alert_toast(errorMsg, 'error');
			console.error('Status: ' + status + ' | Error: ' + error);
		}
	});
}

function send_invoice_email(id) {
	Swal.fire({
		title: 'Sending Email',
		text: 'Please wait...',
		allowOutsideClick: false,
		allowEscapeKey: false,
		showConfirmButton: false,
		didOpen: () => {
			Swal.showLoading()
		}
	});
	
	$.ajax({
		url: _base_url_ + "classes/Invoices.php?f=send_invoice_email",
		method: 'POST',
		data: {id: id},
		dataType: 'json',
		success: function(resp){
			if(resp.status == 'success'){
				alert_toast(resp.msg, 'success');
			} else {
				alert_toast(resp.msg || 'Failed to send email. Please try again.', 'error');
			}
		},
		error: function(xhr, status, error) {
			console.error('Email Error:', error);
			alert_toast('An error occurred while sending the email. Please check your email configuration.', 'error');
		}
	});
}

function refreshTable(tabType) {
	Swal.fire({
		title: 'Refreshing Data',
		text: 'Please wait...',
		allowOutsideClick: false,
		allowEscapeKey: false,
		showConfirmButton: false,
		didOpen: () => {
			Swal.showLoading()
		}
	});
	
	setTimeout(() => {
		location.reload();
	}, 1000);
}

function exportToExcel(tabType) {
	Swal.fire({
		title: 'Preparing Export',
		text: 'Generating Excel file...',
		allowOutsideClick: false,
		allowEscapeKey: false,
		showConfirmButton: false,
		didOpen: () => {
			Swal.showLoading()
		}
	});
	
	setTimeout(() => {
		alert_toast('Your Excel file is being downloaded.', 'success');
		
		window.location.href = _base_url_ + "classes/Invoices.php?f=export_invoices&type=" + tabType;
	}, 2000);
}

function sendReminderEmails(tabType) {
	Swal.fire({
		title: 'Send Reminder Emails?',
		text: `This will send reminder emails to all customers with ${tabType} invoices.`,
		icon: 'question',
		showCancelButton: true,
		confirmButtonColor: '#28a745',
		cancelButtonColor: '#6c757d',
		confirmButtonText: '<i class="fas fa-envelope"></i> Send Reminders',
		cancelButtonText: '<i class="fas fa-times"></i> Cancel'
	}).then((result) => {
		if (result.isConfirmed) {
			Swal.fire({
				title: 'Sending Reminders',
				text: 'Please wait while we send the emails...',
				allowOutsideClick: false,
				allowEscapeKey: false,
				showConfirmButton: false,
				didOpen: () => {
					Swal.showLoading()
				}
			});
			
			$.ajax({
				url: _base_url_ + "classes/Invoices.php?f=send_reminder_emails",
				method: 'POST',
				data: {type: tabType},
				dataType: 'json',
				success: function(resp){
					if(resp.status == 'success'){
						alert_toast(resp.msg, 'success');
					} else {
						alert_toast(resp.msg || 'Failed to send reminders. Please try again.', 'error');
					}
				},
				error: function(xhr, status, error) {
					console.error('Reminder Error:', error);
					alert_toast('An error occurred while sending reminders. Please try again.', 'error');
				}
			});
		}
	});
}

function generateReport(tabType) {
	Swal.fire({
		title: 'Generate Report',
		text: 'Creating detailed report...',
		allowOutsideClick: false,
		allowEscapeKey: false,
		showConfirmButton: false,
		didOpen: () => {
			Swal.showLoading()
		}
	});
	
	setTimeout(() => {
		alert_toast('Your report is ready for download.', 'success');
		
		window.location.href = _base_url_ + "classes/Invoices.php?f=generate_report&type=" + tabType;
	}, 2000);
}
</script>
