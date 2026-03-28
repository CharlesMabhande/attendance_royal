<?php
session_start();

if (isset($_SESSION['uid'])) {
    echo "";
} else {
    header('location: ../login.php');
}

// Database connection
include('../dbcon.php');
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
require_admin_permission('records');

$rfjs_logo_pdf_b64 = '';
$rfjs_logo_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'logo-rfjs.png';
if (is_readable($rfjs_logo_path)) {
    $rfjs_logo_pdf_b64 = base64_encode(file_get_contents($rfjs_logo_path));
}

// Initialize search query
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = mysqli_real_escape_string($con, $_GET['search']);
}

// Initialize filters (grade-locked accounts: only their class)
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$scoped = admin_class_scope();
if ($scoped !== null) {
    $class_filter = $scoped;
}
$term_filter = isset($_GET['term']) ? $_GET['term'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';

// Build query with filters
$where_conditions = [];
if (!empty($searchQuery)) {
    $where_conditions[] = "`u_name` LIKE '%$searchQuery%'";
}
if (!empty($class_filter)) {
    $where_conditions[] = "`u_class` = '$class_filter'";
}
if (!empty($term_filter)) {
    $where_conditions[] = "`term` = '$term_filter'";
}
if (!empty($year_filter)) {
    $where_conditions[] = "`year` = '$year_filter'";
}

if (count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(' AND ', $where_conditions);
    $sql = "SELECT * FROM `user_mark` $where_clause ORDER BY `year` DESC, `term` DESC, `u_class` ASC, `u_position` ASC";
} else {
    $sql = "SELECT * FROM `user_mark` ORDER BY `year` DESC, `term` DESC, `u_class` ASC, `u_position` ASC";
}

$run = mysqli_query($con, $sql);
?>

<html>
<head>
    <title>Student Records - RFJS</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="../csss/allstudentdata.css" type="text/css">
    <link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.0/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Flamenco', Georgia, serif;
            background: var(--rfjs-bg-page);
            min-height: 100vh;
            color: #333;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        header {
            padding: 20px;
        }
        
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
            padding: 20px;
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: white;
            font-size: 32px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Filter Form */
        .filter-form {
            background: rgba(255, 255, 255, 0.15);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            color: white;
            font-weight: bold;
            font-size: 15px;
        }
        
        .filter-input, .filter-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .filter-select option {
            background: #0d1128;
            color: white;
            padding: 10px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
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
        
        .btn-search {
            background: var(--rfjs-gradient-btn);
            color: white;
        }
        
        .btn-clear {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            text-decoration: none;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        /* Table Container — horizontal scroll so all columns stay readable */
        .table-container {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            max-width: 100%;
        }
        
        .data-table {
            width: max-content;
            min-width: 100%;
            table-layout: auto;
            border-collapse: collapse;
            background: #fff9f5;
        }
        
        .data-table th {
            background: linear-gradient(135deg, #0d1128 0%, #a85a32 100%);
            color: #faf8f5;
            padding: 18px 15px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        
        .data-table td {
            padding: 15px 12px;
            text-align: center;
            border: 1px solid #f0d4c4;
            color: #3d2c28;
            font-weight: bold;
            background: #fff8f4;
            font-size: 14px;
        }
        
        .data-table tr:nth-child(even) td {
            background: #fff2eb;
        }
        
        .data-table tr:hover td {
            background: #ffe8d9;
            transition: background 0.2s ease;
        }
        
        /* Special styling for important columns */
        .data-table td:last-child {
            background: #0d1128;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        .col-name { 
            color: #2c1810;
            font-weight: bold;
            text-align: left;
            padding-left: 20px;
            min-width: 10rem;
        }
        
        .data-table thead tr:last-child th {
            min-width: 3.25rem;
        }
        
        .data-table tbody td:nth-child(n+8):nth-child(-n+19) {
            min-width: 3rem;
        }
        
        .table-scroll-hint {
            display: none;
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin: 0 0 12px 0;
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.15);
            border-radius: 8px;
        }
        
        @media (max-width: 1600px) {
            .table-scroll-hint {
                display: block;
            }
        }
        
        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 30px;
            color: white;
            font-size: 18px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            margin-top: 20px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Results Count */
        .results-count {
            text-align: center;
            margin-bottom: 20px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.15);
            padding: 15px 25px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Active Filters Display */
        .active-filters {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid var(--rfjs-orange);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .active-filters h4 {
            color: white;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-tag {
            display: inline-block;
            background: rgba(var(--rfjs-orange-rgb), 0.22);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            margin-right: 12px;
            margin-bottom: 10px;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-content-header {
                padding: 15px;
            }
            
            .table-container {
                padding: 20px;
            }
            
            .data-table th, .data-table td {
                padding: 12px 10px;
                font-size: 13px;
            }
        }
        
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
            
            .filter-form {
                padding: 20px;
            }
            
            .filter-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            h2 {
                font-size: 26px;
                padding: 15px;
            }
            
            .data-table {
                font-size: 13px;
            }
            
            .filter-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .main-content-header {
                padding: 10px;
            }
            
            .table-container {
                padding: 15px;
                margin: 10px;
            }
            
            .data-table th, .data-table td {
                padding: 10px 8px;
                font-size: 12px;
            }
            
            h2 {
                font-size: 22px;
            }
            
            .filter-tag {
                font-size: 12px;
                padding: 6px 12px;
                margin-right: 8px;
                margin-bottom: 8px;
            }
            
            .results-count {
                font-size: 16px;
                padding: 12px 20px;
            }
        }
        
        /* FIXED PRINT STYLES - Ensure full table prints across all pages */
        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }
            
            body {
                background: white !important;
                color: black !important;
                font-size: 12px !important;
                overflow: visible !important;
            }
            
            /* External CSS fixes header to 800px — must reset for multi-page print */
            header {
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                overflow: visible !important;
                background: white !important;
                background-image: none !important;
            }
            
            .main-nav, .filter-form, .filter-buttons, .btn-clear, .btn-print,
            .export-buttons, .table-scroll-hint,
            .active-filters, .results-count, .no-results .btn {
                display: none !important;
            }
            
            .table-container {
                background: white !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
                width: 100% !important;
                max-width: none !important;
            }
            
            .data-table {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                display: table !important;
                table-layout: auto !important;
                background: white !important;
                border-collapse: collapse !important;
                page-break-inside: auto !important;
            }
            
            .data-table thead {
                display: table-header-group !important;
            }
            
            .data-table tbody {
                display: table-row-group !important;
            }
            
            .data-table th {
                background: #0d1128 !important;
                color: white !important;
                border: 1px solid #ddd !important;
                padding: 6px 4px !important;
                font-size: 9px !important;
                position: static !important;
            }
            
            .data-table td {
                color: #333 !important;
                background: white !important;
                border: 1px solid #ddd !important;
                padding: 4px 3px !important;
                font-size: 9px !important;
                page-break-inside: avoid !important;
            }
            
            .data-table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .data-table tr:nth-child(even) td {
                background: #fff2eb !important;
            }
            
            h2 {
                color: black !important;
                text-shadow: none !important;
                background: white !important;
                border: none !important;
                padding: 10px 0 !important;
                margin-bottom: 15px !important;
                font-size: 20px !important;
            }
            
            .letterhead {
                display: flex !important;
                align-items: center !important;
                background: #fff !important;
                border: none !important;
                border-bottom: 3px solid #c96b3d !important;
                border-radius: 0 !important;
                padding: 12px 0 16px 0 !important;
                margin-bottom: 16px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .letterhead .rfjs-logo-img {
                max-height: 52px !important;
            }
            .letterhead-text h1 {
                color: #0d1128 !important;
                font-size: 20px !important;
                text-shadow: none !important;
            }
            .letterhead-text p {
                color: #444 !important;
                font-size: 13px !important;
            }
            .page-title-hide-print {
                display: none !important;
            }
            
            /* Force all columns to be visible */
            .data-table th,
            .data-table td {
                display: table-cell !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            /* Hide the main content header padding */
            .main-content-header {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
        
        .letterhead {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 22px;
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .letterhead .rfjs-logo-img {
            flex-shrink: 0;
        }
        .letterhead-text h1 {
            color: white;
            font-size: 1.35rem;
            margin-bottom: 4px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        .letterhead-text p {
            color: rgba(255, 255, 255, 0.92);
            font-size: 0.95rem;
            margin: 2px 0;
        }
        
        /* Export buttons container */
        .export-buttons {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="row clearfix">
                <ul class="main-nav" animate slideInDown>
                    <li><a href="../index.php"><i class="fas fa-home"></i> HOME</a></li>
                    <li><a href="aboutus.php"><i class="fas fa-info-circle"></i> ABOUT</a></li>
                    <li><a href="contactus.php"><i class="fas fa-envelope"></i> CONTACT</a></li>
                    <li class="logout"><a href="admindash.php"><i class="fas fa-tachometer-alt"></i> DASHBOARD</a></li>
                </ul>
            </div>
        </nav>
        <div class="main-content-header">
            <!-- Print Header (only shows when printing) -->
            <div class="letterhead">
                <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="letterhead-logo rfjs-logo-img rfjs-logo-crest" width="120" height="120">
                <div class="letterhead-text">
                    <h1>Royal Family Junior School</h1>
                    <p>Student Academic Records Database</p>
                    <p>Generated on: <?php echo date('F j, Y'); ?></p>
                </div>
            </div>
            
            <h2 class="page-title-hide-print"><i class="fas fa-database"></i> Student Records Database</h2>
            <?php if (admin_is_class_scoped()): ?>
                <p style="text-align:center; color: var(--rfjs-text-accent-strong, #1a2a52); font-weight:600; margin: 0 0 18px;">
                    <i class="fas fa-lock"></i> Showing <strong><?php echo htmlspecialchars(admin_class_scope_label()); ?></strong> students only.
                </p>
            <?php endif; ?>
            
            <!-- Filter Form -->
            <div class="filter-form">
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="filter-label"><i class="fas fa-search"></i> Search by Name</label>
                            <input type="text" name="search" class="filter-input" placeholder="Enter student name" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label"><i class="fas fa-graduation-cap"></i> Grade</label>
                            <?php if ($scoped !== null): ?>
                                <input type="hidden" name="class" value="<?php echo htmlspecialchars($scoped); ?>"/>
                                <span class="filter-select" style="display:inline-block;padding:10px 14px;width:100%;box-sizing:border-box;border-radius:8px;background:rgba(255,255,255,0.95);">
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
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label"><i class="fas fa-calendar-alt"></i> Term</label>
                            <select name="term" class="filter-select">
                                <option value="">All Terms</option>
                                <option value="1" <?php echo ($term_filter == '1') ? 'selected' : ''; ?>>Term 1</option>
                                <option value="2" <?php echo ($term_filter == '2') ? 'selected' : ''; ?>>Term 2</option>
                                <option value="3" <?php echo ($term_filter == '3') ? 'selected' : ''; ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label"><i class="fas fa-calendar"></i> Year</label>
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
                        </div>
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-search">
                            <i class="fas fa-search"></i> Search Records
                        </button>
                        <?php if($class_filter != '' || $term_filter != '' || $year_filter != '' || $searchQuery != ''): ?>
                            <a href="allstudentdata.php" class="btn btn-clear">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php
            // Show active filters
            $filters = [];
            if($class_filter != '') $filters[] = rfjs_filter_grade_caption($class_filter);
            if($term_filter != '') $filters[] = "Term: $term_filter";
            if($year_filter != '') $filters[] = "Year: $year_filter";
            if($searchQuery != '') $filters[] = "Name contains: '$searchQuery'";
            
            if(count($filters) > 0): ?>
                <div class="active-filters">
                    <h4><i class="fas fa-filter"></i> Active Filters:</h4>
                    <?php foreach($filters as $filter): ?>
                        <span class="filter-tag"><?php echo $filter; ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Get total results count
            $total_results = mysqli_num_rows($run);
            ?>
            
            <!-- Results Count and Export Buttons -->
            <div style="text-align: center; margin-bottom: 25px;">
                <span class="results-count">
                    <i class="fas fa-list-alt"></i> 
                    <?php echo $total_results; ?> record<?php echo $total_results != 1 ? 's' : ''; ?> found
                </span>
                
                <?php if ($total_results > 0): ?>
                    <div class="export-buttons">
                        <button onclick="printTable()" class="btn btn-print">
                            <i class="fas fa-print"></i> Print Table
                        </button>
                        <button onclick="exportToPDF()" class="btn" style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white;">
                            <i class="fas fa-file-pdf"></i> Export as PDF
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Records Table -->
            <div class="table-container">
                <?php if ($total_results > 0): ?>
                    <p class="table-scroll-hint"><i class="fas fa-arrows-alt-h"></i> Scroll sideways to view all subject columns</p>
                    <table class="data-table" id="studentRecordsTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 150px;">Student Name</th>
                                <th style="width: 80px;">Grade</th>
                                <th style="width: 100px;">Admission No</th>
                                <th style="width: 80px;">Term</th>
                                <th style="width: 80px;">Year</th>
                                <th style="width: 80px;">Position</th>
                                <th colspan="2" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">Mathematics</th>
                                <th colspan="2" style="background: linear-gradient(135deg, #3498db, #2980b9);">English</th>
                                <th colspan="2" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">Shona</th>
                                <th colspan="2" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">Social Science</th>
                                <th colspan="2" style="background: linear-gradient(135deg, #f39c12, #e67e22);">PE & Arts</th>
                                <th colspan="2" style="background: linear-gradient(135deg, #1abc9c, #16a085);">Science & Tech</th>
                                <th style="width: 100px; background: linear-gradient(135deg, #0d1128, #a85a32);">Total Marks</th>
                            </tr>
                            <tr>
                                <th colspan="7"></th>
                                <th>P1</th><th>P2</th>
                                <th>P1</th><th>P2</th>
                                <th>P1</th><th>P2</th>
                                <th>P1</th><th>P2</th>
                                <th>P1</th><th>P2</th>
                                <th>P1</th><th>P2</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($run, 0); // Reset pointer
                            while ($row = mysqli_fetch_assoc($run)): 
                            ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td class="col-name"><?php echo htmlspecialchars($row['u_name']); ?></td>
                                    <td>Grade <?php echo $row['u_class']; ?></td>
                                    <td><?php echo $row['u_rollno']; ?></td>
                                    <td>Term <?php echo $row['term']; ?></td>
                                    <td><?php echo $row['year']; ?></td>
                                    <td><?php echo $row['u_position']; ?></td>
                                    <td><?php echo $row['u_mathematics_1']; ?></td>
                                    <td><?php echo $row['u_mathematics_2']; ?></td>
                                    <td><?php echo $row['u_english_1']; ?></td>
                                    <td><?php echo $row['u_english_2']; ?></td>
                                    <td><?php echo $row['u_shona_1']; ?></td>
                                    <td><?php echo $row['u_shona_2']; ?></td>
                                    <td><?php echo $row['u_social_science_1']; ?></td>
                                    <td><?php echo $row['u_social_science_2']; ?></td>
                                    <td><?php echo $row['u_physical_education_arts_1']; ?></td>
                                    <td><?php echo $row['u_physical_education_arts_2']; ?></td>
                                    <td><?php echo $row['u_science_technology_1']; ?></td>
                                    <td><?php echo $row['u_science_technology_2']; ?></td>
                                    <td><strong><?php echo $row['u_total']; ?></strong></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-user-graduate" style="font-size: 60px; margin-bottom: 25px; color: rgba(255, 255, 255, 0.7);"></i><br>
                        <?php if($class_filter != '' || $term_filter != '' || $year_filter != '' || $searchQuery != ''): ?>
                            <h3 style="color: white; margin-bottom: 15px; font-size: 24px;">No Records Found</h3>
                            <p style="color: rgba(255, 255, 255, 0.9); font-size: 16px; max-width: 500px; margin: 0 auto 25px;">
                                No student records match your search criteria. Try adjusting your filters or search term.
                            </p>
                        <?php else: ?>
                            <h3 style="color: white; margin-bottom: 15px; font-size: 24px;">No Student Records</h3>
                            <p style="color: rgba(255, 255, 255, 0.9); font-size: 16px; max-width: 500px; margin: 0 auto 25px;">
                                The student database is currently empty. Add student records to get started.
                            </p>
                        <?php endif; ?>
                        <a href="addmark.php" class="btn btn-search" style="margin-top: 20px; padding: 15px 30px; font-size: 16px;">
                            <i class="fas fa-user-plus"></i> Add Student Records
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <?php
    $pdf_rows = [];
    if ($total_results > 0) {
        mysqli_data_seek($run, 0);
        while ($row = mysqli_fetch_assoc($run)) {
            $pdf_rows[] = [
                (string) $row['id'],
                substr((string) $row['u_name'], 0, 34),
                'Gr. ' . $row['u_class'],
                (string) $row['u_rollno'],
                'T' . $row['term'],
                (string) $row['year'],
                (string) $row['u_position'],
                (string) $row['u_mathematics_1'],
                (string) $row['u_mathematics_2'],
                (string) $row['u_english_1'],
                (string) $row['u_english_2'],
                (string) $row['u_shona_1'],
                (string) $row['u_shona_2'],
                (string) $row['u_social_science_1'],
                (string) $row['u_social_science_2'],
                (string) $row['u_physical_education_arts_1'],
                (string) $row['u_physical_education_arts_2'],
                (string) $row['u_science_technology_1'],
                (string) $row['u_science_technology_2'],
                (string) $row['u_total'],
            ];
        }
    }
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function printTable() {
            const tc = document.querySelector('.table-container');
            const dt = document.querySelector('.data-table');
            if (!tc || !dt) {
                window.print();
                return;
            }
            const originalStyles = {
                bodyOverflow: document.body.style.overflow,
                tableContainerOverflow: tc.style.overflow,
                dataTableWidth: dt.style.width
            };
            document.body.style.overflow = 'visible';
            tc.style.overflow = 'visible';
            dt.style.width = '100%';
            window.print();
            setTimeout(function () {
                document.body.style.overflow = originalStyles.bodyOverflow;
                tc.style.overflow = originalStyles.tableContainerOverflow;
                if (originalStyles.dataTableWidth) {
                    dt.style.width = originalStyles.dataTableWidth;
                }
            }, 1000);
        }
        
        function exportToPDF() {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library did not load. Check your internet connection and refresh the page.');
                return;
            }
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
            const PW = 297, PH = 210;
            const M = 10;
            const contentW = PW - 2 * M;
            const colWidths = [9, 59, 16, 14, 14, 14, 12, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 18];
            const ROW_H = 7;
            const TH_H = 8;
            const Y_BOTTOM_SAFE = 193;
            const Y_FOOTER = 198;
            const headers = [
                'ID', 'Student', 'Grade', 'Adm', 'Term', 'Year', 'Pos',
                'M1', 'M2', 'E1', 'E2', 'Sh1', 'Sh2', 'So1', 'So2', 'PE1', 'PE2', 'St1', 'St2', 'Total'
            ];
            const PURPLE = [13, 17, 40];
            const PURPLE_D = [10, 14, 32];
            const ZEBRA = [255, 245, 238];
            const HEADER_BAND_H = 30;
            const logoPdf = <?php echo $rfjs_logo_pdf_b64 ? json_encode('data:image/png;base64,' . $rfjs_logo_pdf_b64) : 'null'; ?>;
            
            function drawWatermark() {
                doc.setTextColor(242, 240, 248);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(28);
                for (let i = 0; i < 5; i++) {
                    doc.text('Royal Family Junior School', 40 + i * 65, 30 + (i % 3) * 55, { angle: 35 });
                }
            }
            
            function drawPageHeaderBand(pageNum) {
                drawWatermark();
                doc.setFillColor.apply(doc, PURPLE);
                doc.rect(M, M, contentW, HEADER_BAND_H, 'F');
                doc.setDrawColor.apply(doc, PURPLE_D);
                doc.setLineWidth(0.3);
                doc.rect(M, M, contentW, HEADER_BAND_H, 'S');
                if (logoPdf) {
                    try {
                        doc.addImage(logoPdf, 'PNG', M + 2, M + 3, 16, 16);
                    } catch (e) {}
                }
                doc.setTextColor(255, 255, 255);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(14);
                doc.text('Royal Family Junior School', M + (logoPdf ? 22 : 4), M + 11);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9.5);
                doc.text('Student Academic Records', M + (logoPdf ? 22 : 4), M + 18);
                doc.setTextColor(210, 210, 215);
                doc.setFontSize(8);
                const filterSuffix = <?php echo json_encode(count($filters) > 0 ? '  |  Filters: ' . implode(', ', $filters) : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
                let sub = 'Generated: ' + new Date().toLocaleString() + filterSuffix;
                doc.text(sub, M + (logoPdf ? 22 : 4), M + 25, { maxWidth: contentW - (logoPdf ? 26 : 8) });
                return M + HEADER_BAND_H + 6;
            }
            
            function drawColumnHeaderRow(y) {
                let x = M;
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(7);
                doc.setTextColor(255, 255, 255);
                doc.setFillColor.apply(doc, PURPLE_D);
                headers.forEach((h, i) => {
                    doc.rect(x, y, colWidths[i], TH_H, 'F');
                    doc.text(h, x + colWidths[i] / 2, y + 5.4, { align: 'center' });
                    x += colWidths[i];
                });
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(40, 40, 40);
                return y + TH_H;
            }
            
            function drawContinuedPageHeader() {
                doc.addPage();
                drawWatermark();
                const contH = 14;
                doc.setFillColor.apply(doc, PURPLE);
                doc.rect(M, M, contentW, contH, 'F');
                if (logoPdf) {
                    try {
                        doc.addImage(logoPdf, 'PNG', M + 2, M + 2, 10, 10);
                    } catch (e) {}
                }
                doc.setTextColor(255, 255, 255);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(9);
                if (logoPdf) {
                    doc.text('Royal Family Junior School — continued', M + 14, M + 8.5);
                } else {
                    doc.text('Royal Family Junior School — continued', PW / 2, M + 8.5, { align: 'center' });
                }
                return drawColumnHeaderRow(M + contH + 2);
            }
            
            let yPosition = drawPageHeaderBand(1);
            yPosition = drawColumnHeaderRow(yPosition);
            
            const tableRows = <?php echo json_encode($pdf_rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
            tableRows.forEach(function (rowData, rowIdx) {
                if (yPosition + ROW_H > Y_BOTTOM_SAFE) {
                    yPosition = drawContinuedPageHeader();
                }
                const rowBg = (rowIdx % 2 === 0) ? [255, 255, 255] : ZEBRA;
                let xPosition = M;
                rowData.forEach(function (cell, index) {
                    const w = colWidths[index];
                    const cx = xPosition + w / 2;
                    const cy = yPosition + ROW_H / 2 + 1.8;
                    doc.setFillColor.apply(doc, index === 19 ? PURPLE : rowBg);
                    doc.rect(xPosition, yPosition, w, ROW_H, 'F');
                    doc.setFontSize(index === 1 ? 6.5 : 7);
                    doc.setFont('helvetica', index === 19 ? 'bold' : 'normal');
                    doc.setTextColor(index === 19 ? 255 : 45, index === 19 ? 255 : 45, index === 19 ? 255 : 45);
                    if (index === 1) {
                        const lines = doc.splitTextToSize(String(cell), w - 3);
                        doc.text(lines, xPosition + 1.5, yPosition + 3.8);
                    } else {
                        doc.text(String(cell), cx, cy, { align: 'center' });
                    }
                    xPosition += w;
                });
                doc.setTextColor(0, 0, 0);
                doc.setFont('helvetica', 'normal');
                yPosition += ROW_H;
            });
            
            const totalPages = doc.internal.getNumberOfPages();
            for (let p = 1; p <= totalPages; p++) {
                doc.setPage(p);
                doc.setDrawColor(210, 208, 218);
                doc.setLineWidth(0.2);
                doc.line(M, Y_FOOTER - 4, PW - M, Y_FOOTER - 4);
                doc.setFontSize(8.5);
                doc.setTextColor(95, 95, 110);
                doc.setFont('helvetica', 'normal');
                doc.text('Page ' + p + ' of ' + totalPages, PW / 2, Y_FOOTER, { align: 'center' });
                doc.setFontSize(7.5);
                doc.setTextColor(140, 140, 155);
                doc.text('Royal Family Junior School · Student Records', PW / 2, Y_FOOTER + 5, { align: 'center' });
            }
            
            const filename = 'RFJS_Student_Records_<?php echo date("Y-m-d"); ?>.pdf';
            doc.save(filename);
        }
        
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight rows with mouseover
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.boxShadow = '0 4px 15px rgba(160, 95, 65, 0.28)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.boxShadow = 'none';
                });
            });
            
            // Make table scrollable with keyboard
            let currentRow = 0;
            document.addEventListener('keydown', function(e) {
                const rows = document.querySelectorAll('.data-table tbody tr');
                if (rows.length === 0) return;
                
                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        if (currentRow < rows.length - 1) {
                            rows[currentRow].classList.remove('selected-row');
                            currentRow++;
                            rows[currentRow].classList.add('selected-row');
                            rows[currentRow].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        if (currentRow > 0) {
                            rows[currentRow].classList.remove('selected-row');
                            currentRow--;
                            rows[currentRow].classList.add('selected-row');
                            rows[currentRow].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                        break;
                }
            });
            
            // Add CSS for selected row
            const style = document.createElement('style');
            style.textContent = `
                .selected-row td {
                    background: #8f5a42 !important;
                    color: #faf8f5 !important;
                    transform: scale(1.02);
                    transition: all 0.3s ease;
                }
            `;
            document.head.appendChild(style);
            
            // Auto-focus search input
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
            }
            
            // Add column highlighting on header hover
            const headers = document.querySelectorAll('.data-table th');
            headers.forEach((header, index) => {
                header.addEventListener('mouseenter', function() {
                    const cells = document.querySelectorAll(`.data-table td:nth-child(${index + 1})`);
                    cells.forEach(cell => {
                        cell.style.background = '#ffe8d9';
                        cell.style.transition = 'background 0.3s ease';
                    });
                });
                
                header.addEventListener('mouseleave', function() {
                    const cells = document.querySelectorAll(`.data-table td:nth-child(${index + 1})`);
                    cells.forEach((cell, cellIndex) => {
                        cell.style.background = cellIndex % 2 === 0 ? '#fff8f4' : '#fff2eb';
                    });
                });
            });
        });
    </script>
</body>
</html>