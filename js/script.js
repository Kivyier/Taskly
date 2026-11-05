/**
 * COMP016 Lab Activity 4 — Taskly JavaScript
 * Todoist-inspired interactions:
 *  1. Add task panel toggle
 *  2. Delete confirmation
 *  3. Inline edit toggle
 *  4. Auto-focus on inputs
 *  5. Live character counter (255 max)
 *  6. Mobile sidebar toggle
 *  7. Auto-dismiss notifications
 *  8. Keyboard shortcuts
 */

// ─── DOM READY ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // ─── ELEMENTS ──────────────────────────────────────────────────────
    var addTrigger     = document.getElementById('add-trigger');
    var addPanel       = document.getElementById('add-panel');
    var addTaskBtn     = document.getElementById('add-task-btn');
    var sidebarAddBtn  = document.getElementById('sidebar-add-btn');
    var cancelAddBtn   = document.getElementById('cancel-add');
    var taskNameInput  = document.getElementById('task_name');
    var descInput      = document.getElementById('task_description');
    var charCounter    = document.getElementById('char-counter');
    var sidebar        = document.getElementById('sidebar');
    var hamburgerBtn   = document.getElementById('hamburger-btn');
    var maxChars       = 255;

    // ─── ADD TASK PANEL TOGGLE ─────────────────────────────────────────
    // Show the add task form and hide the "+ Add task" button
    function showAddPanel() {
        if (addTrigger) addTrigger.style.display = 'none';
        if (addPanel) {
            addPanel.style.display = 'block';
            // Auto-focus the task name input
            if (taskNameInput) {
                taskNameInput.focus();
            }
        }
    }

    // Hide the add task form and show the trigger button
    function hideAddPanel() {
        if (addPanel) addPanel.style.display = 'none';
        if (addTrigger) addTrigger.style.display = 'block';
    }

    // Click "+ Add task" inline button in the main content
    if (addTaskBtn) {
        addTaskBtn.addEventListener('click', showAddPanel);
    }

    // Click sidebar "Add task" button
    if (sidebarAddBtn) {
        sidebarAddBtn.addEventListener('click', function () {
            showAddPanel();
            // On mobile, close the sidebar after clicking
            if (sidebar && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                var overlay = document.querySelector('.sidebar-overlay');
                if (overlay) overlay.classList.remove('active');
            }
        });
    }

    // Click "Cancel" to close the add form
    if (cancelAddBtn) {
        cancelAddBtn.addEventListener('click', hideAddPanel);
    }

    // ─── CHARACTER COUNTER ─────────────────────────────────────────────
    // Live count of remaining characters in description field
    if (descInput && charCounter) {
        descInput.addEventListener('input', function () {
            var remaining = maxChars - descInput.value.length;

            // Clamp to zero — never show negative
            if (remaining < 0) remaining = 0;

            charCounter.textContent = remaining + ' character' + (remaining !== 1 ? 's' : '') + ' remaining';

            // Visual warning when running low (≤ 20 chars left)
            if (remaining <= 20) {
                charCounter.style.color = '#dc4c3e';
            } else {
                charCounter.style.color = '';
            }
        });
    }

    // ─── KEYBOARD SHORTCUT ─────────────────────────────────────────────
    // Press 'q' to quickly open the add task panel (like Todoist)
    document.addEventListener('keydown', function (e) {
        // Don't trigger if user is typing in an input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            return;
        }

        if (e.key === 'q' || e.key === 'Q') {
            e.preventDefault();
            showAddPanel();
        }
    });

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
                    if (chevron) chevron.textContent = '›';
                } else {
                    item.style.display = 'none';
                    if (chevron) chevron.textContent = '‹';
                }
            });
        });
    });
});


// ─── DELETE CONFIRMATION ───────────────────────────────────────────────
// Called from the delete form's onsubmit attribute.
// Returns false to cancel the form submission if the user declines.
function confirmDelete() {
    return confirm("Are you sure you want to delete this task?");
}


// ─── EDIT TOGGLE ───────────────────────────────────────────────────────
// Toggles the inline edit form for a specific task.
// Each task has a display div (display-<index>) and edit div (edit-<index>).
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
