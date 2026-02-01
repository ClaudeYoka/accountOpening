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
                                                                    AND type = 'chequier_request' AND is_read = 0  ORDER BY created_at DESC");
                        while ($notification = mysqli_fetch_assoc($notification_query)) {
                    ?>
                    <li>
                        <a href="notification_details?id=<?php echo $notification['id']; ?>" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                            <div class="notification-content">
                                <h3><?php echo h($notification['message']); ?></h3>
                                <span><?php echo h($notification['created_at']); ?></span>
                            </div>
                        </a>
                    </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</div>


