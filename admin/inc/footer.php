<script>
  $(document).ready(function(){
     window.viewer_modal = function($src = ''){
      start_loader()
      var t = $src.split('.')
      t = t[1]
      if(t =='mp4'){
        var view = $("<video src='"+$src+"' controls autoplay></video>")
      }else{
        var view = $("<img src='"+$src+"' />")
      }
      $('#viewer_modal .modal-content video,#viewer_modal .modal-content img').remove()
      $('#viewer_modal .modal-content').append(view)
      $('#viewer_modal').modal({
              show:true,
              backdrop:'static',
              keyboard:false,
              focus:true
            })
            end_loader()  

  }
    window.uni_modal = function($title = '' , $url='',$size="", $backdrop="static", $options={}){
        
        if($options && $options.content){
            $('#uni_modal .modal-title').html($title)
            $('#uni_modal .modal-body').html($options.content)
            if($size != ''){
                $('#uni_modal .modal-dialog').addClass($size+'  modal-dialog-centered')
            }else{
                $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md modal-dialog-centered")
            }
            $('#uni_modal').modal({
              show:true,
              backdrop: $backdrop,
              keyboard:false,
              focus:true
            })
            return;
        }
        
        
        start_loader()
        $.ajax({
            url:$url,
            error:err=>{
                console.log(err)
                alert("An error occured")
                end_loader()
            },
            success:function(resp){
                if(resp){
                    $('#uni_modal .modal-title').html($title)
                    $('#uni_modal .modal-body').html(resp)
                    if($size != ''){
                        $('#uni_modal .modal-dialog').addClass($size+'  modal-dialog-centered')
                    }else{
                        $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md modal-dialog-centered")
                    }
                    $('#uni_modal').modal({
                      show:true,
                      backdrop: $backdrop,
                      keyboard:false,
                      focus:true
                    })
                    end_loader()
                } else {
                    end_loader()
                }
            }
        })
    }
    window._conf = function($msg='',$func='',$params = []){
       var $btn = $('#confirm_modal #confirm');
       $btn.off('click.confirm').removeAttr('onclick');
       $btn.on('click.confirm', function(){
         var fn = typeof window[$func] === 'function' ? window[$func] : null;
         if (fn) fn.apply(null, $params.map(function(p){ return (p !== '' && !isNaN(p)) ? +p : p; }));
       });
       $('#confirm_modal .modal-body').html($msg);
       $('#confirm_modal').modal('show');
       $('#confirm_modal').one('shown.bs.modal', function(){ $btn.focus(); });
    }
    
    
    $(document).on('submit', '#take-action-form', function(e) {
        e.preventDefault();
        var _this = $(this);
        var formData = new FormData(this);
        
        console.log('Submitting order status update...');
        console.log('Order ID:', formData.get('id'));
        console.log('New Status:', formData.get('status'));
        
        $('.err-msg').remove();
        start_loader();
        
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=update_order_status",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                console.log('Response:', xhr.responseText);
                alert_toast("An error occurred: " + error, 'error');
                end_loader();
            },
            success: function(resp) {
                console.log('Response received:', resp);
                if(typeof resp == 'object' && resp.status == 'success'){
                    alert_toast("Order status updated successfully!", 'success');
                    setTimeout(function(){
                        location.reload();
                    }, 500);
                } else if(resp.status == 'failed' && !!resp.msg){
                    var el = $('<div>');
                    el.addClass("alert alert-danger err-msg").text(resp.msg);
                    _this.prepend(el);
                    el.show('slow');
                    end_loader();
                } else {
                    alert_toast("An error occurred", 'error');
                    end_loader();
                    console.log('Unexpected response:', resp);
                }
            }
        });
    });

    
    $(document).on('hidden.bs.modal', '.modal', function () {
        var visibleModals = $('.modal.show:visible');
        if (visibleModals.length === 0) {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
            $('.modal').not('.show').css('display', 'none');
        } else {
            $('body').addClass('modal-open');
            var backdrops = $('.modal-backdrop');
            if (backdrops.length > visibleModals.length) {
                backdrops.slice(visibleModals.length).remove();
            }
        }
        end_loader();
    });
  })
</script>
<footer class="main-footer text-sm">
        <strong>Copyright © <?php echo date('Y') ?>. 
        
        </strong>
        All rights reserved.
        
      </footer>
    </div>
    
   
    
    <script>
      $.widget.bridge('uibutton', $.ui.button)
    </script>
    
    <script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <script src="<?php echo base_url ?>plugins/chart.js/Chart.min.js"></script>
    
    <script src="<?php echo base_url ?>plugins/sparklines/sparkline.js"></script>
    
    <script src="<?php echo base_url ?>plugins/select2/js/select2.full.min.js"></script>
    
    <script src="<?php echo base_url ?>plugins/jqvmap/jquery.vmap.min.js"></script>
    <script src="<?php echo base_url ?>plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
    
    <script src="<?php echo base_url ?>plugins/jquery-knob/jquery.knob.min.js"></script>
    
    <script src="<?php echo base_url ?>plugins/moment/moment.min.js"></script>
    <script src="<?php echo base_url ?>plugins/daterangepicker/daterangepicker.js"></script>
    
    <script src="<?php echo base_url ?>plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    
    <script src="<?php echo base_url ?>plugins/summernote/summernote-bs4.min.js"></script>
    <script src="<?php echo base_url ?>plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo base_url ?>plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="<?php echo base_url ?>plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="<?php echo base_url ?>plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    
    
    
    <script src="<?php echo base_url ?>dist/js/adminlte.js"></script>
    <div class="daterangepicker ltr show-ranges opensright">
      <div class="ranges">
        <ul>
          <li data-range-key="Today">Today</li>
          <li data-range-key="Yesterday">Yesterday</li>
          <li data-range-key="Last 7 Days">Last 7 Days</li>
          <li data-range-key="Last 30 Days">Last 30 Days</li>
          <li data-range-key="This Month">This Month</li>
          <li data-range-key="Last Month">Last Month</li>
          <li data-range-key="Custom Range">Custom Range</li>
        </ul>
      </div>
      <div class="drp-calendar left">
        <div class="calendar-table"></div>
        <div class="calendar-time" style="display: none;"></div>
      </div>
      <div class="drp-calendar right">
        <div class="calendar-table"></div>
        <div class="calendar-time" style="display: none;"></div>
      </div>
      <div class="drp-buttons"><span class="drp-selected"></span><button class="cancelBtn btn btn-sm btn-default" type="button">Cancel</button><button class="applyBtn btn btn-sm btn-primary" disabled="disabled" type="button">Apply</button> </div>
    </div>
    <div class="jqvmap-label" style="display: none; left: 1093.83px; top: 394.361px;">Idaho</div>