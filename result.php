<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form is submitted
if(isset($_POST['submit']))
{
    // Include database connection - check if file exists
    if(file_exists('dbcon.php')) {
        include('dbcon.php');
    } else {
        die("Database connection file not found. Please check dbcon.php exists.");
    }
    if (file_exists(__DIR__ . '/schema_helpers.php')) {
        require_once __DIR__ . '/schema_helpers.php';
    }
    
    // Check if connection is successful
    if(!isset($con) || !$con) {
        die("Database connection failed. Please check your database settings.");
    }
    
    // Sanitize inputs
    $standerd = mysqli_real_escape_string($con, $_POST['std']);
    $rollno = mysqli_real_escape_string($con, $_POST['rollno']);
    $term = mysqli_real_escape_string($con, $_POST['term']);
    $year = mysqli_real_escape_string($con, $_POST['year']);
    
    // Public portal: only rows marked published = 1 (after migration adds column)
    $pub = (function_exists('rfjs_user_mark_has_published_column') && rfjs_user_mark_has_published_column($con))
        ? ' AND `published` = 1'
        : '';
    // Check if viewing all terms or specific term
    if ($term == 'all') {
        $sql = "SELECT * FROM `user_mark` WHERE `u_class`='$standerd' AND `u_rollno`='$rollno'$pub ORDER BY `year` ASC, `term` ASC";
        $sql2 = "SELECT * FROM `user_mark` WHERE `u_class`='$standerd' AND `u_rollno`='$rollno'$pub ORDER BY `year` ASC, `term` ASC LIMIT 1";
    } else {
        $sql = "SELECT * FROM `user_mark` WHERE `u_class`='$standerd' AND `u_rollno`='$rollno' AND `term`='$term' AND `year`='$year'$pub";
        $sql2 = "SELECT * FROM `user_mark` WHERE `u_class`='$standerd' AND `u_rollno`='$rollno' AND `term`='$term' AND `year`='$year'$pub";
    }
    
    $run = mysqli_query($con, $sql);
    $run2 = mysqli_query($con, $sql2);
    
    if(!$run || !$run2) {
        die("Query failed: " . mysqli_error($con));
    }
    
    $row_count = mysqli_num_rows($run);
    
    if($row_count > 0)
    {
        // Get first record for student info
        $first_record = mysqli_fetch_assoc($run2);
        mysqli_data_seek($run, 0); // Reset pointer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>RFJS RESULTS PORTAL - Royal Family Junior School</title>
    <link rel="icon" href="image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="csss/rfjs-theme.css" type="text/css">
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
            font-weight: 600;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh;
            background: var(--rfjs-bg-page);
            color: #333;
            overflow-x: auto;
        }

        /* Navigation */
        .main-nav {
            list-style: none;
            display: flex;
            justify-content: center;
            padding: 15px 20px;
            background: rgba(30, 58, 138, 0.9);
            gap: 15px;
            flex-wrap: wrap;
        }

        .main-nav a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
        }

        .main-nav a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content-header {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: clamp(12px, 3vw, 30px) clamp(12px, 3vw, 20px);
            width: 100%;
            box-sizing: border-box;
        }

        /* Results Container */
        .results-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: clamp(16px, 4vw, 30px);
            width: 100%;
            max-width: min(1680px, calc(100vw - 24px));
            margin: 0 auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* School Header */
        .school-header {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: clamp(12px, 3vw, 20px);
            margin-bottom: 25px;
            padding: clamp(14px, 3vw, 20px);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            border: 3px solid #1e3a8a;
        }

        .school-header .school-logo {
            flex-shrink: 0;
        }

        .school-title {
            text-align: center;
            min-width: 0;
        }

        .school-title h1 {
            font-size: clamp(1.05rem, 4vw, 28px);
            color: #1e3a8a;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .school-title h2 {
            font-size: clamp(1rem, 3.5vw, 28px);
            color: #1e40af;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .school-title h3 {
            font-size: clamp(0.95rem, 3vw, 22px);
            color: #374151;
            font-weight: bold;
        }

        /* Student Info */
        .student-info {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 15px;
            border: 2px solid #1e3a8a;
        }

        .student-name {
            font-size: 28px;
            margin-bottom: 15px;
            color: #1e3a8a;
            text-align: left;
            text-transform: uppercase;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }

        .info-grid, .info-grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .info-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #1e3a8a;
        }

        .info-label {
            font-size: 13px;
            text-transform: uppercase;
            color: #1e40af;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .info-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
        }

        /* Tables — full width + horizontal scroll inside container */
        .results-table, .summary-table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            overflow: visible;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            table-layout: auto;
        }

        .results-table th, .summary-table th {
            background: #1e3a8a;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 14px;
            text-transform: uppercase;
        }

        .results-table td, .summary-table td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .results-table tr:nth-child(even), .summary-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .subject-name {
            text-align: left;
            font-weight: bold;
            padding-left: 15px;
        }

        /* Summary Section */
        .summary-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 25px 0;
        }

        .summary-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #0d1128 0%, #5a3d32 45%, #8a4a32 100%);
            border-radius: 10px;
            color: #faf8f5;
        }

        .summary-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .summary-label {
            font-size: 14px;
            text-transform: uppercase;
        }

        /* Result Status */
        .result-status {
            text-align: center;
            margin: 25px 0;
            padding: 25px;
            background: var(--rfjs-banner-warm);
            border-radius: 10px;
            border: 1px solid rgba(90, 60, 45, 0.12);
        }

        .result-status h3 {
            font-size: 24px;
            color: #1e3a8a;
            margin-bottom: 10px;
        }

        .status-pass {
            color: #059669;
            font-size: 36px;
            font-weight: bold;
        }

        .status-fail {
            color: #dc2626;
            font-size: 36px;
            font-weight: bold;
        }

        /* Term Header */
        .term-header {
            text-align: center;
            background: #1e3a8a;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        /* Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: #1e3a8a;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-success {
            background: #059669;
            color: white;
        }

        .btn-info {
            background: #0891b2;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 15px;
            background: #1e3a8a;
            color: white;
            font-size: 14px;
        }

        /* Color classes */
        .colorchange { color: #059669; font-weight: bold; }
        .colorchange1 { color: #8a6040; font-weight: bold; }

        /* Print options */
        .print-options {
            margin: 20px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            border: 2px solid #1e3a8a;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .option-group {
            background: white;
            padding: 12px;
            border-radius: 6px;
        }

        .option-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #1e40af;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .radio-group {
            display: flex;
            gap: 15px;
        }

        .print-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }

        .print-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }

        .print-action-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-save { background: #059669; color: white; }
        .btn-cancel { background: #dc2626; color: white; }

        /* Term selector */
        .term-selector {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .term-btn {
            padding: 8px 16px;
            background: white;
            border: 2px solid #1e3a8a;
            color: #1e3a8a;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .term-btn.active {
            background: #1e3a8a;
            color: white;
        }

        .results-table-container {
            display: none;
        }

        .results-table-container.active {
            display: block;
        }

        /* Marquee */
        .marquee-container {
            margin: 20px 0;
            padding: 12px;
            background: #fef3c7;
            border-radius: 8px;
            border: 1px solid rgba(180, 140, 90, 0.45);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .school-header {
                flex-direction: column;
                text-align: center;
            }
            
            .summary-section {
                grid-template-columns: 1fr;
            }
            
            .info-grid, .info-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        /* Print styles */
        @media print {
            .no-print, .print-options, .action-buttons, nav, footer, .marquee-container, .term-selector {
                display: none !important;
            }
            
            .results-container {
                box-shadow: none;
                padding: 0;
            }

            .school-logo.rfjs-logo-img {
                max-height: 100px !important;
                max-width: 120px !important;
            }
            
            .school-header, .student-info, .results-table, .summary-section, .result-status {
                border: 2px solid #000 !important;
                background: white !important;
            }
            
            .results-table th, .summary-table th {
                background: #1e3a8a !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <nav class="no-print">
        <ul class="main-nav">
            <li><a href="index.php"><i class="fas fa-home"></i> HOME</a></li>
            <li><a href="admin/aboutus.php"><i class="fas fa-info-circle"></i> ABOUT</a></li>
            <li><a href="admin/contactus.php"><i class="fas fa-envelope"></i> CONTACT</a></li>
            <li><a href="login.php"><i class="fas fa-lock"></i> ADMIN LOGIN</a></li>
            <li><a href="https://www.royalfamilyjunior.ac.zw/" target="_blank" rel="noopener"><i class="fas fa-graduation-cap"></i> MAIN SITE</a></li>
        </ul>
    </nav>

    <div class="main-content-header">
        <div class="results-container">
            <!-- School Header -->
            <div class="school-header">
                <img src="image/logo-rfjs.png" alt="Royal Family Junior School" class="school-logo rfjs-logo-img rfjs-logo-crest" width="120" height="120">
                <div class="school-title">
                    <h1>Royal Family Junior School</h1>
                    <h2>ACADEMIC RESULTS - <?php echo ($term == 'all') ? 'FULL TRANSCRIPT' : "TERM $term, $year"; ?></h2>
                    <h3>OFFICIAL STUDENT REPORT CARD</h3>
                </div>
            </div>

            <!-- Student Information -->
            <div class="student-info">
                <div class="student-name"><?php echo strtoupper($first_record['u_name']); ?></div>
                
                <?php if ($term != 'all'): ?>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ADMISSION NUMBER</div>
                        <div class="info-value"><?php echo $first_record['u_rollno']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">CURRENT GRADE</div>
                        <div class="info-value"><?php echo $first_record['u_class']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">VIEWING TERM</div>
                        <div class="info-value">Term <?php echo $term; ?>, <?php echo $year; ?></div>
                    </div>
                </div>
                <div class="info-grid-2">
                    <div class="info-item">
                        <div class="info-label">CLASS POSITION</div>
                        <div class="info-value"><?php echo $first_record['u_position']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">TOTAL MARKS</div>
                        <div class="info-value"><?php echo $first_record['u_total']; ?> / 600</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ADMISSION NUMBER</div>
                        <div class="info-value"><?php echo $first_record['u_rollno']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">CURRENT GRADE</div>
                        <div class="info-value"><?php echo $first_record['u_class']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">VIEWING</div>
                        <div class="info-value">All Terms Transcript</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($term == 'all'): ?>
                <!-- TRANSCRIPT VIEW -->
                <div class="viewing-indicator no-print">
                    <i class="fas fa-file-alt"></i> VIEWING FULL ACADEMIC TRANSCRIPT - ALL TERMS
                </div>
                
                <div class="transcript-summary">
                    <h3 style="text-align: center; color: #1e3a8a; margin: 20px 0;">ACADEMIC TRANSCRIPT SUMMARY</h3>
                    
                    <?php
                    $all_records = [];
                    $term_records = [];
                    
                    mysqli_data_seek($run, 0);
                    while($record = mysqli_fetch_assoc($run)) {
                        $all_records[] = $record;
                        $term_key = $record['year'] . '-T' . $record['term'] . '-G' . $record['u_class'];
                        if (!isset($term_records[$term_key])) {
                            $term_records[$term_key] = $record;
                        }
                    }
                    
                    if (count($term_records) > 1): ?>
                        <div class="term-selector no-print">
                            <?php foreach($term_records as $term_key => $record): 
                                $year = $record['year'];
                                $term_num = $record['term'];
                                $grade = $record['u_class'];
                            ?>
                                <button class="term-btn" onclick="showTerm('<?php echo $term_key; ?>', this)">Term <?php echo $term_num; ?>, <?php echo $year; ?></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <table class="summary-table">
                        <tr>
                            <th>PERIOD</th>
                            <th>GRADE</th>
                            <th>TOTAL</th>
                            <th>POSITION</th>
                            <th>AVERAGE</th>
                            <th>STATUS</th>
                        </tr>
                        
                        <?php
                        $overall_total = 0;
                        $term_count = 0;
                        
                        foreach($term_records as $record) {
                            $total_marks = $record['u_total'];
                            $average = ($total_marks / 600) * 100;
                            $status = ($total_marks > 299) ? 'PASS' : 'FAIL';
                            
                            $overall_total += $average;
                            $term_count++;
                        ?>
                        <tr>
                            <td><strong>Term <?php echo $record['term']; ?>, <?php echo $record['year']; ?></strong></td>
                            <td>Grade <?php echo $record['u_class']; ?></td>
                            <td><?php echo $total_marks; ?>/600</td>
                            <td><?php echo $record['u_position']; ?></td>
                            <td><?php echo number_format($average, 1); ?>%</td>
                            <td style="color: <?php echo ($status == 'PASS' ? '#059669' : '#dc2626'); ?>; font-weight: bold;"><?php echo $status; ?></td>
                        </tr>
                        <?php } ?>
                        
                        <?php if ($term_count > 0): 
                            $overall_average = $overall_total / $term_count;
                        ?>
                        <tr style="background: #e6f0fa; font-weight: bold;">
                            <td colspan="4" style="text-align: right;">OVERALL AVERAGE:</td>
                            <td colspan="2"><?php echo number_format($overall_average, 1); ?>%</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <!-- Term Results -->
                <?php foreach($term_records as $term_key => $record): ?>
                    <div class="results-table-container" id="<?php echo $term_key; ?>">
                        <div class="term-header">
                            <h3>TERM <?php echo $record['term']; ?> - <?php echo $record['year']; ?> RESULTS (GRADE <?php echo $record['u_class']; ?>)</h3>
                        </div>
                        
                        <?php
                        $subjects = [
                            'SCIENCE AND TECH' => [$record['u_science_technology_1'], $record['u_science_technology_2']],
                            'ENGLISH' => [$record['u_english_1'], $record['u_english_2']],
                            'MATHEMATICS' => [$record['u_mathematics_1'], $record['u_mathematics_2']],
                            'SHONA' => [$record['u_shona_1'], $record['u_shona_2']],
                            'SOCIAL SCIENCE' => [$record['u_social_science_1'], $record['u_social_science_2']],
                            'PE AND ARTS' => [$record['u_physical_education_arts_1'], $record['u_physical_education_arts_2']]
                        ];
                        
                        $total_marks = 0;
                        $total_units = 0;
                        ?>
                        
                        <table class="results-table">
                            <tr>
                                <th>SUBJECT</th>
                                <th>PAPER 1</th>
                                <th>PAPER 2</th>
                                <th>TOTAL</th>
                                <th>UNITS</th>
                            </tr>
                            
                            <?php foreach($subjects as $subject => $marks): 
                                $total = $marks[0] + $marks[1];
                                $total_marks += $total;
                                
                                if ($total >= 85) $unit = 1;
                                elseif ($total >= 77) $unit = 2;
                                elseif ($total >= 70) $unit = 3;
                                elseif ($total >= 60) $unit = 4;
                                elseif ($total >= 50) $unit = 5;
                                elseif ($total >= 40) $unit = 6;
                                elseif ($total >= 30) $unit = 7;
                                elseif ($total >= 20) $unit = 8;
                                else $unit = 9;
                                
                                $total_units += $unit;
                            ?>
                            <tr>
                                <td class="subject-name"><?php echo $subject; ?></td>
                                <td><?php echo $marks[0]; ?></td>
                                <td><?php echo $marks[1]; ?></td>
                                <td class="colorchange"><?php echo $total; ?></td>
                                <td class="colorchange1"><?php echo $unit; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr style="background: #1e3a8a; color: white;">
                                <td style="font-weight: bold;">TOTAL</td>
                                <td><?php echo array_sum(array_column($subjects, 0)); ?></td>
                                <td><?php echo array_sum(array_column($subjects, 1)); ?></td>
                                <td><?php echo $total_marks; ?></td>
                                <td><?php echo $total_units; ?></td>
                            </tr>
                        </table>
                        
                        <div class="summary-section">
                            <div class="summary-item">
                                <div class="summary-value"><?php echo $total_marks; ?></div>
                                <div class="summary-label">TOTAL MARKS</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value"><?php echo $total_units; ?></div>
                                <div class="summary-label">TOTAL UNITS</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value"><?php echo $record['u_position']; ?></div>
                                <div class="summary-label">POSITION</div>
                            </div>
                        </div>
                        
                        <div class="result-status">
                            <h3>RESULT STATUS</h3>
                            <div class="<?php echo ($total_marks <= 299) ? 'status-fail' : 'status-pass'; ?>">
                                <?php echo ($total_marks <= 299) ? 'FAIL' : 'PASS'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <script>
                    function showTerm(id, btn) {
                        document.querySelectorAll('.results-table-container').forEach(div => div.classList.remove('active'));
                        document.getElementById(id).classList.add('active');
                        document.querySelectorAll('.term-btn').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    }
                    
                    // Show first term by default
                    window.onload = function() {
                        var firstBtn = document.querySelector('.term-btn');
                        if(firstBtn) {
                            var firstId = firstBtn.getAttribute('onclick').match(/'([^']+)'/)[1];
                            document.getElementById(firstId).classList.add('active');
                            firstBtn.classList.add('active');
                        }
                    }
                </script>
                
            <?php else: ?>
                <!-- SINGLE TERM VIEW -->
                <?php 
                $record = mysqli_fetch_assoc($run);
                
                $subjects = [
                    'SCIENCE AND TECH' => [$record['u_science_technology_1'], $record['u_science_technology_2']],
                    'ENGLISH' => [$record['u_english_1'], $record['u_english_2']],
                    'MATHEMATICS' => [$record['u_mathematics_1'], $record['u_mathematics_2']],
                    'SHONA' => [$record['u_shona_1'], $record['u_shona_2']],
                    'SOCIAL SCIENCE' => [$record['u_social_science_1'], $record['u_social_science_2']],
                    'PE AND ARTS' => [$record['u_physical_education_arts_1'], $record['u_physical_education_arts_2']]
                ];
                
                $total_marks = 0;
                $total_units = 0;
                ?>
                
                <div class="term-header">
                    <h3>TERM <?php echo $term; ?> - <?php echo $year; ?> RESULTS</h3>
                </div>
                
                <table class="results-table">
                    <tr>
                        <th>SUBJECT</th>
                        <th>PAPER 1</th>
                        <th>PAPER 2</th>
                        <th>TOTAL</th>
                        <th>UNITS</th>
                    </tr>
                    
                    <?php foreach($subjects as $subject => $marks): 
                        $total = $marks[0] + $marks[1];
                        $total_marks += $total;
                        
                        if ($total >= 85) $unit = 1;
                        elseif ($total >= 77) $unit = 2;
                        elseif ($total >= 70) $unit = 3;
                        elseif ($total >= 60) $unit = 4;
                        elseif ($total >= 50) $unit = 5;
                        elseif ($total >= 40) $unit = 6;
                        elseif ($total >= 30) $unit = 7;
                        elseif ($total >= 20) $unit = 8;
                        else $unit = 9;
                        
                        $total_units += $unit;
                    ?>
                    <tr>
                        <td class="subject-name"><?php echo $subject; ?></td>
                        <td><?php echo $marks[0]; ?></td>
                        <td><?php echo $marks[1]; ?></td>
                        <td class="colorchange"><?php echo $total; ?></td>
                        <td class="colorchange1"><?php echo $unit; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr style="background: #1e3a8a; color: white;">
                        <td style="font-weight: bold;">TOTAL</td>
                        <td><?php echo array_sum(array_column($subjects, 0)); ?></td>
                        <td><?php echo array_sum(array_column($subjects, 1)); ?></td>
                        <td><?php echo $total_marks; ?></td>
                        <td><?php echo $total_units; ?></td>
                    </tr>
                </table>

                <div class="summary-section">
                    <div class="summary-item">
                        <div class="summary-value"><?php echo $total_marks; ?></div>
                        <div class="summary-label">TOTAL MARKS</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo $total_units; ?></div>
                        <div class="summary-label">TOTAL UNITS</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value"><?php echo $record['u_position']; ?></div>
                        <div class="summary-label">POSITION</div>
                    </div>
                </div>

                <div class="result-status">
                    <h3>YOUR RESULT IS</h3>
                    <div class="<?php echo ($total_marks <= 299) ? 'status-fail' : 'status-pass'; ?>">
                        <?php echo ($total_marks <= 299) ? 'FAIL' : 'PASS'; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Print Options -->
            <div class="print-options no-print" id="printOptions" style="display: none;">
                <h3><i class="fas fa-print"></i> Print Options</h3>
                <div class="options-grid">
                    <div class="option-group">
                        <div class="option-label">Orientation</div>
                        <div class="radio-group">
                            <label><input type="radio" name="orientation" value="portrait" checked> Portrait</label>
                            <label><input type="radio" name="orientation" value="landscape"> Landscape</label>
                        </div>
                    </div>
                    <div class="option-group">
                        <div class="option-label">Pages</div>
                        <select id="printPages" class="print-select">
                            <option value="all">All</option>
                            <option value="current">Current Page</option>
                        </select>
                    </div>
                    <div class="option-group">
                        <div class="option-label">Color</div>
                        <select id="printColor" class="print-select">
                            <option value="color">Color</option>
                            <option value="bw">Black & White</option>
                        </select>
                    </div>
                </div>
                <div class="print-actions">
                    <button class="print-action-btn btn-save" onclick="printWithOptions()">Print</button>
                    <button class="print-action-btn btn-cancel" onclick="togglePrintOptions()">Cancel</button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons no-print">
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Back to Home</a>
                <?php if ($term != 'all'): ?>
                    <a href="javascript:void(0);" onclick="viewFullTranscript()" class="btn btn-success"><i class="fas fa-file-alt"></i> View Full Transcript</a>
                <?php endif; ?>
                <button onclick="togglePrintOptions()" class="btn btn-info"><i class="fas fa-cog"></i> Print Options</button>
            </div>
        </div>
    </div>

    <footer class="no-print">
        DESIGNED BY CharlzTech Web Developers
    </footer>

    <script>
        function togglePrintOptions() {
            var panel = document.getElementById('printOptions');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }
        
        function printWithOptions() {
            var orientation = document.querySelector('input[name="orientation"]:checked').value;
            var color = document.getElementById('printColor').value;
            
            var style = document.createElement('style');
            var css = '@page { size: A4 ' + orientation + '; }';
            if (color === 'bw') {
                css += 'body { filter: grayscale(100%); }';
            }
            style.innerHTML = css;
            document.head.appendChild(style);
            
            window.print();
            
            setTimeout(function() {
                document.head.removeChild(style);
            }, 1000);
            
            document.getElementById('printOptions').style.display = 'none';
        }
        
        function viewFullTranscript() {
            sessionStorage.setItem('transcript_view', 'true');
            sessionStorage.setItem('admission_no', '<?php echo $rollno; ?>');
            sessionStorage.setItem('grade', '<?php echo $standerd; ?>');
            sessionStorage.setItem('year', '<?php echo $year; ?>');
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>

<?php
    }
    else
    {
        $hasUnpublished = false;
        if (function_exists('rfjs_user_mark_has_published_column') && rfjs_user_mark_has_published_column($con)) {
            if ($term == 'all') {
                $chk = @mysqli_query($con, "SELECT `id` FROM `user_mark` WHERE `u_class`='$standerd' AND `u_rollno`='$rollno' AND (`published` = 0 OR `published` IS NULL) LIMIT 1");
            } else {
                $chk = @mysqli_query($con, "SELECT `id` FROM `user_mark` WHERE `u_class`='$standerd' AND `u_rollno`='$rollno' AND `term`='$term' AND `year`='$year' AND (`published` = 0 OR `published` IS NULL) LIMIT 1");
            }
            if ($chk && mysqli_num_rows($chk) > 0) {
                $hasUnpublished = true;
            }
        }
?>
<?php if ($hasUnpublished): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results not yet published — RFJS</title>
    <link rel="icon" href="image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="csss/rfjs-theme.css" type="text/css">
    <style>
        body { font-family: 'Flamenco', Georgia, serif; background: var(--rfjs-bg-page); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .box { max-width: 420px; background: rgba(255,255,255,0.95); padding: 28px; border-radius: 16px; text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
        h1 { color: var(--rfjs-text-accent-strong, #1a2a52); font-size: 1.25rem; margin-bottom: 12px; }
        p { color: #444; line-height: 1.5; margin-bottom: 20px; }
        a { color: var(--rfjs-orange, #c96b3d); font-weight: 600; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <img src="image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="100" height="100" style="margin-bottom:16px;">
        <h1>Results not yet published</h1>
        <p>Your results are recorded but not yet released on the public portal. Please check again after the school has published them.</p>
        <p><a href="index.php"><i class="fas fa-home"></i> Back to home</a></p>
    </div>
</body>
</html>
<?php else: ?>
<script>
    alert('Record Not found');
    window.location.href = 'index.php';
</script>
<?php endif; ?>
<?php
    }
}
else
{
    // If someone accesses this page directly without form submission
    header('Location: index.php');
    exit;
}
?>