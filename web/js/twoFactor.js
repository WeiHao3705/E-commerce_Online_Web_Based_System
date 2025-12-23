// Two-Factor Authentication JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('code');
    const form = document.getElementById('twoFactorForm') || document.getElementById('twoFactorSetupForm');

    if (codeInput) {
        // Only allow numeric input
        codeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Auto-submit when 6 digits are entered
        codeInput.addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // Small delay to ensure the value is set
                setTimeout(function() {
                    if (form) {
                        form.submit();
                    }
                }, 100);
            }
        });

        // Focus on input when page loads
        codeInput.focus();
    }
});

