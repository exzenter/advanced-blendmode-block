/**
 * Frontend JavaScript for Advanced Blend Mode Block
 * Positions the overlay divs to exactly match the base element
 */

(function () {
    'use strict';

    function positionOverlays() {
        // Find all base elements with the Stripe effect
        const baseElements = document.querySelectorAll('.abmb-stripe-base');

        baseElements.forEach(function (base) {
            // Get the next two siblings (burn and soft layers)
            const burn = base.nextElementSibling;
            const soft = burn ? burn.nextElementSibling : null;

            if (!burn || !burn.classList.contains('abmb-stripe-burn')) return;
            if (!soft || !soft.classList.contains('abmb-stripe-soft')) return;

            // Get the base element's position and dimensions
            const rect = base.getBoundingClientRect();

            // Use offsetTop/offsetLeft which are relative to the offsetParent
            // This correctly positions the overlays relative to the same containing block
            const relativeTop = base.offsetTop;
            const relativeLeft = base.offsetLeft;

            // Get computed styles from base element to copy to overlays
            const computedStyle = window.getComputedStyle(base);

            // Position and style both overlays to match the base exactly
            [burn, soft].forEach(function (overlay) {
                // Position absolutely relative to offsetParent
                overlay.style.position = 'absolute';
                overlay.style.top = relativeTop + 'px';
                overlay.style.left = relativeLeft + 'px';
                overlay.style.width = rect.width + 'px';
                overlay.style.height = rect.height + 'px';

                // Copy typography styles from base element
                overlay.style.fontFamily = computedStyle.fontFamily;
                overlay.style.fontSize = computedStyle.fontSize;
                overlay.style.fontWeight = computedStyle.fontWeight;
                overlay.style.lineHeight = computedStyle.lineHeight;
                overlay.style.letterSpacing = computedStyle.letterSpacing;
                overlay.style.textTransform = computedStyle.textTransform;
                overlay.style.textAlign = computedStyle.textAlign;
                overlay.style.padding = computedStyle.padding;
                overlay.style.margin = '0';
                overlay.style.boxSizing = 'border-box';
            });
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', positionOverlays);
    } else {
        positionOverlays();
    }

    // Debounce helper to prevent rapid-fire calls
    let resizeTimeout;
    function handleResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(positionOverlays, 100);
    }

    // Re-run on window resize (debounced to prevent jank)
    window.addEventListener('resize', handleResize);

    // Re-run on font load (in case fonts affect sizing)
    if (document.fonts) {
        document.fonts.ready.then(positionOverlays);
    }
})();
