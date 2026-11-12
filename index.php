<?php
/**
 * COMP016 - Web Development Lab Activity 5
 * Taskly — Database-Driven To-Do List Web App
 * Refactored from $_SESSION to MySQL via PDO
 */

// ─── Database Connection ────────────────────────────────────────────────────
require_once 'db.php';

// ─── Sanitize helper ────────────────────────────────────────────────────────
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ─── Output escaping helper ─────────────────────────────────────────────────
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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

// ─── Flash message from redirect ────────────────────────────────────────────
$successMsg = isset($_GET['msg']) ? $_GET['msg'] : '';

// ─── POST handler (CRUD) ────────────────────────────────────────────────────
$errors  = [];
$success = '';

// Map GET msg parameter to user-friendly message
if ($successMsg === 'added')   $success = 'Task added successfully!';
if ($successMsg === 'updated') $success = 'Task updated successfully!';
if ($successMsg === 'deleted') $success = 'Task deleted successfully!';
if ($successMsg === 'toggled') $success = 'Task status updated!';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Retrieve the date context from the form
    $formDate  = isset($_POST['task_date']) ? $_POST['task_date'] : $today;
    $formLabel = isset($_POST['form_label']) ? $_POST['form_label'] : '';

    // ── CREATE ──────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $taskName        = isset($_POST['task_name'])        ? trim($_POST['task_name'])        : '';
        $taskDescription = isset($_POST['task_description']) ? trim($_POST['task_description']) : '';
        $taskPriority    = isset($_POST['task_priority'])    ? $_POST['task_priority']          : 'p4';
        $taskLabel       = isset($_POST['task_label'])       ? $_POST['task_label']             : '';
        $taskDate        = isset($_POST['task_date'])        ? $_POST['task_date']              : $today;

        // Validate task name (must not be empty, at least 3 chars)
        if (empty($taskName) || strlen($taskName) < 3) {
            $errors[] = 'Task name must not be empty and must be at least 3 characters.';
        }

        // Validate description (must not be empty, max 255 chars)
        if (empty($taskDescription)) {
            $errors[] = 'Task description must not be empty.';
        } elseif (strlen($taskDescription) > 255) {
            $errors[] = 'Task description must not exceed 255 characters.';
        }

        // Validate task_date with the same regex as the GET date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $taskDate)) {
            $errors[] = 'Invalid date format.';
            $taskDate = $today;
        }

        // Reject past dates
        if ($taskDate < $today) {
            $errors[] = 'Cannot add tasks to a past date.';
        }

        // If valid, INSERT into database using a prepared statement
        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "INSERT INTO tasks (task_name, task_description, priority, label, task_date)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $taskName,
                $taskDescription,
                $taskPriority,
                $taskLabel,
                $taskDate,
            ]);

            // Redirect after success (PRG pattern)
            $redirectParams = ['date' => $taskDate, 'msg' => 'added'];
            if (!empty($formLabel)) {
                $redirectParams['label'] = $formLabel;
            }
            header('Location: index.php' . buildUrl($redirectParams));
            exit;
        }
    }

    // ── UPDATE (edit panel) ─────────────────────────────────────────────
    if ($action === 'update') {
        $taskId = filter_var(
            isset($_POST['task_id']) ? $_POST['task_id'] : '',
            FILTER_VALIDATE_INT
        );

        if ($taskId === false) {
            $errors[] = 'Invalid task ID.';
        }

        $taskName        = isset($_POST['task_name'])        ? trim($_POST['task_name'])        : '';
        $taskDescription = isset($_POST['task_description']) ? trim($_POST['task_description']) : '';
        $taskPriority    = isset($_POST['task_priority'])    ? $_POST['task_priority']          : 'p4';
        $taskLabel       = isset($_POST['task_label'])       ? $_POST['task_label']             : '';

        // Validate task name (must not be empty, at least 3 chars)
        if (empty($taskName) || strlen($taskName) < 3) {
            $errors[] = 'Task name must not be empty and must be at least 3 characters.';
        }

        // Validate description (must not be empty, max 255 chars)
        if (empty($taskDescription)) {
            $errors[] = 'Task description must not be empty.';
        } elseif (strlen($taskDescription) > 255) {
            $errors[] = 'Task description must not exceed 255 characters.';
        }

        // Validate priority
        if (!in_array($taskPriority, ['p1', 'p2', 'p3', 'p4'], true)) {
            $taskPriority = 'p4';
        }

        // Validate label (must be empty or a known label)
        if ($taskLabel !== '' && !array_key_exists($taskLabel, $labelColors)) {
            $errors[] = 'Invalid label selected.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "UPDATE tasks
                 SET task_name = ?, task_description = ?, priority = ?, label = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $taskName,
                $taskDescription,
                $taskPriority,
                $taskLabel,
                $taskId,
            ]);

            // Redirect after success (PRG pattern)
            $redirectParams = ['date' => $formDate, 'msg' => 'updated'];
            if (!empty($formLabel)) {
                $redirectParams['label'] = $formLabel;
            }
            header('Location: index.php' . buildUrl($redirectParams));
            exit;
        }
    }

    // ── DELETE ──────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $taskId = filter_var(
            isset($_POST['task_id']) ? $_POST['task_id'] : '',
            FILTER_VALIDATE_INT
        );

        if ($taskId === false) {
            $errors[] = 'Invalid task ID.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);

            // Redirect after success (PRG pattern)
            $redirectParams = ['date' => $formDate, 'msg' => 'deleted'];
            if (!empty($formLabel)) {
                $redirectParams['label'] = $formLabel;
            }
            header('Location: index.php' . buildUrl($redirectParams));
            exit;
        }
    }

    // ── TOGGLE ──────────────────────────────────────────────────────────────
    if ($action === 'toggle') {
        $taskId = filter_var(
            isset($_POST['task_id']) ? $_POST['task_id'] : '',
            FILTER_VALIDATE_INT
        );

        if ($taskId === false) {
            $errors[] = 'Invalid task ID.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE tasks SET status = IF(status='pending','completed','pending') WHERE id = ?"
            );
            $stmt->execute([$taskId]);

            // Redirect after success (PRG pattern)
            $redirectParams = ['date' => $formDate, 'msg' => 'toggled'];
            if (!empty($formLabel)) {
                $redirectParams['label'] = $formLabel;
            }
            header('Location: index.php' . buildUrl($redirectParams));
            exit;
        }
    }
}

