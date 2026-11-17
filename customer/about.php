<link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/about.css">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<div class="about-page-wrapper">
    <section class="py-5">
        <div class="container">
            <div class="card rounded-0">
                <div class="card-body">
                    
                    <?= htmlspecialchars_decode(file_get_contents('./about.html')) ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="<?php echo base_url ?>customer/assets/js/about.js"></script>
