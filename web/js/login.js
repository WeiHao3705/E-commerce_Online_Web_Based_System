(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var toggle = document.getElementById('togglePassword');
        var input = document.getElementById('password');
        if (!toggle || !input) return;

        toggle.addEventListener('click', function(){
            var type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            if(type === 'text'){
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
                toggle.setAttribute('title','Hide password');
            } else {
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
                toggle.setAttribute('title','Show password');
            }
        });
    });
})();
