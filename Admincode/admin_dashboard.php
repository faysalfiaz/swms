<?php
// 1. INITIALIZE SYSTEM & SESSION
include_once '../classes/Database.php';
include_once '../classes/WasteManager.php';

session_start();

// Initialize Objects
$database = new Database();
$db_connection = $database->getConnection(); 
$app = new WasteManager($db_connection);

// 2. AUTHENTICATION CHECK (Verify if logged in)
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 3. LOGOUT HANDLER
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// 4. ACTION HANDLER (Assign, Clean, Reject)
if (isset($_GET['id']) || (isset($_GET['action']) && $_GET['action'] == 'reject')) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $action = $_GET['action'] ?? ''; 
    $remark = "Action performed by admin"; 

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_remark'])) {
        $remark = mysqli_real_escape_string($db_connection, $_POST['admin_remark']);
        $action = 'reject'; 
    }

    $new_status = "";
    if ($action == 'assign') $new_status = 'Assigned';
    elseif ($action == 'clean') $new_status = 'Cleaned';
    elseif ($action == 'reject') $new_status = 'Rejected';

    if ($new_status != "" && $id > 0) {
        // Calling updateReportStatus through the $app object (WasteManager)
        if ($app->updateReportStatus($id, $new_status, $remark)) {
            header("Location: admin_dashboard.php?success=1");
            exit();
        }
    }
}

// 5. ANALYTICS QUERIES (Fixed Undefined Variable Logic)
$total_res = $db_connection->query("SELECT COUNT(*) as t FROM reports");
$total_all = ($total_res) ? $total_res->fetch_assoc()['t'] : 0;

$pending_res = $db_connection->query("SELECT COUNT(*) as t FROM reports WHERE status='Pending'");
$pending = ($pending_res) ? $pending_res->fetch_assoc()['t'] : 0;

$cleaned_res = $db_connection->query("SELECT COUNT(*) as t FROM reports WHERE status='Cleaned'");
$cleaned = ($cleaned_res) ? $cleaned_res->fetch_assoc()['t'] : 0;

$solve_rate = ($total_all > 0) ? round(($cleaned / $total_all) * 100) : 0;

$active_admins = 1; 

