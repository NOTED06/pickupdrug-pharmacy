<?php

if(isset($_POST['submit_contact'])){
    
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $subject = $conn->real_escape_string(trim($_POST['subject'] ?? ''));
    $message_text = $conn->real_escape_string(trim($_POST['message'] ?? ''));
    
    
    if(empty($name) || empty($email) || empty($message_text)) {
        $_settings->set_flashdata('error', 'Please fill in all required fields (Name, Email, Message).');
        redirect('?p=contact');
        exit;
    }
    
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_settings->set_flashdata('error', 'Please enter a valid email address.');
        redirect('?p=contact');
        exit;
    }
    
    
    $sql = "INSERT INTO inquiry_list (fullname, email, phone, subject, message, status, date_created) 
            VALUES ('$name', '$email', '$phone', '$subject', '$message_text', 0, NOW())";
    
    if($conn->query($sql)) {
        $_settings->set_flashdata('success', 'Thank you for contacting us! We will get back to you soon.');
    } else {
        error_log("Contact form error: " . $conn->error);
        $_settings->set_flashdata('error', 'Sorry, there was an error sending your message. Please try again.');
    }
    
    redirect('?p=contact');
    exit;
}
?>
<link rel="stylesheet" href="<?php echo base_url ?>customer/assets/css/contact.css?v=1.0">
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/customer/mobile-fixes.css?v=<?php echo @filemtime('assets/css/customer/mobile-fixes.css'); ?>">

<div class="container contact-section">
    
    <div class="page-header-banner">
        <h1><i class="fas fa-envelope-open-text"></i> Get In Touch</h1>
        <p>We're here to help and answer any questions you might have</p>
        <div class="header-stats">
            <span class="stat-badge"><i class="fas fa-headset mr-1"></i> 24/7 Support</span>
            <span class="stat-badge"><i class="fas fa-phone mr-1"></i> Call Us Anytime</span>
        </div>
    </div>
    
    <div class="row">
        
        <div class="col-lg-3 col-md-6">
            <div class="contact-card">
                <div class="contact-info-box">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4>Visit Us</h4>
                        <p><?php echo nl2br(htmlspecialchars($_settings->info('address') ?: 'Contact us for our location')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="contact-card">
                <div class="contact-info-box">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4>Call Us</h4>
                        <p>
                            <?php if($_settings->info('phone')): ?>
                            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $_settings->info('phone')); ?>">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($_settings->info('phone')); ?>
                            </a><br>
                            <?php endif; ?>
                            <?php if($_settings->info('mobile')): ?>
                            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $_settings->info('mobile')); ?>">
                                <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($_settings->info('mobile')); ?>
                            </a><br>
                            <?php endif; ?>
                            <small class="text-muted">Mon-Sat: 8AM-8PM</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="contact-card">
                <div class="contact-info-box">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4>Email Us</h4>
                        <p>
                            <?php if($_settings->info('email')): ?>
                            <a href="mailto:<?php echo htmlspecialchars($_settings->info('email')); ?>">
                                <?php echo htmlspecialchars($_settings->info('email')); ?>
                            </a><br>
                            <?php endif; ?>
                            <small class="text-muted">24/7 Support</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="contact-card">
                <div class="contact-info-box">
                    <div class="contact-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4><?php echo htmlspecialchars($_settings->info('name') ?: 'PharmaCare'); ?></h4>
                        <p>
                            Your trusted pharmacy<br>
                            <small class="text-muted">Quality healthcare products</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        
        <div class="col-lg-8">
            <div class="contact-form-card">
                <h3><i class="fas fa-paper-plane"></i> Send us a Message</h3>
                <form id="contactForm" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="(123) 456-7890">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="subject">Subject *</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="How can we help?" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="5" placeholder="Tell us more about your inquiry..." required></textarea>
                    </div>
                    <button type="submit" name="submit_contact" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="business-hours">
                <h5><i class="far fa-clock"></i> Business Hours</h5>
                <ul class="hours-list">
                    <li>
                        <span class="day">Monday - Friday</span>
                        <span class="time">8:00 AM - 8:00 PM</span>
                    </li>
                    <li>
                        <span class="day">Saturday</span>
                        <span class="time">9:00 AM - 6:00 PM</span>
                    </li>
                    <li>
                        <span class="day">Sunday</span>
                        <span class="time">10:00 AM - 4:00 PM</span>
                    </li>
                </ul>
            </div>

            <div class="contact-card mt-3">
                <div class="contact-info-box">
                    <div class="contact-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4>Privacy Assured</h4>
                        <p>Your information is safe and secure. We respect your privacy and never share your data.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mt-4">
        <div class="col-12">
            <h3 class="text-center mb-4" style="color: var(--primary-color); font-weight: 700;">
                <i class="fas fa-map-marked-alt"></i> Find Us on Map
            </h3>
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.9663095343008!2d-74.00425878428698!3d40.74076794379132!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259bf5c1654f3%3A0xc80f9cfce5383d5d!2sGoogle!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url ?>customer/assets/js/contact.js?v=1.0"></script>
