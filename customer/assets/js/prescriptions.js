

let currentAISchedule = [];

function generateTimePickers(medIndex, times) {
    
    const normalizedTimes = times.map(t => normalizeTimeFormat(t)).filter(t => t);
    
    
    if (normalizedTimes.length === 0) {
        normalizedTimes.push('08:00');
    }
    
    return normalizedTimes.map((time, timeIndex) => `
        <div class="time-picker-wrapper" style="display: flex; align-items: center; gap: 2px;">
            <input type="time" class="form-control form-control-sm med-time-picker" 
                   data-med-index="${medIndex}" data-time-index="${timeIndex}"
                   value="${time}" 
                   style="font-size: 12px; padding: 4px 6px; border-radius: 4px; width: 100px; border: 1px solid #99f6e4;">
            ${normalizedTimes.length > 1 ? `
                <button type="button" class="btn btn-sm remove-time-btn" data-med-index="${medIndex}" data-time-index="${timeIndex}"
                        style="background: none; border: none; color: #ef4444; padding: 2px 4px; font-size: 10px;">
                    <i class="fas fa-times"></i>
                </button>
            ` : ''}
        </div>
    `).join('');
}

function normalizeTimeFormat(timeStr) {
    if (!timeStr) return '';
    
    timeStr = timeStr.trim().toLowerCase();
    
    
    if (/^\d{1,2}:\d{2}$/.test(timeStr)) {
        const [h, m] = timeStr.split(':').map(Number);
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    }
    
    
    const match = timeStr.match(/^(\d{1,2}):?(\d{2})?\s*(am|pm)?$/i);
    if (match) {
        let hours = parseInt(match[1], 10);
        const minutes = match[2] ? parseInt(match[2], 10) : 0;
        const period = match[3] ? match[3].toLowerCase() : null;
        
        if (period === 'pm' && hours < 12) hours += 12;
        if (period === 'am' && hours === 12) hours = 0;
        
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }
    
    return '08:00'; 
}

function getTimesFromPickers(medIndex) {
    const times = [];
    $(`.med-time-picker[data-med-index="${medIndex}"]`).each(function() {
        const time = $(this).val();
        if (time) {
            times.push(time);
        }
    });
    return times.join(', ');
}

function addTimePicker(medIndex) {
    const container = $(`.time-pickers-container[data-med-index="${medIndex}"]`);
    const existingPickers = container.find('.med-time-picker').length;
    
    if (existingPickers >= 6) {
        showToast('error', 'Maximum 6 reminder times allowed');
        return;
    }
    
    const newTimeIndex = existingPickers;
    const newPicker = $(`
        <div class="time-picker-wrapper" style="display: flex; align-items: center; gap: 2px;">
            <input type="time" class="form-control form-control-sm med-time-picker" 
                   data-med-index="${medIndex}" data-time-index="${newTimeIndex}"
                   value="12:00" 
                   style="font-size: 12px; padding: 4px 6px; border-radius: 4px; width: 100px; border: 1px solid #99f6e4;">
            <button type="button" class="btn btn-sm remove-time-btn" data-med-index="${medIndex}" data-time-index="${newTimeIndex}"
                    style="background: none; border: none; color: #ef4444; padding: 2px 4px; font-size: 10px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `);
    
    container.find('.add-time-btn').before(newPicker);
    
    
    updateRemoveButtons(medIndex);
}

function removeTimePicker(medIndex, timeIndex) {
    const container = $(`.time-pickers-container[data-med-index="${medIndex}"]`);
    const pickers = container.find('.time-picker-wrapper');
    
    if (pickers.length <= 1) {
        showToast('error', 'At least one reminder time is required');
        return;
    }
    
    pickers.eq(timeIndex).remove();
    
    
    container.find('.time-picker-wrapper').each(function(i) {
        $(this).find('.med-time-picker').attr('data-time-index', i);
        $(this).find('.remove-time-btn').attr('data-time-index', i);
    });
    
    updateRemoveButtons(medIndex);
}

function updateRemoveButtons(medIndex) {
    const container = $(`.time-pickers-container[data-med-index="${medIndex}"]`);
    const pickers = container.find('.time-picker-wrapper');
    
    if (pickers.length === 1) {
        pickers.find('.remove-time-btn').hide();
    } else {
        pickers.find('.remove-time-btn').show();
    }
}

