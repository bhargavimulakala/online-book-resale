<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];

// All conversations this user is part of
$conversations = $conn->query("
    SELECT
        m.book_id,
        b.title as book_title,
        b.id as bid,
        CASE WHEN m.sender_id = $userId THEN m.receiver_id ELSE m.sender_id END as other_id,
        u.name as other_name,
        MAX(m.created_at) as last_msg_time,
        COUNT(CASE WHEN m.receiver_id=$userId AND m.is_read=0 THEN 1 END) as unread
    FROM messages m
    JOIN books b ON m.book_id = b.id
    JOIN users u ON u.id = CASE WHEN m.sender_id=$userId THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = $userId OR m.receiver_id = $userId
    GROUP BY m.book_id, other_id
    ORDER BY last_msg_time DESC
")->fetch_all(MYSQLI_ASSOC);

// View specific conversation
$withUserId = (int)($_GET['with'] ?? 0);
$bookId = (int)($_GET['book'] ?? 0);
$msgs = [];
if ($withUserId && $bookId) {
    // Mark as read
    $conn->query("UPDATE messages SET is_read=1 WHERE receiver_id=$userId AND sender_id=$withUserId AND book_id=$bookId");
    $msgs = $conn->query("
        SELECT m.*, u.name as sender_name
        FROM messages m JOIN users u ON m.sender_id=u.id
        WHERE m.book_id=$bookId AND ((m.sender_id=$userId AND m.receiver_id=$withUserId) OR (m.sender_id=$withUserId AND m.receiver_id=$userId))
        ORDER BY m.created_at ASC
    ")->fetch_all(MYSQLI_ASSOC);
    $otherUser = $conn->query("SELECT name FROM users WHERE id=$withUserId")->fetch_assoc();
    $book = $conn->query("SELECT * FROM books WHERE id=$bookId")->fetch_assoc();
}

$pageTitle = 'Messages';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <h4 class="text-white fw-bold mb-4"><i class="bi bi-envelope me-2 text-gold"></i>Messages</h4>
    <div class="row g-4">
        <!-- Conversation List -->
        <div class="col-md-4">
            <div class="glass-card overflow-hidden">
                <div class="p-3" style="border-bottom:1px solid rgba(212,175,55,0.1);">
                    <h6 class="text-white fw-bold mb-0">Conversations</h6>
                </div>
                <?php if (empty($conversations)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-chat-square" style="font-size:2.5rem;opacity:0.3;"></i>
                    <p class="mt-2 small">No messages yet.</p>
                </div>
                <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <a href="?with=<?= $conv['other_id'] ?>&book=<?= $conv['book_id'] ?>"
                   class="d-flex align-items-center gap-3 p-3 text-decoration-none <?= ($withUserId==$conv['other_id']&&$bookId==$conv['book_id'])?'conversation-active':'' ?>"
                   style="border-bottom:1px solid rgba(255,255,255,0.05);transition:background 0.2s;" onmouseover="this.style.background='rgba(212,175,55,0.05)'" onmouseout="this.style.background=''">
                    <div class="position-relative">
                        <i class="bi bi-person-circle fs-3 text-gold"></i>
                        <?php if ($conv['unread'] > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;"><?= $conv['unread'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-white fw-bold small"><?= sanitize($conv['other_name']) ?></div>
                        <small class="text-muted"><?= sanitize(substr($conv['book_title'],0,30)) ?>...</small>
                    </div>
                    <small class="text-muted flex-shrink-0"><?= timeAgo($conv['last_msg_time']) ?></small>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="col-md-8">
            <?php if ($withUserId && $bookId && isset($otherUser, $book)): ?>
            <div class="glass-card overflow-hidden d-flex flex-column" style="height:550px;">
                <!-- Header -->
                <div class="p-3 d-flex align-items-center gap-3" style="border-bottom:1px solid rgba(212,175,55,0.15);">
                    <i class="bi bi-person-circle fs-3 text-gold"></i>
                    <div>
                        <h6 class="text-white fw-bold mb-0"><?= sanitize($otherUser['name']) ?></h6>
                        <small class="text-muted">Re: <?= sanitize($book['title']) ?></small>
                    </div>
                    <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $bookId ?>" class="btn btn-sm btn-outline-gold ms-auto">View Book</a>
                </div>
                <!-- Messages -->
                <div class="flex-grow-1 p-3 overflow-auto" id="chatBox" style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($msgs as $m): $isMine = $m['sender_id'] == $userId; ?>
                    <div class="d-flex <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div class="chat-bubble <?= $isMine ? 'sent' : 'received' ?>" style="max-width:70%;padding:10px 14px;border-radius:<?= $isMine ? '16px 16px 4px 16px' : '16px 16px 16px 4px' ?>;background:<?= $isMine ? 'rgba(212,175,55,0.15);border:1px solid rgba(212,175,55,0.3)' : 'rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1)' ?>;">
                            <p class="text-white small mb-1"><?= sanitize($m['message']) ?></p>
                            <small class="text-muted" style="font-size:0.7rem;"><?= timeAgo($m['created_at']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($msgs)): ?>
                    <div class="text-center text-muted my-auto"><i class="bi bi-chat d-block fs-2 mb-2 opacity-50"></i>Start the conversation!</div>
                    <?php endif; ?>
                </div>
                <!-- Input -->
                <div class="p-3" style="border-top:1px solid rgba(212,175,55,0.15);">
                    <form id="chatForm" class="d-flex gap-2">
                        <input type="text" id="msgInput" class="form-control" placeholder="Type your message..." required>
                        <button type="submit" class="btn btn-gold px-3"><i class="bi bi-send"></i></button>
                    </form>
                </div>
            </div>
            <script>
            const chatBox = document.getElementById('chatBox');
            chatBox.scrollTop = chatBox.scrollHeight;
            document.getElementById('chatForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const msg = document.getElementById('msgInput').value.trim();
                if (!msg) return;
                fetch('/online-book-resale/ajax/message_action.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: `book_id=<?= $bookId ?>&receiver_id=<?= $withUserId ?>&message=${encodeURIComponent(msg)}`
                }).then(r=>r.json()).then(d=>{
                    if (d.success) {
                        const div = document.createElement('div');
                        div.className = 'd-flex justify-content-end';
                        div.innerHTML = `<div style="max-width:70%;padding:10px 14px;border-radius:16px 16px 4px 16px;background:rgba(212,175,55,0.15);border:1px solid rgba(212,175,55,0.3)"><p class="text-white small mb-1">${msg}</p><small class="text-muted" style="font-size:0.7rem;">just now</small></div>`;
                        chatBox.appendChild(div);
                        chatBox.scrollTop = chatBox.scrollHeight;
                        document.getElementById('msgInput').value = '';
                    }
                });
            });
            </script>
            <?php else: ?>
            <div class="glass-card p-5 text-center" style="height:550px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <i class="bi bi-chat-left-text" style="font-size:5rem;color:#d4af37;opacity:0.3;"></i>
                <h5 class="text-white mt-3">Select a conversation</h5>
                <p class="text-muted">Choose a conversation from the left to view messages.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

