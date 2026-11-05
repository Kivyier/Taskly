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

// ─── POST handler (CRUD) ────────────────────────────────────────────────────
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

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

        // If valid, sanitize and store
        if (empty($errors)) {
            $_SESSION['tasks'][] = [
                'name'        => sanitize($taskName),
                'description' => sanitize($taskDescription),
                'priority'    => sanitize($taskPriority),
                'label'       => sanitize($taskLabel),
                'created'     => date('Y-m-d'),
                'completed'   => false,
            ];
            $success = 'Task added successfully!';
        }
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
    }

    // ── DELETE ──────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $index = isset($_POST['task_index']) ? (int) $_POST['task_index'] : -1;

        if (isset($_SESSION['tasks'][$index])) {
            array_splice($_SESSION['tasks'], $index, 1);
            $success = 'Task deleted successfully!';
        }
    }

    // ── TOGGLE ──────────────────────────────────────────────────────────────
    if ($action === 'toggle') {
        $index = isset($_POST['task_index']) ? (int) $_POST['task_index'] : -1;

        if (isset($_SESSION['tasks'][$index])) {
            $_SESSION['tasks'][$index]['completed'] = !$_SESSION['tasks'][$index]['completed'];
        }
    }
}

// ─── Dynamic values ─────────────────────────────────────────────────────────
$taskCount      = count($_SESSION['tasks']);
$completedCount = 0;
foreach ($_SESSION['tasks'] as $task) {
    if (!empty($task['completed'])) {
        $completedCount++;
    }
}
$pendingCount = $taskCount - $completedCount;
$allDone      = ($taskCount > 0 && $pendingCount === 0);
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
                <button class="icon-btn" title="Notifications">🔔</button>
                <button class="icon-btn" title="Layout">☷</button>
            </div>
        </div>

        <nav class="sidebar-nav">
            <button class="nav-item add-task-btn" id="sidebar-add-btn" type="button">
                <span class="nav-icon plus-icon">+</span>
                <span>Add task</span>
            </button>

            <a href="#" class="nav-item">
                <span class="nav-icon">🔍</span>
                <span>Search</span>
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">📥</span>
                <span>Inbox</span>
            </a>

            <a href="#" class="nav-item active">
                <span class="nav-icon">📅</span>
                <span>Today</span>
                <?php if ($pendingCount > 0): ?>
                <span class="nav-count"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">📆</span>
                <span>Upcoming</span>
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">🏷️</span>
                <span>Filters &amp; Labels</span>
            </a>
        </nav>

        <div class="sidebar-divider"></div>

        <!-- Favorites Section -->
        <div class="sidebar-section">
            <div class="section-header">
                <span class="section-chevron">›</span>
                <span class="section-title">Favorites</span>
            </div>
            <a href="#" class="nav-item project-item">
                <span class="project-dot" style="color: #db4035;">#</span>
                <span>Personal</span>
            </a>
            <a href="#" class="nav-item project-item">
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
            <a href="#" class="nav-item project-item">
                <span class="project-dot" style="color: <?php echo $color; ?>;">#</span>
                <span><?php echo $label; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- ─── MOBILE HEADER ──────────────────────────────────────────── -->
    <div class="mobile-header" id="mobile-header">
        <button class="hamburger" id="hamburger-btn" type="button">☰</button>
        <span class="mobile-title">Today</span>
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
                    <h1>Today</h1>
                    <span class="header-date"><?php echo date('D j M'); ?></span>
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

            <!-- ─── DATE STRIP ─────────────────────────────────────── -->
            <div class="date-strip">
                <?php for ($i = -2; $i <= 4; $i++): ?>
                <?php
                    $timestamp = strtotime(($i >= 0 ? '+' : '') . $i . ' days');
                    $dayName   = date('D', $timestamp);
                    $dayNum    = date('j', $timestamp);
                    $isToday   = ($i === 0);
                ?>
                <div class="date-cell<?php echo $isToday ? ' date-today' : ''; ?>">
                    <span class="date-weekday"><?php echo $dayName; ?></span>
                    <span class="date-number"><?php echo $dayNum; ?></span>
                </div>
                <?php endfor; ?>
            </div>

            <!-- ─── TASK SECTION ───────────────────────────────────── -->
            <section class="task-section">

                <!-- Section Header -->
                <?php if ($taskCount > 0): ?>
                <div class="section-date-header">
                    <span class="section-date"><?php echo date('j M'); ?> ‧ Today</span>
                    <span class="section-task-count"><?php echo $pendingCount; ?> task<?php echo $pendingCount !== 1 ? 's' : ''; ?></span>
                </div>
                <?php endif; ?>

                <?php if ($allDone): ?>
                <!-- ─── ALL DONE CELEBRATION ─────────────────────── -->
                <div class="all-done" style="text-align:center;padding:48px 16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="200" height="180" viewBox="0 0 200 180" fill="none" style="margin-bottom:16px;">
                        <!-- Cat body -->
                        <ellipse cx="100" cy="130" rx="50" ry="35" fill="#f5f0eb" stroke="#e0dcd3" stroke-width="2"/>
                        <!-- Cat head -->
                        <circle cx="100" cy="80" r="30" fill="#f5f0eb" stroke="#e0dcd3" stroke-width="2"/>
                        <!-- Left ear -->
                        <polygon points="78,58 70,35 90,50" fill="#f5f0eb" stroke="#e0dcd3" stroke-width="2"/>
                        <polygon points="80,55 74,40 88,52" fill="#ffd6d6"/>
                        <!-- Right ear -->
                        <polygon points="122,58 130,35 110,50" fill="#f5f0eb" stroke="#e0dcd3" stroke-width="2"/>
                        <polygon points="120,55 126,40 112,52" fill="#ffd6d6"/>
                        <!-- Eyes (happy closed) -->
                        <path d="M88,78 Q92,72 96,78" stroke="#1f1f1f" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <path d="M104,78 Q108,72 112,78" stroke="#1f1f1f" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <!-- Nose -->
                        <circle cx="100" cy="85" r="2" fill="#dc4c3e"/>
                        <!-- Mouth (smile) -->
                        <path d="M95,89 Q100,95 105,89" stroke="#1f1f1f" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                        <!-- Whiskers -->
                        <line x1="70" y1="80" x2="88" y2="83" stroke="#d6d1c7" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="70" y1="88" x2="88" y2="87" stroke="#d6d1c7" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="112" y1="83" x2="130" y2="80" stroke="#d6d1c7" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="112" y1="87" x2="130" y2="88" stroke="#d6d1c7" stroke-width="1.5" stroke-linecap="round"/>
                        <!-- Tail -->
                        <path d="M150,125 Q165,100 145,90" stroke="#e0dcd3" stroke-width="3" fill="none" stroke-linecap="round"/>
                        <!-- Sparkles -->
                        <text x="55" y="45" font-size="16">✨</text>
                        <text x="135" y="40" font-size="14">⭐</text>
                        <text x="45" y="110" font-size="12">🎉</text>
                        <text x="150" y="70" font-size="12">🎊</text>
                    </svg>
                    <p style="font-size:18px;font-weight:700;color:#1f1f1f;margin:0 0 4px;">You reached #TasklyZero 🎉</p>
                    <p style="color:#8b8685;font-size:14px;margin:0;"><?php echo $completedCount; ?> task<?php echo $completedCount !== 1 ? 's' : ''; ?> completed</p>
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
                    <p style="color:#8b8685;font-size:15px;margin:0;">Your task list is empty. Add a task to get started!</p>
                </div>

                <?php else: ?>
                <!-- Task List -->
                <ul class="task-list">
                    <?php foreach ($_SESSION['tasks'] as $index => $task): ?>
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
                                <button type="submit" class="checkbox priority-<?php echo $task['priority']; ?><?php echo $isCompleted ? ' checked' : ''; ?>">
                                    <?php if ($isCompleted): ?><span class="checkmark">✓</span><?php endif; ?>
                                </button>
                            </form>
                            <div class="task-content">
                                <span class="task-name"><?php echo $task['name']; ?></span>
                                <span class="task-desc"><?php echo $task['description']; ?></span>
                                <?php if (!empty($task['label'])): ?>
                                <span class="task-label" style="color: <?php echo $labelColor; ?>;">
                                    <span class="label-icon">🏷</span> <?php echo $task['label']; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="task-right">
                                <?php if (!empty($task['label'])): ?>
                                <span class="task-project" style="color: <?php echo $labelColor; ?>;"><?php echo $task['label']; ?> #</span>
                                <?php endif; ?>
                                <div class="task-actions">
                                    <button type="button" class="action-btn edit-btn" title="Edit" onclick="toggleEdit(<?php echo $index; ?>)">✎</button>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="task_index" value="<?php echo $index; ?>">
                                        <button type="submit" class="action-btn delete-btn" title="Delete">🗑</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Inline Edit Panel -->
                        <div class="edit-panel" id="edit-<?php echo $index; ?>" style="display:none;">
                            <form class="new-task-form" method="POST" action="">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="task_index" value="<?php echo $index; ?>">
                                <div class="form-inputs">
                                    <input type="text" name="task_name" class="input-name" value="<?php echo $task['name']; ?>" placeholder="Task name" required>
                                    <input type="text" name="task_description" class="input-desc" value="<?php echo $task['description']; ?>" placeholder="Description" required>
                                </div>
                                <div class="form-footer">
                                    <div class="form-meta">
                                        <select name="task_priority" class="priority-select">
                                            <option value="p1"<?php echo $task['priority'] === 'p1' ? ' selected' : ''; ?>>🔴 Priority 1</option>
                                            <option value="p2"<?php echo $task['priority'] === 'p2' ? ' selected' : ''; ?>>🟠 Priority 2</option>
                                            <option value="p3"<?php echo $task['priority'] === 'p3' ? ' selected' : ''; ?>>🔵 Priority 3</option>
                                            <option value="p4"<?php echo $task['priority'] === 'p4' ? ' selected' : ''; ?>>⚪ Priority 4</option>
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

                <!-- ─── ADD TASK BUTTON ──────────────────────────── -->
                <div class="add-task-trigger" id="add-trigger">
                    <button type="button" class="add-task-inline" id="add-task-btn">
                        <span class="add-icon">+</span>
                        <span>Add task</span>
                    </button>
                </div>

                <!-- ─── ADD TASK FORM ─────────────────────────────── -->
                <div class="add-task-panel" id="add-panel" style="display:none;">
                    <form class="new-task-form" action="" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="form-inputs">
                            <input type="text" name="task_name" id="task_name" class="input-name" placeholder="Task name" required>
                            <input type="text" name="task_description" id="task_description" class="input-desc" placeholder="Description" required>
                            <small class="char-counter" id="char-counter">255 characters remaining</small>
                        </div>

                        <div class="form-footer">
                            <div class="form-meta">
                                <select name="task_priority" class="priority-select">
                                    <option value="p1">🔴 Priority 1</option>
                                    <option value="p2">🟠 Priority 2</option>
                                    <option value="p3">🔵 Priority 3</option>
                                    <option value="p4" selected>⚪ Priority 4</option>
                                </select>
                                <select name="task_label" class="label-select">
                                    <option value="">No label</option>
                                    <?php foreach ($labelColors as $label => $color): ?>
                                    <option value="<?php echo $label; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-buttons">
                                <button type="button" class="btn-cancel" id="cancel-add">Cancel</button>
                                <button type="submit" class="btn-add-task">Add task</button>
                            </div>
                        </div>
                    </form>
                </div>

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
