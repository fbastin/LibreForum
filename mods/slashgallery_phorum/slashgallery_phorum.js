function slashgallery_open_selector() {
    const width = 800;
    const height = 600;
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;
    
    window.open(
        '/gallery.php?mode=selector',
        'slashgallery_selector',
        `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`
    );
}

// Listen for messages from the selector popup
window.addEventListener('message', function(event) {
    // Only accept messages from our own origin
    if (event.origin !== window.location.origin) return;

    if (event.data && event.data.type === 'slashgallery_select') {
        const url = event.data.url;
        const bbcode = `[img]${url}[/img]`;
        
        // LibreForum editor_tools integration
        if (typeof editor_tools_handle_btn_bbcode === 'function') {
            editor_tools_handle_btn_bbcode(bbcode, '');
        } else {
            // Fallback for raw textarea
            const textarea = document.getElementById('body');
            if (textarea) {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, start) + bbcode + textarea.value.substring(end);
                textarea.focus();
            }
        }
    }
});
