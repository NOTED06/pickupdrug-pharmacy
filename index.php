<?php require_once('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<?php 

$page = isset($_GET['p']) ? $_GET['p'] : 'home';
$page = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $page); 
$page = str_replace('..', '', $page); 
$page = trim($page, '/'); 
if(empty($page)) $page = 'home';
?>
<?php require_once('inc/header.php') ?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>
  $(function(){
    alert_toast(<?php echo json_encode($_settings->flashdata('success')) ?>,'success')
  })
</script>
<?php endif;?>
<body>
<?php 

if($page != 'home') {
    require_once('inc/topBarNav.php');
}
?>
<?php 
    
    if(file_exists($page.".php")) {
        include $page.'.php';
    } elseif(file_exists('customer/'.$page.'.php')) {
        include 'customer/'.$page.'.php';
    } elseif(is_dir($page) && file_exists($page.'/index.php')) {
        include $page.'/index.php';
    } else {
        include '404.html';
    }
?>
<?php if($page != 'home') require_once('inc/footer.php') ?>

<?php if($page != 'home'): ?>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog   rounded-0 modal-md modal-dialog-centered" role="document">
      <div class="modal-content  rounded-0">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal_right" role='dialog'>
    <div class="modal-dialog  rounded-0 modal-full-height  modal-md" role="document">
      <div class="modal-content rounded-0">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        </button>
      </div>
      <div class="modal-body">
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>
<?php endif; ?>

</body>
<script>

document.addEventListener("DOMContentLoaded", function() {
  
  removeCredit();

  
  setInterval(removeCredit, 500);

  function removeCredit() {
    document.querySelectorAll("div").forEach(el => {
      if (el.innerText && el.innerText.includes("Developed by oretnom23")) {
        el.remove();
      }
    });
  }
});
</script>

</html>