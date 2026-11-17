<script>
  $(document).ready(function(){
    $('#p_use').click(function(){
      uni_modal("Privacy Policy","policy.php","mid-large")
    })
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
    window.uni_modal = function($title = '' , $url='',$size=""){
        
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right','');
        
        start_loader()
        $.ajax({
            url:$url,
            error:err=>{
                end_loader()
                console.error('[uni_modal] Failed to load:', $url, err)
                
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right','');
                alert("An error occurred loading the page.")
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
                      backdrop:'static',
                      keyboard:false,
                      focus:true
                    })
                    end_loader()
                } else {
                    end_loader()
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right','');
                }
            }
        })
    }

    window._conf = function($msg='',$func='',$params = []){
       $('#confirm_modal #confirm').attr('onclick',$func+"("+$params.join(',')+")")
       $('#confirm_modal .modal-body').html($msg)
       $('#confirm_modal').modal('show')
    }
  })
  
  
  window.updateCartBadge = function() {
    $.ajax({
        url: 'ajax.php?action=get_cart_count',
        method: 'GET',
        dataType: 'json',
        success: function(resp) {
            if(resp.status === 'success') {
                const badge = $('#cartBadge');
                if(resp.count > 0) {
                    badge.text(resp.count).show();
                } else {
                    badge.hide();
                }
            }
        },
        error: function() {
            console.log('Failed to update cart badge');
        }
    });
  }
</script>
<script src="<?php echo base_url ?>assets/js/landing.js"></script>

<style>

html {
    height: 100%;
    margin: 0;
    padding: 0;
}

body {
    min-height: 100vh;
    margin: 0 !important;
    padding: 0 !important;
    display: flex !important;
    flex-direction: column !important;
}

.content-wrapper {
    min-height: auto !important;
    flex: 1 0 auto !important;
}

.compact-footer {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    color: white;
    padding: 2rem 0 1rem;
    margin-top: auto !important;
    margin-bottom: 0 !important;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
    width: 100%;
}

.daterangepicker, .jqvmap-label {
    display: none !important;
    position: absolute !important;
    left: -9999px !important;
}

.compact-footer::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #0d9488 0%, #14b8a6 50%, #0d9488 100%);
}

.compact-footer::after {
    content: "";
    position: absolute;
    top: -30px;
    right: -30px;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.08) 0%, transparent 70%);
    border-radius: 50%;
}

.compact-footer .footer-content {
    position: relative;
    z-index: 1;
}

.compact-footer .footer-section {
    margin-bottom: 1.2rem;
}

.compact-footer .footer-logo {
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.compact-footer .footer-logo svg {
    color: #14b8a6;
    width: 22px;
    height: 22px;
}

.compact-footer .footer-description {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.75rem;
    line-height: 1.4;
    margin-bottom: 0.8rem;
}

.compact-footer .footer-title {
    color: white;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.8rem;
    position: relative;
    padding-bottom: 6px;
}

.compact-footer .footer-title::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, #0d9488 0%, #14b8a6 100%);
    border-radius: 2px;
}

.compact-footer .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.compact-footer .footer-links li {
    margin-bottom: 0.4rem;
}

.compact-footer .footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.75rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.compact-footer .footer-links a:hover {
    color: #14b8a6;
    transform: translateX(3px);
}

.compact-footer .footer-links a svg {
    width: 12px;
    height: 12px;
    flex-shrink: 0;
}

.compact-footer .footer-contact-item {
    display: flex;
    align-items: start;
    gap: 8px;
    margin-bottom: 0.6rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.75rem;
}

.compact-footer .footer-contact-item svg {
    color: #14b8a6;
    flex-shrink: 0;
    margin-top: 2px;
    width: 14px;
    height: 14px;
}

.compact-footer .footer-social {
    display: flex;
    gap: 8px;
    margin-top: 0.8rem;
}

.compact-footer .footer-social a {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.2s ease;
    text-decoration: none;
}

.compact-footer .footer-social a svg {
    width: 14px;
    height: 14px;
}

.compact-footer .footer-social a:hover {
    background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4);
}

