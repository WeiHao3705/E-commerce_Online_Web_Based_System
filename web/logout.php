<?php
session_start();
// Redirect to controller logout to handle cleanup
header('Location: controller/MemberController.php?action=logout');
exit;