function setQuickTimes(medIndex, timesStr) {
    const times = timesStr.split(',').map(t => t.trim());
    const container = $(`.time-pickers-container[data-med-index="${medIndex}"]`);
    
    
    container.find('.time-picker-wrapper').remove();
    
    
    const pickersHtml = generateTimePickers(medIndex, times);
    container.find('.add-time-btn').before(pickersHtml);
}

function openPrescriptionUpload() {
    console.log('🚀 Opening prescription upload modal...');
    
    
    const form = document.getElementById('prescriptionUploadForm');
    const previewContainer = document.getElementById('prescriptionPreviewContainer');
    
    if (form) {
        form.reset();
        console.log('✅ Form reset');
    }
    
    if (previewContainer) {
        previewContainer.style.display = 'none';
        previewContainer.innerHTML = '';
        console.log('✅ Preview container cleared');
    } else {
        console.warn('⚠️ Preview container not found during modal open');
    }
    
    
    $('#uploadPrescriptionModal').off('shown.bs.modal');
    
    
    $('#uploadPrescriptionModal').one('shown.bs.modal', function() {
        console.log('📂 Modal shown, initializing drag-drop...');
        initializeDragDrop();
    });
    
    $('#uploadPrescriptionModal').modal('show');
    console.log('✅ Modal show triggered');
}

function validateFile(file) {
    console.log('🔍 Validating file:', {
        name: file.name,
        type: file.type,
        size: file.size,
        sizeInMB: (file.size / 1024 / 1024).toFixed(2) + ' MB'
    });
    
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/jfif', 'application/pdf'];
    const maxSize = 5 * 1024 * 1024; 
    
    if (!allowedTypes.includes(file.type)) {
        console.error('❌ Invalid file type:', file.type);
        console.log('Allowed types:', allowedTypes);
        showToast('error', `Invalid file type: ${file.type}. Only JPG, PNG, and PDF files are allowed`);
        return false;
    }
    
    if (file.size > maxSize) {
        console.error('❌ File too large:', file.size, 'bytes (', (file.size / 1024 / 1024).toFixed(2), 'MB)');
        showToast('error', 'File size must be less than 5MB');
        return false;
    }
    
    console.log('✅ File validation passed');
    return true;
}