.compact-footer .footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 1rem;
    margin-top: 1.5rem;
    text-align: center;
}

.compact-footer .footer-bottom-text {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.7rem;
    margin: 0;
}

.compact-footer .footer-bottom-links {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 0.6rem;
    flex-wrap: wrap;
}

.compact-footer .footer-bottom-links a {
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.7rem;
    transition: color 0.2s ease;
}

.compact-footer .footer-bottom-links a:hover {
    color: #14b8a6;
}

@media (max-width: 768px) {
    .compact-footer {
        padding: 1.5rem 0 0.8rem;
        margin-top: 2rem;
    }
    
    .compact-footer .footer-section {
        margin-bottom: 1.5rem;
    }
    
    .compact-footer .footer-logo {
        font-size: 1.1rem;
    }
}
</style>

<footer class="compact-footer">
    <div class="container footer-content">
        <div class="row">
            
            <div class="col-lg-4 col-md-6">
                <div class="footer-section">
                    <div class="footer-logo">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                        <?php echo htmlspecialchars($_settings->info('name') ?: 'MedPick'); ?>
                    </div>
                    <p class="footer-description">
                        <?php echo is_file(base_app.'tagline.html') ? htmlspecialchars_decode(file_get_contents(base_app.'tagline.html')) : 'Your trusted healthcare partner since 2025. We provide quality pharmaceutical products and services with excellence and care.'; ?>
                    </p>
                    <div class="footer-social">
                        <a href="#" title="Facebook">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" title="Twitter">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="#" title="Instagram">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="#" title="LinkedIn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-2 col-md-6">
                <div class="footer-section">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li>
                            <a href="<?php echo base_url ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo base_url ?>?p=about">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                About Us
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo base_url ?>?p=contact">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                Contact
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            
            <div class="col-lg-3 col-md-6">
                <div class="footer-section">
                    <h5 class="footer-title">Support</h5>
                    <ul class="footer-links">
                        <li>
                            <a href="#">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                Help Center
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                FAQs
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                Shipping Info
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                Returns
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            
            <div class="col-lg-3 col-md-6">
                <div class="footer-section">
                    <h5 class="footer-title">Contact Us</h5>
                    <div class="footer-contact-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span><?php echo htmlspecialchars($_settings->info('address') ?: 'Contact us for location'); ?></span>
                    </div>
                    <?php if($_settings->info('phone')): ?>
                    <div class="footer-contact-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span><a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $_settings->info('phone')); ?>" style="color: inherit;"><?php echo htmlspecialchars($_settings->info('phone')); ?></a></span>
                    </div>
                    <?php endif; ?>
                    <?php if($_settings->info('mobile')): ?>
                    <div class="footer-contact-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                        <span><a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $_settings->info('mobile')); ?>" style="color: inherit;"><?php echo htmlspecialchars($_settings->info('mobile')); ?></a></span>
                    </div>
                    <?php endif; ?>
                    <?php if($_settings->info('email')): ?>
                    <div class="footer-contact-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <span><a href="mailto:<?php echo htmlspecialchars($_settings->info('email')); ?>" style="color: inherit;"><?php echo htmlspecialchars($_settings->info('email')); ?></a></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="footer-bottom">
            <p class="footer-bottom-text">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($_settings->info('name') ?: 'MedPick'); ?>. All rights reserved. Your Trusted Healthcare Partner.</p>
            <div class="footer-bottom-links">
                <a href="<?php echo base_url ?>?p=contact">Contact Us</a>
                <a href="mailto:<?php echo htmlspecialchars($_settings->info('email')); ?>">Email</a>
                <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $_settings->info('phone') ?: $_settings->info('mobile')); ?>">Call</a>
            </div>
        </div>
    </div>
</footer>

<?php include(__DIR__ . '/../Chatbot/chatbot_widget.php'); ?>

<?php if (file_exists(__DIR__ . '/toast-notification.php')) include_once(__DIR__ . '/toast-notification.php'); ?>

   
    
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
    
    <script src="<?php echo base_url ?>plugins/html2canvas.min.js"></script>