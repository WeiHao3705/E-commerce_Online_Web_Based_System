(function(){
    const track = document.getElementById('productSlider');
    if(!track) return;

    let offset = 0;
    let speed = 0.4; // px per frame
    let cycleWidth = 0; // Width of one complete set of original images
    let rafId = null;
    let isInitialized = false;

    function ensureImagesLoaded(callback){
        const imgs = track.querySelectorAll('img');
        let remaining = imgs.length;
        if(remaining === 0){ callback(); return; }
        
        imgs.forEach(img => {
            if(img.complete && img.naturalHeight !== 0){
                decrement();
            } else {
                img.addEventListener('load', decrement);
                img.addEventListener('error', decrement);
            }
        });

        function decrement(){
            remaining--;
            if(remaining === 0) callback();
        }
    }

    function setupSlider(){
        // Stop animation if running during resize
        if(rafId) cancelAnimationFrame(rafId);
        
        const currentSlides = Array.from(track.children);

        if(!isInitialized) {
            cycleWidth = 0;
            const originals = Array.from(track.children);
            originals.forEach(el => {
                const style = window.getComputedStyle(el);
                const mr = parseFloat(style.marginRight) || 0;
                cycleWidth += el.offsetWidth + mr;
            });
            isInitialized = true;
        }
        
        let currentTotalWidth = 0;
        Array.from(track.children).forEach(el => {
            currentTotalWidth += el.offsetWidth + parseFloat(getComputedStyle(el).marginRight||0);
        });
        
        // Robust method: Clone the original set repeatedly until safe
        const originals = Array.from(track.children).slice(0, track.children.length); // snapshot current
        
        // If cycleWidth is 0 (hidden or no items), stop.
        if(cycleWidth <= 0) return;

        while(currentTotalWidth < window.innerWidth + cycleWidth * 2) {
             originals.forEach(orig => {
                const clone = orig.cloneNode(true);
                clone.classList.add('clone'); // optional, for debugging
                track.appendChild(clone);
                const style = window.getComputedStyle(clone);
                const mr = parseFloat(style.marginRight) || 0;
                currentTotalWidth += clone.offsetWidth + mr;
             });
        }
        
        // Start animation
        step();
    }

    function step(){
        offset -= speed;
        
        if (Math.abs(offset) >= cycleWidth) {
            offset += cycleWidth; 
        }

        track.style.transform = `translate3d(${offset}px,0,0)`;
        rafId = requestAnimationFrame(step);
    }

    // Initialization
    ensureImagesLoaded(() => {
        setupSlider();
    });

    // Handle Resize (optional: simpler to just refresh, but here we just ensure width is okay)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
        }, 200);
    });

})();