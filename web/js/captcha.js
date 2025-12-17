// Add enter key support and auto-focus
document.addEventListener('DOMContentLoaded', function() {
    const input = document.querySelector('input[name="captcha_input"]');
    if (input) {
        input.focus();
    }
});
