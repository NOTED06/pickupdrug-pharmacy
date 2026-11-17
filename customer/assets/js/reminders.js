

function addTimeField() {
    const container = document.getElementById('reminderTimes');
    const input = document.createElement('input');
    input.type = 'time';
    input.className = 'form-control mb-2';
    input.name = 'reminder_times[]';
    input.required = true;
    container.appendChild(input);
}

$('#addReminderForm').submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'create');
    
    $.ajax({
        url: 'reminders.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Reminder created successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.error);
            }
        },
        error: function() {
            alert('Failed to create reminder. Please try again.');
        }
    });
});

function pauseReminder(id) {
    if (confirm('Pause this reminder?')) {
        $.post('reminders.php', {action: 'pause', reminder_id: id}, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.error);
            }
        }, 'json');
    }
}

function resumeReminder(id) {
    $.post('reminders.php', {action: 'resume', reminder_id: id}, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Error: ' + response.error);
        }
    }, 'json');
}

function completeReminder(id) {
    if (confirm('Mark this reminder as completed?')) {
        $.post('reminders.php', {action: 'complete', reminder_id: id}, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.error);
            }
        }, 'json');
    }
}

function deleteReminder(id) {
    if (confirm('Delete this reminder permanently?')) {
        $.post('reminders.php', {action: 'delete', reminder_id: id}, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.error);
            }
        }, 'json');
    }
}

function editReminder(id) {
    $.post('reminders.php', {action: 'get_reminder', reminder_id: id}, function(response) {
        if (response.success && response.data) {
            const r = response.data;
            $('#edit_reminder_id').val(r.id);
            $('#edit_medicine_name').val(r.medicine_name);
            $('#edit_dosage').val(r.dosage);
            $('#edit_frequency').val(r.frequency);
            $('#edit_start_date').val(r.start_date);
            $('#edit_end_date').val(r.end_date || '');
            $('#edit_duration_days').val(r.duration_days || '');
            $('#edit_instructions').val(r.instructions || '');
            $('#edit_timing_food').val(r.timing_food || '');
            $('#edit_interactions_medicines').val(r.interactions_medicines || '');
            $('#edit_interactions_alcohol').prop('checked', r.interactions_alcohol == 1);
            $('#edit_side_effects').val(r.side_effects || '');
            $('#edit_missed_dose_instructions').val(r.missed_dose_instructions || '');
            
            
            const container = $('#edit_times_container');
            container.empty();
            if (r.reminder_times && r.reminder_times.length > 0) {
                r.reminder_times.forEach(function(time) {
                    const timeStr = time.substring(0, 5); 
                    container.append('<div class="input-group mb-2">' +
                        '<input type="time" class="form-control" name="reminder_times[]" value="' + timeStr + '" required>' +
                        '<div class="input-group-append">' +
                        '<button type="button" class="btn btn-danger" onclick="$(this).closest(\'.input-group\').remove()">' +
                        '<i class="fas fa-times"></i></button></div></div>');
                });
            } else {
                addEditTimeInput();
            }
            
            $('#editReminderModal').modal('show');
        } else {
            alert('Error loading reminder: ' + (response.error || 'Unknown error'));
        }
    }, 'json');
}

function addEditTimeInput() {
    $('#edit_times_container').append('<div class="input-group mb-2">' +
        '<input type="time" class="form-control" name="reminder_times[]" value="08:00" required>' +
        '<div class="input-group-append">' +
        '<button type="button" class="btn btn-danger" onclick="$(this).closest(\'.input-group\').remove()">' +
        '<i class="fas fa-times"></i></button></div></div>');
}

function setupMultipleDoses() {
    
    const times = ['06:00', '10:00', '14:00', '18:00', '22:00'];
    const container = $('#edit_times_container');
    container.empty();
    times.forEach(function(time) {
        container.append('<div class="input-group mb-2">' +
            '<input type="time" class="form-control" name="reminder_times[]" value="' + time + '" required>' +
            '<div class="input-group-append">' +
            '<button type="button" class="btn btn-danger" onclick="$(this).closest(\'.input-group\').remove()">' +
            '<i class="fas fa-times"></i></button></div></div>');
    });
}

$('#editReminderForm').submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'update');
    
    
    const times = [];
    $(this).find('input[name="reminder_times[]"]').each(function() {
        times.push($(this).val() + ':00');
    });
    formData.set('reminder_times', JSON.stringify(times));
    
    $.ajax({
        url: 'reminders.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Reminder updated successfully!');
                $('#editReminderModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'Failed to update reminder'));
            }
        },
        error: function() {
            alert('Failed to update reminder. Please try again.');
        }
    });
});

$(document).on('click', '[data-action="edit-reminder"]', function() {
    const reminderId = $(this).data('reminder-id');
    editReminder(reminderId);
});
