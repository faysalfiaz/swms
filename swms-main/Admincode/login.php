<?php 
session_start();

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Secure Static Credentials
    if ($email === "admin@swms.com" && $password === "admin123") {
        // FIXED: Added 'admin_id' to match your dashboard's authentication check
        $_SESSION['admin_id'] = 1; 
        $_SESSION['admin_verified'] = true;
        
        header("Location: admin_dashboard.php");
        exit();
    } else { 
        $error = "AUTHENTICATION FAILURE: INVALID ACCESS KEY"; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWMS | Secure Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,800;1,800&display=swap');
        
        body { 
            background: #020617; 
            color: white; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow: hidden;
        }

        /* Graphic Design Elements */
        .bg-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
            filter: blur(80px);
            z-index: -1;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(25px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .input-accent:focus {
            border-color: #10b981;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
        }

        /* Animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .float-img { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative p-4">

    <div class="bg-glow top-0 left-0"></div>
    <div class="bg-glow bottom-0 right-0"></div>

    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-0 overflow-hidden glass-card rounded-[3rem]">
        
        <div class="hidden lg:flex flex-col items-center justify-center p-12 bg-emerald-500/5 border-r border-white/5 relative">
            <div class="absolute top-10 left-10 opacity-20">
                <i class="fas fa-microchip text-6xl text-emerald-500"></i>
            </div>
            
            <div class="relative">
                <div class="absolute -inset-4 bg-emerald-500/20 rounded-full blur-3xl opacity-30"></div>
                <img src="https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&q=80&w=800" 
                     alt="Waste Management Tech" 
                     class="w-80 h-80 object-cover rounded-[3rem] float-img shadow-2xl border border-white/10">
            </div>

            <div class="mt-12 text-center">
                <h1 class="text-3xl font-black italic uppercase tracking-tighter">System <span class="text-emerald-500">Integrity</span></h1>
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.4em] mt-4 max-w-xs leading-loose">
                    Advanced waste monitoring and field operations console.
                </p>
            </div>
            
            <div class="absolute bottom-10 flex gap-6 text-emerald-500/30 text-xl">
                <i class="fas fa-satellite"></i>
                <i class="fas fa-shield-virus"></i>
                <i class="fas fa-server"></i>
            </div>
        </div>

        <div class="p-10 md:p-20 flex flex-col justify-center">
            <div class="mb-10">
                <div class="w-12 h-1 bg-emerald-500 mb-6"></div>
                <h2 class="text-4xl font-black uppercase italic tracking-tighter">Secure <span class="text-emerald-500">Login</span></h2>
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-[0.4em] mt-2">Authorized Personnel Only</p>
            </div>

            <form method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Operator ID</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="email" name="email" required placeholder="admin@swms.com" 
                               class="w-full bg-white/5 border border-white/10 p-5 pl-12 rounded-2xl outline-none text-sm input-accent transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Security Token</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="password" name="password" required placeholder="••••••••" 
                               class="w-full bg-white/5 border border-white/10 p-5 pl-12 rounded-2xl outline-none text-sm input-accent transition-all">
                    </div>
                </div>

                <?php if($error): ?>
                    <div class="bg-red-500/10 border border-red-500/20 p-4 rounded-xl flex items-center gap-3">
                        <i class="fas fa-triangle-exclamation text-red-500 text-xs"></i>
                        <p class="text-red-500 text-[10px] font-black uppercase tracking-tighter"><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-black py-5 rounded-2xl font-black uppercase tracking-widest text-[11px] transition-all transform active:scale-95 shadow-xl shadow-emerald-500/10">
                    Execute Authentication
                </button>
            </form>

            <footer class="mt-12 pt-8 border-t border-white/5 flex justify-between items-center">
                <span class="text-[8px] font-bold text-slate-600 uppercase tracking-widest">© 2026 SWMS Ops.</span>
                <div class="flex gap-4 opacity-30">
                    <i class="fab fa-bluetooth-b text-xs"></i>
                    <i class="fas fa-wifi text-xs"></i>
                </div>
            </footer>
        </div>
    </div>

</body>
</html>