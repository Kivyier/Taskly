/**
 * Taskly — script.js
 * Full client-side interactivity for the Todoist-inspired to-do app.
 */

// ─── Global functions (must be outside DOMContentLoaded) ─────────────────────

/**
 * Confirm before deleting a task.
 */
function confirmDelete() {
    return confirm("Are you sure you want to delete this task?");
}

/**
 * Toggle between task display row and inline edit panel.
 */
function toggleEdit(index) {
    var display = document.getElementById('display-' + index);
    var edit = document.getElementById('edit-' + index);

    if (!display || !edit) return;

    if (edit.style.display === 'none' || edit.style.display === '') {
        // Show edit panel, hide display row
        display.style.display = 'none';
        edit.style.display = 'block';

        // Auto-focus and select the task name input
        var nameInput = edit.querySelector('input[name="task_name"]');
        if (nameInput) {
            nameInput.focus();
            nameInput.select();
        }
    } else {
        // Show display row, hide edit panel
        display.style.display = 'flex';
        edit.style.display = 'none';
    }
}

// ─── DOMContentLoaded ────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {

    // ── Element references ──────────────────────────────────────────────────
    var addTaskBtn    = document.getElementById('add-task-btn');
    var sidebarAddBtn = document.getElementById('sidebar-add-btn');
    var addTrigger    = document.getElementById('add-trigger');
    var addPanel      = document.getElementById('add-panel');
    var cancelAdd     = document.getElementById('cancel-add');
    var taskName      = document.getElementById('task_name');
    var taskDesc      = document.getElementById('task_description');
    var charCounter   = document.getElementById('char-counter');
    var hamburgerBtn  = document.getElementById('hamburger-btn');
    var sidebar       = document.getElementById('sidebar');

    // ── Add task panel toggle ───────────────────────────────────────────────
    function openAddPanel() {
        if (addTrigger) addTrigger.style.display = 'none';
        if (addPanel) addPanel.style.display = 'block';
        if (taskName) taskName.focus();
    }

    function closeAddPanel() {
        if (addPanel) addPanel.style.display = 'none';
        if (addTrigger) addTrigger.style.display = 'block';
    }

    if (addTaskBtn) addTaskBtn.addEventListener('click', openAddPanel);
    if (sidebarAddBtn) sidebarAddBtn.addEventListener('click', openAddPanel);
    if (cancelAdd) cancelAdd.addEventListener('click', closeAddPanel);

    // ── Character counter ───────────────────────────────────────────────────
    if (taskDesc && charCounter) {
        taskDesc.addEventListener('input', function () {
            var remaining = 255 - taskDesc.value.length;
            if (remaining < 0) remaining = 0;
            charCounter.textContent = remaining + ' characters remaining';

            if (remaining <= 20) {
                charCounter.style.color = '#dc4c3e';
            } else {
                charCounter.style.color = '';
            }
        });
    }

    // ── Keyboard shortcut — Q to open add panel ─────────────────────────────
    document.addEventListener('keydown', function (e) {
        // Skip if user is typing in an input, textarea, or select
        var tag = document.activeElement.tagName.toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

        if (e.key === 'q' || e.key === 'Q') {
            e.preventDefault();
            openAddPanel();
        }
    });

    // ── Mobile sidebar toggle ───────────────────────────────────────────────
    // Create overlay element
    var overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);z-index:99;display:none;';
    document.body.appendChild(overlay);

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        overlay.classList.add('active');
        overlay.style.display = 'block';
        // On mobile, translate sidebar back into view
        if (sidebar) sidebar.style.transform = 'translateX(0)';
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        overlay.classList.remove('active');
        overlay.style.display = 'none';
        // On mobile, hide sidebar again
        if (sidebar) sidebar.style.transform = '';
    }

    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    overlay.addEventListener('click', closeSidebar);

    // ── Auto-dismiss notifications ──────────────────────────────────────────
    var notifications = document.querySelectorAll('.notification');
    notifications.forEach(function (el) {
        el.style.transition = 'opacity 0.4s ease';
        setTimeout(function () {
            el.style.opacity = '0';
            setTimeout(function () {
                el.style.display = 'none';
            }, 400);
        }, 4000);
    });

    // ── Section chevron toggle ──────────────────────────────────────────────
    var sectionHeaders = document.querySelectorAll('.section-header');
    sectionHeaders.forEach(function (header) {
        header.addEventListener('click', function () {
            var section = header.parentElement;
            var chevron = header.querySelector('.section-chevron');
            var items = section.querySelectorAll('.project-item');

            // Toggle visibility of project items
            var isCollapsed = false;
            items.forEach(function (item) {
                if (item.style.display === 'none') {
                    item.style.display = '';
                    isCollapsed = false;
                } else {
                    item.style.display = 'none';
                    isCollapsed = true;
                }
            });

            // Flip chevron
            if (chevron) {
                chevron.textContent = isCollapsed ? '‹' : '›';
            }
        });
    });

});
