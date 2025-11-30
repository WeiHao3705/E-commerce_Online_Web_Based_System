<?php 
session_start();
$pageTitle = 'Contact Us';

// Calculate base path
$currentFileDir = dirname(__FILE__);
$webBasePath = str_replace('\\', '/', $currentFileDir) . '/';
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webBasePath);
$prefix = str_replace('\\', '/', $relativePath) . '/';

include 'general/_header.php';
include 'general/_navbar.php';
?>

<!-- Contact Us Page Styles -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#ec1313",
            "background-light": "#f8f6f6",
            "background-dark": "#221010",
          },
          fontFamily: {
            "display": ["Lexend"]
          },
          borderRadius: {
            "DEFAULT": "0.25rem",
            "lg": "0.5rem",
            "xl": "0.75rem",
            "full": "9999px"
          },
        },
      },
    }
</script>

<style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      font-size: 20px;
    }
    
    body {
        font-family: 'Lexend', sans-serif;
    }
    
    /* Override default body styles for contact page */
    .contact-page-wrapper {
        background-color: #f8f6f6;
        min-height: calc(100vh - 200px);
    }
</style>

<main class="contact-page-wrapper">
<div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
<div class="layout-container flex h-full grow flex-col">
<div class="px-4 md:px-10 lg:px-20 xl:px-40 flex flex-1 justify-center py-5">
<div class="layout-content-container flex flex-col max-w-6xl flex-1">

<main class="flex-1 py-10 md:py-16">
<div class="flex min-w-72 flex-col gap-3 px-4 pb-10 text-center">
<p class="text-4xl font-black leading-tight tracking-[-0.033em] md:text-5xl text-[#1b0d0d] dark:text-[#f8f6f6]">Contact Our Team</p>
<p class="text-base font-normal leading-normal text-[#9a4c4c] dark:text-[#a18989] max-w-xl mx-auto">Have a question or need support? Fill out the form below and we'll get back to you as soon as possible.</p>
</div>

