<?php
include_once '../classes/Database.php'; 
include_once '../classes/WasteManager.php';

$database = new Database();
$db_connection = $database->getConnection();
$app = new WasteManager($db_connection); 

$categories_result = $db_connection->query("SELECT * FROM waste_categories");
?>
<!DOCTYPE html> 
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Smart Waste Management System | Citizen Portal</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
<style>
.hidden-section { display: none !important; }

body {
  background: linear-gradient(-45deg, #f8fafc, #f1f5f9, #ecfdf5, #f0fdf4);
  background-size: 400% 400%;
  animation: gradientBG 15s ease infinite;
  min-height: 100vh;
  color: #334155;
  scroll-behavior: smooth;
}

@keyframes gradientBG {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.glass-card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(16, 185, 129, 0.2);
  box-shadow: 0 10px 30px -5px rgba(16, 185, 129, 0.1);
  transition: all 0.3s ease;
}

.glass-card:hover { 
  box-shadow: 0 15px 35px -5px rgba(16, 185, 129, 0.2); 
  transform: translateY(-2px); 
}

.input-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

.input-box:focus { 
  border-color: #10b981; 
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15); 
  outline: none;
}

.gradient-green {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
  color: white;
}

.gradient-green:hover { 
  box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); 
  filter: brightness(1.05); 
}

