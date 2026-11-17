<?php
  
  
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="theme-color" content="#0d9488">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
  	<title><?php echo $_settings->info('title') != false ? $_settings->info('title').' | ' : '' ?><?php echo $_settings->info('name') ?></title>
    <link rel="icon" href="<?php echo validate_image($_settings->info('logo')) ?>" />
    
    
    
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">
    
    <?php if(isset($page) && $page != 'home'): ?>
    
    
    
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
      
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
   
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/jqvmap/jqvmap.min.css">
    
    <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.css">
    <link rel="stylesheet" href="<?php echo base_url ?>dist/css/custom.css">
    <link rel="stylesheet" href="<?php echo base_url ?>assets/css/styles.css">
    
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/daterangepicker/daterangepicker.css">
    
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/summernote/summernote-bs4.min.css">
    <?php endif; ?>
    
    
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">

    <link rel="stylesheet" href="<?php echo base_url ?>assets/css/main.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/main.css') ?: time(); ?>">
    <link rel="stylesheet" href="<?php echo base_url ?>assets/css/landing.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/landing.css') ?: time(); ?>">

    <style type="text/css">
      @keyframes chartjs-render-animation{from{opacity:.99}to{opacity:1}}.chartjs-render-monitor{animation:chartjs-render-animation 1ms}.chartjs-size-monitor,.chartjs-size-monitor-expand,.chartjs-size-monitor-shrink{position:absolute;direction:ltr;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1}.chartjs-size-monitor-expand>div{position:absolute;width:1000000px;height:1000000px;left:0;top:0}.chartjs-size-monitor-shrink>div{position:absolute;width:200%;height:200%;left:0;top:0}
    </style>

     
    <script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
    
    <script src="<?php echo base_url ?>plugins/jquery-ui/jquery-ui.min.js"></script>
    
    
    <script src="<?php echo base_url ?>plugins/sweetalert2/sweetalert2.min.js"></script>
    
    <script>
        var _base_url_ = '<?php echo base_url ?>';
        var _csrf_token_ = '<?php echo csrf_token() ?>';
        
        
        $(document).ready(function() {
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (settings.type && settings.type.toUpperCase() === 'POST') {
                        
                        xhr.setRequestHeader('X-CSRF-TOKEN', _csrf_token_);
                        
                        
                        if (settings.data && typeof settings.data === 'string' && settings.data.indexOf('csrf_token') === -1) {
                            settings.data += '&csrf_token=' + encodeURIComponent(_csrf_token_);
                        } else if (!settings.data) {
                            settings.data = 'csrf_token=' + encodeURIComponent(_csrf_token_);
                        } else if (settings.data instanceof FormData && !settings.data.has('csrf_token')) {
                            settings.data.append('csrf_token', _csrf_token_);
                        }
                    }
                }
            });
        });
        
        
        (function() {
            var originalFetch = window.fetch;
            window.fetch = function(url, options) {
                options = options || {};
                if (options.method && options.method.toUpperCase() === 'POST') {
                    
                    options.headers = options.headers || {};
                    if (typeof options.headers.set === 'function') {
                        options.headers.set('X-CSRF-TOKEN', _csrf_token_);
                    } else {
                        options.headers['X-CSRF-TOKEN'] = _csrf_token_;
                    }
                    
                    if (options.body && typeof options.body === 'string' && options.body.indexOf('csrf_token') === -1) {
                        options.body += '&csrf_token=' + encodeURIComponent(_csrf_token_);
                    } else if (options.body instanceof FormData && !options.body.has('csrf_token')) {
                        options.body.append('csrf_token', _csrf_token_);
                    }
                }
                return originalFetch.call(this, url, options);
            };
        })();
    </script>
    
    
    <script src="<?php echo base_url ?>assets/js/toast-notifications.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/toast-notifications.js') ?: time(); ?>"></script>
    
    <?php if(isset($page) && $page != 'home'): ?>
    
    <script src="<?php echo base_url ?>plugins/toastr/toastr.min.js"></script>
    <script src="<?php echo base_url ?>dist/js/script.js?v=<?php echo filemtime(__DIR__ . '/../dist/js/script.js') ?: time(); ?>"></script>
    <script src="<?php echo base_url ?>assets/js/scripts.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/scripts.js') ?: time(); ?>"></script>
    <?php endif; ?>

    <?php echo html_entity_decode($_settings->load_data()); ?>
    
    <?php if(isset($page) && $page != 'home'): ?>
    <style>
    #main-header{
        position:relative;
        background: rgb(0,0,0)!important;
        background: radial-gradient(circle, rgba(0,0,0,0.48503151260504207) 22%, rgba(0,0,0,0.39539565826330536) 49%, rgba(0,212,255,0) 100%)!important;
    }
    #main-header:before{
        content:"";
        position:absolute;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background-image:url(<?php echo base_url.$_settings->info('cover') ?>);
        background-repeat: no-repeat;
        background-size: cover;
        filter: drop-shadow(0px 7px 6px black);
        z-index:-1;
    }

 </style>
 <?php endif; ?>

  </head>