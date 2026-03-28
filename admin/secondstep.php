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
    <title>Homepage</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
<link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
<link rel="stylesheet" href="../csss/secondstep.css" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Flamenco" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.0/animate.min.css">

</head>
<body>
    <header>
      <nav>
        <div class="row clearfix">
            <a href="../index.php" style="float:left;display:block;margin:4px 14px 8px 0;"><img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="64" height="64" style="display:block;"></a>
            <ul class="main-nav" animate slideInDown>
                <li><a href="../index.php">Home</a></li>
                <li><a href="aboutus.php">About Us</a></li>
                <li><a href="contactus.php">Contact Us</a></li>
            </ul>
        </div>
      </nav>
      <div class="main-content-header">
          
          <form method="post" action="thirdstep.php">
              <h2>Step 2/2 : Add Exam mark</h2>
         
          <td><input type="hidden" name="class" class="class" value="<?php  echo $_POST['class']; ?>" required/></td>
          
          <td><input type="hidden" name="rollno" class="rollno" value="<?php  echo $_POST['rollno']; ?>" required/></td>
          
              
              
          <table class="table1">
              <span> <h4 class="h_3">PAPER 1</h4></span>
             <tr>
                <th></th>SCIENCE & TECHY<th> ENGLISH </th> <th>MATHEMATICS</th>
             </tr>
             <tr>
                 <td><input type='text' name='u_science_technology_1' placeholder='ENTER MARK' required/></td>
                 <td><input type='text' name='u_english_1' placeholder='ENTER MARK' required/></td>
                 <td><input type='text' name='u_mathematics_1' placeholder='ENTER MARK' required/></td>
             </tr>
             </table>
             <table class="table2">
             <tr>
                 <th>SHONA</th>   <th>SOCIAL SCIENCE</th> <th>P E & ARTS</th> 
             </tr>
                 <tr>
                 
                 <td><input type='text' name='u_shona_1' placeholder='ENTER MARK' required/></td>
                 <td><input type='text' name='u_social_science_1' placeholder='ENTER MARK' required/></td>
                 <td><input type='text' name='u_physical_education_arts_1' placeholder='ENTER MARK' required/></td>
             </tr>
             
         </table>
          <span> <h4 class="h3">PAPER 2</h4> </span>
         <table class="table4">
             <tr>
                <th></th>SCIENCE & TECH<th> ENGLISH </th> <th>MATHEMATICS</th>
             </tr>
             <tr>
                 <td><input type='text' name='u_science_technology_2' placeholder='ENTER MARK' required/></td>
                 <td><input type='text' name='u_english_2' placeholder='ENTER MARK' required/></td>
                 <td><input type='text' name='u_mathematics_2' placeholder='ENTER MARK' required/></td>
             </tr>
             </table>
             <table class="table2">
             <tr>
                 <th>SHONA</th>   <th>SOCIAL SCIENCE</th> <th>P E & ARTS</th> 
             </tr>
             <tr>
                 <td><input type='text' name='u_shona_2' placeholder='ENTER MARK' required/></td>
                 <td><input type='text' name='u_social_science_2' placeholder='ENTER MARK' required/></td>
                 <td><input type='text' name='u_physical_education_arts_2' placeholder='ENTER MARK' required/></td>
             </tr>
             <tr>
             <td align="center" colspan="2"><input type="submit" name="submit" value="Submit" class="submit"/></td>   
             </tr>
             
         </table>
         
       
       </form>
      </div>
    </header>
    
</body>
</html>
<?php
if(isset($_POST['submit1']))
{ 
    include('../dbcon.php');
    $u_name=$_POST['u_name'];
    $u_class=$_POST['u_class'];
    $u_rollno=$_POST['u_rollno'];
    $u_father=$_POST['u_father'];
    $u_mother=$_POST['u_mother'];
    $u_mobile=$_POST['u_mobile'];
    $u_village=$_POST['u_village'];
    
    $u_image=$_FILES['u_image']['u_name'];
    $tempname=$_FILES['u_image']['tmp_name'];
    move_uploaded_file($tempname,"../dataimg/$u_image");
    
    $sql="INSERT INTO `Student_data`(`u_name`, `u_class`, `u_rollno`, `u_father`, `u_mother`, `u_mobile`, `u_village`, `u_image`) VALUES ('$u_name','$u_class','$u_rollno','$u_father','$u_mother','$u_mobile','$u_village','$u_image')";
    $run=mysqli_query($con,$sql);
    if($run)
    {
        ?>
        <script>
        alert('1step Complete and this is second  Step');
        </script>
        <?php
    }
    else
    {
       ?>
        <script>
        alert('Failed');
        </script>
        <?php 
    }
}

?>
