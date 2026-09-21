// =====================================================
// AssetGuard — Main JavaScript
// =====================================================

/**
 * Renders an animated circular gauge (SVG) inside a container.
 * Usage: renderGauge('gauge-1', 72, '#F2A65A');
 */
function renderGauge(containerId, percentage, color = '#F2A65A') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const radius = 38;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percentage / 100) * circumference;

    container.innerHTML = `
        <svg width="90" height="90" viewBox="0 0 90 90">
            <circle class="gauge-bg" cx="45" cy="45" r="${radius}" fill="none" stroke-width="8"></circle>
            <circle class="gauge-fill" cx="45" cy="45" r="${radius}" fill="none" stroke-width="8"
                stroke="${color}"
                stroke-dasharray="${circumference}"
                stroke-dashoffset="${circumference}"
                transform="rotate(-90 45 45)"></circle>
        </svg>
        <div class="gauge-label">${percentage}%</div>
    `;

    // Animate after paint
    requestAnimationFrame(() => {
        const fill = container.querySelector('.gauge-fill');
        if (fill) fill.style.strokeDashoffset = offset;
    });
}

// Auto-dismiss flash alerts after 4 seconds
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.glass-alert');
    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity 400ms ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 4000);
    });
});
