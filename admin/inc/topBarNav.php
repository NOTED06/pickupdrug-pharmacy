<style>
  .user-img{
        position: absolute;
        height: 27px;
        width: 27px;
        object-fit: cover;
        left: -7%;
        top: -12%;
  }
  .btn-rounded{
        border-radius: 50px;
  }
</style>

      <nav class="main-header navbar navbar-expand navbar-light shadow text-sm">
        
        <ul class="navbar-nav">
          <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo base_url ?>" class="nav-link"><?php echo (!isMobileDevice()) ? $_settings->info('name'):$_settings->info('short_name'); ?> - Admin</a>
          </li>
        </ul>
        
        <ul class="navbar-nav ml-auto">
          
          <li class="nav-item dropdown" id="admin-notification-dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" role="button" title="Notifications">
              <i class="far fa-bell"></i>
              <span class="badge badge-danger navbar-badge" id="admin-notif-count" style="display:none;">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="max-height:400px;overflow-y:auto;">
              <span class="dropdown-item dropdown-header d-flex justify-content-between align-items-center">
                <span>Notifications</span>
                <a href="#" id="admin-mark-all-read" class="text-sm text-muted">Mark all read</a>
              </span>
              <div class="dropdown-divider"></div>
              <div id="admin-notif-list">
                <span class="dropdown-item text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</span>
              </div>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item dropdown-footer" id="admin-notif-see-all">See All Notifications</a>
            </div>
          </li>
          
          
          
          <li class="nav-item">
            <div class="btn-group nav-link">
                  <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2 user-img" alt="User Image"></span>
                    <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <span class="sr-only">Toggle Dropdown</span>
                  </button>
                  <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="<?php echo base_url.'admin/?page=user' ?>"><span class="fa fa-user"></span> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
                  </div>
              </div>
          </li>
          <li class="nav-item">
            
          </li>
         
        </ul>
      </nav>
      

<script>
$(document).ready(function(){
    var _base_url = '<?php echo base_url ?>';
    
    function loadAdminNotifications(){
        $.ajax({
            url: _base_url + 'classes/Master.php?f=get_admin_notifications',
            type: 'GET',
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    var count = resp.unread_count || 0;
                    var $badge = $('#admin-notif-count');
                    if(count > 0){
                        $badge.text(count > 99 ? '99+' : count).show();
                    } else {
                        $badge.hide();
                    }
                    
                    var html = '';
                    if(resp.data && resp.data.length > 0){
                        resp.data.slice(0, 10).forEach(function(n){
                            var priorityIcon = '';
                            var bgClass = n.is_read == 0 ? 'bg-light' : '';
                            if(n.priority === 'urgent'){
                                priorityIcon = '<i class="fas fa-exclamation-triangle text-danger mr-1"></i>';
                                if(n.is_read == 0) bgClass = 'bg-danger-light';
                            } else if(n.priority === 'warning'){
                                priorityIcon = '<i class="fas fa-exclamation-circle text-warning mr-1"></i>';
                            }
                            
                            html += '<a href="#" class="dropdown-item admin-notif-item '+ bgClass +'" data-id="'+n.id+'" data-order="'+(n.order_id||'')+'">';
                            html += '<div class="d-flex align-items-start">';
                            html += '<div class="flex-grow-1" style="white-space:normal;max-width:280px;">';
                            html += '<strong class="text-sm">'+priorityIcon+ n.title+'</strong>';
                            html += '<p class="text-xs text-muted mb-0" style="line-height:1.3;">'+n.message.substring(0,120)+(n.message.length>120?'...':'')+'</p>';
                            html += '</div>';
                            html += '<span class="text-xs text-muted ml-2 text-nowrap">'+n.time_ago+'</span>';
                            html += '</div></a>';
                            html += '<div class="dropdown-divider"></div>';
                        });
                    } else {
                        html = '<span class="dropdown-item text-center text-muted py-3">No notifications</span>';
                    }
                    $('#admin-notif-list').html(html);
                }
            }
        });
    }
    
    
    $(document).on('click', '.admin-notif-item', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var orderId = $(this).data('order');
        
        $.post(_base_url + 'classes/Master.php?f=mark_admin_notification_read', {id: id}, function(){
            loadAdminNotifications();
        }, 'json');
        
        if(orderId){
            window.location.href = _base_url + 'admin/?page=orders&view_order=' + orderId;
        }
    });
    
    
    $('#admin-mark-all-read').on('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        $.post(_base_url + 'classes/Master.php?f=mark_admin_notification_read', {id: 0}, function(){
            loadAdminNotifications();
        }, 'json');
    });
    
    
    $('#admin-notif-see-all').on('click', function(e){
        e.preventDefault();
        
        window.location.href = _base_url + 'admin/?page=orders';
    });
    
    
    loadAdminNotifications();
    
    
    setInterval(loadAdminNotifications, 30000);
});
</script>
<style>
.bg-danger-light { background-color: #fff5f5 !important; }
#admin-notif-list .admin-notif-item:hover { background-color: #f0f0f0 !important; }
#admin-notification-dropdown .dropdown-menu { min-width: 350px; }
</style>