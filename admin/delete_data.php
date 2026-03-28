<?php
session_start();
if (!isset($_SESSION['uid'])) {
    header('location: ../login.php');
    exit;
}
include('../dbcon.php');
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
require_admin_permission('delete_marks');
admin_require_rollno_in_scope($con, (string) $_REQUEST['sid']);

    $rollno=$_REQUEST['sid']; 
    
    $sql1="DELETE FROM `user_mark` WHERE `u_rollno`='$rollno';";

    $sql2="DELETE FROM `student_data` WHERE `u_rollno`='$rollno';";
    $run=mysqli_query($con,$sql1);

    $run=mysqli_query($con,$sql2);
    if($run==true)
    {
        ?>
        <script>
        alert('Data mark Succesfully');
        window.open('deleteform.php?sid=<?php echo $rollno; ?>', '_self');
        </script>
        <?php
    }

?>
