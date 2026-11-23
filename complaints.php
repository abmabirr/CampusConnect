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
    $subject = post_value('subject');
    $description = post_value('description');
    if ($subject === '' || strlen($subject) > 180 || $description === '' || strlen($description) > 10000) {
        $message = 'Add a subject and description (up to 10,000 characters).';
        $messageType = 'error';
    } else {
        $insert = $conn->prepare('INSERT INTO complaints (student_id, subject, description, complaint_date) VALUES (?, ?, ?, CURRENT_DATE())');
        $insert->bind_param('iss', $student['student_id'], $subject, $description);
        $insert->execute();
        $message = 'Your complaint has been submitted.';
        $messageType = 'success';
    }
}

$list = $conn->prepare('SELECT complaint_id, subject, description, complaint_date, status, response FROM complaints WHERE student_id = ? ORDER BY complaint_date DESC');
$list->bind_param('i', $student['student_id']);
$list->execute();
$complaints = $list->get_result();
page_header('Complaints');
?>
<section class="content-panel narrow-panel">
    <p class="eyebrow">Student support</p><h2>Submit a complaint</h2>
    <?php if ($message !== '') { ?><p class="form-message <?php echo e($messageType); ?>"><?php echo e($message); ?></p><?php } ?>
    <form method="post" class="form-grid single-column">
        <?php echo csrf_field(); ?>
        <div class="form-group"><label for="subject">Subject</label><input id="subject" name="subject" maxlength="180" value="<?php echo e(post_value('subject')); ?>" required></div>
        <div class="form-group"><label for="description">Details</label><textarea id="description" name="description" rows="5" maxlength="10000" required><?php echo e(post_value('description')); ?></textarea></div>
        <button type="submit" class="button">Submit complaint</button>
    </form>
</section>
<section class="content-panel">
    <div class="section-heading"><div><p class="eyebrow">Follow up</p><h2>My complaints</h2></div></div>
    <?php if ($complaints->num_rows === 0) { ?><p class="empty-state">You have not submitted any complaints.</p><?php } ?>
    <?php while ($complaint = $complaints->fetch_assoc()) { ?>
        <article class="request-card"><div class="request-heading"><div><h3><?php echo e($complaint['subject']); ?></h3><time><?php echo e(date('M j, Y · g:i A', strtotime($complaint['complaint_date']))); ?></time></div><span class="status status-<?php echo e(strtolower(str_replace(' ', '-', $complaint['status']))); ?>"><?php echo e($complaint['status']); ?></span></div>
            <p><?php echo nl2br(e($complaint['description'])); ?></p>
            <?php if ($complaint['response'] !== null && $complaint['response'] !== '') { ?><div class="response"><strong>Admin response</strong><p><?php echo nl2br(e($complaint['response'])); ?></p></div><?php } ?>
        </article>
    <?php } ?>
</section>
<?php page_footer(); ?>
