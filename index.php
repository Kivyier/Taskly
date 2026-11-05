<?php
session_start();

// ─── Initialize session tasks ───────────────────────────────────────────────
if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [];
}

// ─── Sanitize helper ────────────────────────────────────────────────────────
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ─── Label colors ───────────────────────────────────────────────────────────
$labelColors = [
    'Personal' => '#db4035',
    'School'   => '#ff9933',
    'Work'     => '#4073ff',
    'Health'   => '#058527',
    'Home'     => '#a970ff',
];

// ─── Date & Label Filtering ─────────────────────────────────────────────────
$today        = date('Y-m-d');
$selectedDate = isset($_GET['date']) ? $_GET['date'] : $today;
$selectedLabel = isset($_GET['label']) ? $_GET['label'] : '';

// Validate the date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = $today;
}

// Check if selected date is in the past
$isPastDate = ($selectedDate < $today);

// Human-friendly label for the selected date
$selectedTimestamp = strtotime($selectedDate);
$diffDays = (int) round(($selectedTimestamp - strtotime($today)) / 86400);

if ($diffDays === 0) {
    $pageTitle = 'Today';
} elseif ($diffDays === 1) {
    $pageTitle = 'Tomorrow';
} elseif ($diffDays === -1) {
    $pageTitle = 'Yesterday';
} else {
    $pageTitle = date('l', $selectedTimestamp);
}

// ─── POST handler (CRUD) ────────────────────────────────────────────────────
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Retrieve the date context from the form
    $formDate = isset($_POST['task_date']) ? $_POST['task_date'] : $today;

    // ── CREATE ──────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $taskName        = isset($_POST['task_name'])        ? $_POST['task_name']        : '';
        $taskDescription = isset($_POST['task_description']) ? $_POST['task_description'] : '';
        $taskPriority    = isset($_POST['task_priority'])    ? $_POST['task_priority']    : 'p4';
        $taskLabel       = isset($_POST['task_label'])       ? $_POST['task_label']       : '';

        // Validation
        if (empty(trim($taskName)) || strlen(trim($taskName)) < 3) {
            $errors[] = 'Task name must not be empty and must be at least 3 characters.';
        }
        if (empty(trim($taskDescription))) {
            $errors[] = 'Task description must not be empty.';
        } elseif (strlen(trim($taskDescription)) > 255) {
            $errors[] = 'Task description must not exceed 255 characters.';
        }

        // Block adding tasks on past dates
        if ($formDate < $today) {
            $errors[] = 'Cannot add tasks to past dates.';
        }

        // If valid, sanitize and store
        if (empty($errors)) {
            $_SESSION['tasks'][] = [
                'name'        => sanitize($taskName),
                'description' => sanitize($taskDescription),
                'priority'    => sanitize($taskPriority),
                'label'       => sanitize($taskLabel),
                'created'     => $formDate,
                'completed'   => false,
            ];
            $success = 'Task added successfully!';
        }

        // Stay on the same date after POST
        $selectedDate = $formDate;
    }

    // ── UPDATE ──────────────────────────────────────────────────────────────
    if ($action === 'update') {
        $index           = isset($_POST['task_index'])       ? (int) $_POST['task_index'] : -1;
        $taskName        = isset($_POST['task_name'])        ? $_POST['task_name']        : '';
        $taskDescription = isset($_POST['task_description']) ? $_POST['task_description'] : '';
        $taskPriority    = isset($_POST['task_priority'])    ? $_POST['task_priority']    : 'p4';
        $taskLabel       = isset($_POST['task_label'])       ? $_POST['task_label']       : '';

        // Validation
        if (empty(trim($taskName)) || strlen(trim($taskName)) < 3) {
            $errors[] = 'Task name must not be empty and must be at least 3 characters.';
        }
        if (empty(trim($taskDescription))) {
            $errors[] = 'Task description must not be empty.';
        } elseif (strlen(trim($taskDescription)) > 255) {
            $errors[] = 'Task description must not exceed 255 characters.';
        }

        if (empty($errors) && isset($_SESSION['tasks'][$index])) {
            $_SESSION['tasks'][$index]['name']        = sanitize($taskName);
            $_SESSION['tasks'][$index]['description'] = sanitize($taskDescription);
            $_SESSION['tasks'][$index]['priority']    = sanitize($taskPriority);
            $_SESSION['tasks'][$index]['label']       = sanitize($taskLabel);
            $success = 'Task updated successfully!';
        }

        $selectedDate = $formDate;
    }

    // ── DELETE ──────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $index = isset($_POST['task_index']) ? (int) $_POST['task_index'] : -1;

        if (isset($_SESSION['tasks'][$index])) {
            array_splice($_SESSION['tasks'], $index, 1);
            $success = 'Task deleted successfully!';
        }

        $selectedDate = $formDate;
    }

    // ── TOGGLE ──────────────────────────────────────────────────────────────
    if ($action === 'toggle') {
        $index = isset($_POST['task_index']) ? (int) $_POST['task_index'] : -1;

        if (isset($_SESSION['tasks'][$index])) {
            $_SESSION['tasks'][$index]['completed'] = !$_SESSION['tasks'][$index]['completed'];
        }

        $selectedDate = $formDate;
    }

    // Recalculate isPastDate after POST redirect context
    $isPastDate = ($selectedDate < $today);
}