// ─── READ ───────────────────────────────────────────────────────────────────
// Prepared SELECT to fetch tasks for the selected date.
// Adds AND label = ? only when a valid label filter is set.
// Sorted in SQL: pending first, then by priority, then by id.
$filteredTasks = [];

if (!empty($selectedLabel) && array_key_exists($selectedLabel, $labelColors)) {
    // Filter by date AND label
    $stmt = $pdo->prepare(
        "SELECT * FROM tasks
         WHERE task_date = ? AND label = ?
         ORDER BY status = 'completed', priority, id"
    );
    $stmt->execute([$selectedDate, $selectedLabel]);
} else {
    // Filter by date only
    $stmt = $pdo->prepare(
        "SELECT * FROM tasks
         WHERE task_date = ?
         ORDER BY status = 'completed', priority, id"
    );
    $stmt->execute([$selectedDate]);
}

$filteredTasks = $stmt->fetchAll();

// ─── Compute counts (template depends on these) ────────────────────────────
$taskCount      = count($filteredTasks);
$completedCount = 0;
foreach ($filteredTasks as $task) {
    $taskStatus = isset($task['status']) ? $task['status'] : 'pending';
    if ($taskStatus === 'completed') {
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
                <span class="nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span>
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
        <span class="mobile-title"><?php echo e($pageTitle); ?></span>
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
                    <h1><?php echo e($pageTitle); ?></h1>
                    <span class="header-date"><?php echo date('D j M', $selectedTimestamp); ?></span>
                    <?php if (!empty($selectedLabel)): ?>
                        <span class="active-filter">Filtered: <?php echo e($selectedLabel); ?> &mdash; <a href="<?php echo buildUrl(['date' => $selectedDate]); ?>">Clear</a></span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- ─── NOTIFICATIONS ──────────────────────────────────── -->
            <?php if (!empty($errors)): ?>
            <div class="notification notification-error" style="background:#fdecea;color:#b71c1c;border:1px solid #f5c6cb;border-radius:8px;padding:12px 16px;margin-bottom:16px;">
                <ul style="margin:0;padding-left:18px;">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="notification notification-success" style="background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;border-radius:8px;padding:12px 16px;margin-bottom:16px;">
                <p style="margin:0;"><?php echo e($success); ?></p>
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
                    <span class="section-date"><?php echo date('j M', $selectedTimestamp); ?> · <?php echo e($pageTitle); ?></span>
                    <span class="section-task-count"><?php echo $pendingCount; ?> task<?php echo $pendingCount !== 1 ? 's' : ''; ?></span>
                </div>
                <?php endif; ?>

                <?php if ($taskCount === 0): ?>
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

                <?php if ($allDone): ?>
                <!-- ─── ALL DONE MESSAGE ──────────────────────────── -->
                <div class="all-done" style="text-align:center;padding:16px 16px 20px;">
                    <span style="font-size:20px;color:#2e7d32;display:inline-block;vertical-align:middle;margin-right:6px;">&#10003;</span>
                    <span style="font-size:15px;font-weight:700;color:#1f1f1f;vertical-align:middle;">All tasks completed!</span>
                    <p style="color:#8b8685;font-size:13px;margin:4px 0 0;"><?php echo $completedCount; ?> task<?php echo $completedCount !== 1 ? 's' : ''; ?> completed</p>
                </div>
                <?php endif; ?>

                <!-- Task List -->
                <ul class="task-list">
                    <?php foreach ($filteredTasks as $task): ?>
                    <?php
                        $taskId      = $task['id'];
                        $labelColor  = isset($labelColors[$task['label']]) ? $labelColors[$task['label']] : '#999';
                        $isCompleted = (isset($task['status']) && $task['status'] === 'completed');
                    ?>
                    <li class="task-item<?php echo $isCompleted ? ' completed' : ''; ?>">
                        <!-- Display Row -->
                        <div class="task-row" id="display-<?php echo $taskId; ?>">
                            <form class="check-form" method="POST" action="">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                <input type="hidden" name="task_date" value="<?php echo e($selectedDate); ?>">
                                <input type="hidden" name="form_label" value="<?php echo e($selectedLabel); ?>">
                                <button type="submit" class="checkbox priority-<?php echo e($task['priority']); ?><?php echo $isCompleted ? ' checked' : ''; ?>">
                                    <?php if ($isCompleted): ?><span class="checkmark">&#10003;</span><?php endif; ?>
                                </button>
                            </form>
                            <div class="task-content">
                                <span class="task-name"><?php echo e($task['task_name']); ?></span>
                                <span class="task-desc"><?php echo e($task['task_description']); ?></span>
                                <?php if (!empty($task['label'])): ?>
                                <span class="task-label" style="color: <?php echo $labelColor; ?>;">
                                    <?php echo e($task['label']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="task-right">
                                <?php if (!empty($task['label'])): ?>
                                <span class="task-project" style="color: <?php echo $labelColor; ?>;"><?php echo e($task['label']); ?> #</span>
                                <?php endif; ?>
                                <div class="task-actions">
                                    <button type="button" class="action-btn edit-btn" title="Edit" onclick="toggleEdit(<?php echo $taskId; ?>)">&#9998;</button>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                        <input type="hidden" name="task_date" value="<?php echo e($selectedDate); ?>">
                                        <input type="hidden" name="form_label" value="<?php echo e($selectedLabel); ?>">
                                        <button type="submit" class="action-btn delete-btn" title="Delete">&#128465;</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Inline Edit Panel -->
                        <div class="edit-panel" id="edit-<?php echo $taskId; ?>" style="display:none;">
                            <form class="new-task-form" method="POST" action="">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                <input type="hidden" name="task_date" value="<?php echo e($selectedDate); ?>">
                                <input type="hidden" name="form_label" value="<?php echo e($selectedLabel); ?>">
                                <div class="form-inputs">
                                    <input type="text" name="task_name" class="input-name" value="<?php echo e($task['task_name']); ?>" placeholder="Task name" required>
                                    <input type="text" name="task_description" class="input-desc" value="<?php echo e($task['task_description']); ?>" placeholder="Description" required>
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
                                        <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $taskId; ?>)">Cancel</button>
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
                        <input type="hidden" name="task_date" value="<?php echo e($selectedDate); ?>">
                        <input type="hidden" name="form_label" value="<?php echo e($selectedLabel); ?>">
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
        <p>&copy; <?php echo date('Y'); ?> COMP016 Lab Activity 5 — Taskly</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
