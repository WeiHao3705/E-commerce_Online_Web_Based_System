(function(){
    const track = document.getElementById('productSlider');
    if(!track) return;

    let offset = 0;
    let speed = 0.6; // px per frame
    let slideWidth = 0; // width of a single slide (first)
    let totalWidth = 0; // total width of all slides in the track
    let rafId = null;

    // Create a seamless loop by cloning slides to ensure continuous width
    (function duplicateForLoop(){
        const slides = Array.from(track.children);
        const minTotalWidth = window.innerWidth * 2; // ensure at least 2x viewport width
        totalWidth = slides.reduce((acc, el)=> acc + el.offsetWidth + parseFloat(getComputedStyle(el).marginRight||0), 0);
        let i = 0;
        while(totalWidth < minTotalWidth && slides.length){
            const clone = slides[i % slides.length].cloneNode(true);
            track.appendChild(clone);
            totalWidth += clone.offsetWidth + parseFloat(getComputedStyle(clone).marginRight||0);
            i++;
        }
    })();

    function computeSlideWidth(){
        const first = track.firstElementChild;
        if(!first) return 0;
        const style = window.getComputedStyle(first);
        const mr = parseFloat(style.marginRight) || 0;
        return first.offsetWidth + mr; // use offsetWidth to avoid forced layout each frame
    }

    function ensureImagesLoaded(callback){
        const imgs = track.querySelectorAll('img');
        let remaining = imgs.length;
        if(remaining === 0){ callback(); return; }
        imgs.forEach(img => {
            if(img.complete){
                if(--remaining === 0) callback();
            } else {
                img.addEventListener('load', () => { if(--remaining === 0) callback(); });
                img.addEventListener('error', () => { if(--remaining === 0) callback(); });
            }
        });
    }

    function step(){
        offset -= speed;
        // use translate3d for GPU acceleration
        track.style.transform = `translate3d(${offset}px,0,0)`;
        if(slideWidth <= 0 || totalWidth <= 0){ rafId = requestAnimationFrame(step); return; }
        // Wrap when we've scrolled the full width of the track to avoid visible restart
        if(Math.abs(offset) >= totalWidth){
            offset += totalWidth; // wrap seamlessly without moving DOM
        }
        rafId = requestAnimationFrame(step);
    }

    function computeTotalWidth(){
        let sum = 0;
        for(const el of track.children){
            const style = window.getComputedStyle(el);
            const mr = parseFloat(style.marginRight) || 0;
            sum += el.offsetWidth + mr;
        }
        return sum;
    }

    function onResize(){
        slideWidth = computeSlideWidth();
        totalWidth = computeTotalWidth();
    }

    window.addEventListener('resize', () => { onResize(); });

    ensureImagesLoaded(() => {
        onResize();
        // set initial transform to avoid jump
        track.style.transform = 'translate3d(0,0,0)';
        rafId = requestAnimationFrame(step);
    });
})();
