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
    $labId = filter_var(post_value('lab_id'), FILTER_VALIDATE_INT);
    $date = post_value('booking_date');
    $start = post_value('start_time');
    $end = post_value('end_time');
    $purpose = post_value('purpose');
    $validDate = DateTime::createFromFormat('!Y-m-d', $date);
    $validStart = DateTime::createFromFormat('!H:i', $start);
    $validEnd = DateTime::createFromFormat('!H:i', $end);
    if (!$labId || !$validDate || $validDate->format('Y-m-d') !== $date || $date < date('Y-m-d') || !$validStart || $validStart->format('H:i') !== $start || !$validEnd || $validEnd->format('H:i') !== $end || $end <= $start || $purpose === '') {
        $message = 'Choose a valid lab, date, time range, and enter a purpose.';
        $messageType = 'error';
    } else {
        $labCheck = $conn->prepare("SELECT lab_id FROM labs WHERE lab_id = ? AND availability_status = 'Available'");
        $labCheck->bind_param('i', $labId);
        $labCheck->execute();
        $labExists = $labCheck->get_result()->num_rows === 1;
        $labCheck->close();
        if (!$labExists) {
            $message = 'That lab is unavailable. Select another lab.';
            $messageType = 'error';
        } else {
            $conflict = $conn->prepare("SELECT booking_id FROM lab_bookings WHERE lab_id = ? AND booking_date = ? AND status IN ('Pending', 'Approved') AND start_time < ? AND end_time > ? LIMIT 1");
            $conflict->bind_param('isss', $labId, $date, $end, $start);
            $conflict->execute();
            $slotTaken = $conflict->get_result()->num_rows > 0;
            $conflict->close();
            if ($slotTaken) {
                $message = 'This lab has an overlapping request for that time. Choose another slot.';
                $messageType = 'error';
            } else {
                $insert = $conn->prepare('INSERT INTO lab_bookings (student_id, lab_id, booking_date, start_time, end_time, purpose) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->bind_param('iissss', $student['student_id'], $labId, $date, $start, $end, $purpose);
                $insert->execute();
                $message = 'Lab booking request submitted.';
                $messageType = 'success';
            }
        }
    }
}

$labs = $conn->query('SELECT lab_id, lab_name, lab_location, capacity FROM labs WHERE availability_status = \'Available\' ORDER BY lab_name');
$list = $conn->prepare('SELECT b.booking_date, b.start_time, b.end_time, b.purpose, b.status, l.lab_name, l.lab_location FROM lab_bookings b JOIN labs l ON l.lab_id = b.lab_id WHERE b.student_id = ? ORDER BY b.booking_date DESC, b.start_time DESC');
$list->bind_param('i', $student['student_id']);
$list->execute();
$bookings = $list->get_result();
page_header('Lab Booking');
?>
<section class="content-panel narrow-panel">
    <p class="eyebrow">Reserve campus facilities</p><h2>Request a lab booking</h2>
    <?php if ($message !== '') { ?><p class="form-message <?php echo e($messageType); ?>"><?php echo e($message); ?></p><?php } ?>
    <?php if ($labs->num_rows === 0) { ?><p class="empty-state">No labs are currently available.</p><?php } else { ?>
    <form method="post" class="form-grid">
        <?php echo csrf_field(); ?>
        <div class="form-group full-width"><label for="lab_id">Laboratory</label><select id="lab_id" name="lab_id" required><option value="">Select a lab</option><?php while ($lab = $labs->fetch_assoc()) { ?><option value="<?php echo (int) $lab['lab_id']; ?>" <?php echo post_value('lab_id') == $lab['lab_id'] ? 'selected' : ''; ?>><?php echo e($lab['lab_name'] . ' · ' . $lab['lab_location'] . ' · capacity ' . $lab['capacity']); ?></option><?php } ?></select></div>
        <div class="form-group"><label for="booking_date">Date</label><input id="booking_date" type="date" name="booking_date" min="<?php echo e(date('Y-m-d')); ?>" value="<?php echo e(post_value('booking_date')); ?>" required></div>
        <div class="form-group"><label for="start_time">Start time</label><input id="start_time" type="time" name="start_time" value="<?php echo e(post_value('start_time')); ?>" required></div>
        <div class="form-group"><label for="end_time">End time</label><input id="end_time" type="time" name="end_time" value="<?php echo e(post_value('end_time')); ?>" required></div>
        <div class="form-group full-width"><label for="purpose">Purpose</label><textarea id="purpose" name="purpose" rows="3" required><?php echo e(post_value('purpose')); ?></textarea></div>
        <button type="submit" class="button">Request lab booking</button>
    </form><?php } ?>
</section>
<section class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Booking history</p><h2>My lab requests</h2></div></div>
    <?php if ($bookings->num_rows === 0) { ?><p class="empty-state">No lab bookings yet.</p><?php } ?>
    <?php while ($booking = $bookings->fetch_assoc()) { ?>
        <article class="request-card"><div class="request-heading"><div><h3><?php echo e($booking['lab_name']); ?></h3><time><?php echo e($booking['lab_location'] . ' · ' . date('M j, Y', strtotime($booking['booking_date'])) . ' · ' . date('g:i A', strtotime($booking['start_time'])) . '–' . date('g:i A', strtotime($booking['end_time']))); ?></time></div><span class="status status-<?php echo e(strtolower($booking['status'])); ?>"><?php echo e($booking['status']); ?></span></div><p><?php echo nl2br(e($booking['purpose'])); ?></p></article>
    <?php } ?>
</section>
<?php page_footer(); ?>
