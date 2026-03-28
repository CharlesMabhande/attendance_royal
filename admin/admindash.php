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

?>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
    <link rel="stylesheet" href="../csss/admindash.css" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Flamenco" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.0/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Flamenco', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            background-attachment: fixed;
        }

        /* Navigation Styles */
        .nav-container {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .row.clearfix {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-nav {
            list-style: none;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .main-nav li {
            margin: 0;
        }

        .main-nav a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            padding: 12px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            white-space: nowrap;
            font-size: 15px;
            backdrop-filter: blur(5px);
        }

        .main-nav a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .main-nav .logout a {
            background: rgba(231, 76, 60, 0.2);
            border-color: rgba(231, 76, 60, 0.3);
        }

        .main-nav .logout a:hover {
            background: rgba(231, 76, 60, 0.3);
            transform: translateY(-3px);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 auto 15px auto;
            width: 100%;
            max-width: 250px;
            justify-content: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Dashboard Container */
        html { overflow-x: hidden; }
        .dashboard-container {
            max-width: min(1680px, 100%);
            margin: 0 auto;
            padding: clamp(20px, 4vw, 40px) clamp(14px, 3vw, 24px);
            min-height: calc(100vh - 80px);
            min-height: calc(100dvh - 80px);
            width: 100%;
            box-sizing: border-box;
        }
        
        .dashboard-logo-wrap {
            text-align: center;
            margin-bottom: clamp(14px, 3vw, 22px);
        }

        .dashboard-title {
            text-align: center;
            margin-bottom: clamp(28px, 5vw, 50px);
            color: white;
            font-size: clamp(1.5rem, 5vw, 3rem);
            text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: clamp(12px, 2vw, 22px);
            margin-top: 30px;
        }
        
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: clamp(18px, 2vw, 28px) clamp(12px, 1.5vw, 18px);
            text-align: center;
            transition: all 0.4s ease;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            border-color: #667eea;
        }
        
        .card-icon {
            font-size: clamp(40px, 3.5vw, 52px);
            margin-bottom: clamp(14px, 2vw, 22px);
            color: #667eea;
            transition: transform 0.4s ease;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .dashboard-card:hover .card-icon {
            transform: scale(1.15) rotate(5deg);
        }
        
        .card-title {
            font-size: clamp(15px, 1.15vw, 20px);
            font-weight: bold;
            margin-bottom: clamp(8px, 1.2vw, 14px);
            color: #333;
            line-height: 1.4;
        }
        
        .card-description {
            font-size: clamp(12px, 0.95vw, 14px);
            color: #666;
            line-height: 1.5;
            margin-bottom: clamp(10px, 1.5vw, 16px);
        }
        
        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
            width: 100%;
        }

        .card-link:hover .card-title {
            color: #764ba2;
        }

        /* Welcome Message */
        .welcome-message {
            text-align: center;
            margin-bottom: 40px;
            padding: 25px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .welcome-message h3 {
            color: white;
            font-size: 24px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .welcome-message p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            line-height: 1.5;
        }

        /* System Info */
        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .info-card i {
            font-size: 32px;
            color: #f093fb;
            margin-bottom: 15px;
        }

        .info-card h4 {
            color: white;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .info-card p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        /* Footer */
        .admin-footer {
            text-align: center;
            padding: 20px;
            color: white;
            background: rgba(0, 0, 0, 0.2);
            font-size: 14px;
            backdrop-filter: blur(5px);
            margin-top: 40px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Responsive Design — dashboard cards: 5 → 3 → 2 → 1 per row */
        @media (max-width: 1199px) {
            .dashboard-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: clamp(14px, 2.5vw, 24px);
            }
        }

        @media (max-width: 1024px) {
            .dashboard-title {
                font-size: 2.5rem;
                margin-bottom: 40px;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 10px 0;
            }

            .main-nav {
                display: none;
                flex-direction: column;
                gap: 12px;
                width: 100%;
            }

            .main-nav.active {
                display: flex;
            }

            .main-nav a {
                width: 100%;
                max-width: 300px;
                justify-content: center;
                padding: 15px 25px;
                font-size: 16px;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .dashboard-container {
                padding: 30px 15px;
            }
            
            .dashboard-title {
                font-size: 2.2rem;
                margin-bottom: 35px;
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px;
            }
            
            .dashboard-card {
                padding: 25px 20px;
            }
            
            .card-icon {
                font-size: 50px;
                margin-bottom: 20px;
            }
            
            .card-title {
                font-size: 20px;
            }
            
            .card-description {
                font-size: 14px;
            }
            
            .welcome-message {
                padding: 20px;
                margin-bottom: 30px;
            }
            
            .welcome-message h3 {
                font-size: 20px;
            }
        }

        @media (max-width: 640px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
                max-width: 400px;
                margin-left: auto;
                margin-right: auto;
            }

            .dashboard-title {
                font-size: 1.8rem;
                margin-bottom: 30px;
            }

            .main-nav a {
                max-width: 280px;
                padding: 14px 20px;
            }
            
            .system-info {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 25px 10px;
            }
            
            .dashboard-title {
                font-size: 1.6rem;
                margin-bottom: 25px;
            }
            
            .dashboard-card {
                padding: 20px 15px;
            }
            
            .card-icon {
                font-size: 45px;
                margin-bottom: 15px;
            }
            
            .card-title {
                font-size: 18px;
            }
            
            .card-description {
                font-size: 13px;
            }

            .main-nav a {
                max-width: 250px;
                font-size: 15px;
                padding: 12px 18px;
            }

            .mobile-menu-toggle {
                font-size: 15px;
                padding: 12px 20px;
                max-width: 220px;
            }
            
            .welcome-message {
                padding: 15px;
                margin-bottom: 25px;
            }
            
            .welcome-message h3 {
                font-size: 18px;
            }
            
            .welcome-message p {
                font-size: 14px;
            }
        }

        @media (max-width: 360px) {
            .dashboard-title {
                font-size: 1.4rem;
            }
            
            .dashboard-card {
                padding: 18px 12px;
            }
            
            .card-icon {
                font-size: 40px;
            }
            
            .card-title {
                font-size: 16px;
            }

            .main-nav a {
                max-width: 220px;
                font-size: 14px;
                padding: 10px 15px;
            }
            
            .mobile-menu-toggle {
                font-size: 14px;
                padding: 10px 15px;
                max-width: 200px;
            }
        }

        /* Animation delays for cards */
        .dashboard-card.animated {
            animation: fadeInUp 0.6s ease forwards;
        }

        .dashboard-card:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-card:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-card:nth-child(3) { animation-delay: 0.3s; }
        .dashboard-card:nth-child(4) { animation-delay: 0.4s; }
        .dashboard-card:nth-child(5) { animation-delay: 0.5s; }
        .dashboard-card:nth-child(6) { animation-delay: 0.6s; }
        .dashboard-card:nth-child(7) { animation-delay: 0.65s; }
        .dashboard-card:nth-child(8) { animation-delay: 0.7s; }
        .dashboard-card:nth-child(9) { animation-delay: 0.75s; }
        .dashboard-card:nth-child(10) { animation-delay: 0.8s; }
        .dashboard-card:nth-child(11) { animation-delay: 0.85s; }
        .dashboard-card:nth-child(12) { animation-delay: 0.9s; }

        /* Print Styles */
        @media print {
            .main-nav, .mobile-menu-toggle, .admin-footer {
                display: none !important;
            }
            
            body {
                background: white !important;
            }
            
            .dashboard-container {
                padding: 20px !important;
            }
            
            .dashboard-card {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }

        /* Accessibility Improvements */
        .card-link:focus {
            outline: 3px solid #667eea;
            outline-offset: 3px;
        }

        .mobile-menu-toggle:focus {
            outline: 3px solid white;
            outline-offset: 2px;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .dashboard-card {
                background: rgba(30, 30, 40, 0.95);
            }
            
            .card-title {
                color: #e0e0e0;
            }
            
            .card-description {
                color: #aaa;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="nav-container">
            <div class="row clearfix">
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle navigation menu">
                    <i class="fas fa-bars"></i> Menu
                </button>
                <ul class="main-nav" animate slideInDown>
                    <li><a href="../index.php"><i class="fas fa-home"></i> HOME</a></li>
                    <li><a href="aboutus.php"><i class="fas fa-info-circle"></i> ABOUT</a></li>
                    <li><a href="contactus.php"><i class="fas fa-envelope"></i> CONTACT</a></li>
                    <?php if (admin_can('users')): ?>
                    <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> USERS</a></li>
                    <?php endif; ?>
                    <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> LOGOUT</a></li>
                </ul>
            </div>
        </nav>
        
        <div class="dashboard-container">
            <div class="dashboard-logo-wrap animated fadeIn">
                <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="130" height="130">
            </div>
            <h1 class="dashboard-title animated fadeIn">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </h1>
            
            <!-- Welcome Message -->
            <div class="welcome-message animated fadeIn">
                <h3>Welcome to the Results Management System</h3>
                <p>Manage student marks, view records, and handle tasks you are allowed to access. Your access level: <strong><?php echo htmlspecialchars(admin_role_label()); ?></strong><?php if (admin_is_class_scoped()): ?> — students: <strong><?php echo htmlspecialchars(admin_class_scope_label()); ?></strong> only<?php endif; ?></p>
            </div>

            <?php if (isset($_GET['denied'])): ?>
                <div class="welcome-message animated fadeIn" style="border: 1px solid #f5c6cb; background: rgba(248, 215, 218, 0.95);">
                    <p style="color: #721c24; margin: 0;"><i class="fas fa-lock"></i> You do not have access to that page. Open a tool from the cards below that your role allows.</p>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <!-- Manage admin users -->
                <?php if (admin_can('users')): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="manage_users.php" class="card-link" aria-label="Manage admin users">
                        <div class="card-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="card-title">Admin users</div>
                        <div class="card-description">
                            Create staff logins, reset passwords, rename accounts, or remove users. All accounts use the same login page as today.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Manage users
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Publish results (super administrators only — role full) -->
                <?php if (admin_current_role() === 'full'): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="publish_results.php" class="card-link" aria-label="Publish results to public portal">
                        <div class="card-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="card-title">Publish results</div>
                        <div class="card-description">
                            Control when marks appear on the public results portal. New records stay hidden until super administrators publish them (per student on the marks page or in bulk here).
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Open publish centre
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Staff directory (teachers & ancillary) -->
                <?php if (admin_can('staff_directory')): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="staff_list.php" class="card-link" aria-label="Staff directory">
                        <div class="card-icon">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <div class="card-title">Staff Directory</div>
                        <div class="card-description">
                            Manage teachers and ancillary staff: qualifications, roles, level of work, employment status, and PDF certificates. Add new staff or remove those who have left.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Open directory
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Add Student Marks Card -->
                <?php if (admin_can('marks')): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="addmark.php" class="card-link" aria-label="Add Student Marks">
                        <div class="card-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="card-title">Add Student Marks</div>
                        <div class="card-description">
                            Add new student marks for any academic term and year. Create comprehensive student records with detailed subject marks and performance data.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Get Started
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Update Student Marks Card -->
                <?php if (admin_can('marks')): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="addmark.php" class="card-link" aria-label="Update Student Marks">
                        <div class="card-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="card-title">Update Student Marks</div>
                        <div class="card-description">
                            Modify existing student marks and information. Edit subject scores, update positions, and maintain accurate academic records across all terms.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Manage Records
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Delete Student Marks Card -->
                <?php if (admin_can('delete_marks')): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="addmark.php?action=delete" class="card-link" aria-label="Delete Student Marks">
                        <div class="card-icon">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                        <div class="card-title">Delete Student Marks</div>
                        <div class="card-description">
                            Remove student marks and records from the system. Delete individual term records or complete student profiles with proper authorization.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Remove Records
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Student Records Card -->
                <?php if (admin_can('records')): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="allstudentdata.php" class="card-link" aria-label="View Student Records">
                        <div class="card-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="card-title">Student Records Database</div>
                        <div class="card-description">
                            View and manage all student data and academic records. Access comprehensive information including marks, positions, and performance across all terms.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> View Database
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Student Messages Card -->
                <?php if (admin_can('messages')): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="usermassage.php" class="card-link" aria-label="View Student Messages">
                        <div class="card-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="card-title">Student Messages</div>
                        <div class="card-description">
                            Read and respond to messages from students and parents. Manage communication and address concerns regarding results and academic performance.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> View Messages
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Generate Reports Card -->
                <?php if (admin_can('reports')): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="school_reports.php" class="card-link" aria-label="Generate Reports">
                        <div class="card-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="card-title">Generate Reports</div>
                        <div class="card-description">
                            Academic summaries, class statistics, subject averages, top learners, and staff exports — printable in the browser, plus CSV and PDF downloads.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Open report centre
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Bulk move — next term (school-wide; hidden for grade-locked accounts) -->
                <?php if (admin_can('bulk_term') && !admin_is_class_scoped()): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="bulk_move_term.php" class="card-link" aria-label="Bulk move to next term">
                        <div class="card-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div class="card-title">Bulk Move — Next Term</div>
                        <div class="card-description">
                            Copy a whole grade or ECD cohort to the next term with blank marks. Term 3 rolls to Term 1 of the following year. Original term records stay unchanged.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Open tool
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Bulk move — next grade -->
                <?php if (admin_can('bulk_grade') && !admin_is_class_scoped()): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="bulk_move_grade.php" class="card-link" aria-label="Bulk move to next grade">
                        <div class="card-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="card-title">Bulk Move — Next Grade</div>
                        <div class="card-description">
                            Promote Grades 1–6 to the next grade with new blank records (same term; year can match the source or be set to another year). Grade 7 is excluded—learners have completed primary school.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Open tool
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- ECD → new year (A→B, B→Grade 1) -->
                <?php if (admin_can('bulk_ecd') && !admin_is_class_scoped()): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="bulk_move_ecd_year.php" class="card-link" aria-label="ECD promote to new year">
                        <div class="card-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="card-title">ECD — New Year Promotion</div>
                        <div class="card-description">
                            ECD A → ECD B, and ECD B → Grade 1 (Term 1, blank marks). Choose next year, same year, or a custom year on the new records. Original rows stay unchanged.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Open tool
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Bulk delete term records (super admin only) -->
                <?php if (admin_current_role() === 'full' && !admin_is_class_scoped()): ?>
                <div class="dashboard-card animated fadeInUp">
                    <a href="bulk_delete_term_records.php" class="card-link" aria-label="Bulk delete term records">
                        <div class="card-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div class="card-title">Bulk Delete — Term Records</div>
                        <div class="card-description">
                            Remove all mark rows for a chosen year and term(s), optionally limited to one class. Preview counts first; permanent delete for super administrators only.
                        </div>
                        <div style="color: #667eea; font-weight: bold; margin-top: 15px;">
                            <i class="fas fa-arrow-right"></i> Open tool
                        </div>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!admin_dashboard_has_any_tool()): ?>
                <div class="welcome-message animated fadeIn" style="margin-top: 20px;">
                    <p style="color: #721c24;"><i class="fas fa-info-circle"></i> No dashboard tools are assigned to your role. Ask a super administrator to update your account in <strong>Admin users</strong>.</p>
                </div>
            <?php endif; ?>
            
            <!-- System Information -->
            <div class="system-info animated fadeIn" style="animation-delay: 0.7s;">
                <div class="info-card">
                    <i class="fas fa-calendar-alt"></i>
                    <h4>Multi-Term Support</h4>
                    <p>Manage results for Term 1, 2, 3 and any academic year</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-file-pdf"></i>
                    <h4>PDF Generation</h4>
                    <p>Generate both term results and full academic transcripts</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-search"></i>
                    <h4>Advanced Search</h4>
                    <p>Filter records by grade, term, year, and student name</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h4>Mobile Friendly</h4>
                    <p>Fully responsive design works on all devices</p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="admin-footer animated fadeIn" style="animation-delay: 0.8s;">
                <p><i class="fas fa-shield-alt"></i> Admin Session Active | Royal Family Junior School Results Management System</p>
                <p>DESIGNED BY CharlzTech Web Developers | © 2026 RFJS - All rights reserved</p>
            </div>
        </div>
    </header>

    <script>
        function toggleMobileMenu() {
            const nav = document.querySelector('.main-nav');
            nav.classList.toggle('active');
            
            const button = document.querySelector('.mobile-menu-toggle');
            if (nav.classList.contains('active')) {
                button.innerHTML = '<i class="fas fa-times"></i> Close Menu';
            } else {
                button.innerHTML = '<i class="fas fa-bars"></i> Menu';
            }
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.querySelector('.main-nav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!nav.contains(event.target) && !toggle.contains(event.target) && nav.classList.contains('active')) {
                nav.classList.remove('active');
                toggle.innerHTML = '<i class="fas fa-bars"></i> Menu';
            }
        });

        // Close mobile menu when window is resized to larger screen
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const nav = document.querySelector('.main-nav');
                const toggle = document.querySelector('.mobile-menu-toggle');
                if (nav.classList.contains('active')) {
                    nav.classList.remove('active');
                    toggle.innerHTML = '<i class="fas fa-bars"></i> Menu';
                }
            }
        });

        // Add touch event for better mobile experience
        document.addEventListener('touchstart', function() {}, {passive: true});
        
        // Add keyboard navigation support
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const nav = document.querySelector('.main-nav');
                const toggle = document.querySelector('.mobile-menu-toggle');
                if (nav.classList.contains('active')) {
                    nav.classList.remove('active');
                    toggle.innerHTML = '<i class="fas fa-bars"></i> Menu';
                    toggle.focus();
                }
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add hover effects for cards
        document.querySelectorAll('.dashboard-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
            });
        });
        
        // Session time warning
        let sessionWarningShown = false;
        const sessionTimeout = 25 * 60 * 1000; // 25 minutes
        
        setTimeout(() => {
            if (!sessionWarningShown) {
                sessionWarningShown = true;
                if (confirm('Your session will expire in 5 minutes. Would you like to extend it?')) {
                    // Refresh the page to extend session
                    location.reload();
                }
            }
        }, sessionTimeout);
    </script>
</body>
</html>