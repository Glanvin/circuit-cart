document.addEventListener('DOMContentLoaded', initFilters);

function initFilters() {
    const groups = document.querySelectorAll('[data-filter-group]');
    const cards = document.querySelectorAll('#parts-container .part-card');
    const noResults = document.getElementById('no-results');

    if (!groups.length || !cards.length) return;

    // Active selection per group: { 'device-type': '', 'category': '' } ('' = All)
    const selection = {};

    groups.forEach(group => {
        const groupName = group.dataset.filterGroup;
        selection[groupName] = '';

        group.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', () => {
                selection[groupName] = btn.dataset.filter;

                group.querySelectorAll('[data-filter]').forEach(b => {
                    b.classList.remove('active', 'btn-info');
                    b.classList.add('btn-outline-secondary');
                });
                btn.classList.add('active', 'btn-info');
                btn.classList.remove('btn-outline-secondary');

                applyFilters();
            });
        });
    });

    function applyFilters() {
        let visibleCount = 0;

        cards.forEach(card => {
            const matches = Object.entries(selection).every(([group, value]) => {
                return value === '' || card.dataset[group.replace(/-(\w)/g, (_, c) => c.toUpperCase())] === value;
            });

            card.classList.toggle('d-none', !matches);
            if (matches) visibleCount++;
        });

        if (noResults) {
            noResults.classList.toggle('d-none', visibleCount > 0);
        }
    }
}
