<?php
// 1. INITIALIZE SESSION FIRST
session_start();

if (!isset($_SESSION['initiated'])) {
    $_SESSION['initiated'] = time();
} elseif (time() - $_SESSION['initiated'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = time();
}

// 2. INCLUDE & DATABASE INIT
include_once '../classes/Database.php';
include_once '../classes/WasteManager.php';

$database = new Database();
$db_connection = $database->getConnection(); 
$app = new WasteManager($db_connection);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. AUTHENTICATION CHECK
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 4. LOGOUT HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_action'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
}

// 5. ACTION HANDLER (Assign, Clean, Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid security token.");
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $action = $_POST['action'] ?? ''; 

    if ($id > 0) {
        if ($action === 'assign' && isset($_POST['worker_id'])) {
            $worker_id = intval($_POST['worker_id']);
            
            $worker_check = $db_connection->prepare("SELECT id FROM users WHERE id = ? AND role = 'worker'");
            if ($worker_check) {
                $worker_check->bind_param("i", $worker_id);
                $worker_check->execute();
                $worker_exists = $worker_check->get_result()->num_rows > 0;
                $worker_check->close();

                if ($worker_exists) {
                    $stmt = $db_connection->prepare("UPDATE reports SET status = 'Assigned', assigned_worker_id = ?, admin_remark = 'Assigned to worker' WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ii", $worker_id, $id);
                        $stmt->execute();
                        $stmt->close();
                        header("Location: admin_dashboard.php?success=1");
                        exit();
                    }
                }
            }
        } 
        elseif ($action === 'clean') {
            $stmt = $db_connection->prepare("UPDATE reports SET status = 'Cleaned', admin_remark = 'Cleaned by worker/admin' WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                header("Location: admin_dashboard.php?success=1");
                exit();
            }
        } 
        elseif ($action === 'reject') {
            $remark = !empty($_POST['admin_remark']) ? trim($_POST['admin_remark']) : "Rejected by admin";
            $stmt = $db_connection->prepare("UPDATE reports SET status = 'Rejected', admin_remark = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $remark, $id);
                $stmt->execute();
                $stmt->close();
                header("Location: admin_dashboard.php?success=1");
                exit();
            }
        }
    }
}

// 6. ANALYTICS QUERIES
$stats_query = $db_connection->query("
    SELECT 
        COUNT(*) as total_all,
        SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN LOWER(status) = 'cleaned' THEN 1 ELSE 0 END) as cleaned
    FROM reports
");
$stats = $stats_query ? $stats_query->fetch_assoc() : ['total_all' => 0, 'pending' => 0, 'cleaned' => 0];

$total_all  = (int)($stats['total_all'] ?? 0);
$pending    = (int)($stats['pending'] ?? 0);
$cleaned    = (int)($stats['cleaned'] ?? 0);
$solve_rate = ($total_all > 0) ? round(($cleaned / $total_all) * 100) : 0;
$active_admins = 1; 

// Fetch workers
$workers_map = [];
$workers_res = $db_connection->query("SELECT id, fullname FROM users WHERE role = 'worker'");
if ($workers_res) {
    while ($w = $workers_res->fetch_assoc()) {
        $workers_map[(int)$w['id']] = $w['fullname'];
    }
    $workers_res->free();
}

// CORRECTED QUERY: JOINING WITH THE 'feedback' TABLE & 'comments' COLUMN
$reports = [];
$query = "SELECT r.id as report_primary_id, r.*, 
                 IFNULL(fb.rating, 0) as user_rating, 
                 IFNULL(fb.comments, '') as user_feedback 
          FROM reports r 
          LEFT JOIN (
              SELECT report_id, rating, comments 
              FROM feedback 
              WHERE id IN (SELECT MAX(id) FROM feedback GROUP BY report_id)
          ) fb ON r.id = fb.report_id 
          ORDER BY r.id DESC";

$res = $db_connection->query($query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $reports[] = $row;
    }
}

