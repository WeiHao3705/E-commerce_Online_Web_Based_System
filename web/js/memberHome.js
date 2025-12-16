$(document).ready(function(){
    var $carousel = $('#homeCarousel');
    if (!$carousel.length) return;
    var $slides = $carousel.find('.slide');
    var $indicators = $carousel.find('.indicator');
    var $prev = $carousel.find('.prev');
    var $next = $carousel.find('.next');
    var current = 0;
    var interval = null;
    var delay = 3000; // 3 seconds

    function show(n) {
        if (!$slides.length) return;
        $slides.eq(current).removeClass('active');
        $indicators.eq(current).removeClass('active');
        current = (n + $slides.length) % $slides.length;
        $slides.eq(current).addClass('active');
        $indicators.eq(current).addClass('active');
    }

    function nextSlide(){ show(current + 1); }
    function prevSlide(){ show(current - 1); }

    function start(){ if (interval) clearInterval(interval); interval = setInterval(nextSlide, delay); }
    function stop(){ if (interval) { clearInterval(interval); interval = null; } }

    $next.on('click', function(e){ e.preventDefault(); nextSlide(); stop(); start(); });
    $prev.on('click', function(e){ e.preventDefault(); prevSlide(); stop(); start(); });

    $indicators.on('click', function(){ 
        var idx = parseInt($(this).attr('data-index')) || 0; 
        show(idx); 
        stop(); 
        start(); 
    });

    $carousel.on('mouseenter', stop);
    $carousel.on('mouseleave', start);

    // Start autoplay
    start();
});