function handleFileSelect(event) {
    console.log('🔍 handleFileSelect called', event);
    
    const file = event.target.files[0];
    const previewContainer = document.getElementById('prescriptionPreviewContainer');
    const uploadZone = document.getElementById('uploadZone');
    
    console.log('📁 Selected file:', file);
    console.log('📦 Preview container:', previewContainer);
    
    if (!previewContainer) {
        console.error('❌ Preview container not found!');
        alert('Error: Preview container not found. Please refresh the page.');
        return;
    }
    
    if (!file) {
        console.log('No file selected, clearing preview');
        previewContainer.style.display = 'none';
        previewContainer.innerHTML = '';
        return;
    }
    
    console.log('File details:', {
        name: file.name,
        type: file.type,
        size: file.size,
        sizeInMB: (file.size / 1024 / 1024).toFixed(2) + ' MB'
    });
    
    
    if (!validateFile(file)) {
        console.log('❌ File validation failed');
        event.target.value = ''; 
        return;
    }
    
    console.log('✅ File validation passed');
    
    
    if (file.type.startsWith('image/') || file.type === 'image/jfif') {
        console.log('📸 Rendering image preview...');
        const reader = new FileReader();
        
        reader.onload = function(e) {
            console.log('✅ FileReader loaded image');
            
            previewContainer.innerHTML = `
                <div style="text-align: center; padding: 15px; background: #f0fdf4; border-radius: 10px; border: 2px solid #86efac; width: 100%;">
                    <img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 250px; border-radius: 8px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">
                    <p style="margin: 0; color: #059669; font-weight: 600; font-size: 14px;">
                        <i class="fas fa-check-circle"></i> ${file.name}
                    </p>
                    <small style="color: #6b7280; font-size: 12px;">${(file.size / 1024).toFixed(2)} KB</small>
                </div>
            `;
            previewContainer.style.display = 'block';
            previewContainer.style.visibility = 'visible';
            previewContainer.style.opacity = '1';
            
            console.log('✅ Preview displayed successfully');
        };
        
        reader.onerror = function(error) {
            console.error('❌ FileReader error:', error);
            showToast('error', 'Failed to read image file');
        };
        
        reader.readAsDataURL(file);
    } else if (file.type === 'application/pdf') {
        console.log('📄 Rendering PDF preview...');
        previewContainer.innerHTML = `
            <div style="text-align: center; padding: 20px; background: #fef2f2; border-radius: 10px; border: 2px solid #fca5a5; width: 100%;">
                <i class="fas fa-file-pdf" style="font-size: 60px; color: #dc2626; margin-bottom: 10px; display: block;"></i>
                <p style="margin: 0; color: #dc2626; font-weight: 600; font-size: 14px;">
                    <i class="fas fa-check-circle"></i> ${file.name}
                </p>
                <small style="color: #6b7280; font-size: 12px;">${(file.size / 1024).toFixed(2)} KB</small>
            </div>
        `;
        previewContainer.style.display = 'block';
        previewContainer.style.visibility = 'visible';
        previewContainer.style.opacity = '1';
        
        
        setTimeout(() => {
            previewContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
        
        console.log('✅ PDF preview displayed successfully');
        console.log('Preview container styles:', {
            display: previewContainer.style.display,
            visibility: previewContainer.style.visibility,
            opacity: previewContainer.style.opacity
        });
    } else {
        console.warn('⚠️ Unsupported file type:', file.type);
    }
}

function handlePrescriptionUpload(event) {
    const form = document.getElementById('prescriptionUploadForm');
    const fileInput = document.getElementById('prescriptionFile');
    
    if (!fileInput.files.length) {
        alert('Please select a file to upload');
        return;
    }
    
    const formData = new FormData(form);
    
    
    console.log('Uploading file:', fileInput.files[0].name);
    console.log('FormData entries:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ':', pair[1]);
    }
    
    
    const uploadBtn = event ? event.target : document.getElementById('uploadBtn');
    const originalText = uploadBtn.innerHTML;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Validating with AI...';
    uploadBtn.disabled = true;
    
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=upload_prescription',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            console.log('Server response:', resp);
            
            if (resp.success) {
                $('#uploadPrescriptionModal').modal('hide');
                
                
                if (resp.instructions) {
                    showMedicationInstructions(resp.instructions);
                } else {
                    showToast('success', resp.message || 'Prescription uploaded successfully!');
                }
                
                setTimeout(() => location.reload(), 3000);
            } else {
                
                if (resp.validation_failed) {
                    
                    showValidationError(resp.reason || resp.message);
                } else {
                    showToast('error', resp.message || 'Upload failed. Please try again.');
                }
                uploadBtn.innerHTML = originalText;
                uploadBtn.disabled = false;
            }
        },
        error: function(xhr, status, error) {
            console.error('Upload error:', error);
            console.error('XHR:', xhr);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            showToast('error', 'An error occurred during upload.');
            uploadBtn.innerHTML = originalText;
            uploadBtn.disabled = false;
        }
    });
}

function showValidationError(reason) {
    const modal = $('#uploadPrescriptionModal');
    
    
    modal.find('.validation-alert').remove();
    
    
    const alert = $(`
        <div class="alert alert-danger validation-alert" style="margin: 15px 0;">
            <h6 class="mb-2"><i class="fas fa-exclamation-triangle mr-1"></i> Invalid Prescription Image</h6>
            <p class="mb-1 small">${reason}</p>
            <hr style="margin: 10px 0; border-color: rgba(255,255,255,0.3);">
            <p class="mb-0 small"><strong>Valid prescription must contain:</strong></p>
            <ul class="mb-0 small" style="padding-left: 20px;">
                <li>Doctor's name and signature</li>
                <li>Patient information</li>
                <li>Medication names and dosages</li>
                <li>Date and clinic/hospital details</li>
            </ul>
        </div>
    `);
    
    
    modal.find('.modal-body').prepend(alert);
    
    
    setTimeout(() => alert.fadeOut(() => alert.remove()), 10000);
}

function viewImage(imagePath) {
    document.getElementById('viewerImage').src = imagePath;
    $('#imageViewerModal').modal('show');
}