::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-thumb { background: #10b981; border-radius: 10px; }
</style>
</head>
<body class="font-sans">

<nav class="sticky top-0 z-50 glass-card px-8 py-4 flex justify-between items-center border-b border-emerald-100">
  <div class="flex items-center gap-3">
    <div class="bg-emerald-600 p-2 rounded-xl text-white shadow-lg">
      <i class="fas fa-recycle animate-pulse"></i>
    </div>
    <span class="font-black text-lg text-slate-800 tracking-tighter uppercase italic">
      Smart <span class="text-emerald-600"> Waste Management </span> System
    </span>
  </div>
  <div class="flex items-center gap-6">
    <a href="#about-section" class="hidden md:block text-[10px] font-black uppercase text-slate-500 hover:text-emerald-600 transition">About System</a>
    <button id="logout-btn" onclick="logoutUser()" class="hidden-section px-5 py-2 bg-red-50 text-red-600 border border-red-100 rounded-full font-bold text-[10px] hover:bg-red-600 hover:text-white transition-all uppercase">
      Logout <i class="fas fa-sign-out-alt ml-1"></i>
    </button>
  </div>
</nav>

<div id="auth-page" class="min-h-screen flex items-center justify-center p-6">
  <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
    <div class="space-y-8">
        <div class="inline-block px-4 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold uppercase tracking-widest">
            🌱 Smart City Initiative
        </div>
        <h1 class="text-6xl font-black text-slate-800 leading-tight">
            Smart Waste <br><span class="text-emerald-600">Management System</span>
        </h1>
        <p class="text-slate-600 text-lg">Report urban pollution in real-time. Our system connects your reports directly to collection teams.</p>
    </div>

    <div class="glass-card rounded-[3rem] p-10 lg:p-12 border-white shadow-xl">
      <form id="register-form" class="space-y-5" onsubmit="return completeRegistration(event)">
        <h2 class="text-3xl font-black text-slate-800 uppercase italic">Create Account</h2>
        <p class="text-emerald-600 text-[10px] font-black tracking-widest uppercase">Registration required for new users</p>
        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Full Name</label>
            <input type="text" id="reg-name" placeholder="Enter your name" class="input-box w-full p-4 rounded-2xl outline-none" required/>
        </div>
        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Email Address</label>
            <input type="email" id="reg-email" placeholder="email@example.com" class="input-box w-full p-4 rounded-2xl outline-none" required/>
        </div>
        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Password</label>
            <input type="password" id="reg-pass" placeholder="Min 8 characters" class="input-box w-full p-4 rounded-2xl outline-none" required/>
        </div>
        <button type="submit" class="w-full gradient-green py-5 rounded-2xl font-black uppercase tracking-widest text-xs">Register & Get Started</button>
        <p class="text-center text-xs text-slate-400 mt-6 font-bold uppercase">
          Already a member? <button type="button" onclick="toggleAuth('login')" class="text-emerald-600 font-black">Login Here</button>
        </p>
      </form>

      <form id="login-form" class="hidden-section space-y-5" onsubmit="return enterDashboard(event)">
        <h2 class="text-3xl font-black text-slate-800 italic uppercase text-center">Citizen Login</h2>
        <p class="text-emerald-600 text-[10px] font-black tracking-widest uppercase text-center">Enter your credentials</p>
        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Email</label>
            <input type="email" id="login-email" placeholder="email@example.com" class="input-box w-full p-5 rounded-2xl outline-none" required/>
        </div>
        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Password</label>
            <input type="password" id="login-pass" placeholder="Password" class="input-box w-full p-5 rounded-2xl outline-none" required/>
        </div>
        <button type="submit" class="w-full gradient-green py-5 rounded-2xl font-black uppercase tracking-widest text-xs">Enter Portal</button>
        <p class="text-center text-xs text-slate-400 mt-6 font-bold uppercase">
          New user? <button type="button" onclick="toggleAuth('register')" class="text-emerald-600 font-black">Register First</button>
        </p>
      </form>
    </div>
  </div>
</div>

<div id="dashboard-page" class="hidden-section">
  <main class="max-w-5xl mx-auto py-16 px-6 grid grid-cols-1 lg:grid-cols-12 gap-10">
    <div class="lg:col-span-7">
      <section class="glass-card rounded-[3rem] p-10 border-emerald-50 shadow-xl">
        <h3 class="font-black text-slate-800 text-xl uppercase tracking-tighter mb-8 flex items-center gap-3 italic">
          <span class="w-3 h-3 bg-emerald-500 rounded-full animate-ping"></span> Report Waste Issue
        </h3>
        <div class="space-y-6">
          <textarea id="desc-box" class="input-box w-full p-5 rounded-3xl outline-none h-32" placeholder="Describe the waste issue..."></textarea>
          <input id="loc-box" type="text" class="input-box w-full p-5 rounded-3xl outline-none" placeholder="Enter Road, Sector, or Landmark"/>
          
          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Select Waste Categories</label>
            <div class="grid grid-cols-2 gap-2 bg-white p-4 rounded-3xl border border-e2e8f0">
              <?php 
              if ($categories_result && $categories_result->num_rows > 0) {
                  while($cat = $categories_result->fetch_assoc()) {
                      echo '<label class="flex items-center gap-2 text-xs text-slate-600 font-bold cursor-pointer p-2 hover:bg-emerald-50 rounded-xl">';
                      echo '<input type="checkbox" name="categories" value="' . $cat['id'] . '" class="category-checkbox accent-emerald-600 w-4 h-4">';
                      echo htmlspecialchars($cat['category_name']);
                      echo '</label>';
                  }
              }
              ?>
            </div>
          </div>

          <div class="relative border-4 border-dashed border-emerald-50 rounded-[2rem] p-10 bg-emerald-50/10 hover:border-emerald-500/50 transition-all text-center group cursor-pointer">
              <input type="file" id="photo-input" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="handlePreview(event)"/>
              <div id="pre-upload" class="space-y-3">
                <i class="fas fa-camera-retro text-4xl text-emerald-200 group-hover:text-emerald-500 transition"></i>
                <p class="text-[10px] text-emerald-400 font-bold uppercase tracking-widest">Click to Attach Photo</p>
              </div>
              <div id="post-upload" class="hidden-section space-y-3">
                <img id="preview-img" class="w-full max-h-64 object-cover rounded-2xl shadow-lg border-4 border-white mb-4 mx-auto"/>
                <button type="button" onclick="clearPhoto()" class="text-red-500 text-[10px] font-bold uppercase underline">Remove Photo</button>
              </div>
          </div>
          <button onclick="submitData()" type="button" class="w-full gradient-green py-6 rounded-3xl font-black text-lg shadow-xl uppercase tracking-widest">Submit Complaint</button>
        </div>
      </section>
    </div>

    <div class="lg:col-span-5 space-y-6">
      <div class="bg-slate-800 rounded-[2rem] p-8 text-white shadow-xl border-b-4 border-emerald-600">
        <h4 class="text-[9px] uppercase font-black tracking-widest text-emerald-400 mb-1">Status</h4>
        <p class="text-lg font-bold italic">System Online</p>
      </div>
      <div id="status-container" class="space-y-4">
      </div>
    </div>
  </main>
</div>

<section id="about-section" class="max-w-6xl mx-auto py-20 px-6">
    <div class="glass-card rounded-[3rem] p-12 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-10 opacity-10">
            <i class="fas fa-leaf text-[150px] text-emerald-600"></i>
        </div>
        <div class="relative z-10">
            <h2 class="text-4xl font-black text-slate-800 uppercase italic mb-6">About Our <span class="text-emerald-600">Initiative</span></h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-4">
                    <div class="w-12 h-12 gradient-green rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-bullseye text-white"></i>
                    </div>
                    <h4 class="font-black text-slate-700 uppercase text-xs tracking-widest">Our Mission</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">To provide a seamless platform for citizens to report urban waste and ensure a cleaner, greener environment for the community.</p>
                </div>
                <div class="space-y-4">
                    <div class="w-12 h-12 gradient-green rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-bolt text-white"></i>
                    </div>
                    <h4 class="font-black text-slate-700 uppercase text-xs tracking-widest">Real-time Connection</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Your reports are instantly sent to our collection teams, reducing the time between complaint and resolution.</p>
                </div>
                <div class="space-y-4">
                    <div class="w-12 h-12 gradient-green rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-star text-white"></i>
                    </div>
                    <h4 class="font-black text-slate-700 uppercase text-xs tracking-widest">Public Accountability</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Rate our services and provide feedback to help us improve. We believe in transparency and community feedback.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function toggleAuth(mode){
  document.getElementById("register-form").classList.toggle("hidden-section", mode==="login");
  document.getElementById("login-form").classList.toggle("hidden-section", mode!=="login");
}

function completeRegistration(e){
  if(e) e.preventDefault();
  let name = document.getElementById("reg-name").value,
      email = document.getElementById("reg-email").value,
      pass = document.getElementById("reg-pass").value;

  fetch("register.php", {
      method: "POST",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: `fullname=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(pass)}`
  }).then(r=>r.text()).then(data => {
      if(data.trim()==="success") { 
          alert("Success! Please login."); 
          toggleAuth("login"); 
      } else {
          alert(data);
      }
  });
  return false;
}

function enterDashboard(e){
  if(e) e.preventDefault();
  let email = document.getElementById("login-email").value,
      pass = document.getElementById("login-pass").value;

  fetch("login.php", {
      method: "POST",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(pass)}`
  }).then(r=>r.text()).then(data => {
      if(data.trim()==="success"){
          document.getElementById("auth-page").classList.add("hidden-section");
          document.getElementById("dashboard-page").classList.remove("hidden-section");
          document.getElementById("logout-btn").classList.remove("hidden-section");
          loadComplaints();
      } else {
          alert("Invalid Credentials");
      }
  });
  return false;
}

function logoutUser(){ location.reload(); }

function handlePreview(event){
  const file = event.target.files[0];
  if(!file) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    document.getElementById("preview-img").src = e.target.result;
    document.getElementById("pre-upload").classList.add("hidden-section");
    document.getElementById("post-upload").classList.remove("hidden-section");
  };
  reader.readAsDataURL(file);
}

function clearPhoto(){
  document.getElementById("photo-input").value = "";
  document.getElementById("pre-upload").classList.remove("hidden-section");
  document.getElementById("post-upload").classList.add("hidden-section");
}

function submitData(){
  let desc = document.getElementById("desc-box").value,
      loc = document.getElementById("loc-box").value,
      image = document.getElementById("photo-input").files[0];

  if(!loc || !image){ 
      alert("Location and Photo are required."); 
      return; 
  }

  let fd = new FormData();
  fd.append("description", desc);
  fd.append("location", loc);
  fd.append("image", image);

  const selectedCategories = document.querySelectorAll('.category-checkbox:checked');
  selectedCategories.forEach(cb => {
      fd.append("categories[]", cb.value);
  });

  fetch("submit_report.php", { method: "POST", body: fd })
  .then(r=>r.text()).then(data => {
      if(data.trim()==="success"){
          alert("Submitted!");
          document.getElementById("desc-box").value = "";
          document.getElementById("loc-box").value = "";
          document.querySelectorAll('.category-checkbox').forEach(cb => cb.checked = false);
          clearPhoto();
          loadComplaints();
      } else {
          alert(data);
      }
  });
}

function loadComplaints(){
  fetch("get_complaints.php").then(r=>r.json()).then(data => {
    const container = document.getElementById("status-container");
    container.innerHTML = "";
    if(!data || !data.length) { 
        container.innerHTML = '<div class="text-center p-14 glass-card rounded-[2.5rem] opacity-60"><p class="text-xs text-emerald-400 font-bold uppercase">No Complaints Yet</p></div>';
        return; 
    }
    data.forEach(c => {
        let div = document.createElement("div");
        div.className = "glass-card p-5 rounded-3xl border-emerald-50 mb-4 relative group";
        
        let rawStatus = c.status ? c.status.trim().toLowerCase() : 'pending';
        let displayStatus = c.status || 'Cleaned';
        
        if (rawStatus === 'cleaned' || rawStatus === 'completed' || rawStatus === 'archive ready' || rawStatus === 'verified complete') {
            displayStatus = 'Cleaned';
        }

        let statusColor = "text-emerald-600 bg-emerald-50 border-emerald-100";
        if(rawStatus === 'pending') {
            statusColor = "text-amber-600 bg-amber-50 border-amber-100";
        } else if(rawStatus === 'assigned') {
            statusColor = "text-blue-600 bg-blue-50 border-blue-100";
        } else if(rawStatus === 'rejected') {
            statusColor = "text-rose-600 bg-rose-50 border-rose-100";
        }

        let cardContent = `
            <div class="flex justify-between items-center mb-2">
                <span class="text-[10px] font-black uppercase ${statusColor} px-2.5 py-1 rounded-full border shadow-sm flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    ${displayStatus}
                </span>
                <div class="flex items-center gap-2">
                  <span class="text-[9px] text-slate-400 font-bold">Ref: #${c.id}</span>
                  <button onclick="deletePost(${c.id})" title="Remove Post" class="text-red-400 hover:text-red-600 text-xs px-2 py-0.5 rounded-full hover:bg-red-50 transition-all">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </div>
            </div>
            <p class="font-bold text-slate-700 text-sm mb-1">${c.description || 'Waste Issue'}</p>
            <p class="text-[10px] text-slate-500 mb-1"><i class="fas fa-map-marker-alt mr-1 text-emerald-500"></i> ${c.location}</p>
        `;

        if (c.categories) {
            cardContent += `<p class="text-[10px] text-emerald-600 font-bold mb-3"><i class="fas fa-tags mr-1"></i> ${c.categories}</p>`;
        } else {
            cardContent += `<div class="mb-3"></div>`;
        }

        cardContent += `<img src="uploads/${c.image}" class="w-full h-32 object-cover rounded-2xl mb-2"/>`;

        if (c.team_name) {
            cardContent += `
                <div class="mt-2 p-3 bg-slate-50 rounded-2xl border border-slate-100 text-[10px]">
                    <p class="font-black text-slate-700 uppercase"><i class="fas fa-users text-emerald-500 mr-1"></i> ${c.team_name}</p>
                    ${c.leader_phone ? `<p class="text-slate-500 font-medium mt-0.5"><i class="fas fa-phone mr-1"></i> ${c.leader_phone}</p>` : ''}
                </div>
            `;
        }

        if (rawStatus === 'cleaned' || rawStatus === 'completed' || rawStatus === 'archive ready' || rawStatus === 'verified complete') {
            if (c.rating && c.rating > 0) {
                cardContent += `
                    <div class="mt-3 pt-3 border-t border-emerald-50">
                        <p class="text-[9px] font-black text-emerald-600 uppercase">Your Rating: ${"⭐".repeat(c.rating)}</p>
                        <p class="text-[10px] text-slate-400 italic mt-1 font-medium">"${c.feedback || ''}"</p>
                    </div>
                `;
            } else {
                cardContent += `
                    <div class="mt-4 p-4 bg-emerald-500/10 rounded-2xl border border-emerald-500/20">
                        <p class="text-[10px] font-black uppercase text-emerald-600 mb-2 tracking-widest text-center">Cleaned! Rate our service</p>
                        <select id="rate-${c.id}" class="w-full bg-white text-[11px] p-2 rounded-xl mb-2 outline-none border border-emerald-100 font-bold">
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Good</option>
                            <option value="3">⭐⭐ Average</option>
                            <option value="2">⭐⭐ Poor</option>
                            <option value="1">⭐ Terrible</option>
                        </select>
                        <textarea id="msg-${c.id}" placeholder="Any feedback?" class="w-full bg-white text-[11px] p-2 rounded-xl mb-2 h-16 outline-none border border-emerald-100"></textarea>
                        <button onclick="submitFeedback(${c.id})" class="w-full bg-emerald-600 text-white py-2 rounded-xl text-[10px] font-black uppercase shadow-lg hover:scale-105 transition-transform">Submit Review</button>
                    </div>
                `;
            }
        }

        div.innerHTML = cardContent;
        container.prepend(div);
    });
  });
}

function deletePost(id) {
    if (!confirm("Are you sure you want to remove this post from your view?")) return;

    let fd = new FormData();
    fd.append("report_id", id);

    fetch("delete_post.php", { method: "POST", body: fd })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === "success") {
            loadComplaints();
        } else {
            alert("Error: " + data);
        }
    });
}

function submitFeedback(id) {
    const rating = document.getElementById(`rate-${id}`).value;
    const feedback = document.getElementById(`msg-${id}`).value;
    const formData = new FormData();
    formData.append('report_id', id);
    formData.append('rating', rating);
    formData.append('feedback', feedback);

    fetch('submit_feedback.php', { method: 'POST', body: formData })
    .then(res => res.text()).then(data => {
        if (data.trim() === "success") { 
            alert("Thank you!"); 
            loadComplaints(); 
        } else {
            alert("Error: " + data);
        }
    });
}
</script>
</body>
</html>