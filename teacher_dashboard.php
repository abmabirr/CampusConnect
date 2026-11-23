<?php
require_once 'includes/helpers.php';
require_once 'includes/db.php';
require_role('Teacher');

$teacherQuery = $conn->prepare('SELECT teacher_id FROM teachers WHERE user_id = ?');
$teacherQuery->bind_param('i', $_SESSION['user_id']);
$teacherQuery->execute();
$teacher = $teacherQuery->get_result()->fetch_assoc();
$teacherQuery->close();
if (!$teacher) {
    http_response_code(404);
    exit('Teacher profile not found. Contact an administrator.');
}
$teacherId = (int) $teacher['teacher_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $appointmentId = filter_var(post_value('appointment_id'), FILTER_VALIDATE_INT);
    $newStatus = post_value('status');
    if (!$appointmentId || !in_array($newStatus, array('Approved', 'Rejected', 'Completed'), true)) {
        $message = 'Invalid appointment update.';
        $messageType = 'error';
    } else {
        $owned = $conn->prepare('SELECT appointment_id, appointment_date, appointment_time FROM appointments WHERE appointment_id = ? AND teacher_id = ? AND status IN (\'Pending\', \'Approved\')');
        $owned->bind_param('ii', $appointmentId, $teacherId);
        $owned->execute();
        $appointment = $owned->get_result()->fetch_assoc();
        $owned->close();
        if (!$appointment) {
            $message = 'That appointment cannot be updated.';
            $messageType = 'error';
        } elseif ($newStatus === 'Approved') {
            $conflict = $conn->prepare("SELECT appointment_id FROM appointments WHERE teacher_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'Approved' AND appointment_id <> ? LIMIT 1");
            $conflict->bind_param('issi', $teacherId, $appointment['appointment_date'], $appointment['appointment_time'], $appointmentId);
            $conflict->execute();
            $hasConflict = $conflict->get_result()->num_rows > 0;
            $conflict->close();
            if ($hasConflict) {
                $message = 'Another appointment is already approved for this time.';
                $messageType = 'error';
            }
        }
        if ($message === '') {
            $update = $conn->prepare('UPDATE appointments SET status = ? WHERE appointment_id = ? AND teacher_id = ?');
            $update->bind_param('sii', $newStatus, $appointmentId, $teacherId);
            $update->execute();
            $message = 'Appointment status updated.';
            $messageType = 'success';
        }
    }
}

$list = $conn->prepare('SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.purpose, a.status, u.name AS student_name, u.email AS student_email, s.phone FROM appointments a JOIN students s ON s.student_id = a.student_id JOIN users u ON u.user_id = s.user_id WHERE a.teacher_id = ? ORDER BY CASE a.status WHEN \'Pending\' THEN 0 WHEN \'Approved\' THEN 1 ELSE 2 END, a.appointment_date, a.appointment_time');
$list->bind_param('i', $teacherId);
$list->execute();
$appointments = $list->get_result();
$notices = $conn->query("SELECT title, description, publish_date FROM notices WHERE status = 'Published' ORDER BY publish_date DESC LIMIT 4");
$summaryQuery = $conn->prepare("SELECT COUNT(*) AS total, SUM(status = 'Pending') AS pending, SUM(status = 'Approved') AS approved FROM appointments WHERE teacher_id = ?");
$summaryQuery->bind_param('i', $teacherId);
$summaryQuery->execute();
$summary = $summaryQuery->get_result()->fetch_assoc();
$summaryQuery->close();
page_header('Teacher Dashboard');
?>
<section class="welcome-section"><div><p class="eyebrow light">Faculty workspace</p><h2>Welcome, <?php echo e($_SESSION['name']); ?></h2><p>Review appointment requests and share updates with your students.</p></div><a class="button button-light" href="notices.php">Campus notices</a></section>
<section class="stats-grid dashboard-stats">
    <article class="stat-card"><span class="stat-icon stat-violet" aria-hidden="true">◷</span><span class="stat-label">Total appointments</span><strong><?php echo (int) $summary['total']; ?></strong><a href="#appointments">View requests <span aria-hidden="true">→</span></a></article>
    <article class="stat-card"><span class="stat-icon stat-amber" aria-hidden="true">✳</span><span class="stat-label">Awaiting your review</span><strong><?php echo (int) $summary['pending']; ?></strong><a href="#appointments">Review pending <span aria-hidden="true">→</span></a></article>
    <article class="stat-card"><span class="stat-icon stat-mint" aria-hidden="true">✓</span><span class="stat-label">Approved meetings</span><strong><?php echo (int) $summary['approved']; ?></strong><a href="#appointments">See schedule <span aria-hidden="true">→</span></a></article>
</section>
<?php if ($message !== '') { ?><p class="form-message <?php echo e($messageType); ?>"><?php echo e($message); ?></p><?php } ?>
<section id="appointments" class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Student requests</p><h2>Appointments</h2></div></div>
    <?php if ($appointments->num_rows === 0) { ?><p class="empty-state">No appointment requests yet.</p><?php } ?>
    <?php while ($appointment = $appointments->fetch_assoc()) { ?>
        <article class="request-card"><div class="request-heading"><div><h3><?php echo e($appointment['student_name']); ?></h3><time><?php echo e($appointment['student_email'] . ' · ' . $appointment['phone']); ?></time></div><span class="status status-<?php echo e(strtolower($appointment['status'])); ?>"><?php echo e($appointment['status']); ?></span></div>
            <p><strong><?php echo e(date('M j, Y', strtotime($appointment['appointment_date'])) . ' · ' . date('g:i A', strtotime($appointment['appointment_time']))); ?></strong></p><p><?php echo nl2br(e($appointment['purpose'])); ?></p>
            <?php if (in_array($appointment['status'], array('Pending', 'Approved'), true)) { ?><form method="post" class="inline-actions"><?php echo csrf_field(); ?><input type="hidden" name="appointment_id" value="<?php echo (int) $appointment['appointment_id']; ?>"><button class="button button-small" name="status" value="Approved">Approve</button><button class="button button-small button-muted" name="status" value="Rejected">Reject</button><?php if ($appointment['status'] === 'Approved') { ?><button class="button button-small button-muted" name="status" value="Completed">Mark completed</button><?php } ?></form><?php } ?>
        </article>
    <?php } ?>
</section>
<section class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Campus updates</p><h2>Latest notices</h2></div><a class="text-link" href="notices.php">All notices</a></div>
    <?php if ($notices->num_rows === 0) { ?><p class="empty-state">No published notices currently.</p><?php } ?>
    <?php while ($notice = $notices->fetch_assoc()) { ?><article class="notice-preview"><div><h3><?php echo e($notice['title']); ?></h3><time><?php echo e(date('M j, Y', strtotime($notice['publish_date']))); ?></time></div><p><?php echo nl2br(e($notice['description'])); ?></p></article><?php } ?>
</section>
<?php page_footer(); ?>
