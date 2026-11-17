<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * from `category_list` where id = ? and delete_flag = 0");
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
<div class="container-fluid">
    <dl>
        <dt class="text-muted">Category</dt>
        <dd class="pl-4"><?= isset($name) ? $name : "" ?></dd>
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
    </dl>
</div>
<div class="text-right">
    <button class="btn btn-primary btn-sm bg-gradient-primary rounded-0" type="button" id="edit_data"><i class="fa fa-edit"></i> Edit</button>
    <button class="btn btn-secondary btn-sm bg-gradient-secondary rounded-0" type="button" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
</div>
<script>
    $(function(){
        $('#edit_data').click(function(){
            uni_modal("Update Category Details","categories/manage_category.php?id=<?= isset($id) ? $id : '' ?>")
        })
    })
</script>