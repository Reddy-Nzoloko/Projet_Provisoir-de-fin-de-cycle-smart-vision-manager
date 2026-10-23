(() => {
    const menuButton = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    const setMenuOpen = (isOpen) => {
        sidebar.classList.toggle('-translate-x-full', !isOpen);
        overlay.classList.toggle('hidden', !isOpen);
        menuButton.setAttribute('aria-expanded', String(isOpen));
    };

    menuButton.addEventListener('click', () => {
        setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true');
    });
    overlay.addEventListener('click', () => setMenuOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenuOpen(false);
    });

    if (typeof Chart === 'undefined') return;

    const data = window.smartVisionDashboard;
    const gridColor = '#f1f0ee';
    const tickColor = '#a8a29e';
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1c1917',
                padding: 11,
                cornerRadius: 10,
                titleFont: { family: 'DM Sans', size: 11, weight: '500' },
                bodyFont: { family: 'DM Sans', size: 12, weight: '600' },
                displayColors: false
            }
        },
        scales: {
            x: {
                grid: { display: false },
                border: { display: false },
                ticks: { color: tickColor, font: { family: 'DM Sans', size: 10 }, maxRotation: 0 }
            },
            y: {
                beginAtZero: true,
                grid: { color: gridColor, drawTicks: false },
                border: { display: false, dash: [3, 4] },
                ticks: { color: tickColor, font: { family: 'DM Sans', size: 10 }, padding: 10, maxTicksLimit: 5 }
            }
        }
    };

    new Chart(document.getElementById('payments-chart'), {
        type: 'line',
        data: {
            labels: data.payments.labels,
            datasets: [{
                data: data.payments.values,
                borderColor: '#1c1917',
                backgroundColor: (context) => {
                    const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 260);
                    gradient.addColorStop(0, 'rgba(28, 25, 23, .12)');
                    gradient.addColorStop(1, 'rgba(28, 25, 23, 0)');
                    return gradient;
                },
                fill: true,
                tension: 0.38,
                borderWidth: 2.2,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#1c1917',
                pointBorderWidth: 2
            }]
        },
        options: {
            ...baseOptions,
            plugins: {
                ...baseOptions.plugins,
                tooltip: {
                    ...baseOptions.plugins.tooltip,
                    callbacks: { label: (context) => `${new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(context.parsed.y)}` }
                }
            }
        }
    });

    new Chart(document.getElementById('traffic-chart'), {
        type: 'bar',
        data: {
            labels: data.traffic.labels,
            datasets: [{
                data: data.traffic.values,
                backgroundColor: '#292524',
                hoverBackgroundColor: '#57534e',
                borderRadius: 5,
                maxBarThickness: 22
            }]
        },
        options: {
            ...baseOptions,
            plugins: {
                ...baseOptions.plugins,
                tooltip: {
                    ...baseOptions.plugins.tooltip,
                    callbacks: { label: (context) => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(context.parsed.y)} Go` }
                }
            }
        }
    });
})();
