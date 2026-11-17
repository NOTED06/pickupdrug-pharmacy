<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * from `stock_list` where id = ?");
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
    <form action="" id="stock-form">
        <?php
        require_once('../../app/helpers/SecurityHelper.php');
        echo SecurityHelper::csrfField();
        ?>
        <input type="hidden" name="id" value='<?= isset($id) ? $id : '' ?>'>
        <input type="hidden" name="product_id" value='<?= isset($product_id) ? $product_id : (isset($_GET['pid']) ? $_GET['pid'] : '') ?>'>
        
        <div class="form-group">
            <label for="code" class="control-label">Stock Code</label>
            <input type="text" class="form-control form-control-sm rounded-0" name="code" id="code" required="required" value="<?= isset($code) ? $code : '' ?>" placeholder="e.g., BATCH-2025-001">
            <small class="form-text text-muted">
                <i class="fa fa-info-circle"></i> Unique identifier for this stock batch
            </small>
        </div>
        
        <div class="form-group">
            <label for="quantity" class="control-label">Quantity</label>
            <input type="number" class="form-control form-control-sm rounded-0 text-right" name="quantity" id="quantity" required="required" value="<?= isset($quantity) ? $quantity : '' ?>" min="1" step="1" placeholder="Enter quantity">
            <small class="form-text text-muted">
                <i class="fa fa-info-circle"></i> Number of units in this stock batch
            </small>
        </div>
        
        <div class="form-group">
            <label for="expiration" class="control-label">Expiration Date</label>
            <input type="date" class="form-control form-control-sm rounded-0" name="expiration" id="expiration" value="<?= isset($expiration) && !empty($expiration) ? date("Y-m-d", strtotime($expiration)) : '' ?>" min="<?= date('Y-m-d') ?>">
            <small class="form-text text-muted">
                <i class="fa fa-info-circle"></i> Leave blank if product has no expiration date
            </small>
        </div>
    </form>
</div>

<script>
$(function(){
    
    $('#code').on('focus', function() {
        if($(this).val() === '') {
            const timestamp = Date.now();
            const randomNum = Math.floor(Math.random() * 1000);
            $(this).val('BATCH-' + timestamp + '-' + randomNum);
        }
    });
    
    
    $('#expiration').on('change', function() {
        const selectedDate = new Date($(this).val());
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if(selectedDate < today) {
            alert_toast('Warning: You are setting an expiration date in the past', 'warning');
        }
    });
    
    
    $('#stock-form').submit(function(e){
        e.preventDefault();
        var _this = $(this)
        $('.err-msg').remove();
        
        var el = $('<div>');
        el.addClass("alert alert-danger err-msg");
        el.hide();
        
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_stock",
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error: err => {
                console.log(err)
                alert_toast("An error occurred", 'error');
                end_loader();
            },
            success: function(resp){
                if(typeof resp == 'object' && resp.status == 'success'){
                    alert_toast(resp.msg || 'Stock entry saved successfully', 'success');
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                } else if(resp.status == 'failed' && !!resp.msg){
                    el.text(resp.msg);
                    _this.prepend(el);
                    el.show('slow');
                    $("html, body, .modal").scrollTop(0);
                    end_loader();
                } else {
                    alert_toast("An error occurred", 'error');
                    end_loader();
                }
            }
        })
    })
})
</script>