document.addEventListener('DOMContentLoaded', function(){
    window.addEventListener('pageshow', function(event){
        var navEntries = [];
        try {
            var entries = performance.getEntriesByType && performance.getEntriesByType('navigation');
            if (entries && entries.length) navEntries = entries;
        } catch(e){}

        var isBackForward = (event.persisted === true) || (navEntries.length && navEntries[0].type === 'back_forward');
        if (isBackForward) {
            var url = window.location.href.split('?')[0] + '?start=1';
            window.location.replace(url);
        }
    });
});
