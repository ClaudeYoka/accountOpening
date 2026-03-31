<div class="user-notification">
    <div class="dropdown">
        <a class="dropdown-toggle no-arrow" href="#" role="button" data-toggle="dropdown">
            <i class="icon-copy dw dw-notification"></i>
            <?php
                $empid = $session_id;
                $count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM tblnotification WHERE emp_id = '$empid' AND type= 'chequier_request' AND is_read = 0");
                $count_result = mysqli_fetch_assoc($count_query);
                $notification_count = $count_result['count'];
            ?>
            <span class="notification-active" id="notification-count"><?php echo $notification_count; ?></span>
        </a>

        <div class="dropdown-menu dropdown-menu-right">
            <div class="notification-header">
                <h6>Notifications</h6>
                <a href="clear_notifications.php" class="clear-all">Tout supprimer</a>
            </div>
            <div class="notification-list mx-h-350 customscroll">
                <ul id="notification-list">
                    <?php
                        $notification_query = mysqli_query($conn, "SELECT * FROM tblnotification WHERE emp_id = '$empid'
                                                                    AND type = 'chequier_request' AND is_read = 0  ORDER BY created_at DESC LIMIT 10");
                        while ($notification = mysqli_fetch_assoc($notification_query)) {
                            $msg = $notification['message'];
                            // $short = (strlen($msg) > 80) ? substr($msg,0,77) . '...' : $msg;
                            $time = date('d/m H:i', strtotime($notification['created_at']));
                    ?>
                    <li id="notif-<?php echo $notification['id']; ?>">
                        <a href="notification_details?id=<?php echo $notification['id']; ?>" onclick="markAsRead(<?php echo $notification['id']; ?>, this); return false;">
                            <div class="notification-icon"><i class="dw dw-mail-3" style="font-size:18px;color:#007db8"></i></div>
                            <div class="notification-content">
                                <h3><?php echo h($msg); ?></h3>
                                <span><?php echo h($time); ?></span>
                            </div>
                            <div class="delete-notification" title="Marquer lu">&times;</div>
                        </a>
                    </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function markAsRead(id, el) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            }).then(r => r.json()).then(resp => {
                if (resp.status === 'success') {
                    // remove item from list
                    var li = document.getElementById('notif-' + id);
                    if (li) li.parentNode.removeChild(li);
                    // decrement count
                    var countEl = document.getElementById('notification-count');
                    if (countEl) {
                        var c = parseInt(countEl.textContent) || 0;
                        c = Math.max(0, c - 1);
                        countEl.textContent = c;
                    }
                    // navigate to details if element provided
                    if (el && el.href) {
                        window.location = el.href;
                    }
                } else {
                    console.error('Mark read failed', resp);
                    if (el && el.href) window.location = el.href;
                }
            }).catch(err => {
                console.error(err);
                if (el && el.href) window.location = el.href;
            });
        }
    </script>
</div>


