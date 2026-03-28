<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFJS RESULTS PORTAL</title>
    <link rel="icon" href="image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="csss/style.css" type="text/css">
    <link rel="stylesheet" href="csss/rfjs-theme.css" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Flamenco" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.0/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url(../image/blackboard.jpg);
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Navigation - Centered and Responsive */
        .nav-container {
            width: 100%;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .main-nav {
            list-style: none;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            max-width: 1200px;
            margin: 0 auto;
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        .main-nav li {
            margin: 0;
        }

        .main-nav a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            font-family: 'Times New Roman', Times, serif;
            text-align: center;
            white-space: nowrap;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .main-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            margin: 0 auto 15px auto;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        /* Transparent Result Card */
        .result-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        .result-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8), rgba(240, 147, 251, 0.8));
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .result-header {
            margin-bottom: 40px;
        }

        .result-header h1 {
            color: white;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        .result-header i {
            color: #fff;
            font-size: 36px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .result-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        /* Form Styles */
        .result-form {
            width: 100%;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: white;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            font-family: 'Times New Roman', Times, serif;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 18px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .form-input, .form-select {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            color: white;
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-family: 'Times New Roman', Times, serif;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.15);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-family: 'Times New Roman', Times, serif;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            background: linear-gradient(135deg, rgba(118, 75, 162, 0.9), rgba(102, 126, 234, 0.9));
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 20px;
            color: white;
            background: rgba(0, 0, 0, 0.2);
            font-size: 14px;
            backdrop-filter: blur(5px);
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-nav {
                gap: 12px;
            }
            
            .main-nav a {
                padding: 10px 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 15px;
            }
            
            .main-nav {
                gap: 10px;
            }
            
            .main-nav a {
                padding: 8px 14px;
                font-size: 13px;
            }
            
            .result-container {
                padding: 40px 30px;
                margin: 20px;
            }
            
            .result-header h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 640px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .main-nav {
                display: none;
                flex-direction: column;
                gap: 8px;
                width: 100%;
            }
            
            .main-nav.active {
                display: flex;
            }
            
            .main-nav a {
                width: 100%;
                max-width: 280px;
                justify-content: center;
                padding: 12px 20px;
            }
            
            .nav-container {
                padding: 15px 10px;
            }
        }

        @media (max-width: 480px) {
            .result-container {
                padding: 30px 20px;
            }
            
            .result-header h1 {
                font-size: 24px;
                flex-direction: column;
                gap: 10px;
            }
            
            .form-input, .form-select {
                padding: 12px 12px 12px 45px;
            }
            
            .main-nav a {
                max-width: 250px;
                padding: 10px 15px;
            }
        }

        @media (max-width: 360px) {
            .result-container {
                padding: 25px 15px;
            }
            
            .result-header h1 {
                font-size: 22px;
            }
            
            .main-nav a {
                max-width: 220px;
                font-size: 12px;
            }
            
            .form-input, .form-select {
                padding: 10px 10px 10px 40px;
                font-size: 14px;
            }
        }

        /* Additional glow effect for better visibility */
        .result-container {
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        
        /* Style for select options */
        .form-select option {
            background: #2c3e50;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="nav-container">
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i> Menu
        </button>
        <ul class="main-nav" animate slideInDown>
            <li><a href="index.php"><i class="fas fa-home"></i> HOME</a></li>
            <li><a href="admin/aboutus.php"><i class="fas fa-info-circle"></i> ABOUT</a></li>
            <li><a href="admin/contactus.php"><i class="fas fa-envelope"></i> CONTACT</a></li>
            <li><a href="login.php"><i class="fas fa-lock"></i> ADMIN LOGIN</a></li>
            <li><a href="https://www.royalfamilyjunior.ac.zw/"><i class="fas fa-graduation-cap"></i>MAIN SITE</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="result-container animated fadeInUp">
            <div style="text-align:center;margin-bottom:clamp(12px,3vw,20px);">
                <img src="image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="140" height="140">
            </div>
            <div class="result-header">
                <h1><i class="fas fa-search"></i> GET YOUR RESULT</h1>
                <p>Enter your admission details to view your results</p>
            </div>
            
            <form class="result-form" method="post" action="result.php">
                <div class="form-group">
                    <label for="rollno">Admission Number</label>
                    <div class="input-with-icon">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="rollno" name="rollno" class="form-input" placeholder="Enter your admission number" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="std">Grade</label>
                    <div class="input-with-icon">
                        <i class="fas fa-graduation-cap"></i>
                        <select id="std" name="std" class="form-select" required>
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
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="term">Term</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <select id="term" name="term" class="form-select" required>
                            <option value="">Select Term</option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                            <option value="all">All Terms (Full Transcript)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="year">Year</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar"></i>
                        <input type="number" id="year" name="year" class="form-input" placeholder="Enter year (e.g., 2026)" required min="2020" max="2030" value="2026">
                    </div>
                </div>
                
                <button type="submit" name="submit" class="submit-btn">
                    <i class="fas fa-check-circle"></i> GET RESULTS
                </button>
            </form>
        </div>
    </main>

    <footer>
        DESIGNED BY CharlzTech Web Developers
    </footer>

    <script>
        function toggleMobileMenu() {
            const nav = document.querySelector('.main-nav');
            nav.classList.toggle('active');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.querySelector('.main-nav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!nav.contains(event.target) && !toggle.contains(event.target)) {
                nav.classList.remove('active');
            }
        });

        // Close mobile menu when window is resized to larger screen
        window.addEventListener('resize', function() {
            if (window.innerWidth > 640) {
                document.querySelector('.main-nav').classList.remove('active');
            }
        });
        
        // Auto-fill current year
        document.addEventListener('DOMContentLoaded', function() {
            const yearInput = document.getElementById('year');
            const currentYear = new Date().getFullYear();
            if (yearInput && !yearInput.value) {
                yearInput.value = currentYear;
            }
            
            // Show/hide term field based on selection
            const termSelect = document.getElementById('term');
            if (termSelect) {
                termSelect.addEventListener('change', function() {
                    const yearField = document.getElementById('year');
                    if (this.value === 'all') {
                        yearField.placeholder = "Enter starting year (e.g., 2024)";
                        yearField.value = "2024";
                    } else {
                        yearField.placeholder = "Enter year (e.g., 2026)";
                        yearField.value = currentYear;
                    }
                });
            }
        });
    </script>
</body>
</html>