<div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16 xl:gap-24 px-4">
<div class="flex flex-col gap-6">
<h2 class="text-2xl font-bold leading-tight tracking-[-0.015em] text-[#1b0d0d] dark:text-[#f8f6f6]">Send us a Message</h2>
<form class="grid grid-cols-1 gap-6 sm:grid-cols-2" method="POST" action="<?php echo $prefix; ?>contact.php">
<div class="sm:col-span-1">
<label class="flex flex-col w-full">
<p class="text-base font-medium leading-normal pb-2 text-[#1b0d0d] dark:text-[#f8f6f6]">Full Name</p>
<input name="full_name" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-[#1b0d0d] dark:text-[#f8f6f6] focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#e7cfcf] dark:border-[#3d2b2b] bg-background-light dark:bg-background-dark h-14 placeholder:text-[#9a4c4c] dark:placeholder:text-[#a18989] p-[15px] text-base font-normal leading-normal" placeholder="Enter your full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"/>
</label>
</div>

<div class="sm:col-span-1">
<label class="flex flex-col w-full">
<p class="text-base font-medium leading-normal pb-2 text-[#1b0d0d] dark:text-[#f8f6f6]">Email Address</p>
<input type="email" name="email" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-[#1b0d0d] dark:text-[#f8f6f6] focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#e7cfcf] dark:border-[#3d2b2b] bg-background-light dark:bg-background-dark h-14 placeholder:text-[#9a4c4c] dark:placeholder:text-[#a18989] p-[15px] text-base font-normal leading-normal" placeholder="you@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
</label>
</div>

<div class="sm:col-span-2">
<label class="flex flex-col w-full">
<p class="text-base font-medium leading-normal pb-2 text-[#1b0d0d] dark:text-[#f8f6f6]">Subject</p>
<input name="subject" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-[#1b0d0d] dark:text-[#f8f6f6] focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#e7cfcf] dark:border-[#3d2b2b] bg-background-light dark:bg-background-dark h-14 placeholder:text-[#9a4c4c] dark:placeholder:text-[#a18989] p-[15px] text-base font-normal leading-normal" placeholder="How can we help?" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"/>
</label>
</div>

<div class="sm:col-span-2">
<label class="flex flex-col w-full">
<p class="text-base font-medium leading-normal pb-2 text-[#1b0d0d] dark:text-[#f8f6f6]">Your Message</p>
<textarea name="message" class="form-textarea flex w-full min-w-0 flex-1 resize-y overflow-hidden rounded-lg text-[#1b0d0d] dark:text-[#f8f6f6] focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#e7cfcf] dark:border-[#3d2b2b] bg-background-light dark:bg-background-dark min-h-36 placeholder:text-[#9a4c4c] dark:placeholder:text-[#a18989] p-[15px] text-base font-normal leading-normal" placeholder="Write your message here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
</label>
</div>

<div class="sm:col-span-2">
<button type="submit" class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-6 bg-primary text-white text-base font-bold leading-normal tracking-[0.015em] hover:bg-[#d01111] transition-colors">
<span class="truncate">Send Message</span>
</button>
</div>
</form>
</div>

<div class="flex flex-col gap-8">
<div class="flex flex-col gap-6">
<h3 class="text-2xl font-bold leading-tight tracking-[-0.015em] text-[#1b0d0d] dark:text-[#f8f6f6]">Other Ways to Connect</h3>
<div class="flex flex-col gap-4">
<div class="flex items-center gap-4">
<div class="flex size-10 items-center justify-center rounded-lg bg-[#f3e7e7] dark:bg-[#2a1a1a]"><span class="material-symbols-outlined text-[#1b0d0d] dark:text-[#f8f6f6]">mail</span></div>
<p class="text-base font-medium text-[#1b0d0d] dark:text-[#f8f6f6]">info@ngear.com</p>
</div>

<div class="flex items-center gap-4">
<div class="flex size-10 items-center justify-center rounded-lg bg-[#f3e7e7] dark:bg-[#2a1a1a]"><span class="material-symbols-outlined text-[#1b0d0d] dark:text-[#f8f6f6]">call</span></div>
<p class="text-base font-medium text-[#1b0d0d] dark:text-[#f8f6f6]">+60 11-5550 5761</p>
</div>

<div class="flex items-center gap-4">
<div class="flex size-10 items-center justify-center rounded-lg bg-[#f3e7e7] dark:bg-[#2a1a1a]"><span class="material-symbols-outlined text-[#1b0d0d] dark:text-[#f8f6f6]">location_on</span></div>
<p class="text-base font-medium text-[#1b0d0d] dark:text-[#f8f6f6]">Midvalley Megamall Kuala Lumpur</p>
</div>
</div>

<div class="flex gap-4 pt-2">
<a class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#f3e7e7] dark:bg-[#2a1a1a] text-[#1b0d0d] dark:text-[#f8f6f6] hover:bg-[#e7d7d7] dark:hover:bg-[#3a2a2a] transition-colors" href="#" aria-label="Facebook">
<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
</svg>
</a>

<a class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#f3e7e7] dark:bg-[#2a1a1a] text-[#1b0d0d] dark:text-[#f8f6f6] hover:bg-[#e7d7d7] dark:hover:bg-[#3a2a2a] transition-colors" href="#" aria-label="Instagram">
<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
</svg>
</a>

<a class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#f3e7e7] dark:bg-[#2a1a1a] text-[#1b0d0d] dark:text-[#f8f6f6] hover:bg-[#e7d7d7] dark:hover:bg-[#3a2a2a] transition-colors" href="#" aria-label="X (Twitter)">
<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
</svg>
</a>
</div>
</div>

<div class="aspect-video w-full overflow-hidden rounded-xl">
<iframe 
    class="h-full w-full border-0 rounded-xl" 
    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.8123456789!2d101.6769!3d3.1181!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc37d12d8c9231%3A0xf4b6e5c5c5c5c5c5!2sMid%20Valley%20Megamall!5e0!3m2!1sen!2smy!4v1234567890123!5m2!1sen!2smy"
    allowfullscreen="" 
    loading="lazy" 
    referrerpolicy="no-referrer-when-downgrade"
    title="Mid Valley Megamall Location">
</iframe>
</div>
</div>
</div>
</div>
</main>
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
        // TODO: Implement email sending or database storage here
        // For now, we'll just show a success message
        echo '<div class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">';
        echo '<p>Thank you! Your message has been sent successfully.</p>';
        echo '</div>';
        
        // Clear form data
        $_POST = [];
    } else {
        // Display errors
        echo '<div class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">';
        echo '<ul class="list-disc list-inside">';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}
?>

<?php include 'general/_footer.php'; ?>

