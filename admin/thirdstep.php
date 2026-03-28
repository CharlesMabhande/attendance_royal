<?php
session_start();
				
				if(isset($_SESSION['uid']))
				{
					echo "";					
				}
				else
				{
					header('location: ../login.php');
				}

include('../dbcon.php');
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
require_admin_permission('marks');
				
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Homepage</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
<link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
<link rel="stylesheet" href="../csss/addmark.css" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Flamenco" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.0/animate.min.css">

</head>
<body>
    <header>
      <nav>
        <div class="row clearfix">
          <a href="../index.php" class="logo" style="float:left;display:block;margin-right:12px;"><img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="64" height="64" style="display:block;"></a>
            <ul class="main-nav" animate slideInDown>
                <li><a href="../index.php">Home</a></li>
                <li><a href="aboutus.php">About Us</a></li>
                <li><a href="contactus.php">Contact Us</a></li>
            </ul>
        </div>
      </nav>
      <div class="main-content-header">
          <h2>First Step- Student Details</h2>
       
      </div>
    </header>
    
</body>
</html>
<?php
if(isset($_POST['submit']))
{
include('../dbcon.php');
    $u_class=$_POST['u_class'];
    $u_rollno=$_POST['u_rollno'];
    $u_science_technology_1=$_POST['u_science_technology_1'];
    $u_english_1=$_POST['u_english_1'];
    $math1=$_POST['u_mathematics_1'];
    $u_shona_1=$_POST['u_shona_1'];
    $u_social_science_1=$_POST['u_social_science_1'];
    $u_physical_education_arts_1=$_POST['u_physical_education_arts_1'];
    
    $u_science_technology_2=$_POST['u_science_technology_2'];
    $u_english_2=$_POST['u_english_2'];
    $u_mathematics_2=$_POST['u_mathematics_2'];
    $u_shona_2=$_POST['u_shona_2'];
    $u_social_science_2=$_POST['u_social_science_2'];
    $u_physical_education_arts_2=$_POST['u_physical_education_arts_2'];
    
    $sql="INSERT INTO `user_mark`(`u_rollno`,`u_class`,`u_mathematics_1`, `u_english_1`, `u_shona_1`, `u_social_science_1`, `u_physical_education_arts_1`, `u_science_technology_1`, `u_mathematics_2`, `u_english_2`, `u_shona_2`, `u_social_science_2`, `u_physical_education_arts_2`, `u_science_technology_2`) VALUES ('$u_rollno','$u_class','$u_mathematics_1','$u_english_1','$u_shona_1','$u_social_science_1','$u_physical_education_arts_1','$u_science_technology_1','$u_mathematics_2','$u_english_2','$u_shona_2','$u_social_science_2','$u_physical_education_arts_2','$u_science_technology_2')";
    
    $run=mysqli_query($con,$sql);
    if($run)
    {
        ?>
        <script>
        alert('Data Inserted Succesfully');
        window.open('admindash.php?sid=<?php echo $u_rollno; ?>', '_self');
        </script>
        <?php
    }
}
?>