// Fetch all reports for the table
$res = $app->getAllReports();
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
            <a href="?logout=true" class="group flex items-center gap-4 bg-red-500/5 hover:bg-red-500/10 border border-red-500/20 px-8 py-4 rounded-2xl transition-all">
                <span class="text-red-500 text-[10px] font-black uppercase tracking-widest">Terminate Session</span>
                <i class="fas fa-power-off text-red-500 group-hover:rotate-90 transition-transform"></i>
            </a>
        </header>

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

        <div class="glass-panel rounded-[3.5rem] overflow-hidden border-white/5">
            <div class="p-8 border-b border-white/5 bg-white/[0.01] flex justify-between items-center">
                <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Live Field Operations</h3>
                <i class="fas fa-compact-disc animate-spin text-emerald-500/20 text-xs"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white/[0.02] text-[10px] uppercase text-slate-500 font-black tracking-widest">
                        <tr>
                            <th class="p-8">Satellite Imagery</th>
                            <th>Deployment Site</th>
                            <th>Current State</th>
                            <th class="text-right p-8">Operational Command</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php while($row = $res->fetch_assoc()): 
                            $imgPath = "../Usercode/uploads/" . $row['image'];
                        ?>
                        <tr class="group hover:bg-emerald-500/[0.02] transition-all">
                            <td class="p-8">
                                <div class="relative w-28 h-20 overflow-hidden rounded-2xl border border-white/10 group-hover:border-emerald-500/40 transition-all">
                                    <img src="<?php echo $imgPath; ?>" 
                                         onerror="this.src='https://via.placeholder.com/300x200?text=DATA+MISSING'"
                                         onclick="showFullImg(this.src)" 
                                         class="w-full h-full object-cover cursor-zoom-in group-hover:scale-110 transition-transform duration-700">
                                </div>
                            </td>
                            <td>
                                <p class="font-black text-base uppercase italic tracking-tighter"><?php echo $row['location']; ?></p>
                               <p class="text-base text-white mt-1 font-bold leading-relaxed max-w-xs normal-case"><?php echo ucfirst(strtolower($row['description'])); ?></p>
                            </td>
                            <td>
                                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-white/5 border border-white/5">
                                    <?php 
                                        $dotColor = 'bg-yellow-500 animate-pulse';
                                        $textColor = 'text-yellow-500';
                                        if($row['status'] == 'Cleaned') { $dotColor = 'bg-emerald-500'; $textColor = 'text-emerald-500'; }
                                        if($row['status'] == 'Rejected') { $dotColor = 'bg-red-500'; $textColor = 'text-red-500'; }
                                        if($row['status'] == 'Assigned') { $dotColor = 'bg-blue-500'; $textColor = 'text-blue-500'; }
                                    ?>
                                    <span class="h-1.5 w-1.5 rounded-full <?php echo $dotColor; ?>"></span>
                                    <span class="text-[9px] font-black uppercase tracking-widest <?php echo $textColor; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="p-8 text-right">
                                <?php if($row['status'] == 'Pending'): ?>
                                    <form action="admin_dashboard.php?action=reject&id=<?php echo $row['id']; ?>" method="POST" class="flex flex-col items-end gap-2">
                                        <textarea name="admin_remark" required placeholder="Reason for rejection..." 
                                                  class="bg-white/5 border border-white/10 rounded-lg p-2 text-[10px] text-white w-52 outline-none focus:border-red-500/50 placeholder:text-slate-600 transition-all"></textarea>
                                        
                                        <div class="flex gap-2">
                                            <button type="submit" class="bg-red-500/10 text-red-500 border border-red-500/20 px-4 py-3 rounded-xl text-[9px] font-black uppercase tracking-tighter hover:bg-red-500 hover:text-white transition-all">
                                                Reject Report
                                            </button>
                                            <a href="?action=assign&id=<?php echo $row['id']; ?>" 
                                               class="bg-white text-black px-6 py-3 rounded-xl text-[9px] font-black uppercase tracking-tighter hover:bg-emerald-500 hover:text-white transition-all">
                                                Initiate Assignment
                                            </a>
                                        </div>
                                    </form>

                                <?php elseif($row['status'] == 'Assigned'): ?>
                                    <a href="?action=clean&id=<?php echo $row['id']; ?>" 
                                       class="inline-block bg-emerald-600 text-white px-8 py-3 rounded-xl text-[9px] font-black uppercase tracking-tighter hover:bg-emerald-400 transition-all">
                                        Finalize Cleanup
                                    </a>

                                <?php elseif($row['status'] == 'Rejected'): ?>
                                    <div class="flex flex-col items-end">
                                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-red-500 italic">Report Rejected</span>
                                        <p class="text-[10px] mt-1 text-slate-400 font-bold uppercase italic bg-white/5 px-3 py-1 rounded-lg border border-white/5">
                                            "<?php echo htmlspecialchars($row['admin_remark'] ?? 'No reason given'); ?>"
                                        </p>
                                    </div>

                                <?php else: ?>
                                    <div class="flex flex-col items-end">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[9px] font-black uppercase tracking-[0.2em] italic opacity-40">Archive Ready</span>
                                            <span class="text-[8px] mt-1 text-emerald-500 font-bold uppercase opacity-40">Verified Complete</span>
                                        </div>
                                        
                                        <?php 
                                        $reportId = $row['id'];
                                        // '@' চিহ্নটি ব্যবহার করা হয়েছে যাতে টেবিল না থাকলেও এরর না দেয়
                                        $ratingRes = @$db_connection->query("SELECT * FROM ratings WHERE report_id = $reportId");
                                        if ($ratingRes && $ratingRes->num_rows > 0): 
                                            $ratingData = $ratingRes->fetch_assoc();
                                        ?>
                                            <div class="mt-4 pt-3 border-t border-white/5 flex flex-col items-end">
                                                <div class="flex gap-1 text-[10px] text-yellow-500">
                                                    <?php 
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo ($i <= $ratingData['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star opacity-20"></i>';
                                                    }
                                                    ?>
                                                </div>
                                                <p class="text-[9px] mt-1.5 text-slate-400 font-bold uppercase italic bg-white/[0.03] px-3 py-1.5 rounded-lg border border-white/5">
                                                    "<?php echo htmlspecialchars($ratingData['feedback'] ?? ''); ?>"
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="imgViewer" class="modal items-center justify-center p-8 lg:p-20" onclick="this.style.display='none'">
        <div class="relative w-full h-full flex items-center justify-center">
            <img id="fullImg" class="max-h-full max-w-full rounded-[3rem] border border-white/20 shadow-2xl">
            <div class="absolute top-0 right-0 p-10"><i class="fas fa-times text-white/20 text-3xl hover:text-white cursor-pointer"></i></div>
        </div>
    </div>

    <script>
        function showFullImg(src) {
            const viewer = document.getElementById('imgViewer');
            const img = document.getElementById('fullImg');
            viewer.style.display = "flex";
            img.src = src;
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") document.getElementById('imgViewer').style.display = 'none';
        });
    </script>
</body>
</html>