function deletePrescription(id) {
    if (!confirm('Are you sure you want to delete this prescription? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=delete_prescription',
        method: 'POST',
        data: {
            prescription_id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast('success', response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', response.message || 'Delete failed');
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            console.error('Status code:', xhr.status);
            showToast('error', 'An error occurred');
        }
    });
}

function showMedicationInstructions(instructions) {
    const tipsHtml = instructions.tips.map(tip => `<li>${tip}</li>`).join('');
    
    const modal = $(`
        <div class="modal fade" id="medicationInstructionsModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px; border: none;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); color: white; border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle mr-2"></i>
                            ${instructions.title}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="padding: 25px;">
                        <div class="alert alert-success" style="border-left: 4px solid #0d9488;">
                            <i class="fas fa-prescription-bottle mr-2"></i>
                            <strong>Prescription Uploaded Successfully!</strong>
                        </div>
                        
                        <p style="color: #334155; line-height: 1.6;">
                            ${instructions.content}
                        </p>
                        
                        <div style="background: #f0fdfa; border-radius: 10px; padding: 15px; margin-top: 15px;">
                            <h6 style="color: #0d9488; margin-bottom: 12px;">
                                <i class="fas fa-lightbulb mr-1"></i> Important Tips:
                            </h6>
                            <ul style="margin: 0; padding-left: 20px; color: #334155; line-height: 1.8;">
                                ${tipsHtml}
                            </ul>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-primary" data-dismiss="modal" style="background: #0d9488; border: none; padding: 10px 30px; border-radius: 8px;">
                                <i class="fas fa-check mr-1"></i> Got it, Thanks!
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    $('body').append(modal);
    modal.modal('show');
    
    
    modal.on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

function openCustomizeModal(prescriptionId, existingNotes) {
    $('#customize_prescription_id').val(prescriptionId);
    $('#custom_notes').val(existingNotes || '');
    
    
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=get_prescription_schedule',
        method: 'GET',
        data: { prescription_id: prescriptionId },
        dataType: 'json',
        success: function(resp) {
            if (resp.success && resp.schedule && resp.schedule.length > 0) {
                
                $('#ai_schedule_section').show();
                $('#med_count').text(resp.schedule.length);
                
                
                let scheduleHtml = '';
                
                resp.schedule.forEach(function(med, index) {
                    const medId = 'med_reminder_' + index;
                    const isChecked = med.create_reminder == '1' ? 'checked' : '';
                    const detailsId = 'details_' + index;
                    
                    
                    const existingTimes = (med.times || '').split(',').map(t => t.trim()).filter(t => t);
                    const timePickersHtml = generateTimePickers(index, existingTimes);
                    
                    scheduleHtml += `
                        <div class="medication-card" data-index="${index}" style="background: white; border-radius: 8px; padding: 10px; margin-bottom: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 2px solid ${isChecked ? '#0d9488' : '#e5e7eb'};">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <input type="text" class="form-control form-control-sm med-name" data-med-index="${index}" value="${med.medicine_name || ''}" 
                                       placeholder="Medication name" style="font-weight: 700; color: #0f766e; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 4px; padding: 4px 8px; flex: 1; margin-right: 10px;">
                                <button type="button" class="btn btn-sm toggle-details" data-target="${detailsId}" style="background: none; border: none; color: #64748b; padding: 2px 6px; font-size: 12px;">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <div class="custom-control custom-switch" style="padding-left: 2.5rem;">
                                    <input type="checkbox" class="custom-control-input medication-reminder-toggle" id="${medId}" ${isChecked} data-med-index="${index}">
                                    <label class="custom-control-label" for="${medId}" style="font-size: 11px; color: #0d9488; font-weight: 600; cursor: pointer;">
                                        <i class="fas fa-bell mr-1"></i>Remind
                                    </label>
                                </div>
                            </div>
                            <div id="${detailsId}" class="med-details" style="display: block;">
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <input type="text" class="form-control form-control-sm med-dosage" data-med-index="${index}" value="${med.dosage || ''}" 
                                           placeholder="Dosage" style="font-size: 12px; padding: 4px 8px; border-radius: 4px;">
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="text" class="form-control form-control-sm med-frequency" data-med-index="${index}" value="${med.frequency || ''}" 
                                           placeholder="Frequency" style="font-size: 12px; padding: 4px 8px; border-radius: 4px;">
                                </div>
                                <div class="col-12 mb-2">
                                    <label style="font-size: 11px; color: #64748b; margin-bottom: 4px; display: block;">
                                        <i class="fas fa-clock mr-1"></i>Reminder Times
                                    </label>
                                    <div class="time-pickers-container" data-med-index="${index}" style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                        ${timePickersHtml}
                                        <button type="button" class="btn btn-sm add-time-btn" data-med-index="${index}" 
                                                style="background: #e0f2f1; color: #0d9488; border: none; font-size: 11px; padding: 4px 8px; border-radius: 4px;">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                    <div style="margin-top: 6px; display: flex; gap: 4px;">
                                        <button type="button" class="btn btn-sm quick-time-btn" data-times="08:00,20:00" data-target="${index}" 
                                                style="background: #f0fdfa; color: #0d9488; border: 1px solid #99f6e4; font-size: 10px; padding: 3px 6px; border-radius: 4px;">2x daily</button>
                                        <button type="button" class="btn btn-sm quick-time-btn" data-times="08:00,14:00,20:00" data-target="${index}" 
                                                style="background: #f0fdfa; color: #0d9488; border: 1px solid #99f6e4; font-size: 10px; padding: 3px 6px; border-radius: 4px;">3x daily</button>
                                        <button type="button" class="btn btn-sm quick-time-btn" data-times="08:00,12:00,16:00,20:00" data-target="${index}" 
                                                style="background: #f0fdfa; color: #0d9488; border: 1px solid #99f6e4; font-size: 10px; padding: 3px 6px; border-radius: 4px;">4x daily</button>
                                    </div>
                                </div>
                                <div class="col-12 mb-2">
                                    <input type="number" class="form-control form-control-sm med-duration" data-med-index="${index}" value="${med.duration_days || ''}" 
                                           placeholder="Duration (days)" style="font-size: 12px; padding: 4px 8px; border-radius: 4px;">
                                </div>
                            </div>
                            <textarea class="form-control form-control-sm med-instructions" data-med-index="${index}" rows="1" 
                                      placeholder="Special instructions..." style="margin-top: 4px; font-size: 11px; padding: 4px 8px; border-radius: 4px; background: #f0fdfa;">${med.instructions || ''}</textarea>
                            </div>
                        </div>
                    `;
                });
                
                $('#ai_schedule_content').html(scheduleHtml);
                $('#ai_schedule_section').show();
                
                
                currentAISchedule = resp.schedule;
                
                
                $('.medication-reminder-toggle').on('change', function() {
                    const card = $(this).closest('.medication-card');
                    if ($(this).is(':checked')) {
                        card.css('border', '2px solid #0d9488');
                        card.css('box-shadow', '0 4px 8px rgba(13, 148, 136, 0.15)');
                    } else {
                        card.css('border', '2px solid #e5e7eb');
                        card.css('box-shadow', '0 2px 4px rgba(0,0,0,0.05)');
                    }
                });
                
                
                $(document).off('click', '.quick-time-btn').on('click', '.quick-time-btn', function() {
                    const times = $(this).data('times');
                    const targetIndex = $(this).data('target');
                    setQuickTimes(targetIndex, times);
                    
                    
                    $(this).css('background', '#0d9488').css('color', 'white');
                    setTimeout(() => {
                        $(this).css('background', '#f0fdfa').css('color', '#0d9488');
                    }, 300);
                });
                
                
                $(document).off('click', '.add-time-btn').on('click', '.add-time-btn', function() {
                    const medIndex = $(this).data('med-index');
                    addTimePicker(medIndex);
                });
                
                
                $(document).off('click', '.remove-time-btn').on('click', '.remove-time-btn', function() {
                    const medIndex = $(this).data('med-index');
                    const timeIndex = $(this).data('time-index');
                    removeTimePicker(medIndex, timeIndex);
                });
                
                
                $('#enable_all_reminders').on('click', function() {
                    $('.medication-reminder-toggle').prop('checked', true).trigger('change');
                });
                
                
                $('#disable_all_reminders').on('click', function() {
                    $('.medication-reminder-toggle').prop('checked', false).trigger('change');
                });
                
                
                $('.toggle-details').on('click', function() {
                    const targetId = $(this).data('target');
                    const details = $('#' + targetId);
                    const icon = $(this).find('i');
                    
                    if (details.is(':visible')) {
                        details.slideUp(200);
                        icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    } else {
                        details.slideDown(200);
                        icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    }
                });
                
                
                if (resp.schedule.length >= 5) {
                    $('.med-details').hide();
                    $('.toggle-details i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            } else {
                currentAISchedule = [];
                $('#ai_schedule_section').hide();
            }
        },
        error: function() {
            $('#ai_schedule_section').hide();
        }
    });
    
    $('#customizePrescriptionModal').modal('show');
}

function saveCustomization() {
    const prescriptionId = $('#customize_prescription_id').val();
    const notes = $('#custom_notes').val();
    
    
    const medicationReminders = [];
    $('.medication-reminder-toggle').each(function() {
        const index = $(this).data('med-index');
        const isEnabled = $(this).is(':checked');
        if (currentAISchedule[index]) {
            
            const times = getTimesFromPickers(index);
            
            medicationReminders.push({
                medicine_name: $(`.med-name[data-med-index="${index}"]`).val() || currentAISchedule[index].medicine_name,
                dosage: $(`.med-dosage[data-med-index="${index}"]`).val() || currentAISchedule[index].dosage,
                frequency: $(`.med-frequency[data-med-index="${index}"]`).val() || currentAISchedule[index].frequency,
                times: times || currentAISchedule[index].times,
                duration_days: $(`.med-duration[data-med-index="${index}"]`).val() || currentAISchedule[index].duration_days,
                instructions: $(`.med-instructions[data-med-index="${index}"]`).val() || currentAISchedule[index].instructions,
                create_reminder: isEnabled ? '1' : '0'
            });
        }
    });
    
    $.ajax({
        url: window.BASE_URL + 'ajax.php?action=customize_prescription',
        method: 'POST',
        data: {
            prescription_id: prescriptionId,
            notes: notes,
            medication_reminders: JSON.stringify(medicationReminders)
        },
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                $('#customizePrescriptionModal').modal('hide');
                showToast('success', 'Customization saved successfully!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('error', resp.message || 'Failed to save customization');
            }
        },
        error: function() {
            showToast('error', 'An error occurred while saving');
        }
    });
}

function showToast(type, message) {
    const bgColor = type === 'success' ? '#0d9488' : '#dc2626';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    
    const toast = $(`
        <div style="position: fixed; top: 20px; right: 20px; z-index: 9999; 
                    background: ${bgColor}; color: white; padding: 15px 25px; 
                    border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                    display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease;">
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(() => {
        toast.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}

function initializeDragDrop() {
    console.log('🎯 initializeDragDrop called');
    
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('prescriptionFile');
    
    console.log('Upload zone element:', uploadZone);
    console.log('File input element:', fileInput);
    
    if (!uploadZone || !fileInput) {
        console.error('❌ Upload zone or file input not found!');
        console.log('uploadZone:', uploadZone);
        console.log('fileInput:', fileInput);
        return;
    }
    
    console.log('✅ Elements found, setting up drag-drop handlers...');
    
    
    const newUploadZone = uploadZone.cloneNode(true);
    uploadZone.parentNode.replaceChild(newUploadZone, uploadZone);
    const uploadZoneRef = document.getElementById('uploadZone');
    
    console.log('✅ Upload zone cloned and replaced');
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZoneRef.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    console.log('✅ Prevent default handlers attached');
    
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZoneRef.addEventListener(eventName, () => {
            console.log('🎨 Adding dragover class');
            uploadZoneRef.classList.add('dragover');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadZoneRef.addEventListener(eventName, () => {
            console.log('🎨 Removing dragover class');
            uploadZoneRef.classList.remove('dragover');
        }, false);
    });
    
    console.log('✅ Visual feedback handlers attached');
    
    
    uploadZoneRef.addEventListener('drop', function(e) {
        console.log('📦 Drop event triggered');
        const dt = e.dataTransfer;
        const files = dt.files;
        
        console.log('Dropped files:', files);
        
        if (files.length > 0) {
            const file = files[0];
            console.log('Processing dropped file:', file.name);
            
            
            if (!validateFile(file)) {
                console.log('Validation failed for dropped file');
                return;
            }
            
            try {
                
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                console.log('✅ File assigned to input:', fileInput.files);
            } catch (error) {
                console.error('Error assigning file:', error);
            }
            
            
            handleFileSelect({ target: fileInput });
            
            console.log('✅ File dropped and processed successfully:', file.name);
        } else {
            console.warn('No files in drop event');
        }
    }, false);
    
    
    uploadZoneRef.addEventListener('click', function(e) {
        console.log('🖱️ Upload zone clicked, triggering file input');
        fileInput.click();
    }, false);
    
    console.log('✅ Drag & drop initialized successfully');
    console.log('✅ Click handler attached');
}

document.addEventListener('DOMContentLoaded', function() {
    
    
    
    
    
    
    
    
    
    
    
    console.log('Prescriptions.js loaded - actions delegated to global-action-handler.js');
});