// ─── Filter tasks for selected date and label ───────────────────────────────
$filteredTasks = [];
foreach ($_SESSION['tasks'] as $index => $task) {
    $dateMatch  = ($task['created'] === $selectedDate);
    $labelMatch = empty($selectedLabel) || ($task['label'] === $selectedLabel);

    if ($dateMatch && $labelMatch) {
        $filteredTasks[$index] = $task;
    }
}

$taskCount      = count($filteredTasks);
$completedCount = 0;
foreach ($filteredTasks as $task) {
    if (!empty($task['completed'])) {
        $completedCount++;
    }
}
$pendingCount = $taskCount - $completedCount;
$allDone      = ($taskCount > 0 && $pendingCount === 0);

// Build query string helper
function buildUrl($params = []) {
    $query = http_build_query($params);
    return '?' . $query;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskly — To-Do List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- ─── SIDEBAR ────────────────────────────────────────────────── -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="avatar">T</div>
                <span class="username">Taskly</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="<?php echo buildUrl(['date' => $today]); ?>" class="nav-item<?php echo (empty($selectedLabel) && $selectedDate === $today) ? ' active' : ''; ?>">
                <span class="nav-icon">||</span>
                <span>Today</span>
            </a>
        </nav>

        <div class="sidebar-divider"></div>

        <!-- Favorites Section -->
        <div class="sidebar-section">
            <div class="section-header">
                <span class="section-chevron">›</span>
                <span class="section-title">Favorites</span>
            </div>
            <a href="<?php echo buildUrl(['date' => $selectedDate, 'label' => 'Personal']); ?>" class="nav-item project-item<?php echo $selectedLabel === 'Personal' ? ' active' : ''; ?>">
                <span class="project-dot" style="color: #db4035;">#</span>
                <span>Personal</span>
            </a>
            <a href="<?php echo buildUrl(['date' => $selectedDate, 'label' => 'School']); ?>" class="nav-item project-item<?php echo $selectedLabel === 'School' ? ' active' : ''; ?>">
                <span class="project-dot" style="color: #ff9933;">#</span>
                <span>School</span>
            </a>
        </div>

        <!-- My Projects Section -->
        <div class="sidebar-section">
            <div class="section-header">
                <span class="section-chevron">›</span>
                <span class="section-title">My Projects</span>
            </div>
            <?php foreach ($labelColors as $label => $color): ?>
            <a href="<?php echo buildUrl(['date' => $selectedDate, 'label' => $label]); ?>" class="nav-item project-item<?php echo $selectedLabel === $label ? ' active' : ''; ?>">
                <span class="project-dot" style="color: <?php echo $color; ?>;">#</span>
                <span><?php echo $label; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- ─── MOBILE HEADER ──────────────────────────────────────────── -->
    <div class="mobile-header" id="mobile-header">
        <button class="hamburger" id="hamburger-btn" type="button">☰</button>
        <span class="mobile-title"><?php echo $pageTitle; ?></span>
        <?php if ($pendingCount > 0): ?>
        <span class="mobile-count"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
    </div>

    <!-- ─── MAIN CONTENT ──────────────────────────────────────────── -->
    <main class="main-content">
        <div class="content-wrapper">

            <!-- ─── PAGE HEADER ────────────────────────────────────── -->
            <header class="page-header">
                <div class="page-title-row">
                    <h1><?php echo $pageTitle; ?></h1>
                    <span class="header-date"><?php echo date('D j M', $selectedTimestamp); ?></span>
                    <?php if (!empty($selectedLabel)): ?>
                        <span class="active-filter">Filtered: <?php echo sanitize($selectedLabel); ?> &mdash; <a href="<?php echo buildUrl(['date' => $selectedDate]); ?>">Clear</a></span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- ─── NOTIFICATIONS ──────────────────────────────────── -->
            <?php if (!empty($errors)): ?>
            <div class="notification notification-error" style="background:#fdecea;color:#b71c1c;border:1px solid #f5c6cb;border-radius:8px;padding:12px 16px;margin-bottom:16px;">
                <ul style="margin:0;padding-left:18px;">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo sanitize($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="notification notification-success" style="background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;border-radius:8px;padding:12px 16px;margin-bottom:16px;">
                <p style="margin:0;"><?php echo sanitize($success); ?></p>
            </div>
            <?php endif; ?>

            <!-- ─── DATE STRIP (Clickable) ────────────────────────── -->
            <div class="date-strip">
                <?php for ($i = -2; $i <= 4; $i++): ?>
                <?php
                    $timestamp = strtotime(($i >= 0 ? '+' : '') . $i . ' days');
                    $dayName   = date('D', $timestamp);
                    $dayNum    = date('j', $timestamp);
                    $dayDate   = date('Y-m-d', $timestamp);
                    $isActive  = ($dayDate === $selectedDate);
                    $dateUrl   = buildUrl(array_merge(['date' => $dayDate], $selectedLabel ? ['label' => $selectedLabel] : []));
                ?>
                <a href="<?php echo $dateUrl; ?>" class="date-cell<?php echo $isActive ? ' date-today' : ''; ?>" title="<?php echo date('D j M', $timestamp); ?>">
                    <span class="date-weekday"><?php echo $dayName; ?></span>
                    <span class="date-number"><?php echo $dayNum; ?></span>
                </a>
                <?php endfor; ?>
            </div>

            <!-- ─── TASK SECTION ───────────────────────────────────── -->
            <section class="task-section">

                <!-- Section Header -->
                <?php if ($taskCount > 0): ?>
                <div class="section-date-header">
                    <span class="section-date"><?php echo date('j M', $selectedTimestamp); ?> · <?php echo $pageTitle; ?></span>
                    <span class="section-task-count"><?php echo $pendingCount; ?> task<?php echo $pendingCount !== 1 ? 's' : ''; ?></span>
                </div>
                <?php endif; ?>

                <?php if ($allDone): ?>
                <!-- ─── ALL DONE ───────────────────────────────────── -->
                <div class="all-done" style="text-align:center;padding:48px 16px;">
                    <p style="font-size:48px;margin:0 0 12px;">&#10003;</p>
                    <p style="font-size:18px;font-weight:700;color:#1f1f1f;margin:0 0 4px;">All tasks completed!</p>
                    <p style="color:#8b8685;font-size:14px;margin:0;"><?php echo $completedCount; ?> task<?php echo $completedCount !== 1 ? 's' : ''; ?> done</p>
                </div>

                <?php elseif ($taskCount === 0): ?>
                <!-- ─── EMPTY STATE ──────────────────────────────── -->
                <div class="empty-state" style="text-align:center;padding:48px 16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="220" height="180" viewBox="0 0 220 180" fill="none" style="margin-bottom:16px;">
                        <rect x="50" y="40" width="120" height="100" rx="12" fill="#f5f3ef" stroke="#e0dcd3" stroke-width="2"/>
                        <rect x="70" y="62" width="80" height="8" rx="4" fill="#d6d1c7"/>
                        <rect x="70" y="80" width="60" height="8" rx="4" fill="#e0dcd3"/>
                        <rect x="70" y="98" width="70" height="8" rx="4" fill="#e0dcd3"/>
                        <circle cx="58" cy="66" r="6" fill="#dc4c3e" opacity="0.7"/>
                        <circle cx="58" cy="84" r="6" fill="#f5a623" opacity="0.7"/>
                        <circle cx="58" cy="102" r="6" fill="#4073ff" opacity="0.7"/>
                        <path d="M100 140 Q110 155 120 140" stroke="#d6d1c7" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                    <?php if ($isPastDate): ?>
                    <p style="color:#8b8685;font-size:15px;margin:0;">No tasks were scheduled for this day.</p>
                    <?php else: ?>
                    <p style="color:#8b8685;font-size:15px;margin:0;">No tasks yet. Use the form below to add one!</p>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <!-- Task List -->
                <ul class="task-list">
                    <?php foreach ($filteredTasks as $index => $task): ?>
                    <?php
                        $labelColor  = isset($labelColors[$task['label']]) ? $labelColors[$task['label']] : '#999';
                        $isCompleted = !empty($task['completed']);
                    ?>
                    <li class="task-item<?php echo $isCompleted ? ' completed' : ''; ?>">
                        <!-- Display Row -->
                        <div class="task-row" id="display-<?php echo $index; ?>">
                            <form class="check-form" method="POST" action="">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="task_index" value="<?php echo $index; ?>">
                                <input type="hidden" name="task_date" value="<?php echo $selectedDate; ?>">
                                <button type="submit" class="checkbox priority-<?php echo $task['priority']; ?><?php echo $isCompleted ? ' checked' : ''; ?>">
                                    <?php if ($isCompleted): ?><span class="checkmark">&#10003;</span><?php endif; ?>
                                </button>
                            </form>
                            <div class="task-content">
                                <span class="task-name"><?php echo $task['name']; ?></span>
                                <span class="task-desc"><?php echo $task['description']; ?></span>
                                <?php if (!empty($task['label'])): ?>
                                <span class="task-label" style="color: <?php echo $labelColor; ?>;">
                                    <?php echo $task['label']; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="task-right">
                                <?php if (!empty($task['label'])): ?>
                                <span class="task-project" style="color: <?php echo $labelColor; ?>;"><?php echo $task['label']; ?> #</span>
                                <?php endif; ?>
                                <div class="task-actions">
                                    <button type="button" class="action-btn edit-btn" title="Edit" onclick="toggleEdit(<?php echo $index; ?>)">&#9998;</button>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="task_index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="task_date" value="<?php echo $selectedDate; ?>">
                                        <button type="submit" class="action-btn delete-btn" title="Delete">&#128465;</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Inline Edit Panel -->
                        <div class="edit-panel" id="edit-<?php echo $index; ?>" style="display:none;">
                            <form class="new-task-form" method="POST" action="">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="task_index" value="<?php echo $index; ?>">
                                <input type="hidden" name="task_date" value="<?php echo $selectedDate; ?>">
                                <div class="form-inputs">
                                    <input type="text" name="task_name" class="input-name" value="<?php echo $task['name']; ?>" placeholder="Task name" required>
                                    <input type="text" name="task_description" class="input-desc" value="<?php echo $task['description']; ?>" placeholder="Description" required>
                                </div>
                                <div class="form-footer">
                                    <div class="form-meta">
                                        <select name="task_priority" class="priority-select">
                                            <option value="p1"<?php echo $task['priority'] === 'p1' ? ' selected' : ''; ?>>P1 - Urgent</option>
                                            <option value="p2"<?php echo $task['priority'] === 'p2' ? ' selected' : ''; ?>>P2 - High</option>
                                            <option value="p3"<?php echo $task['priority'] === 'p3' ? ' selected' : ''; ?>>P3 - Medium</option>
                                            <option value="p4"<?php echo $task['priority'] === 'p4' ? ' selected' : ''; ?>>P4 - Low</option>
                                        </select>
                                        <select name="task_label" class="label-select">
                                            <option value="">No label</option>
                                            <?php foreach ($labelColors as $lbl => $clr): ?>
                                            <option value="<?php echo $lbl; ?>"<?php echo $task['label'] === $lbl ? ' selected' : ''; ?>><?php echo $lbl; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-buttons">
                                        <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $index; ?>)">Cancel</button>
                                        <button type="submit" class="btn-save">Save</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <!-- ─── ADD TASK FORM (only for today and future) ──── -->
                <?php if (!$isPastDate): ?>
                <div class="add-task-panel" id="add-panel">
                    <form class="new-task-form" action="" method="POST">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="task_date" value="<?php echo $selectedDate; ?>">
                        <div class="form-inputs">
                            <input type="text" name="task_name" id="task_name" class="input-name" placeholder="Task name" required>
                            <input type="text" name="task_description" id="task_description" class="input-desc" placeholder="Description" required>
                            <small class="char-counter" id="char-counter">255 characters remaining</small>
                        </div>

                        <div class="form-footer">
                            <div class="form-meta">
                                <select name="task_priority" class="priority-select">
                                    <option value="p1">P1 - Urgent</option>
                                    <option value="p2">P2 - High</option>
                                    <option value="p3">P3 - Medium</option>
                                    <option value="p4" selected>P4 - Low</option>
                                </select>
                                <select name="task_label" class="label-select">
                                    <option value="">No label</option>
                                    <?php foreach ($labelColors as $label => $color): ?>
                                    <option value="<?php echo $label; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn-add-task">Add task</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

            </section>

        </div>
    </main>

    <!-- ─── FOOTER ──────────────────────────────────────────────────── -->
    <footer class="app-footer">
        <p>&copy; <?php echo date('Y'); ?> COMP016 Lab Activity 4 — Taskly</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
