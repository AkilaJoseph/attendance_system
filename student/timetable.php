<?php
session_start();
include '../config/db_connect.php';
include '../includes/functions.php';

// Security: Check if user is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

include '../includes/header.php';

$student_id = $_SESSION['user_id'];

// Get view mode and filter
$view = isset($_GET['view']) ? $_GET['view'] : 'grid';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'upcoming';

// Get week for grid view
$week_offset = isset($_GET['week']) ? intval($_GET['week']) : 0;
$week_start = getWeekStart(date('Y-m-d', strtotime("$week_offset weeks")));
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

// Build timetable grid
$timetable = buildTimetableGrid($conn, $student_id, $week_start, 'student');

// Get events for list view
if ($filter == 'all') {
    $events = getUserEvents($conn, $student_id);
} else {
    $events = getUpcomingEvents($conn, $student_id, 30);
}

$days = $timetable['days'];
$time_slots = $timetable['time_slots'];
$grid = $timetable['grid'];

// Get current day and time for highlighting
$current_day = date('l');
$current_hour = (int)date('H');
?>

<div class="container">
    <div class="page-header">
        <h2>My Timetable</h2>
        <p>View your class schedule and upcoming events</p>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- View Toggle & Actions -->
    <div class="action-bar">
        <a href="add_event.php" class="btn btn-primary">+ Add Event</a>

        <div class="view-toggle">
            <a href="?view=grid&week=<?php echo $week_offset; ?>" class="toggle-btn <?php echo $view == 'grid' ? 'active' : ''; ?>">
                <span class="toggle-icon">&#9783;</span> Grid
            </a>
            <a href="?view=list&filter=<?php echo $filter; ?>" class="toggle-btn <?php echo $view == 'list' ? 'active' : ''; ?>">
                <span class="toggle-icon">&#9776;</span> List
            </a>
        </div>
    </div>

    <?php if ($view == 'grid'): ?>
    <!-- GRID VIEW -->
    <div class="timetable-container">
        <!-- Week Navigation -->
        <div class="week-navigation">
            <a href="?view=grid&week=<?php echo $week_offset - 1; ?>" class="week-nav-btn">&laquo; Previous</a>
            <div class="week-display">
                <span class="week-label">Week of</span>
                <span class="week-dates">
                    <?php echo date('M j', strtotime($week_start)); ?> -
                    <?php echo date('M j, Y', strtotime($week_end)); ?>
                </span>
                <?php if ($week_offset == 0): ?>
                <span class="current-week-badge">This Week</span>
                <?php endif; ?>
            </div>
            <a href="?view=grid&week=<?php echo $week_offset + 1; ?>" class="week-nav-btn">Next &raquo;</a>
        </div>

        <!-- Quick Jump -->
        <div class="week-quick-jump">
            <?php if ($week_offset != 0): ?>
            <a href="?view=grid&week=0" class="btn btn-sm btn-secondary">Go to This Week</a>
            <?php endif; ?>
        </div>

        <!-- Timetable Grid -->
        <div class="timetable-grid-wrapper">
            <table class="timetable-grid">
                <thead>
                    <tr>
                        <th class="time-header">Time</th>
                        <?php foreach ($days as $index => $day):
                            $day_date = date('Y-m-d', strtotime($week_start . " +$index days"));
                            $is_today = ($day_date == date('Y-m-d'));
                        ?>
                        <th class="day-header <?php echo $is_today ? 'today' : ''; ?>">
                            <span class="day-name"><?php echo $day; ?></span>
                            <span class="day-date"><?php echo date('M j', strtotime($day_date)); ?></span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $slot):
                        $slot_hour = (int)substr($slot, 0, 2);
                        $display_time = date('g A', strtotime($slot));
                        $is_current_hour = ($week_offset == 0 && $current_hour == $slot_hour);
                    ?>
                    <tr class="<?php echo $is_current_hour ? 'current-hour' : ''; ?>">
                        <td class="time-cell"><?php echo $display_time; ?></td>
                        <?php foreach ($days as $index => $day):
                            $day_date = date('Y-m-d', strtotime($week_start . " +$index days"));
                            $is_today = ($day_date == date('Y-m-d'));
                            $cell_items = $grid[$day][$slot] ?? [];
                            $has_items = !empty($cell_items);
                        ?>
                        <td class="grid-cell <?php echo $is_today ? 'today' : ''; ?> <?php echo $has_items ? 'has-items' : ''; ?>">
                            <?php foreach ($cell_items as $item): ?>
                                <?php if ($item['type'] == 'recurring'): ?>
                                <!-- Recurring Class (from course schedule) -->
                                <div class="grid-item recurring-class <?php echo getScheduleTypeClass($item['schedule_type']); ?>">
                                    <?php if (!empty($item['course_color'])): ?>
                                    <span class="course-color-dot" style="background-color: <?php echo htmlspecialchars($item['course_color']); ?>;"></span>
                                    <?php endif; ?>
                                    <span class="item-course"><?php echo htmlspecialchars($item['course_code']); ?></span>
                                    <span class="item-type"><?php echo getScheduleTypeName($item['schedule_type']); ?></span>
                                    <span class="item-time">
                                        <?php echo date('g:i', strtotime($item['start_time'])); ?> -
                                        <?php echo date('g:i A', strtotime($item['end_time'])); ?>
                                    </span>
                                    <?php if ($item['room']): ?>
                                    <span class="item-room"><?php echo htmlspecialchars($item['room']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php elseif ($item['type'] == 'personal'): ?>
                                <!-- Personal Schedule Item -->
                                <div class="grid-item personal-item <?php echo getPersonalScheduleTypeClass($item['schedule_type']); ?>">
                                    <?php if (!empty($item['course_color'])): ?>
                                    <span class="course-color-dot" style="background-color: <?php echo htmlspecialchars($item['course_color']); ?>;"></span>
                                    <?php endif; ?>
                                    <span class="item-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                    <?php if (!empty($item['course_code'])): ?>
                                    <span class="item-course" title="<?php echo htmlspecialchars($item['course_name'] ?? $item['course_code']); ?>">(<?php echo htmlspecialchars($item['course_code']); ?>)</span>
                                    <?php endif; ?>
                                    <span class="item-type"><?php echo getPersonalScheduleTypeName($item['schedule_type']); ?></span>
                                    <span class="item-time">
                                        <?php echo date('g:i', strtotime($item['start_time'])); ?> -
                                        <?php echo date('g:i A', strtotime($item['end_time'])); ?>
                                    </span>
                                    <?php if (!empty($item['location'])): ?>
                                    <span class="item-room"><?php echo htmlspecialchars($item['location']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <!-- One-time Event -->
                                <div class="grid-item event-item <?php echo getEventTypeClass($item['event_type']); ?>">
                                    <span class="item-star">&#9733;</span>
                                    <span class="item-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                    <span class="item-type"><?php echo getEventTypeName($item['event_type']); ?></span>
                                    <?php if ($item['course_code']): ?>
                                    <span class="item-course-badge"><?php echo htmlspecialchars($item['course_code']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend -->
        <div class="timetable-legend">
            <span class="legend-title">Course Schedule:</span>
            <span class="legend-item"><span class="legend-color schedule-type-lecture"></span> Lecture</span>
            <span class="legend-item"><span class="legend-color schedule-type-tutorial"></span> Tutorial</span>
            <span class="legend-item"><span class="legend-color schedule-type-lab"></span> Lab</span>
        </div>
        <div class="timetable-legend">
            <span class="legend-title">My Schedule:</span>
            <span class="legend-item"><span class="legend-color personal-type-class"></span> Class</span>
            <span class="legend-item"><span class="legend-color personal-type-ue"></span> UE</span>
            <span class="legend-item"><span class="legend-color personal-type-ca"></span> CA</span>
            <span class="legend-item"><span class="legend-color personal-type-study"></span> Study</span>
            <span class="legend-item"><span class="legend-color personal-type-personal"></span> Personal</span>
        </div>
        <div class="timetable-legend">
            <span class="legend-title">Events:</span>
            <span class="legend-item"><span class="legend-color event-type-ue"></span> Unit Exam</span>
            <span class="legend-item"><span class="legend-color event-type-ca"></span> CA</span>
            <span class="legend-item"><span class="legend-color event-type-assignment"></span> Assignment</span>
        </div>
    </div>

    <?php else: ?>
    <!-- LIST VIEW -->
    <div class="filter-tabs">
        <a href="?view=list&filter=upcoming" class="tab <?php echo $filter == 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
        <a href="?view=list&filter=all" class="tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All Events</a>
    </div>

    <?php if ($events->num_rows > 0): ?>
    <div class="events-list">
        <?php
        $current_date = '';
        while ($event = $events->fetch_assoc()):
            $event_date = date('Y-m-d', strtotime($event['event_date']));
            $show_date_header = ($event_date != $current_date);
            $current_date = $event_date;
        ?>

        <?php if ($show_date_header): ?>
            <div class="date-header">
                <?php
                $date = new DateTime($event['event_date']);
                $today = new DateTime();
                $tomorrow = (new DateTime())->modify('+1 day');

                if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
                    echo "Today - " . $date->format('l, M j');
                } elseif ($date->format('Y-m-d') == $tomorrow->format('Y-m-d')) {
                    echo "Tomorrow - " . $date->format('l, M j');
                } else {
                    echo $date->format('l, M j, Y');
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="event-card <?php echo getEventTypeClass($event['event_type']); ?>">
            <div class="event-time">
                <?php echo $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : 'All day'; ?>
            </div>
            <div class="event-details">
                <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                <div class="event-meta">
                    <span class="event-type-badge"><?php echo getEventTypeName($event['event_type']); ?></span>
                    <?php if ($event['course_name']): ?>
                        <span class="event-course"><?php echo htmlspecialchars($event['course_code']); ?></span>
                    <?php endif; ?>
                    <?php if ($event['is_course_event']): ?>
                        <span class="course-event-badge">Course Event</span>
                    <?php endif; ?>
                </div>
                <?php if ($event['description']): ?>
                    <div class="event-description"><?php echo htmlspecialchars($event['description']); ?></div>
                <?php endif; ?>
            </div>
            <div class="event-actions">
                <span class="time-until"><?php echo getTimeUntilEvent($event['event_date'], $event['event_time']); ?></span>
                <?php if (!$event['is_course_event']): ?>
                    <form action="../actions/delete_event.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this event?');">
                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <input type="hidden" name="redirect" value="timetable.php?view=list">
                        <button type="submit" class="btn-icon" title="Delete">&#128465;</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128197;</div>
            <h3>No events found</h3>
            <p>Add your first event to start tracking your schedule.</p>
            <a href="add_event.php" class="btn btn-primary">+ Add Event</a>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
