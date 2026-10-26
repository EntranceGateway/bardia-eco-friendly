<?php
/**
 * CMS — Contact Submissions Inbox with Email Reply
 */

$pageTitle = 'Contact Submissions';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/template-engine.php';

$success = '';
$error   = '';

$allowedStatuses = ['new', 'read', 'replied', 'archived'];

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id   = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM contact_submissions WHERE id = ?");
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() ? 'Submission deleted.' : '';
        if (!$success) $error = 'Delete failed: ' . $stmt->error;
        $stmt->close();

    } elseif ($action === 'update_status') {
        $id     = (int)   ($_POST['id']     ?? 0);
        $status =          $_POST['status'] ?? '';
        if ($id > 0 && in_array($status, $allowedStatuses, true)) {
            $stmt = $conn->prepare("UPDATE contact_submissions SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $id);
            $success = $stmt->execute() ? 'Status updated to "' . $status . '".' : '';
            if (!$success) $error = 'Update failed: ' . $stmt->error;
            $stmt->close();
        } else {
            $error = 'Invalid ID or status.';
        }
    }
}

// ---------- Count per status for badges ----------
$counts = ['new' => 0, 'read' => 0, 'replied' => 0, 'archived' => 0];
try {
    $cRes = db_query($conn, "SELECT status, COUNT(*) AS c FROM contact_submissions GROUP BY status");
    while ($cr = $cRes->fetch_assoc()) { $counts[$cr['status']] = (int) $cr['c']; }
} catch (RuntimeException $e) {
    error_log('[contact-submissions-enhanced] counts fetch: ' . $e->getMessage());
}

// ---------- Active filter ----------
$filterStatus = $_GET['status'] ?? '';
if (!in_array($filterStatus, $allowedStatuses, true)) $filterStatus = '';

