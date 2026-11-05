/**
 * COMP016 Lab Activity 4 — Taskly JavaScript
 * Interactions:
 *  1. Delete confirmation
 *  2. Inline edit toggle
 *  3. Live character counter (255 max)
 *  4. Mobile sidebar toggle
 *  5. Auto-dismiss notifications
 *  6. Section chevron toggle
 */

// ─── DOM READY ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // ─── ELEMENTS ──────────────────────────────────────────────────────
    var descInput      = document.getElementById('task_description');
    var charCounter    = document.getElementById('char-counter');
    var sidebar        = document.getElementById('sidebar');
    var hamburgerBtn   = document.getElementById('hamburger-btn');
    var maxChars       = 255;

    // ─── CHARACTER COUNTER ─────────────────────────────────────────────
    // Live count of remaining characters in description field
    if (descInput && charCounter) {
        descInput.addEventListener('input', function () {
            var remaining = maxChars - descInput.value.length;

            // Clamp to zero
            if (remaining < 0) remaining = 0;

            charCounter.textContent = remaining + ' character' + (remaining !== 1 ? 's' : '') + ' remaining';

            // Visual warning when running low
            if (remaining <= 20) {
                charCounter.style.color = '#dc4c3e';
            } else {
                charCounter.style.color = '';
            }
        });
    }

    // ─── MOBILE SIDEBAR TOGGLE ─────────────────────────────────────────
    if (hamburgerBtn && sidebar) {
        // Create overlay element for mobile
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        hamburgerBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // ─── AUTO-DISMISS NOTIFICATIONS ────────────────────────────────────
    // Fade out success/error messages after 4 seconds
    var notifications = document.querySelectorAll('.notification');
    notifications.forEach(function (notif) {
        setTimeout(function () {
            notif.style.transition = 'opacity 0.4s ease, max-height 0.4s ease';
            notif.style.opacity = '0';
            setTimeout(function () {
                notif.style.display = 'none';
            }, 400);
        }, 4000);
    });

    // ─── SECTION CHEVRON TOGGLE ────────────────────────────────────────
    // Toggle sidebar sections open/closed
    var sectionHeaders = document.querySelectorAll('.section-header');
    sectionHeaders.forEach(function (header) {
        header.addEventListener('click', function () {
            var section = header.parentElement;
            var items = section.querySelectorAll('.project-item');
            var chevron = header.querySelector('.section-chevron');

            items.forEach(function (item) {
                if (item.style.display === 'none') {
                    item.style.display = 'flex';
                    if (chevron) chevron.textContent = '>';
                } else {
                    item.style.display = 'none';
                    if (chevron) chevron.textContent = '<';
                }
            });
        });
    });
});


// ─── DELETE CONFIRMATION ───────────────────────────────────────────────
function confirmDelete() {
    return confirm("Are you sure you want to delete this task?");
}


// ─── EDIT TOGGLE ───────────────────────────────────────────────────────
function toggleEdit(index) {
    var displayDiv = document.getElementById('display-' + index);
    var editDiv    = document.getElementById('edit-'    + index);

    if (!displayDiv || !editDiv) return;

    // If edit form is currently hidden, show it
    if (editDiv.style.display === 'none' || editDiv.style.display === '') {
        editDiv.style.display    = 'block';
        displayDiv.style.display = 'none';

        // Auto-focus and select the task name in the edit form
        var editNameInput = editDiv.querySelector('input[name="task_name"]');
        if (editNameInput) {
            editNameInput.focus();
            editNameInput.select();
        }
    } else {
        // Hide edit form, restore the task display
        editDiv.style.display    = 'none';
        displayDiv.style.display = 'flex';
    }
}
