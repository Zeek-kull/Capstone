<?php
// Flash partial - include this file where you want flash alerts displayed.
// It reads from $_SESSION['success_message'] and $_SESSION['error_message'] and then clears them.
if (session_status() == PHP_SESSION_NONE) session_start();

$hasFlash = false;
if (isset($_SESSION['success_message']) && $_SESSION['success_message'] !== '') {
    $hasFlash = true;
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message']) && $_SESSION['error_message'] !== '') {
    $hasFlash = true;
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Render any server-side flashes
if ($hasFlash) {
    ?>
    <div class="container mt-3 cp-flash-container" role="status" aria-live="polite" aria-atomic="true">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show cp-flash" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show cp-flash" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// JS helper: always output so client-side code can create flashes that match server-side ones.
?>
<script>
// showFlash(type, message, timeoutMs) — type: 'success'|'danger'|'warning'|'info'
window.showFlash = function(type, message, timeoutMs) {
    try {
        timeoutMs = typeof timeoutMs === 'number' ? timeoutMs : 5000;
        var container = document.querySelector('.cp-flash-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'container mt-3 cp-flash-container';
            container.setAttribute('role', 'status');
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            // insert at top of body for visibility
            if (document.body.firstChild) {
                document.body.insertBefore(container, document.body.firstChild);
            } else {
                document.body.appendChild(container);
            }
        }

        var alertDiv = document.createElement('div');
    alertDiv.setAttribute('role', 'alert');
    // make sure assistive tech knows this message is new
    alertDiv.setAttribute('aria-atomic', 'true');
        alertDiv.className = 'alert alert-' + (type || 'info') + ' alert-dismissible fade show cp-flash';
        alertDiv.innerHTML = document.createTextNode(message) ? '' : '';
        // safe text node
        var textNode = document.createTextNode(message);
        alertDiv.appendChild(textNode);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'close';
        btn.setAttribute('data-dismiss', 'alert');
        btn.setAttribute('aria-label', 'Close');
        btn.innerHTML = '<span aria-hidden="true">&times;</span>';
        btn.addEventListener('click', function(){
            try { alertDiv.parentNode && alertDiv.parentNode.removeChild(alertDiv); } catch(e){}
        });
        alertDiv.appendChild(btn);

        container.appendChild(alertDiv);

        // ensure we get the CSS transition
        if (!alertDiv.classList.contains('fade')) alertDiv.classList.add('fade');
        if (!alertDiv.classList.contains('show')) alertDiv.classList.add('show');

        // schedule hide
        setTimeout(function(){
            alertDiv.classList.remove('show');
            var removed = false;
            var removeFn = function(){
                if (removed) return; removed = true;
                try { alertDiv.parentNode && alertDiv.parentNode.removeChild(alertDiv); } catch(e){}
            };
            alertDiv.addEventListener('transitionend', removeFn);
            setTimeout(removeFn, 700);
        }, timeoutMs);
    } catch(e) { /* silent */ }
};

// Auto-hide any existing server-rendered flashes (only if present)
(function(){
    try {
        var flashes = document.querySelectorAll('.cp-flash');
        if (!flashes || !flashes.length) return;
        flashes.forEach(function(f){
            if (!f.classList.contains('fade')) f.classList.add('fade');
            if (!f.classList.contains('show')) f.classList.add('show');
            setTimeout(function(){
                f.classList.remove('show');
                var removed = false;
                var removeFn = function(){
                    if (removed) return; removed = true;
                    try { f.parentNode && f.parentNode.removeChild(f); } catch(e){}
                };
                f.addEventListener('transitionend', removeFn);
                setTimeout(removeFn, 700);
            }, 5000);
        });
    } catch(e){}
})();
</script>
<?php
?>