(function () {
    'use strict';

    let activeButton = null;
    const tooltip = document.createElement('div');
    tooltip.id = 'feature-tooltip-panel';
    tooltip.className = 'feature-tooltip-panel';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.hidden = true;
    document.body.appendChild(tooltip);

    function positionTooltip() {
        if (!activeButton || tooltip.hidden || window.innerWidth <= 767) return;
        const trigger = activeButton.getBoundingClientRect();
        const panel = tooltip.getBoundingClientRect();
        const gutter = 12;
        const left = Math.min(
            window.innerWidth - panel.width - gutter,
            Math.max(gutter, trigger.left + (trigger.width / 2) - (panel.width / 2))
        );
        let top = trigger.bottom + 10;
        if (top + panel.height > window.innerHeight - gutter) {
            top = Math.max(gutter, trigger.top - panel.height - 10);
        }
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }

    function closeTooltip() {
        if (activeButton) {
            activeButton.classList.remove('is-open');
            activeButton.setAttribute('aria-expanded', 'false');
            activeButton.removeAttribute('aria-describedby');
        }
        activeButton = null;
        tooltip.hidden = true;
        tooltip.textContent = '';
        tooltip.style.left = '';
        tooltip.style.top = '';
    }

    function openTooltip(button) {
        const content = (button.dataset.tooltip || '').trim();
        if (!content) return;
        if (activeButton && activeButton !== button) closeTooltip();
        activeButton = button;
        tooltip.textContent = content;
        tooltip.hidden = false;
        button.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        button.setAttribute('aria-describedby', tooltip.id);
        requestAnimationFrame(positionTooltip);
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.feature-info');
        if (button) {
            event.preventDefault();
            event.stopPropagation();
            if (activeButton === button) closeTooltip();
            else openTooltip(button);
            return;
        }
        if (!event.target.closest('#feature-tooltip-panel')) closeTooltip();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeTooltip();
    });

    window.addEventListener('resize', positionTooltip, { passive: true });
    window.addEventListener('orientationchange', closeTooltip, { passive: true });
    document.addEventListener('scroll', positionTooltip, true);
})();