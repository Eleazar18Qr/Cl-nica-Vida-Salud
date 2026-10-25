document.addEventListener('DOMContentLoaded', () => {
    if (typeof bootstrap === 'undefined') {
        return;
    }

    const alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach((alert) => {
        window.setTimeout(() => {
            const instance = bootstrap.Alert.getOrCreateInstance(alert);
            instance.close();
        }, 6000);
    });
});
