document.addEventListener('DOMContentLoaded', function() {
    const joinClassForm = document.getElementById('joinClassForm');
    const classCodeInput = document.getElementById('classCode');
    const joinClassBtn = document.getElementById('joinClassBtn');
    const notification = document.getElementById('notification');

    if (joinClassForm) {
        joinClassForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (joinClassBtn) {
                joinClassBtn.disabled = true;
                joinClassBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Joining...';
            }

            try {
                const response = await fetch('join_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        classCode: classCodeInput.value.trim()
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    showNotification('success', data.message);
                    // Reload the page after successful join
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('error', data.error);
                }
            } catch (error) {
                showNotification('error', 'An error occurred while joining the class');
            } finally {
                if (joinClassBtn) {
                    joinClassBtn.disabled = false;
                    joinClassBtn.innerHTML = 'Join Class';
                }
            }
        });
    }

    function showNotification(type, message) {
        if (notification) {
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            notification.style.display = 'block';
            notification.style.position = 'fixed';
            notification.style.top = '80px';
            notification.style.left = '50%';
            notification.style.transform = 'translateX(-50%)';
            notification.style.zIndex = '1500';
            notification.style.minWidth = '300px';
            notification.style.textAlign = 'center';
            notification.style.boxShadow = '0 4px 10px rgba(0, 0, 0, 0.2)';
        }
    }
}); 