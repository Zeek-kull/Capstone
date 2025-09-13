<?php
// Flash partial (toasts) - include where you want flash alerts displayed.
// Reads from $_SESSION['success_message'], $_SESSION['error_message'] and clears them.
if (session_status() == PHP_SESSION_NONE) session_start();

$messages = [];
if (!empty($_SESSION['success_message'])) {
    $messages[] = ['type' => 'success', 'text' => $_SESSION['success_message']];
    unset($_SESSION['success_message']);
}
if (!empty($_SESSION['error_message'])) {
    $messages[] = ['type' => 'danger', 'text' => $_SESSION['error_message']];
    unset($_SESSION['error_message']);
}
?>

<!-- Toast wrapper (fixed position) -->
<div class="cp-toast-wrapper" aria-live="polite" aria-atomic="true" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000;"></div>

<script>
// showFlash(type, message, timeoutMs)
// type: 'success' | 'danger' | 'warning' | 'info'
window.showFlash = function(type, message, timeoutMs) {
    try {
        timeoutMs = typeof timeoutMs === 'number' ? timeoutMs : 3000;
        var wrapper = document.querySelector('.cp-toast-wrapper');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'cp-toast-wrapper';
            wrapper.setAttribute('aria-live', 'polite');
            wrapper.setAttribute('aria-atomic', 'true');
            wrapper.style.position = 'fixed';
            wrapper.style.top = '1rem';
            wrapper.style.right = '1rem';
            wrapper.style.zIndex = 11000;
            document.body.appendChild(wrapper);
        }

        var toast = document.createElement('div');
        toast.className = 'toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.minWidth = '220px';
        toast.style.marginBottom = '0.5rem';

        var header = document.createElement('div');
        header.className = 'toast-header';
        // apply header color based on type
        var headerBgClass = (type === 'danger' ? 'bg-danger text-white' : (type === 'success' ? 'bg-success text-white' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-info text-dark')));
        header.className += ' ' + headerBgClass;
        var strong = document.createElement('strong');
        strong.className = 'me-auto';
        strong.textContent = (type === 'danger' ? 'Error' : (type === 'success' ? 'Success' : (type === 'warning' ? 'Warning' : 'Notice')));
        header.appendChild(strong);

        // var closeBtn = document.createElement('button');
        // closeBtn.type = 'button';
        // // use white close icon for dark headers
        // closeBtn.className = 'btn-close';
        // if (headerBgClass.indexOf('text-white') !== -1) {
        //     closeBtn.className += ' btn-close-white';
        // }
        // closeBtn.setAttribute('aria-label', 'Close');
        // closeBtn.addEventListener('click', function(){ try { toast.parentNode && toast.parentNode.removeChild(toast); } catch(e){} });
        // header.appendChild(closeBtn);

        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;

        toast.appendChild(header);
        toast.appendChild(body);
        wrapper.appendChild(toast);

        if (window.bootstrap && typeof bootstrap.Toast === 'function') {
            try {
                var bsToast = new bootstrap.Toast(toast, { delay: timeoutMs });
                bsToast.show();
                toast.addEventListener('hidden.bs.toast', function(){ try { toast.parentNode && toast.parentNode.removeChild(toast); } catch(e){} });
            } catch(e) {
                setTimeout(function(){ try { toast.parentNode && toast.parentNode.removeChild(toast); } catch(e){} }, timeoutMs + 300);
            }
        } else {
            toast.style.transition = 'opacity 0.25s ease';
            toast.style.opacity = '1';
            setTimeout(function(){
                toast.style.opacity = '0';
                setTimeout(function(){ try { toast.parentNode && toast.parentNode.removeChild(toast); } catch(e){} }, 300);
            }, timeoutMs);
        }
    } catch(e) {
        try { alert(message); } catch(e){}
    }
};

(function(){
    try {
        var msgs = <?php echo json_encode($messages, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        msgs.forEach(function(m){
            if (window.showFlash) showFlash(m.type === 'danger' ? 'danger' : (m.type || 'info'), m.text, 3000);
        });
    } catch(e){}
})();
</script>