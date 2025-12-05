<?php
session_start();
$pageTitle = 'Contact Us';

// Calculate base path
$currentFileDir = dirname(__FILE__);
$webBasePath = str_replace('\\', '/', $currentFileDir) . '/';
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webBasePath);
$prefix = str_replace('\\', '/', $relativePath) . '/';

// Include PHPMailer
require_once $webBasePath . 'lib/PHPMailer.php';
require_once $webBasePath . 'lib/SMTP.php';

include 'general/_header.php';
include 'general/_navbar.php';
?>

<!-- Contact Us Page Styles -->
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="<?php echo $prefix; ?>css/contact.css">

<main class="contact-page-wrapper">
  <div class="contact-container">
    <div class="contact-layout">
      <div class="contact-content-wrapper">
        <div class="contact-main">
          <section class="contact-section">
            <div class="contact-header">
              <h1 class="contact-title">Contact Our Team</h1>
              <p class="contact-subtitle">Have a question or need support? Fill out the form below and we'll get back to you as soon as possible.</p>
            </div>

            <div class="contact-grid">
              <div class="contact-form-section">
                <h2 class="contact-section-title">Send us a Message</h2>
                <form class="contact-form" method="POST" action="<?php echo $prefix; ?>contact.php">
                  <div class="contact-field">
                    <label class="contact-label">
                      <p class="contact-label-text">Full Name</p>
                      <input name="full_name" class="contact-input" placeholder="Enter your full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" />
                    </label>
                  </div>

                  <div class="contact-field">
                    <label class="contact-label">
                      <p class="contact-label-text">Email Address</p>
                      <input type="email" name="email" class="contact-input" placeholder="you@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                    </label>
                  </div>

                  <div class="contact-field-full">
                    <label class="contact-label">
                      <p class="contact-label-text">Subject</p>
                      <input name="subject" class="contact-input" placeholder="How can we help?" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" />
                    </label>
                  </div>

                  <div class="contact-field-full">
                    <label class="contact-label">
                      <p class="contact-label-text">Your Message</p>
                      <textarea name="message" class="contact-textarea" placeholder="Write your message here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </label>
                  </div>

                  <div class="contact-field-full">
                    <button type="submit" class="contact-submit-btn">
                      <span>Send Message</span>
                    </button>
                  </div>
                </form>
              </div>

              <div class="contact-info-section">
                <div class="contact-info-block">
                  <h3 class="contact-info-title">Other Ways to Connect</h3>
                  <div class="contact-info-list">
                    <div class="contact-info-item">
                      <div class="contact-icon-box">
                        <span class="material-symbols-outlined">mail</span>
                      </div>
                      <p class="contact-info-text">info@ngear.com</p>
                    </div>

                    <div class="contact-info-item">
                      <div class="contact-icon-box">
                        <span class="material-symbols-outlined">call</span>
                      </div>
                      <p class="contact-info-text">+60 11-5550 5761</p>
                    </div>

                    <div class="contact-info-item">
                      <div class="contact-icon-box">
                        <span class="material-symbols-outlined">location_on</span>
                      </div>
                      <p class="contact-info-text">Midvalley Megamall Kuala Lumpur</p>
                    </div>
                  </div>

                  <div class="contact-social">
                    <a class="contact-social-link" href="https://www.facebook.com/chan.w.song.73" aria-label="Facebook">
                      <svg class="contact-social-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                      </svg>
                    </a>

                    <a class="contact-social-link" href="https://www.instagram.com/hermen__chan?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" aria-label="Instagram">
                      <svg class="contact-social-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                      </svg>
                    </a>

                    <a class="contact-social-link" href="#" aria-label="X (Twitter)">
                      <svg class="contact-social-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                      </svg>
                    </a>
                  </div>
                </div>

                <div class="contact-map-container">
                  <iframe
                    class="contact-map"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.8123456789!2d101.6769!3d3.1181!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc37d12d8c9231%3A0xf4b6e5c5c5c5c5c5!2sMid%20Valley%20Megamall!5e0!3m2!1sen!2smy!4v1234567890123!5m2!1sen!2smy"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Mid Valley Megamall Location">
                  </iframe>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</main>

