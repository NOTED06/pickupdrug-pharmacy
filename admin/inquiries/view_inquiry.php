<?php 
if(isset($_GET['id']) && $_GET['id'] > 0){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE `inquiry_list` set `status` = 1 where id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT * from `inquiry_list` where id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $qry = $stmt->get_result();
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            if(!is_numeric($k))
                $$k = $v;
        }
    }
    $stmt->close();
}
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <label class="control-label text-muted">Inquirer Fullname</label>
            <div class="pl-4 font-weight-bolder"><?= isset($fullname) ? $fullname : '' ?></div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="control-label text-muted">Email</label>
            <div class="pl-4 font-weight-bolder"><?= isset($email) && !empty($email) ? $email : 'N/A' ?></div>
        </div>
        <div class="col-md-6">
            <label class="control-label text-muted">Contact #</label>
            <div class="pl-4 font-weight-bolder"><?= isset($contact) ? $contact : '' ?></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <label class="control-label text-muted">Message</label>
            <div class="pl-4 border rounded p-2 bg-light"><?= isset($message) ? (str_replace(["\n\r", "\n", "\r"], '<br>', $message)) : '' ?></div>
        </div>
    </div>
</div>
<div class="text-right mt-3">
    <button id="delete_inquiry" class="btn btn-danger btn-flat bg-gradient-danger btn-sm" type="button"><i class="fa fa-trash"></i> Delete</button>
    <button class="btn btn-secondary btn-flat bg-gradient-secondary btn-sm" type="button" data-dismiss="modal">Close</button>
</div>
<script>
    $(function(){
        $('#delete_inquiry').click(function(){
			_conf("Are you sure to delete this Inquiry permanently?","delete_inquiry",['<?= isset($id) ? $id : '' ?>'])
		})
    })
</script>