$db_connection->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWMS | Mission Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,800;1,800&display=swap');
        
        body { 
            background: #020617; 
            color: white; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-image: radial-gradient(circle at 50% -20%, #10b98120, transparent);
        }

        .glass-panel { 
            background: rgba(255, 255, 255, 0.03); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            backdrop-filter: blur(12px); 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-panel:hover {
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }

        .status-pulse { position: relative; }
        .status-pulse::after {
            content: ''; position: absolute; width: 100%; height: 100%; background: inherit;
            border-radius: inherit; animation: pulse 2s infinite; opacity: 0.4;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(2.5); opacity: 0; }
        }

        .modal { 
            display: none; position: fixed; z-index: 100; left: 0; top: 0; 
            width: 100%; height: 100%; background: rgba(2, 6, 23, 0.98); 
            backdrop-filter: blur(20px);
        }
    </style>
</head>
<body class="p-8 lg:p-12">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-16 gap-6">
            <div>
                <h2 class="text-5xl font-black uppercase italic tracking-tighter leading-none">
                    Mission <span class="text-emerald-500">Control</span>
                </h2>
                <div class="flex items-center gap-3 mt-4">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 status-pulse"></span>
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.5em]">System Authorization: Active</p>
                </div>
            </div>

            <form action="admin_dashboard.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="logout_action" value="1">
                <button type="submit" class="group flex items-center gap-4 bg-red-500/5 hover:bg-red-500/10 border border-red-500/20 px-8 py-4 rounded-2xl transition-all">
                    <span class="text-red-500 text-[10px] font-black uppercase tracking-widest">Terminate Session</span>
                    <i class="fas fa-power-off text-red-500 group-hover:rotate-90 transition-transform"></i>
                </button>
            </form>
        </header>

        <!-- KPI Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-16">
            <div class="glass-panel p-8 rounded-[2.5rem]">
                <p class="text-slate-500 text-[10px] font-black uppercase mb-3 tracking-widest">Incoming Reports</p>
                <h4 class="text-5xl font-black italic"><?php echo $total_all; ?></h4>
            </div>
            <div class="glass-panel p-8 rounded-[2.5rem] border-l-4 border-yellow-500/50">
                <p class="text-yellow-500/70 text-[10px] font-black uppercase mb-3 tracking-widest">Active Alerts</p>
                <h4 class="text-5xl font-black italic"><?php echo $pending; ?></h4>
            </div>
            <div class="glass-panel p-8 rounded-[2.5rem] bg-emerald-500/[0.03] border-emerald-500/20">
                <p class="text-emerald-500 text-[10px] font-black uppercase mb-3 tracking-widest">Success Rate</p>
                <h4 class="text-5xl font-black italic text-emerald-500"><?php echo $solve_rate; ?><span class="text-2xl ml-1">%</span></h4>
            </div>
            <div class="glass-panel p-8 rounded-[2.5rem] border-l-4 border-blue-500/50">
                <p class="text-blue-500/70 text-[10px] font-black uppercase mb-3 tracking-widest">Resolved</p>
                <h4 class="text-5xl font-black italic"><?php echo $cleaned; ?></h4>
            </div>
            <div class="glass-panel p-8 rounded-[2.5rem] border-l-4 border-emerald-500/50">
                <div class="flex justify-between items-start">
                    <p class="text-slate-500 text-[10px] font-black uppercase mb-3 tracking-widest">Connected</p>
                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse mt-1"></span>
                </div>
                <h4 class="text-5xl font-black italic"><?php echo $active_admins; ?></h4>
            </div>
        </div>

        <!-- Operations Data Table -->
        <div class="glass-panel rounded-[3.5rem] overflow-hidden border-white/5">
            <div class="p-8 border-b border-white/5 bg-white/[0.01] flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-3">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Live Field Operations</h3>
                    <i class="fas fa-compact-disc animate-spin text-emerald-500/20 text-xs"></i>
                </div>
                <!-- Filter Controls -->
                <div class="flex gap-2">
                    <button onclick="filterTable('all')" class="px-3 py-1.5 rounded-lg bg-white/10 text-[9px] font-bold uppercase tracking-wider text-white hover:bg-emerald-500/20 transition-all">All</button>
                    <button onclick="filterTable('pending')" class="px-3 py-1.5 rounded-lg bg-white/5 text-[9px] font-bold uppercase tracking-wider text-yellow-500 hover:bg-yellow-500/20 transition-all">Pending</button>
                    <button onclick="filterTable('assigned')" class="px-3 py-1.5 rounded-lg bg-white/5 text-[9px] font-bold uppercase tracking-wider text-blue-400 hover:bg-blue-500/20 transition-all">Assigned</button>
                    <button onclick="filterTable('cleaned')" class="px-3 py-1.5 rounded-lg bg-white/5 text-[9px] font-bold uppercase tracking-wider text-emerald-400 hover:bg-emerald-500/20 transition-all">Cleaned</button>
                    <button onclick="filterTable('rejected')" class="px-3 py-1.5 rounded-lg bg-white/5 text-[9px] font-bold uppercase tracking-wider text-red-400 hover:bg-red-500/20 transition-all">Rejected</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="reportsTable">
                    <thead class="bg-white/[0.02] text-[10px] uppercase text-slate-500 font-black tracking-widest">
                        <tr>
                            <th class="p-8">Satellite Imagery</th>
                            <th>Deployment Site</th>
                            <th>Current State</th>
                            <th class="text-right p-8">Operational Command</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $row): 
                                $imageFileName = basename($row['image'] ?? '');
                                $imgPath = "../Usercode/uploads/" . htmlspecialchars($imageFileName, ENT_QUOTES, 'UTF-8');
                                $reportId = (int)($row['report_primary_id'] ?? $row['id']);
                                $assignedWorkerId = isset($row['assigned_worker_id']) ? (int)$row['assigned_worker_id'] : null;
                                $status = strtolower(trim($row['status'] ?? ''));
                            ?>
                            <tr class="report-row group hover:bg-emerald-500/[0.02] transition-all" data-status="<?php echo $status; ?>">
                                <td class="p-8">
                                    <div class="relative w-28 h-20 overflow-hidden rounded-2xl border border-white/10 group-hover:border-emerald-500/40 transition-all">
                                        <img src="<?php echo $imgPath; ?>" 
                                             onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200?text=DATA+MISSING';"
                                             onclick="showFullImg(this.src)" 
                                             alt="Report Image"
                                             class="w-full h-full object-cover cursor-zoom-in group-hover:scale-110 transition-transform duration-700">
                                    </div>
                                </td>
                                <td>
                                    <p class="font-black text-base uppercase italic tracking-tighter"><?php echo htmlspecialchars($row['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="text-xs text-slate-400 mt-1 font-bold leading-relaxed max-w-xs uppercase italic"><?php echo htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                    
                                    <?php if (!empty($assignedWorkerId) && isset($workers_map[$assignedWorkerId])): ?>
                                        <p class="text-[10px] text-blue-400 font-bold uppercase mt-2 flex items-center gap-1">
                                            <i class="fas fa-user-check"></i> Assigned Worker: <?php echo htmlspecialchars($workers_map[$assignedWorkerId], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-white/5 border border-white/5">
                                        <?php 
                                            $dotColor = 'bg-yellow-500 animate-pulse';
                                            $textColor = 'text-yellow-500';
                                            if($status === 'cleaned') { $dotColor = 'bg-emerald-500'; $textColor = 'text-emerald-500'; }
                                            elseif($status === 'rejected') { $dotColor = 'bg-red-500'; $textColor = 'text-red-500'; }
                                            elseif($status === 'assigned') { $dotColor = 'bg-blue-500 animate-pulse'; $textColor = 'text-blue-500'; }
                                        ?>
                                        <span class="h-1.5 w-1.5 rounded-full <?php echo $dotColor; ?>"></span>
                                        <span class="text-[9px] font-black uppercase tracking-widest <?php echo $textColor; ?>">
                                            <?php echo htmlspecialchars(ucfirst($row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="p-8 text-right">
                                    <?php if($status === 'pending'): ?>
                                        <div class="flex flex-col items-end gap-3">
                                            <form action="admin_dashboard.php" method="POST" class="flex flex-col items-end gap-2">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="id" value="<?php echo $reportId; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <textarea name="admin_remark" required placeholder="Reason for rejection..." 
                                                          class="bg-white/5 border border-white/10 rounded-lg p-2 text-[10px] text-white w-52 outline-none focus:border-red-500/50 placeholder:text-slate-600 transition-all"></textarea>
                                                <button type="submit" class="bg-red-500/10 text-red-500 border border-red-500/20 px-4 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-tighter hover:bg-red-500 hover:text-white transition-all">
                                                    Reject Report
                                                </button>
                                            </form>

                                            <form action="admin_dashboard.php" method="POST" class="flex flex-col items-end gap-2 border-t border-white/5 pt-3">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="id" value="<?php echo $reportId; ?>">
                                                <input type="hidden" name="action" value="assign">
                                                
                                                <select name="worker_id" required class="bg-slate-900 border border-white/20 rounded-xl p-2.5 text-[10px] text-white w-52 outline-none focus:border-emerald-500">
                                                    <option value="">-- Select Worker --</option>
                                                    <?php if(!empty($workers_map)): ?>
                                                        <?php foreach($workers_map as $w_id => $w_name): ?>
                                                            <option value="<?php echo $w_id; ?>">
                                                                [ID: <?php echo $w_id; ?>] <?php echo htmlspecialchars($w_name, ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option value="" disabled>No workers available</option>
                                                    <?php endif; ?>
                                                </select>

                                                <button type="submit" class="bg-white text-black px-6 py-3 rounded-xl text-[9px] font-black uppercase tracking-tighter hover:bg-emerald-500 hover:text-white transition-all">
                                                    Initiate Assignment
                                                </button>
                                            </form>
                                        </div>

                                    <?php elseif($status === 'assigned'): ?>
                                        <div class="flex flex-col items-end gap-2">
                                            <p class="text-[10px] text-blue-400 font-bold uppercase mb-1">
                                                Worker Assigned & On-Site
                                            </p>
                                            <form action="admin_dashboard.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="id" value="<?php echo $reportId; ?>">
                                                <input type="hidden" name="action" value="clean">
                                                <button type="submit" class="bg-emerald-500 text-slate-950 font-black px-8 py-3.5 rounded-xl text-[10px] uppercase tracking-wider hover:bg-emerald-400 transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                                                    <i class="fas fa-check-double"></i>
                                                    Finalize Cleanup
                                                </button>
                                            </form>
                                        </div>

                                    <?php elseif($status === 'rejected'): ?>
                                        <div class="flex flex-col items-end">
                                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-red-500 italic">Report Rejected</span>
                                            <p class="text-[10px] mt-1 text-slate-400 font-bold uppercase italic bg-white/5 px-3 py-1 rounded-lg border border-white/5">
                                                "<?php echo htmlspecialchars($row['admin_remark'] ?? 'No reason given', ENT_QUOTES, 'UTF-8'); ?>"
                                            </p>
                                        </div>

                                    <?php else: ?>
                                        <!-- CLEANED REPORT WITH RATING AND FEEDBACK -->
                                        <div class="flex flex-col items-end gap-1">
                                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">ARCHIVE READY</span>
                                            <span class="text-[8px] text-emerald-500 font-bold uppercase tracking-widest opacity-80 mb-1">VERIFIED & RESOLVED</span>
                                            
                                            <?php 
                                                $score = isset($row['user_rating']) ? (int)$row['user_rating'] : 0;
                                                $feedbackText = isset($row['user_feedback']) ? trim($row['user_feedback']) : '';
                                            ?>

                                            <!-- Dynamic Star Rating Output -->
                                            <div class="flex gap-1 text-xs my-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $score): ?>
                                                        <i class="fas fa-star text-amber-400"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-slate-700"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>

                                            <!-- Dynamic Feedback Text Box Output -->
                                            <?php if (!empty($feedbackText)): ?>
                                                <div class="bg-slate-900/90 border border-white/10 px-3 py-1 rounded-lg text-right max-w-[180px]">
                                                    <p class="text-[10px] text-slate-300 font-semibold italic tracking-wide break-words">
                                                        "<?php echo htmlspecialchars($feedbackText, ENT_QUOTES, 'UTF-8'); ?>"
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-500 uppercase text-xs tracking-widest font-bold">
                                    No reports found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Viewer -->
    <div id="imgViewer" class="modal items-center justify-center p-8 lg:p-20" onclick="closeImgModal()">
        <div class="relative w-full h-full flex items-center justify-center" onclick="event.stopPropagation()">
            <img id="fullImg" class="max-h-full max-w-full rounded-[3rem] border border-white/20 shadow-2xl" alt="Full imagery preview">
            <button class="absolute top-0 right-0 p-10 focus:outline-none" onclick="closeImgModal()">
                <i class="fas fa-times text-white/20 text-3xl hover:text-white cursor-pointer transition-colors"></i>
            </button>
        </div>
    </div>

    <script>
        function showFullImg(src) {
            const viewer = document.getElementById('imgViewer');
            const img = document.getElementById('fullImg');
            viewer.style.display = "flex";
            img.src = src;
        }

        function closeImgModal() {
            document.getElementById('imgViewer').style.display = 'none';
        }

        function filterTable(status) {
            const rows = document.querySelectorAll('.report-row');
            rows.forEach(row => {
                if (status === 'all' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") closeImgModal();
        });
    </script>
</body>
</html>