<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';

  $errors = [];

  // Validation
  if (empty($full_name)) {
    $errors[] = 'Full name is required.';
  }

  if (empty($email)) {
    $errors[] = 'Email address is required.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
  }

  if (empty($subject)) {
    $errors[] = 'Subject is required.';
  }

  if (empty($message)) {
    $errors[] = 'Message is required.';
  }

  // If no errors, you can process the form (e.g., send email, save to database)
  if (empty($errors)) {
    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
      
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = '6403360weihao@gmail.com';
      $mail->Password   = 'kkch bjlp clpk kfyw';
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = 587;
      $mail->CharSet    = 'UTF-8';
      
      $mail->SMTPDebug = 0;  
      $mail->Debugoutput = 'html'; 
      
      // Recipients
      $mail->setFrom($email, $full_name);
      $mail->addAddress('weihaolee2005@gmail.com', 'Contact Form Recipient');
      $mail->addReplyTo($email, $full_name);
      
      // Content
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = '<h2>Contact Form Submission</h2>' .
                       '<p><strong>From:</strong> ' . htmlspecialchars($full_name) . '</p>' .
                       '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>' .
                       '<p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>' .
                       '<hr>' .
                       '<p><strong>Message:</strong></p>' .
                       '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
      $mail->AltBody = "Contact Form Submission\n\n" .
                       "From: " . $full_name . "\n" .
                       "Email: " . $email . "\n" .
                       "Subject: " . $subject . "\n\n" .
                       "Message:\n" . $message;
      
      $mail->send();
      
      // Success message
      echo '<div class="contact-message contact-message-success">';
      echo '<p>Thank you! Your message has been sent successfully.</p>';
      echo '</div>';
      
      // Clear form data
      $_POST = [];
    } catch (Exception $e) {
      // Error message
      echo '<div class="contact-message contact-message-error">';
      echo '<p><strong>Sorry, there was an error sending your message.</strong></p>';
      
      if ($mail->ErrorInfo) {
        $errorInfo = htmlspecialchars($mail->ErrorInfo);
        echo '<p><strong>Error Details:</strong> ' . $errorInfo . '</p>';
        
        // Provide helpful suggestions based on error type
        if (stripos($errorInfo, 'authenticate') !== false || stripos($errorInfo, 'login') !== false) {
          echo '<div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px;">';
          echo '<p><strong>Authentication Error - Common Solutions:</strong></p>';
          echo '<ul style="margin: 10px 0; padding-left: 20px;">';
          echo '<li>Make sure you\'re using a <strong>Gmail App Password</strong> (16 characters), not your regular password</li>';
          echo '<li>Enable 2-Step Verification in your Google Account</li>';
          echo '<li>Generate a new App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank">Google App Passwords</a></li>';
          echo '<li>Make sure there are no spaces in the App Password</li>';
          echo '</ul>';
          echo '</div>';
        } elseif (stripos($errorInfo, 'connection') !== false || stripos($errorInfo, 'timeout') !== false) {
          echo '<div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px;">';
          echo '<p><strong>Connection Error - Try:</strong></p>';
          echo '<ul style="margin: 10px 0; padding-left: 20px;">';
          echo '<li>Check your internet connection</li>';
          echo '<li>Try using SSL instead of STARTTLS (change Port to 465 and SMTPSecure to ENCRYPTION_SMTPS)</li>';
          echo '<li>Check if your firewall is blocking the connection</li>';
          echo '</ul>';
          echo '</div>';
        }
      } else {
        echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
      }
      
      echo '<p style="margin-top: 15px; font-size: 0.9em; color: #666;">If the problem persists, please contact the administrator.</p>';
      echo '</div>';
    }
  } else {
    // Display errors
    echo '<div class="contact-message contact-message-error">';
    echo '<ul class="contact-message-list">';
    foreach ($errors as $error) {
      echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
  }
}
?>

<?php include 'general/_footer.php'; ?>