<?php
/**
 * Helper functions for GUI-based popup notifications using SweetAlert2.
 */

/**
 * Display a SweetAlert2 notification.
 * 
 * @param string $message The message to display.
 * @param string $type The type of notification: 'success', 'error', 'warning', 'info', 'question'.
 * @param string $redirect Optional URL to redirect to after the alert is closed.
 */
function showNotification($message, $type = 'info', $redirect = null) {
    $title = ucfirst($type);
    $icon = $type;
    
    // Sanitize message for JS
    $message = addslashes($message);
    $message = str_replace(["\r", "\n"], ' ', $message);

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '$title',
                text: '$message',
                icon: '$icon',
                confirmButtonColor: '#1e0178',
                timer: " . ($type === 'success' || $type === 'info' ? '3000' : 'null') . ",
                timerProgressBar: " . ($type === 'success' || $type === 'info' ? 'true' : 'false') . ",
            }).then((result) => {
                " . ($redirect ? "window.location.href = '$redirect';" : "") . "
            });
        });
    </script>";
}

/**
 * Alternative for inline echoes that need to be replaced.
 * This will use Toast-style notifications for non-critical messages.
 */
function showToast($message, $type = 'info') {
    $icon = $type;
    $message = addslashes($message);
    $message = str_replace(["\r", "\n"], ' ', $message);

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: '$icon',
                title: '$message'
            });
        });
    </script>";
}
