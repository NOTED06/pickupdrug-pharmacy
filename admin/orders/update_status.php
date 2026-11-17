<?php 
require_once('../../config.php');
require_once('../../app/helpers/SecurityHelper.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * from `order_list` where id = ?");
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
    <form action="" id="take-action-form">
        <?= SecurityHelper::csrfField() ?>
        <input type="hidden" name="id" value="<?= isset($id) ? $id : '' ?>">
        <div class="form-group">
            <label for="status" class="control-label">Status</label>
            <select class="form-control form-control-sm rounded-0" name="status" id="status" required="required">
                <option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>Pending</option>
                <option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>Packed</option>
                <option value="2" <?= isset($status) && $status == 2 ? 'selected' : '' ?>>Ready for Pick Up</option>
                <option value="3" <?= isset($status) && $status == 3 ? 'selected' : '' ?>>Done</option>
            </select>
        </div>
    </form>
</div>