// ---------- Fetch submissions ----------
$submissions = [];
$sql = "SELECT id, full_name, email, phone, num_guests, preferred_package, travel_dates, message, status, email_count, last_email_sent_at, created_at FROM contact_submissions";
if ($filterStatus !== '') {
    $stmt = $conn->prepare($sql . " WHERE status = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $filterStatus);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY created_at DESC");
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { 
    $row['id'] = (int) $row['id'];
    $row['email_count'] = (int) $row['email_count'];
    $submissions[] = $row;
}
$stmt->close();

// Get email templates
$templates = [];
try {
    $tRes = db_query($conn, "SELECT id, name, subject, description FROM email_templates WHERE is_active = 1 ORDER BY name ASC");
    while ($t = $tRes->fetch_assoc()) { $templates[] = $t; }
} catch (RuntimeException $e) {
    error_log('[contact-submissions-enhanced] templates fetch: ' . $e->getMessage());
}

$statusBadge = [
    'new'      => 'badge-green',
    'read'     => 'badge-gold',
    'replied'  => 'badge',
    'archived' => 'badge-red',
];
$statusLabels = ['new' => '🆕 New', 'read' => '👁 Read', 'replied' => '✅ Replied', 'archived' => '📦 Archived'];
?>

<!-- Quill.js CDN -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<style>
.email-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 8px;
}
.email-history-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 8px;
}
.email-history-item .subject {
    font-weight: 600;
    margin-bottom: 4px;
}
.email-history-item .meta {
    font-size: 0.8rem;
    color: #6b7280;
}
</style>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Status filter tabs -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
    <a href="contact-submissions-enhanced.php" class="btn btn-sm <?= $filterStatus === '' ? 'btn-primary' : 'btn-secondary' ?>">
        All <span style="opacity:.7;">(<?= array_sum($counts) ?>)</span>
    </a>
    <?php foreach ($allowedStatuses as $s): ?>
    <a href="contact-submissions-enhanced.php?status=<?= $s ?>" class="btn btn-sm <?= $filterStatus === $s ? 'btn-primary' : 'btn-secondary' ?>">
        <?= htmlspecialchars($statusLabels[$s]) ?>
        <?php if ($counts[$s] > 0): ?><span style="background:rgba(255,255,255,.25);border-radius:99px;padding:0 6px;margin-left:2px;"><?= $counts[$s] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="tbl-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email / Phone</th><th>Guests</th><th>Package</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                        <?php if ($s['email_count'] > 0): ?>
                            <span class="email-badge">✉️ <?= $s['email_count'] ?></span>
                        <?php endif; ?>
                        <br>
                        <small style="color:#6b7280;"><?= date('d M Y, H:i', strtotime($s['created_at'])) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($s['email']) ?><br>
                        <small><?= htmlspecialchars($s['phone']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($s['num_guests']) ?></td>
                    <td>
                        <?= $s['preferred_package'] ? htmlspecialchars($s['preferred_package']) : '<span style="color:#9ca3af">—</span>' ?>
                        <?php if ($s['travel_dates']): ?><br><small style="color:#6b7280;"><?= htmlspecialchars($s['travel_dates']) ?></small><?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                    <td><span class="badge <?= $statusBadge[$s['status']] ?? 'badge' ?>"><?= ucfirst($s['status']) ?></span></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick='openEmailModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)'>✉️ Reply</button>
                        <button class="btn btn-secondary btn-sm" onclick='viewSubmission(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)'>View</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this submission?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">✕</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($submissions)): ?>
                <tr><td colspan="7" style="color:#6b7280;text-align:center;padding:24px;">
                    <?= $filterStatus ? 'No ' . $filterStatus . ' submissions.' : 'No submissions yet.' ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile cards -->
<div class="m-cards">
    <?php foreach ($submissions as $s): ?>
    <div class="m-card">
        <div class="m-title">
            <?= htmlspecialchars($s['full_name']) ?> 
            <span class="badge <?= $statusBadge[$s['status']] ?? '' ?>"><?= ucfirst($s['status']) ?></span>
            <?php if ($s['email_count'] > 0): ?>
                <span class="email-badge">✉️ <?= $s['email_count'] ?></span>
            <?php endif; ?>
        </div>
        <div class="m-row"><span>Email</span><span><?= htmlspecialchars($s['email']) ?></span></div>
        <div class="m-row"><span>Phone</span><span><?= htmlspecialchars($s['phone']) ?></span></div>
        <div class="m-row"><span>Guests</span><span><?= htmlspecialchars($s['num_guests']) ?></span></div>
        <div class="m-row"><span>Received</span><span><?= date('d M Y', strtotime($s['created_at'])) ?></span></div>
        <div class="m-actions">
            <button class="btn btn-primary btn-sm" onclick='openEmailModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)'>✉️ Reply</button>
            <button class="btn btn-secondary btn-sm" onclick='viewSubmission(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)'>View</button>
            <form method="POST" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- View / Status Modal -->
<div class="modal-backdrop" id="viewModal">
    <div class="modal" style="width:min(640px,96vw);">
        <h3 id="vmName" style="margin-bottom:4px;"></h3>
        <p id="vmMeta" style="color:#6b7280;font-size:.82rem;margin-bottom:16px;"></p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;font-size:.88rem;margin-bottom:14px;">
            <div><span style="color:#6b7280;">Email</span><br><strong id="vmEmail"></strong></div>
            <div><span style="color:#6b7280;">Phone</span><br><strong id="vmPhone"></strong></div>
            <div><span style="color:#6b7280;">Guests</span><br><span id="vmGuests"></span></div>
            <div><span style="color:#6b7280;">Status</span><br><span id="vmStatus"></span></div>
            <div><span style="color:#6b7280;">Package</span><br><span id="vmPackage"></span></div>
            <div><span style="color:#6b7280;">Travel Dates</span><br><span id="vmDates"></span></div>
        </div>

        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:12px;font-size:.9rem;line-height:1.6;margin-bottom:16px;">
            <div style="font-size:.75rem;color:#6b7280;margin-bottom:4px;font-weight:600;">MESSAGE</div>
            <div id="vmMessage" style="white-space:pre-wrap;"></div>
        </div>

        <!-- Email History -->
        <div id="emailHistorySection" style="margin-bottom:16px;display:none;">
            <div style="font-size:.85rem;font-weight:600;margin-bottom:8px;">📧 Email History</div>
            <div id="emailHistoryList"></div>
        </div>

        <!-- Quick status update -->
        <form method="POST" id="statusForm" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" id="vmId">
            <label style="font-size:.85rem;font-weight:500;">Change status:</label>
            <?php foreach ($allowedStatuses as $st): ?>
            <button type="submit" name="status" value="<?= $st ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars($statusLabels[$st]) ?></button>
            <?php endforeach; ?>
        </form>

        <div style="text-align:right;">
            <button class="btn btn-secondary" onclick="closeView()">Close</button>
        </div>
    </div>
</div>

<!-- Email Composer Modal -->
<div class="modal-backdrop" id="emailModal">
    <div class="modal" style="width:min(900px,96vw);max-height:95vh;">
        <h3>✉️ Reply to: <span id="emailRecipientName"></span></h3>
        <p style="color:#6b7280;font-size:0.85rem;margin-bottom:16px;">
            To: <strong id="emailRecipientEmail"></strong>
        </p>
        
        <form id="emailForm" onsubmit="sendEmail(event)">
            <input type="hidden" id="emailSubmissionId">
            
            <!-- Template Selector -->
            <div class="form-group">
                <label>📋 Quick Template (Optional)</label>
                <select id="templateSelect" class="form-control" onchange="loadTemplate()">
                    <option value="">-- Select Template --</option>
                    <?php foreach ($templates as $t): ?>
                    <option value="<?= $t['id'] ?>" data-name="<?= htmlspecialchars($t['name']) ?>">
                        <?= htmlspecialchars($t['name']) ?> 
                        <?php if ($t['description']): ?>- <?= htmlspecialchars($t['description']) ?><?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Subject -->
            <div class="form-group">
                <label>Subject *</label>
                <input type="text" id="emailSubject" class="form-control" required minlength="5" maxlength="500">
            </div>
            
            <!-- Rich Text Editor -->
            <div class="form-group">
                <label>Message *</label>
                <div id="emailBody" style="height:300px;"></div>
            </div>
            
            <!-- Actions -->
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="closeEmailModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="sendBtn">
                    <span id="sendBtnText">📤 Send Email</span>
                    <span id="sendBtnLoader" style="display:none;">⏳ Sending...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentSubmission = null;
let quillEditor = null;

// Initialize Quill
function initEditor() {
    if (quillEditor) return;
    
    quillEditor = new Quill('#emailBody', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'align': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                ['link'],
                ['clean']
            ]
        },
        placeholder: 'Type your email message here...'
    });
}

