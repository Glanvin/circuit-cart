(function () {
    var root = document.documentElement;
    var toggleUrl = root.getAttribute('data-theme-url') || '';
    var stored = root.getAttribute('data-theme-preference') || 'light';
    var VALID = ['light', 'dark', 'system'];
    var systemMedia = window.matchMedia('(prefers-color-scheme: dark)');

    var preference = VALID.indexOf(stored) !== -1 ? stored : 'light';
    var darkToggle = document.getElementById('darkModeToggle');
    var systemToggle = document.getElementById('systemThemeToggle');

    function resolve(themePref) {
        if (themePref === 'system') {
            return systemMedia.matches ? 'dark' : 'light';
        }
        return themePref === 'dark' ? 'dark' : 'light';
    }

    function render() {
        var resolved = resolve(preference);
        root.setAttribute('data-bs-theme', resolved);

        if (darkToggle) {
            darkToggle.checked = resolved === 'dark';
            darkToggle.disabled = preference === 'system';
        }
        if (systemToggle) {
            systemToggle.checked = preference === 'system';
        }
    }

    function persist(themePref) {
        if (!toggleUrl) {
            return;
        }
        fetch(toggleUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: window.CSRF ? window.CSRF.appendTo('theme=' + encodeURIComponent(themePref)) : 'theme=' + encodeURIComponent(themePref)
        });
    }

    if (darkToggle) {
        darkToggle.addEventListener('change', function () {
            if (preference === 'system') {
                return;
            }
            preference = darkToggle.checked ? 'dark' : 'light';
            render();
            persist(preference);
        });
    }

    if (systemToggle) {
        systemToggle.addEventListener('change', function () {
            if (systemToggle.checked) {
                preference = 'system';
            } else {
                preference = resolve('system');
            }
            render();
            persist(preference);
        });
    }

    if (systemMedia.addEventListener) {
        systemMedia.addEventListener('change', function () {
            if (preference !== 'system') {
                return;
            }
            render();
        });
    }

    render();
})();