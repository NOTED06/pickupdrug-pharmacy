

$(document).ready(function() {
    
    updateUnreadCount();
    
    
    $('[data-toggle="tooltip"]').tooltip();
    
    
    $('.mark-read-btn').click(function(e){
        e.preventDefault();
        e.stopPropagation();
        markNotificationAsRead($(this));
    });
    
    
    $('.delete-notif-btn').click(function(e){
        e.preventDefault();
        e.stopPropagation();
        deleteNotification($(this));
    });
    
    
    $('#mark-all-read').click(function(){
        markAllNotificationsAsRead();
    });
});

function updateUnreadCount() {
    var unreadCount = $('.notification-list-item.unread').length;
    $('#unreadCount').text(unreadCount);
}

function markNotificationAsRead(btn) {
    var id = btn.closest('.notification-list-item').data('id');
    var item = btn.closest('.notification-list-item');
    
    $.ajax({
        url: 'ajax.php?action=mark_notification_read',
        method: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success'){
                item.removeClass('unread');
                btn.remove();
                
                
                updateUnreadCount();
                
                
                var badge = $('.notification-badge');
                if(badge.length > 0){
                    var count = parseInt(badge.text()) - 1;
                    if(count <= 0) {
                        badge.hide();
                    } else {
                        badge.text(count);
                    }
                }
            }
        },
        error: function(){
            console.error('Failed to mark notification as read');
        }
    });
}

function deleteNotification(btn) {
    var id = btn.closest('.notification-list-item').data('id');
    var item = btn.closest('.notification-list-item');
    
    Swal.fire({
        title: 'Delete Notification?',
        text: 'Remove this notification?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#f3f4f6',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'compact-swal',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax.php?action=delete_notification',
                method: 'POST',
                data: {id: id},
                dataType: 'json',
                success: function(resp){
                    if(resp.status == 'success'){
                        item.slideUp(300, function(){ 
                            $(this).remove(); 
                            
                            
                            updateUnreadCount();
                            
                            
                            if($('#notification-list .list-group-item').length == 0){
                                location.reload();
                            }
                        });
                        
                        
                        alert_toast('Notification deleted successfully', 'success');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: resp.msg || 'Failed to delete notification',
                            width: '300px',
                            confirmButtonColor: '#14b8a6',
                            customClass: {
                                popup: 'compact-swal'
                            }
                        });
                    }
                },
                error: function(){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to delete notification',
                        width: '300px',
                        confirmButtonColor: '#14b8a6',
                        customClass: {
                            popup: 'compact-swal'
                        }
                    });
                }
            });
        }
    });
}

function markAllNotificationsAsRead() {
    $.ajax({
        url: 'ajax.php?action=mark_all_notifications_read',
        method: 'POST',
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success'){
                
                $('.notification-list-item').removeClass('unread');
                
                
                $('.mark-read-btn').remove();
                
                
                $('.notification-badge').hide();
                
                
                updateUnreadCount();
                
                
                alert_toast('All notifications marked as read', 'success');
                
                
                if(typeof loadNotifications === 'function') {
                    loadNotifications();
                }
            } else {
                alert_toast(resp.msg || 'Error marking notifications', 'error');
            }
        },
        error: function(){
            alert_toast('Failed to mark notifications as read', 'error');
        }
    });
}
