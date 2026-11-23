<?php
require_once 'includes/helpers.php';
require_once 'includes/db.php';
require_role('Student');

$studentQuery = $conn->prepare('SELECT student_id FROM students WHERE user_id = ?');
$studentQuery->bind_param('i', $_SESSION['user_id']);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();
$studentQuery->close();
if (!$student) {
    http_response_code(404);
    exit('Student profile not found. Contact an administrator.');
}

$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $teacherId = filter_var(post_value('teacher_id'), FILTER_VALIDATE_INT);
    $date = post_value('appointment_date');
    $time = post_value('appointment_time');
    $purpose = post_value('purpose');
    $validDate = DateTime::createFromFormat('!Y-m-d', $date);
    $validTime = DateTime::createFromFormat('!H:i', $time);
    if (!$teacherId || !$validDate || $validDate->format('Y-m-d') !== $date || $date < date('Y-m-d') || !$validTime || $validTime->format('H:i') !== $time || $purpose === '' || strlen($purpose) > 500) {
        $message = 'Choose a valid teacher, future date, time, and purpose (up to 500 characters).';
        $messageType = 'error';
    } else {
        $teacherCheck = $conn->prepare("SELECT teacher_id FROM teachers WHERE teacher_id = ? AND EXISTS (SELECT 1 FROM users WHERE users.user_id = teachers.user_id AND users.role = 'Teacher')");
        $teacherCheck->bind_param('i', $teacherId);
        $teacherCheck->execute();
        $teacherExists = $teacherCheck->get_result()->num_rows === 1;
        $teacherCheck->close();
        if (!$teacherExists) {
            $message = 'Select an available teacher.';
            $messageType = 'error';
        } else {
            $conflict = $conn->prepare("SELECT appointment_id FROM appointments WHERE teacher_id = ? AND appointment_date = ? AND appointment_time = ? AND status IN ('Pending', 'Approved') LIMIT 1");
            $conflict->bind_param('iss', $teacherId, $date, $time);
            $conflict->execute();
            $slotTaken = $conflict->get_result()->num_rows > 0;
            $conflict->close();
            if ($slotTaken) {
                $message = 'That teacher already has a request for this time. Choose another slot.';
                $messageType = 'error';
            } else {
                $insert = $conn->prepare('INSERT INTO appointments (student_id, teacher_id, appointment_date, appointment_time, purpose) VALUES (?, ?, ?, ?, ?)');
                $insert->bind_param('iisss', $student['student_id'], $teacherId, $date, $time, $purpose);
                $insert->execute();
                $message = 'Appointment request sent to the teacher.';
                $messageType = 'success';
            }
        }
    }
}

$teachers = $conn->query("SELECT t.teacher_id, u.name, t.designation, d.department_name FROM teachers t JOIN users u ON u.user_id = t.user_id JOIN departments d ON d.department_id = t.department_id WHERE u.role = 'Teacher' ORDER BY u.name");
$list = $conn->prepare('SELECT a.appointment_date, a.appointment_time, a.purpose, a.status, u.name AS teacher_name, t.designation FROM appointments a JOIN teachers t ON t.teacher_id = a.teacher_id JOIN users u ON u.user_id = t.user_id WHERE a.student_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC');
$list->bind_param('i', $student['student_id']);
$list->execute();
$appointments = $list->get_result();
page_header('Teacher Appointments');
?>
<section class="content-panel narrow-panel">
    <p class="eyebrow">Meet your faculty</p><h2>Request an appointment</h2>
    <?php if ($message !== '') { ?><p class="form-message <?php echo e($messageType); ?>"><?php echo e($message); ?></p><?php } ?>
    <?php if ($teachers->num_rows === 0) { ?><p class="empty-state">No teacher profiles are available yet. Contact the administrator.</p><?php } else { ?>
    <form method="post" class="form-grid">
        <?php echo csrf_field(); ?>
        <div class="form-group full-width"><label for="teacher_id">Teacher</label><select id="teacher_id" name="teacher_id" required><option value="">Select a teacher</option><?php while ($teacher = $teachers->fetch_assoc()) { ?><option value="<?php echo (int) $teacher['teacher_id']; ?>" <?php echo post_value('teacher_id') == $teacher['teacher_id'] ? 'selected' : ''; ?>><?php echo e($teacher['name'] . ' · ' . $teacher['designation'] . ' · ' . $teacher['department_name']); ?></option><?php } ?></select></div>
        <div class="form-group"><label for="appointment_date">Date</label><input id="appointment_date" type="date" name="appointment_date" min="<?php echo e(date('Y-m-d')); ?>" value="<?php echo e(post_value('appointment_date')); ?>" required></div>
        <div class="form-group"><label for="appointment_time">Time</label><input id="appointment_time" type="time" name="appointment_time" value="<?php echo e(post_value('appointment_time')); ?>" required></div>
        <div class="form-group full-width"><label for="purpose">Purpose</label><textarea id="purpose" name="purpose" rows="3" maxlength="500" required><?php echo e(post_value('purpose')); ?></textarea></div>
        <button type="submit" class="button">Request appointment</button>
    </form><?php } ?>
</section>
<section class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Request history</p><h2>My appointments</h2></div></div>
    <?php if ($appointments->num_rows === 0) { ?><p class="empty-state">No appointment requests yet.</p><?php } ?>
    <?php while ($appointment = $appointments->fetch_assoc()) { ?>
        <article class="request-card"><div class="request-heading"><div><h3><?php echo e($appointment['teacher_name']); ?> <span class="muted">· <?php echo e($appointment['designation']); ?></span></h3><time><?php echo e(date('M j, Y', strtotime($appointment['appointment_date'])) . ' · ' . date('g:i A', strtotime($appointment['appointment_time']))); ?></time></div><span class="status status-<?php echo e(strtolower($appointment['status'])); ?>"><?php echo e($appointment['status']); ?></span></div><p><?php echo nl2br(e($appointment['purpose'])); ?></p></article>
    <?php } ?>
</section>
<?php page_footer(); ?>
