(function () {
    const links = document.querySelectorAll('.settings-sidebar .nav-link[data-panel]');
    const panels = document.querySelectorAll('[data-panel-content]');

    links.forEach(function (link) {
        link.addEventListener('click', function () {
            links.forEach(function (l) { l.classList.remove('active'); });
            link.classList.add('active');
            panels.forEach(function (p) {
                p.classList.toggle('d-none', p.dataset.panelContent !== link.dataset.panel);
            });
        });
    });
})();