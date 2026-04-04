<?php
    include('includes/config.php');
?>

<ul>
    <?php 
    $receiver = $_GET['receive'];
    $sender   = $_GET['send'];
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_message LEFT JOIN tblemployees ON tblemployees.emp_id = tbl_message.outgoing_msg_id 
    WHERE (incoming_msg_id=? AND outgoing_msg_id=?) OR (outgoing_msg_id=? AND incoming_msg_id=?) ORDER BY msg_id ASC");
    mysqli_stmt_bind_param($stmt, "ssss", $receiver, $sender, $receiver, $sender);
    mysqli_stmt_execute($stmt);
    $query = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_array($query)){ 
    $id = $row['emp_id'];
    if($sender == $id){
    ?> 
    $id = $row['emp_id'];
    if($sender == $id){
    ?>
    <li class="clearfix admin_chat">
    <span class="chat-img">
    <img src="<?php echo (!empty($row['location'])) ? '../uploads/'.$row['location'] : '../uploads/NO-IMAGE-AVAILABLE.jpg'; ?>" alt="">
    </span>
    <div class="chat-body clearfix">
    <p><?php echo htmlspecialchars($row['text_message'], ENT_QUOTES, 'UTF-8'); ?></p>
    <div class="chat_time"><?php echo htmlspecialchars($row['curr_date'] . $row['curr_time'], ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    </li>
    <?php }else{ ?>

    <li class="clearfix">
    <span class="chat-img">
        <img src="<?php echo (!empty($row['location'])) ? '../uploads/'.$row['location'] : '../uploads/NO-IMAGE-AVAILABLE.jpg'; ?>" alt="">
    </span>
    <div class="chat-body clearfix">
        <p><?php echo htmlspecialchars($row['text_message'], ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="chat_time"><?php echo htmlspecialchars($row['curr_date'] . $row['curr_time'], ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    </li>

    <?php } ?>
    <?php } ?>

</ul>