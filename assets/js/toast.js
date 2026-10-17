//Boots any server-rendered flashdata toasts and exposes a client-side helper
//so pages can raise Bootstrap toasts instead of blocking alert() dialogs.
(function () {
    'use strict';

    var STYLES = {
        success: { cls: 'text-bg-success', title: 'Success' },
        error: { cls: 'text-bg-danger', title: 'Error' },
        info: { cls: 'text-bg-info', title: 'Notice' }
    };

    function container() {
        var el = document.querySelector('.toast-container');
        if (!el) {
            el = document.createElement('div');
            el.className = 'toast-container top-0 end-0 z-3';
            document.body.appendChild(el);
        }
        return el;
    }

    //Displays a transient toast. type is success|error|info (defaults to info).
    function showToast(message, type) {
        var style = STYLES[type] || STYLES.info;

        var el = document.createElement('div');
        el.className = 'toast ' + style.cls + ' border-0 fade m-3';
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-atomic', 'true');

        var header = document.createElement('div');
        header.className = 'toast-header ' + style.cls + ' text-white border-bottom border-white border-opacity-25';

        var title = document.createElement('strong');
        title.className = 'me-auto';
        title.textContent = style.title;

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white';
        close.setAttribute('data-bs-dismiss', 'toast');
        close.setAttribute('aria-label', 'Close');

        header.appendChild(title);
        header.appendChild(close);

        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;

        el.appendChild(header);
        el.appendChild(body);
        container().appendChild(el);

        var toast = new bootstrap.Toast(el, { delay: 5000 });
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
        toast.show();

        return toast;
    }

    window.showToast = showToast;

    document.querySelectorAll('.toast').forEach(function (toastEl) {
        new bootstrap.Toast(toastEl).show();
    });
})();
