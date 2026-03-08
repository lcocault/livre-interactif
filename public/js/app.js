/**
 * Livre Interactif — Frontend JavaScript
 * Minimal interactivity: dice animations, confirmations.
 */

document.addEventListener('DOMContentLoaded', function () {
    // ── Dice roll button animation ────────────────────────────────────────
    document.querySelectorAll('button[type="submit"]').forEach(function (btn) {
        if (btn.textContent.includes('🎲')) {
            btn.addEventListener('click', function () {
                btn.classList.add('dice-rolling');
                setTimeout(() => btn.classList.remove('dice-rolling'), 600);
            });
        }
    });

    // ── Auto-dismiss alerts after 8s ──────────────────────────────────────
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 8000);
    });

    // ── Scroll to combat zone when active ────────────────────────────────
    const combatZone = document.getElementById('combat-zone');
    if (combatZone) {
        combatZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // ── Scroll to combat result ───────────────────────────────────────────
    const combatResult = document.querySelector('.combat-result');
    if (combatResult) {
        combatResult.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
