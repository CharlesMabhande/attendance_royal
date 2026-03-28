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

// Database connection
include('../dbcon.php');
require_once dirname(__DIR__) . '/schema_helpers.php';
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
require_addmark_permission();
if (isset($_GET['action']) && strtolower((string) $_GET['action']) === 'reports') {
    header('Location: school_reports.php');
    exit;
}

// Handle form submission
if(isset($_POST['submit']))
{
    if (admin_is_class_scoped()) {
        if (!isset($_POST['u_class']) || !admin_u_class_matches_scope((string) $_POST['u_class'])) {
            header('Location: admindash.php?denied=1');
            exit;
        }
    }
    $u_name = $_POST['u_name'];
    $u_class = $_POST['u_class'];
    $u_rollno = $_POST['u_rollno'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    
    // Check if student already exists for this term/year
    $sql = "SELECT * FROM `user_mark` WHERE `u_rollno` = '$u_rollno' AND `u_class` = '$u_class' AND `term` = '$term' AND `year` = '$year'";
    $run = mysqli_query($con, $sql);
    
    if(mysqli_num_rows($run) > 0)
    {
        ?>
        <script>
            alert('Student already exists with this Roll No for Term <?php echo $term; ?>, <?php echo $year; ?>!');
            window.open('addmark.php','_self');
        </script>
        <?php
    }
    else
    {
        // Insert new student
        if (rfjs_user_mark_has_published_column($con)) {
            $sql = "INSERT INTO `user_mark` (`u_name`, `u_rollno`, `u_class`, `term`, `year`, `u_mathematics_1`, `u_mathematics_2`, `u_english_1`, `u_english_2`, `u_shona_1`, `u_shona_2`, `u_social_science_1`, `u_social_science_2`, `u_physical_education_arts_1`, `u_physical_education_arts_2`, `u_science_technology_1`, `u_science_technology_2`, `u_total`, `u_position`, `u_image`, `published`) 
                VALUES ('$u_name', '$u_rollno', '$u_class', '$term', '$year', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'default.jpg', 0)";
        } else {
            $sql = "INSERT INTO `user_mark` (`u_name`, `u_rollno`, `u_class`, `term`, `year`, `u_mathematics_1`, `u_mathematics_2`, `u_english_1`, `u_english_2`, `u_shona_1`, `u_shona_2`, `u_social_science_1`, `u_social_science_2`, `u_physical_education_arts_1`, `u_physical_education_arts_2`, `u_science_technology_1`, `u_science_technology_2`, `u_total`, `u_position`, `u_image`) 
                VALUES ('$u_name', '$u_rollno', '$u_class', '$term', '$year', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'default.jpg')";
        }
        
        $run = mysqli_query($con, $sql);
        
        if($run)
        {
            ?>
            <script>
                alert('Student Added Successfully for Term <?php echo $term; ?>, <?php echo $year; ?>!');
                window.open('addmark.php','_self');
            </script>
            <?php
        }
        else
        {
            ?>
            <script>
                alert('Error Adding Student!');
                window.open('addmark.php','_self');
            </script>
            <?php
        }
    }
}

// Handle marks update
if(isset($_POST['update_marks']))
{
    admin_require_user_mark_row_in_scope($con, (int) $_POST['id']);
    $id = $_POST['id'];
    $u_class = $_POST['u_class'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    
    // Get all marks
    $u_mathematics_1 = $_POST['u_mathematics_1'];
    $u_mathematics_2 = $_POST['u_mathematics_2'];
    $u_english_1 = $_POST['u_english_1'];
    $u_english_2 = $_POST['u_english_2'];
    $u_shona_1 = $_POST['u_shona_1'];
    $u_shona_2 = $_POST['u_shona_2'];
    $u_social_science_1 = $_POST['u_social_science_1'];
    $u_social_science_2 = $_POST['u_social_science_2'];
    $u_physical_education_arts_1 = $_POST['u_physical_education_arts_1'];
    $u_physical_education_arts_2 = $_POST['u_physical_education_arts_2'];
    $u_science_technology_1 = $_POST['u_science_technology_1'];
    $u_science_technology_2 = $_POST['u_science_technology_2'];
    
    // Calculate total
    $u_total = $u_mathematics_1 + $u_mathematics_2 + $u_english_1 + $u_english_2 + 
               $u_shona_1 + $u_shona_2 + $u_social_science_1 + $u_social_science_2 + 
               $u_physical_education_arts_1 + $u_physical_education_arts_2 + 
               $u_science_technology_1 + $u_science_technology_2;
    
    // Update marks
    $sql = "UPDATE `user_mark` SET 
            `u_mathematics_1` = '$u_mathematics_1',
            `u_mathematics_2` = '$u_mathematics_2',
            `u_english_1` = '$u_english_1',
            `u_english_2` = '$u_english_2',
            `u_shona_1` = '$u_shona_1',
            `u_shona_2` = '$u_shona_2',
            `u_social_science_1` = '$u_social_science_1',
            `u_social_science_2` = '$u_social_science_2',
            `u_physical_education_arts_1` = '$u_physical_education_arts_1',
            `u_physical_education_arts_2` = '$u_physical_education_arts_2',
            `u_science_technology_1` = '$u_science_technology_1',
            `u_science_technology_2` = '$u_science_technology_2',
            `u_total` = '$u_total'
            WHERE `id` = '$id'";
    
    $run = mysqli_query($con, $sql);
    
    if($run)
    {
        // Update positions for the class/term/year
        updatePositions($con, $u_class, $term, $year);
        ?>
        <script>
            alert('Marks Updated Successfully!');
            window.open('addmark.php','_self');
        </script>
        <?php
    }
    else
    {
        ?>
        <script>
            alert('Error Updating Marks!');
            window.open('addmark.php','_self');
        </script>
        <?php
    }
}

// Function to update class positions for specific term/year
function updatePositions($con, $class, $term, $year)
{
    $sql = "SET @position = 0;
            UPDATE `user_mark` 
            SET `u_position` = (@position := @position + 1) 
            WHERE `u_class` = '$class' AND `term` = '$term' AND `year` = '$year'
            ORDER BY `u_total` DESC";
    
    mysqli_multi_query($con, $sql);
}

// Publish / unpublish on public portal (super administrators only)
if (isset($_POST['toggle_publish_row'])) {
    require_full_admin_role();
    if (!rfjs_user_mark_has_published_column($con)) {
        header('Location: addmark.php');
        exit;
    }
    admin_require_user_mark_row_in_scope($con, (int) $_POST['id']);
    $id = (int) $_POST['id'];
    $to = isset($_POST['publish_to']) ? (int) $_POST['publish_to'] : 0;
    $to = $to === 1 ? 1 : 0;
    $st = $con->prepare('UPDATE `user_mark` SET `published` = ? WHERE `id` = ?');
    if ($st) {
        $st->bind_param('ii', $to, $id);
        $st->execute();
        $st->close();
    }
    $rq = isset($_POST['return_query']) ? (string) $_POST['return_query'] : '';
    header('Location: addmark.php' . ($rq !== '' ? '?' . $rq : ''));
    exit;
}

// Handle student deletion
if(isset($_POST['delete_student']))
{
    admin_require_user_mark_row_in_scope($con, (int) $_POST['id']);
    $id = $_POST['id'];
    
    $sql = "DELETE FROM `user_mark` WHERE `id` = '$id'";
    $run = mysqli_query($con, $sql);
    
    if($run)
    {
        ?>
        <script>
            alert('Student Deleted Successfully!');
            window.open('addmark.php','_self');
        </script>
        <?php
    }
    else
    {
        ?>
        <script>
            alert('Error Deleting Student!');
            window.open('addmark.php','_self');
        </script>
        <?php
    }
}

// Get class filter and search term (grade-locked accounts: only their class)
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$scoped = admin_class_scope();
if ($scoped !== null) {
    $class_filter = $scoped;
}
$term_filter = isset($_GET['term']) ? $_GET['term'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get students list with filters
$where_conditions = [];
if($class_filter != '') {
    $where_conditions[] = "`u_class` = '$class_filter'";
}
if($term_filter != '') {
    $where_conditions[] = "`term` = '$term_filter'";
}
if($year_filter != '') {
    $where_conditions[] = "`year` = '$year_filter'";
}
if($search_term != '') {
    $where_conditions[] = "`u_name` LIKE '%$search_term%'";
}

if(count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(' AND ', $where_conditions);
    $sql = "SELECT * FROM `user_mark` $where_clause ORDER BY `year` DESC, `term` DESC, `u_class` ASC, `u_position` ASC";
} else {
    $sql = "SELECT * FROM `user_mark` ORDER BY `year` DESC, `term` DESC, `u_class` ASC, `u_position` ASC";
}

$run = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Student Marks - RFJS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.0/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Flamenco', Georgia, serif;
            background: var(--rfjs-bg-page);
            min-height: 100vh;
            min-height: 100dvh;
            color: #333;
            overflow-x: auto;
        }
        
        /* Navigation */
        .main-nav {
            list-style: none;
            display: flex;
            justify-content: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            gap: 20px;
            flex-wrap: wrap;
            border-radius: 15px;
            margin: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .main-nav li {
            margin: 0;
        }
        
        .main-nav a {
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            font-size: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .main-nav a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .main-content-header {
            padding: clamp(12px, 3vw, 20px);
            max-width: min(1680px, 100%);
            margin: 0 auto;
            width: 100%;
        }

        /* Horizontal scroll — show every column (same idea as RPS allstudentdata) */
        .table-scroll-wrap {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            margin: 20px 0;
        }

        .admin-mark-logo-wrap {
            text-align: center;
            margin: 8px 0 20px;
        }
        
        h2 {
            color: white;
            margin: 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            font-size: 24px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        /* Tables */
        .table1, .table4 {
            width: max-content;
            min-width: 100%;
            margin: 0;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: visible;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .table1 th, .table4 th {
            background: linear-gradient(135deg, #0d1128 0%, #c96b3d 100%);
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-size: 16px;
            font-weight: bold;
        }
        
        .table1 td, .table4 td {
            padding: 15px;
            border-bottom: 1px solid #f0d4c4;
            color: #3d2c28;
            font-weight: bold;
        }
        
        /* Form Inputs - FIXED VISIBILITY */
        .box, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #f0d4c4;
            border-radius: 8px;
            background: white;
            color: #3d2c28;
            font-size: 15px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        /* FIX: Dropdown styling for visibility */
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23c96b3d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            cursor: pointer;
        }
        
        /* FIX: Dropdown options styling - make them visible */
        .form-select option {
            background: white;
            color: #3d2c28;
            padding: 12px;
            font-weight: bold;
            font-size: 14px;
        }
        
        /* FIX: For Firefox */
        .form-select option:checked {
            background: var(--rfjs-orange-mid);
            color: white;
        }
        
        /* FIX: For Chrome/Safari */
        .form-select optgroup,
        .form-select option {
            background-color: white;
            color: #3d2c28;
        }
        
        .box:focus, .form-select:focus {
            outline: none;
            border-color: var(--rfjs-gold);
            box-shadow: 0 0 0 3px rgba(var(--rfjs-orange-rgb), 0.18);
            background: white;
        }
        
        .next_Step {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .next_Step:hover {
            background: linear-gradient(135deg, #219a52, #27ae60);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
        }
        
        /* Filter Form */
        .filter-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid #e6e6ff;
        }
        
        .filter-form h3 {
            color: var(--rfjs-text-accent);
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        /* FIXED: Filter dropdowns with visible options */
        .filter-select, .search-input {
            padding: 12px 15px;
            border: 2px solid #f0d4c4;
            border-radius: 8px;
            background: white;
            color: #3d2c28;
            font-size: 15px;
            font-weight: bold;
            min-width: 180px;
            flex: 1;
        }
        
        .search-input {
            min-width: 250px;
        }
        
        /* FIX: Ensure filter dropdown options are visible */
        .filter-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23c96b3d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            cursor: pointer;
        }
        
        .filter-select option {
            background: white;
            color: #3d2c28;
            padding: 12px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }
        
        .search-btn {
            background: linear-gradient(135deg, #0d1128 0%, #c96b3d 100%);
            color: white;
        }
        
        .btn-clear {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        /* Student Cards */
        .student-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e6e6ff;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .student-header {
            background: linear-gradient(135deg, #0d1128 0%, #c96b3d 100%);
            color: white;
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .current-year {
            color: var(--rfjs-gold-mid);
            font-weight: bold;
        }
        
        /* Marks Table */
        .marks-table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
            margin: 0;
            background: white;
            border-radius: 10px;
            overflow: visible;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .marks-table th {
            background: #f0ebff;
            color: var(--rfjs-text-accent);
            padding: 15px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            border-bottom: 2px solid #e6e6ff;
        }
        
        .marks-table td {
            padding: 12px;
            border: 1px solid #e6e6ff;
            text-align: center;
        }
        
        .marks-input {
            width: 80px;
            padding: 10px;
            border: 2px solid #f0d4c4;
            border-radius: 6px;
            background: white;
            color: #3d2c28;
            text-align: center;
            font-weight: bold;
            font-size: 15px;
        }
        
        .marks-input:focus {
            outline: none;
            border-color: var(--rfjs-gold);
            box-shadow: 0 0 0 3px rgba(var(--rfjs-orange-rgb), 0.18);
        }
        
        /* Action Buttons */
        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        
        .btn-update:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.4);
        }
        
        /* Info Messages */
        .info-message {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.2));
            border: 2px solid #3498db;
            color: #3d2c28;
            padding: 18px 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: bold;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 30px;
            color: #3d2c28;
            font-size: 18px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid #e6e6ff;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-nav {
                flex-direction: column;
                align-items: center;
                gap: 12px;
                padding: 15px;
                margin: 15px;
            }
            
            .main-nav a {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }
            
            .filter-controls {
                flex-direction: column;
            }
            
            .filter-select, .search-input {
                min-width: 100%;
            }
            
            .filter-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .table1 th, .table1 td {
                padding: 12px 10px;
                font-size: 14px;
            }
            
            .marks-input {
                width: 70px;
                padding: 8px;
            }
            
            .student-header {
                font-size: 14px;
                padding: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content-header {
                padding: 15px;
            }
            
            h2 {
                font-size: 20px;
            }
            
            .marks-input {
                width: 60px;
                padding: 6px;
                font-size: 14px;
            }
            
            .btn-update, .btn-delete {
                padding: 10px 20px;
                font-size: 14px;
            }
            
            .next_Step {
                padding: 12px 25px;
                font-size: 15px;
            }
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white !important;
            }
            
            .main-nav, .filter-form, .btn, .btn-update, .btn-delete, .next_Step {
                display: none !important;
            }
            
            .student-card {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            .table1, .marks-table {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="row clearfix">
                <ul class="main-nav" animate slideInDown>
                    <li class="logout"><a href="admindash.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="aboutus.php"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="contactus.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
        </nav>
        <div class="main-content-header">
            <div class="admin-mark-logo-wrap">
                <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="120" height="120" style="display:block;margin:0 auto 12px;">
            </div>
            <?php if (admin_is_class_scoped()): ?>
                <p style="text-align:center; color: var(--rfjs-text-accent-strong, #1a2a52); font-weight:600; margin: 0 0 16px;">
                    <i class="fas fa-lock"></i> Your account is limited to <strong><?php echo htmlspecialchars(admin_class_scope_label()); ?></strong> students only.
                </p>
            <?php endif; ?>
            
            <!-- Add New Student Form -->
            <h2><i class="fas fa-user-plus"></i> Step 1/2 : Add New Student</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="table-scroll-wrap">
                <table class="table1">
                    <tr>
                        <th>Full Name</th>
                        <th>Grade</th>
                        <th>Admission Number</th>
                        <th>Term</th>
                        <th>Year</th>
                    </tr>
                    <tr>
                        <td><input type='text' name='u_name' placeholder='Enter Full Name' required class="box"/></td>
                        <td>
                            <?php if ($scoped !== null): ?>
                                <input type="hidden" name="u_class" value="<?php echo htmlspecialchars($scoped); ?>"/>
                                <span class="form-select" style="display:inline-block;padding:8px 12px;"><?php echo htmlspecialchars(admin_class_scope_label()); ?></span>
                            <?php else: ?>
                            <select name='u_class' required class="form-select">
                                <option value="">Select Grade</option>
                                <option value="ECD A">ECD A</option>
                                <option value="ECD B">ECD B</option>
                                <option value="1">Grade 1</option>
                                <option value="2">Grade 2</option>
                                <option value="3">Grade 3</option>
                                <option value="4">Grade 4</option>
                                <option value="5">Grade 5</option>
                                <option value="6">Grade 6</option>
                                <option value="7">Grade 7</option>
                            </select>
                            <?php endif; ?>
                        </td>
                        <td><input type='number' name='u_rollno' placeholder='Admission Number' required class="box"/></td>
                        <td>
                            <select name='term' required class="form-select">
                                <option value="">Select Term</option>
                                <option value="1">Term 1</option>
                                <option value="2">Term 2</option>
                                <option value="3">Term 3</option>
                            </select>
                        </td>
                        <td><input type='number' name='year' placeholder='Year' required class="box" min="2020" max="2030" value="<?php echo date('Y'); ?>"/></td>
                    </tr>
                </table>
                <table class="table4">
                    <tr>
                        <td align="center" colspan="5">
                            <input type="submit" name="submit" value="Add Student" class="next_Step" style="width: auto; padding: 12px 30px; min-width: 200px;"/>
                        </td>  
                    </tr>
                </table>
                </div>
            </form>

            <!-- Filter Students -->
            <div class="filter-form">
                <h3><i class="fas fa-filter"></i> Filter Students</h3>
                <form method="GET">
                    <div class="filter-controls">
                        <!-- Class Filter -->
                        <?php if ($scoped !== null): ?>
                            <input type="hidden" name="class" value="<?php echo htmlspecialchars($scoped); ?>"/>
                            <span class="filter-select" style="display:inline-block;padding:10px 14px;background:rgba(255,255,255,0.9);border-radius:8px;">
                                <?php echo htmlspecialchars(admin_class_scope_label()); ?> only
                            </span>
                        <?php else: ?>
                        <select name="class" class="filter-select">
                            <option value="">All Grades</option>
                            <option value="ECD A" <?php echo ($class_filter == 'ECD A') ? 'selected' : ''; ?>>ECD A</option>
                            <option value="ECD B" <?php echo ($class_filter == 'ECD B') ? 'selected' : ''; ?>>ECD B</option>
                            <option value="1" <?php echo ($class_filter == '1') ? 'selected' : ''; ?>>Grade 1</option>
                            <option value="2" <?php echo ($class_filter == '2') ? 'selected' : ''; ?>>Grade 2</option>
                            <option value="3" <?php echo ($class_filter == '3') ? 'selected' : ''; ?>>Grade 3</option>
                            <option value="4" <?php echo ($class_filter == '4') ? 'selected' : ''; ?>>Grade 4</option>
                            <option value="5" <?php echo ($class_filter == '5') ? 'selected' : ''; ?>>Grade 5</option>
                            <option value="6" <?php echo ($class_filter == '6') ? 'selected' : ''; ?>>Grade 6</option>
                            <option value="7" <?php echo ($class_filter == '7') ? 'selected' : ''; ?>>Grade 7</option>
                        </select>
                        <?php endif; ?>
                        
                        <!-- Term Filter -->
                        <select name="term" class="filter-select">
                            <option value="">All Terms</option>
                            <option value="1" <?php echo ($term_filter == '1') ? 'selected' : ''; ?>>Term 1</option>
                            <option value="2" <?php echo ($term_filter == '2') ? 'selected' : ''; ?>>Term 2</option>
                            <option value="3" <?php echo ($term_filter == '3') ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                        
                        <!-- Year Filter -->
                        <select name="year" class="filter-select">
                            <option value="">All Years</option>
                            <?php
                            $current_year = date('Y');
                            for($y = $current_year; $y >= 2020; $y--) {
                                $selected = ($year_filter == $y) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>
                        </select>
                        
                        <!-- Name Search -->
                        <input type="text" name="search" class="search-input" placeholder="Search by student name" value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn search-btn">
                            <i class="fas fa-search"></i> Search Students
                        </button>
                        <?php if($class_filter != '' || $term_filter != '' || $year_filter != '' || $search_term != ''): ?>
                            <a href="addmark.php" class="btn btn-clear">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Student List and Marks Management -->
            <h2><i class="fas fa-edit"></i> Step 2/2 : Manage Student Marks</h2>
            
            <?php 
            if(mysqli_num_rows($run) > 0)
            {
                $has_pub_col = rfjs_user_mark_has_published_column($con);
                // Show filter info if active
                if($class_filter != '' || $term_filter != '' || $year_filter != '' || $search_term != '') {
                    echo "<div class='info-message'>";
                    echo "<i class='fas fa-info-circle'></i> Showing ";
                    $filters = [];
                    if($class_filter != '') $filters[] = rfjs_filter_grade_caption($class_filter);
                    if($term_filter != '') $filters[] = "Term $term_filter";
                    if($year_filter != '') $filters[] = "Year $year_filter";
                    if($search_term != '') $filters[] = "Name containing: '$search_term'";
                    echo implode(', ', $filters);
                    echo " <a href='addmark.php' style='color: var(--rfjs-text-accent); margin-left: 10px; text-decoration: none; font-weight: bold;'><i class='fas fa-times'></i> Clear</a>";
                    echo "</div>";
                }
                
                while($data = mysqli_fetch_assoc($run))
                {
                    $row_pub = ($has_pub_col && isset($data['published'])) ? (int) $data['published'] : 0;
                    ?>
                    <div class="student-card">
                        <div class="student-header">
                            <i class="fas fa-user-graduate"></i> 
                            <strong><?php echo $data['u_name']; ?></strong> 
                            - Admission: <?php echo $data['u_rollno']; ?> 
                            - Grade: <?php echo $data['u_class']; ?> 
                            - Term: <?php echo $data['term']; ?> 
                            - Year: <span class="current-year"><?php echo $data['year']; ?></span>
                            - Position: <?php echo $data['u_position']; ?> 
                            - Total Marks: <strong><?php echo $data['u_total']; ?></strong>
                            <?php if ($has_pub_col): ?>
                            <?php if ($row_pub === 1): ?>
                                <span style="margin-left:8px;padding:2px 8px;border-radius:6px;background:#d4edda;color:#155724;font-size:0.85rem;font-weight:700;">Public</span>
                            <?php else: ?>
                                <span style="margin-left:8px;padding:2px 8px;border-radius:6px;background:#f8d7da;color:#721c24;font-size:0.85rem;font-weight:700;">Not on portal</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($has_pub_col && admin_current_role() === 'full'): ?>
                        <form method="post" style="margin:10px 0 14px;padding:10px;background:rgba(26,42,82,0.06);border-radius:8px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <input type="hidden" name="id" value="<?php echo (int) $data['id']; ?>">
                            <input type="hidden" name="return_query" value="<?php echo htmlspecialchars(http_build_query($_GET)); ?>">
                            <input type="hidden" name="publish_to" value="<?php echo $row_pub === 1 ? '0' : '1'; ?>">
                            <span style="font-size:0.9rem;color:#444;"><i class="fas fa-globe"></i> Public results portal:</span>
                            <?php if ($row_pub === 1): ?>
                                <button type="submit" name="toggle_publish_row" value="1" class="btn-delete" style="padding:8px 16px;font-size:14px;">Unpublish</button>
                            <?php else: ?>
                                <button type="submit" name="toggle_publish_row" value="1" class="btn-update" style="padding:8px 16px;font-size:14px;">Publish</button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                            <input type="hidden" name="u_class" value="<?php echo $data['u_class']; ?>">
                            <input type="hidden" name="term" value="<?php echo $data['term']; ?>">
                            <input type="hidden" name="year" value="<?php echo $data['year']; ?>">
                            
                            <!-- Marks Input Table -->
                            <div class="table-scroll-wrap">
                            <table class="marks-table">
                                <tr>
                                    <th>Mathematics 1</th>
                                    <th>Mathematics 2</th>
                                    <th>English 1</th>
                                    <th>English 2</th>
                                    <th>Shona 1</th>
                                    <th>Shona 2</th>
                                </tr>
                                <tr>
                                    <td><input type="number" name="u_mathematics_1" value="<?php echo $data['u_mathematics_1']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_mathematics_2" value="<?php echo $data['u_mathematics_2']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_english_1" value="<?php echo $data['u_english_1']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_english_2" value="<?php echo $data['u_english_2']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_shona_1" value="<?php echo $data['u_shona_1']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_shona_2" value="<?php echo $data['u_shona_2']; ?>" min="0" max="100" class="marks-input"></td>
                                </tr>
                                <tr>
                                    <th>Social Science 1</th>
                                    <th>Social Science 2</th>
                                    <th>Physical Education 1</th>
                                    <th>Physical Education 2</th>
                                    <th>Science & Technology 1</th>
                                    <th>Science & Technology 2</th>
                                </tr>
                                <tr>
                                    <td><input type="number" name="u_social_science_1" value="<?php echo $data['u_social_science_1']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_social_science_2" value="<?php echo $data['u_social_science_2']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_physical_education_arts_1" value="<?php echo $data['u_physical_education_arts_1']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_physical_education_arts_2" value="<?php echo $data['u_physical_education_arts_2']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_science_technology_1" value="<?php echo $data['u_science_technology_1']; ?>" min="0" max="100" class="marks-input"></td>
                                    <td><input type="number" name="u_science_technology_2" value="<?php echo $data['u_science_technology_2']; ?>" min="0" max="100" class="marks-input"></td>
                                </tr>
                            </table>
                            </div>

                            <div class="action-buttons">
                                <input type="submit" name="update_marks" value="Update Marks" class="btn-update">
                                <input type="submit" name="delete_student" value="Delete Student" class="btn-delete" onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.')">
                            </div>
                        </form>
                    </div>
                    <?php
                }
            }
            else
            {
                echo "<div class='no-results'>";
                echo "<i class='fas fa-user-graduate' style='font-size: 60px; margin-bottom: 20px; color: var(--rfjs-gold);'></i><br>";
                if($class_filter != '' || $term_filter != '' || $year_filter != '' || $search_term != '') {
                    echo "<h3 style='color: var(--rfjs-text-accent); margin-bottom: 15px;'>No Students Found</h3>";
                    echo "<p style='color: #3d2c28;'>No students match your search criteria. Try adjusting your filters or search term.</p>";
                } else {
                    echo "<h3 style='color: var(--rfjs-text-accent); margin-bottom: 15px;'>No Students in Database</h3>";
                    echo "<p style='color: #3d2c28;'>The student database is currently empty. Add students using the form above.</p>";
                }
                echo "</div>";
            }
            ?>
        </div>
    </header>
    
    <script>
        // Add interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus first input
            const firstInput = document.querySelector('input[name="u_name"]');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Add visual feedback for form inputs
            const inputs = document.querySelectorAll('.box, .form-select, .marks-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.transform = 'scale(1.02)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Highlight table rows on hover
            const studentCards = document.querySelectorAll('.student-card');
            studentCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.transition = 'transform 0.3s ease';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl + S to submit add student form
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    const submitBtn = document.querySelector('input[name="submit"]');
                    if (submitBtn) {
                        submitBtn.click();
                    }
                }
                
                // Ctrl + F to focus search
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    const searchInput = document.querySelector('.search-input');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
            });
            
            // Add auto-calculate total marks
            const marksInputs = document.querySelectorAll('.marks-input');
            marksInputs.forEach(input => {
                input.addEventListener('input', function() {
                    // Find the parent form
                    const form = this.closest('form');
                    if (form) {
                        const marks = form.querySelectorAll('.marks-input');
                        let total = 0;
                        marks.forEach(mark => {
                            const value = parseFloat(mark.value) || 0;
                            total += value;
                        });
                        
                        // Find and update the total display in student header
                        const totalSpan = form.querySelector('.student-header strong:last-child');
                        if (totalSpan) {
                            const originalText = totalSpan.textContent;
                            const newText = originalText.replace(/\d+/, total);
                            totalSpan.textContent = newText;
                        }
                    }
                });
            });
            
            // Prevent form submission on Enter in search fields
            const searchFields = document.querySelectorAll('.search-input, .filter-select');
            searchFields.forEach(field => {
                field.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const searchForm = this.closest('form');
                        if (searchForm) {
                            searchForm.submit();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>