function openEmailModal(submission) {
    currentSubmission = submission;
    document.getElementById('emailModal').classList.add('active');
    document.getElementById('emailRecipientName').textContent = submission.full_name;
    document.getElementById('emailRecipientEmail').textContent = submission.email;
    document.getElementById('emailSubmissionId').value = submission.id;
    document.getElementById('emailSubject').value = 'Re: Your Enquiry - Bardiya Eco Friendly';
    document.getElementById('templateSelect').value = '';
    
    // Initialize editor if not already done
    if (!quillEditor) {
        initEditor();
    } else {
        quillEditor.root.innerHTML = '';
    }
}

function closeEmailModal() {
    document.getElementById('emailModal').classList.remove('active');
}

async function loadTemplate() {
    const templateId = document.getElementById('templateSelect').value;
    if (!templateId || !currentSubmission) return;
    
    try {
        const response = await fetch(`${API_BASE_URL}/api/email-templates/get?template_id=${templateId}&submission_id=${currentSubmission.id}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            document.getElementById('emailSubject').value = data.data.subject;
            quillEditor.root.innerHTML = data.data.body_html;
        } else {
            alert('Error loading template: ' + data.message);
        }
    } catch (error) {
        alert('Failed to load template: ' + error.message);
    }
}

async function sendEmail(event) {
    event.preventDefault();
    
    const submissionId = document.getElementById('emailSubmissionId').value;
    const subject = document.getElementById('emailSubject').value;
    const bodyHtml = quillEditor.root.innerHTML;
    
    if (!bodyHtml || bodyHtml.trim().length < 10) {
        alert('Please enter an email message.');
        return;
    }
    
    const sendBtn = document.getElementById('sendBtn');
    const sendBtnText = document.getElementById('sendBtnText');
    const sendBtnLoader = document.getElementById('sendBtnLoader');
    
    sendBtn.disabled = true;
    sendBtnText.style.display = 'none';
    sendBtnLoader.style.display = 'inline';
    
    try {
        const response = await fetch(`${API_BASE_URL}/api/emails/send-reply`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                submission_id: parseInt(submissionId),
                subject: subject,
                body_html: bodyHtml,
                body_plain: stripHtml(bodyHtml)
            })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            alert('✅ Email sent successfully!');
            closeEmailModal();
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        alert('❌ Failed to send email: ' + error.message);
    } finally {
        sendBtn.disabled = false;
        sendBtnText.style.display = 'inline';
        sendBtnLoader.style.display = 'none';
    }
}

function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

async function viewSubmission(s) {
    document.getElementById('viewModal').classList.add('active');
    document.getElementById('vmName').textContent    = s.full_name;
    document.getElementById('vmMeta').textContent    = 'Received: ' + s.created_at;
    document.getElementById('vmEmail').textContent   = s.email;
    document.getElementById('vmPhone').textContent   = s.phone;
    document.getElementById('vmGuests').textContent  = s.num_guests;
    document.getElementById('vmStatus').textContent  = s.status;
    document.getElementById('vmPackage').textContent = s.preferred_package || '—';
    document.getElementById('vmDates').textContent   = s.travel_dates || '—';
    document.getElementById('vmMessage').textContent = s.message;
    document.getElementById('vmId').value            = s.id;
    
    // Load email history
    if (s.email_count > 0) {
        try {
            const response = await fetch(`${API_BASE_URL}/api/emails/history?submission_id=${s.id}`);
            const data = await response.json();
            
            if (data.status === 'success' && data.data.length > 0) {
                const historyHtml = data.data.map(email => `
                    <div class="email-history-item">
                        <div class="subject">${escapeHtml(email.subject)}</div>
                        <div class="meta">
                            Sent by ${escapeHtml(email.sent_by)} on ${email.sent_at}
                            <span class="badge ${email.status === 'sent' ? 'badge-green' : 'badge-red'}">${email.status}</span>
                        </div>
                    </div>
                `).join('');
                
                document.getElementById('emailHistoryList').innerHTML = historyHtml;
                document.getElementById('emailHistorySection').style.display = 'block';
            }
        } catch (error) {
            console.error('Failed to load email history:', error);
        }
    } else {
        document.getElementById('emailHistorySection').style.display = 'none';
    }
}

function closeView() { 
    document.getElementById('viewModal').classList.remove('active'); 
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.getElementById('viewModal').addEventListener('click', e => { 
    if (e.target === document.getElementById('viewModal')) closeView(); 
});

document.getElementById('emailModal').addEventListener('click', e => { 
    if (e.target === document.getElementById('emailModal')) closeEmailModal(); 
});
</script>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
