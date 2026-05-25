<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    $r = $_SESSION['user_role'];
    header('Location: ' . APP_URL . ($r==='user'?'/tenant/dashboard.php':($r==='technician'?'/technician/dashboard.php':'/admin/dashboard.php')));
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['password'] ?? '';
    if (!$email || !$pw) { $error = 'Please fill in all fields.'; }
    else {
        $res = login_user($email, $pw);
        if ($res['success']) {
            $r = $res['role'];
            header('Location: ' . APP_URL . ($r==='user'?'/tenant/dashboard.php':($r==='technician'?'/technician/dashboard.php':'/admin/dashboard.php')));
            exit();
        } else { $error = $res['message']; }
    }
}
if (isset($_GET['error'])) $error = htmlspecialchars($_GET['error']);
$success = '';
if (isset($_GET['registered'])) $success = 'Account submitted — awaiting admin approval.';
if (isset($_GET['org_registered'])) $success = 'Organization registered! Sign in with your admin credentials.';
$showModal = ($error || $success || $_SERVER['REQUEST_METHOD']==='POST') ? true : false;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="description" content="FaciliTrack — Digital Facility Maintenance Reporting System. Report issues, track repairs, manage teams.">
<title>FaciliTrack — Digital Facility Maintenance Platform</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo APP_URL; ?>/landing.css">
</head>
<body>

<!-- HEADER -->
<header class="header" id="header">
  <div class="header-inner">
    <a href="#" class="logo-link">
      <div class="logo-mark"><i class="fas fa-tools"></i></div>
      <span class="logo-text">FaciliTrack</span>
    </a>
    <nav class="nav" id="nav">
      <a href="#features">Features</a>
      <a href="#how">How It Works</a>
      <a href="#facilities">Facilities</a>
      <a href="#" onclick="openModal();return false" class="nav-cta"><i class="fas fa-sign-in-alt"></i> Sign In</a>
    </nav>
    <button class="mob-toggle" onclick="document.getElementById('nav').classList.toggle('mob-open')"><i class="fas fa-bars"></i></button>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-badge"><span class="hb-dot"></span> Facility Maintenance Platform</div>
    <h1>From Report to <span>Resolution</span> — Nothing Gets Lost</h1>
    <p class="hero-sub">FaciliTrack replaces manual logbooks and verbal complaints with a centralized digital platform. Report issues, assign technicians, and track every repair — in one place.</p>
    <div class="hero-btns">
      <button class="btn-fill" onclick="openModal()"><i class="fas fa-arrow-right"></i> Get Started</button>
      <a href="#features" class="btn-ghost"><i class="fas fa-play-circle"></i> See Features</a>
    </div>
  </div>
  <div class="hero-img">
    <img src="<?php echo APP_URL; ?>/assets/images/preview-dashboard.png" alt="FaciliTrack Dashboard">
  </div>
</section>

<!-- PAIN POINTS -->
<section class="section section-alt" id="how">
  <div class="section-inner" style="text-align:center">
    <span class="section-label">The Problem</span>
    <h2 class="section-title" style="max-width:600px;margin-left:auto;margin-right:auto">Sound familiar? You're not alone.</h2>
    <p class="section-desc center">Most Nigerian facilities still rely on broken processes for maintenance.</p>
    <div class="pains">
      <div class="pain">
        <div class="pain-icon"><i class="fas fa-book-dead"></i></div>
        <h4>Lost Complaints</h4>
        <p>Verbal reports and paper logbooks lead to forgotten issues and zero accountability.</p>
      </div>
      <div class="pain">
        <div class="pain-icon"><i class="fas fa-clock"></i></div>
        <h4>Delayed Repairs</h4>
        <p>Without tracking, urgent requests sit unattended for days or even weeks.</p>
      </div>
      <div class="pain">
        <div class="pain-icon"><i class="fas fa-eye-slash"></i></div>
        <h4>Zero Visibility</h4>
        <p>Occupants can't check repair status. Managers can't see what's pending. Nobody knows.</p>
      </div>
    </div>
    <p style="font-size:15px;color:#64748b;max-width:500px;margin:0 auto">What if every issue was submitted, assigned, and tracked — <strong style="color:#0f172a">automatically?</strong></p>
  </div>
</section>

<!-- FEATURES -->
<section class="section" id="features">
  <div class="section-inner">
    <span class="section-label">Platform Features</span>
    <h2 class="section-title">One platform for every request,<br>every technician, every facility.</h2>
    <p class="section-desc">Eight modules working together to digitize your maintenance operations.</p>

    <!-- Feature 1: Issue Tracking -->
    <div class="feat-row">
      <div class="feat-text">
        <h3><i class="fas fa-clipboard-list" style="color:#2563EB;margin-right:8px;font-size:22px"></i> Issue Tracking</h3>
        <p>Submit, categorize, and track maintenance requests from report to closure. Every issue gets a unique ID, priority level, and full status history.</p>
        <ul class="feat-list">
          <li><i class="fas fa-check"></i> 10 maintenance categories — plumbing, electrical, structural and more</li>
          <li><i class="fas fa-check"></i> 4 priority levels — Low, Medium, High, Critical</li>
          <li><i class="fas fa-check"></i> Photo attachments with MIME validation and secure storage</li>
          <li><i class="fas fa-check"></i> Real-time status tracking — Pending → In Progress → Resolved → Closed</li>
        </ul>
      </div>
      <div class="feat-img">
        <img src="<?php echo APP_URL; ?>/assets/images/preview-request.png" alt="Issue Tracking">
      </div>
    </div>

    <!-- Feature 2: Work Order Management -->
    <div class="feat-row reverse">
      <div class="feat-text">
        <h3><i class="fas fa-tasks" style="color:#2563EB;margin-right:8px;font-size:22px"></i> Work Order Management</h3>
        <p>Assign maintenance tasks to technicians, track progress in real-time, and document all work with timestamped notes and status transitions.</p>
        <ul class="feat-list">
          <li><i class="fas fa-check"></i> Admin assigns requests to registered technicians</li>
          <li><i class="fas fa-check"></i> Technicians update status and add work comments</li>
          <li><i class="fas fa-check"></i> Full timeline with every action timestamped</li>
          <li><i class="fas fa-check"></i> Tasks sorted by priority — critical issues surface first</li>
        </ul>
      </div>
      <div class="feat-img">
        <img src="<?php echo APP_URL; ?>/assets/images/preview-activity.png" alt="Work Orders">
      </div>
    </div>

    <!-- Feature 3: Dashboard -->
    <div class="feat-row">
      <div class="feat-text">
        <h3><i class="fas fa-chart-bar" style="color:#2563EB;margin-right:8px;font-size:22px"></i> Dashboard &amp; Analytics</h3>
        <p>Role-specific dashboards with live statistics. Admins see everything; users see their own reports; technicians see their assigned tasks.</p>
        <ul class="feat-list">
          <li><i class="fas fa-check"></i> Admin dashboard — total, pending, in-progress, resolved, critical counts</li>
          <li><i class="fas fa-check"></i> Category breakdown showing request distribution</li>
          <li><i class="fas fa-check"></i> Recent requests table with quick-access management</li>
          <li><i class="fas fa-check"></i> Pending registration alerts for admins</li>
        </ul>
      </div>
      <div class="feat-img">
        <img src="<?php echo APP_URL; ?>/assets/images/preview-dashboard.png" alt="Dashboard">
      </div>
    </div>
  </div>
</section>

<!-- MORE FEATURES GRID -->
<section class="section section-alt">
  <div class="section-inner" style="text-align:center">
    <span class="section-label">And More</span>
    <h2 class="section-title">Everything you need, built in.</h2>
    <p class="section-desc center">Security, multi-tenancy, and full audit trails — all standard.</p>
    <div class="pains" style="margin-bottom:0">
      <div class="pain" style="text-align:left">
        <div class="pain-icon" style="background:#eff6ff;margin:0 0 14px 0"><i class="fas fa-users-cog" style="color:#2563EB"></i></div>
        <h4>User &amp; Access Management</h4>
        <p>4 roles with scoped permissions. Admin approval for registrations. Role switching and account deactivation.</p>
      </div>
      <div class="pain" style="text-align:left">
        <div class="pain-icon" style="background:#eff6ff;margin:0 0 14px 0"><i class="fas fa-history" style="color:#2563EB"></i></div>
        <h4>Activity &amp; Audit Log</h4>
        <p>Every status change recorded. Full timeline with user attribution, timestamps, and comments.</p>
      </div>
      <div class="pain" style="text-align:left">
        <div class="pain-icon" style="background:#eff6ff;margin:0 0 14px 0"><i class="fas fa-building" style="color:#2563EB"></i></div>
        <h4>Multi-Organization</h4>
        <p>Isolated data per organization. Unique join codes. Supports offices, campuses, hospitals, schools, and more.</p>
      </div>
      <div class="pain" style="text-align:left">
        <div class="pain-icon" style="background:#eff6ff;margin:0 0 14px 0"><i class="fas fa-shield-alt" style="color:#2563EB"></i></div>
        <h4>Security Built-In</h4>
        <p>Bcrypt password hashing. PDO prepared statements. MIME-validated uploads. Session-based RBAC.</p>
      </div>
      <div class="pain" style="text-align:left">
        <div class="pain-icon" style="background:#eff6ff;margin:0 0 14px 0"><i class="fas fa-exchange-alt" style="color:#2563EB"></i></div>
        <h4>Request Lifecycle</h4>
        <p>Defined status flow: Pending → In Progress → Resolved → Closed. Admin override at any stage.</p>
      </div>
      <div class="pain" style="text-align:left">
        <div class="pain-icon" style="background:#eff6ff;margin:0 0 14px 0"><i class="fas fa-camera" style="color:#2563EB"></i></div>
        <h4>Photo Evidence</h4>
        <p>Attach photos to reports. JPEG, PNG, GIF, WebP supported. 5MB limit. Client-side preview before upload.</p>
      </div>
    </div>
  </div>
</section>

<!-- FACILITY TYPES -->
<section class="section" id="facilities">
  <div class="section-inner" style="text-align:center">
    <span class="section-label">Industries</span>
    <h2 class="section-title">FaciliTrack adapts to your facility type.</h2>
    <p class="section-desc center">One platform, six facility types — each with isolated data and custom workflows.</p>
    <div class="fac-grid">
      <div class="fac"><div class="fac-icon">🏢</div><div><h4>Office Buildings</h4><p>Corporate offices and commercial complexes</p></div></div>
      <div class="fac"><div class="fac-icon">🏫</div><div><h4>Schools &amp; Universities</h4><p>Classrooms, labs, dormitories, and campuses</p></div></div>
      <div class="fac"><div class="fac-icon">🏥</div><div><h4>Hospitals &amp; Clinics</h4><p>Healthcare facilities with critical infrastructure</p></div></div>
      <div class="fac"><div class="fac-icon">🏘️</div><div><h4>Residential Estates</h4><p>Apartments, housing estates, and gated communities</p></div></div>
      <div class="fac"><div class="fac-icon">🏛️</div><div><h4>Campus Facilities</h4><p>University campuses and institutional grounds</p></div></div>
      <div class="fac"><div class="fac-icon">🏗️</div><div><h4>Other Facilities</h4><p>Warehouses, event centers, and custom facility types</p></div></div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <h2>Ready to digitize your maintenance?</h2>
  <p>Start tracking issues in minutes — no setup fees.</p>
  <button class="btn-fill" onclick="openModal()"><i class="fas fa-arrow-right"></i> Sign In to Get Started</button>
</section>

<!-- FOOTER -->
<footer class="footer">
  <p>FaciliTrack &copy; <?php echo date('Y'); ?> — Digital Facility Maintenance Reporting System<br>
  B.Sc. Final Year Project &middot; Caleb University, Lagos</p>
</footer>

<!-- LOGIN MODAL -->
<div class="modal-overlay" id="loginModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    <h2>Welcome back</h2>
    <p class="m-sub">Sign in to your FaciliTrack account</p>

    <?php if ($error): ?>
    <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-suc"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="fg">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>
      <div class="fg">
        <label>Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pw" placeholder="Enter your password" required>
          <button type="button" class="pw-eye" onclick="var p=document.getElementById('pw');p.type=p.type==='password'?'text':'password'"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-fill"><i class="fas fa-sign-in-alt"></i> Sign In</button>
    </form>
    <div class="modal-alt">
      Don't have an account? <a href="register.php">Register here</a><br>
      New organization? <a href="register-organization.php">Register your facility</a>
    </div>
  </div>
</div>

<script>
// Header scroll effect
window.addEventListener('scroll',function(){document.getElementById('header').classList.toggle('scrolled',window.scrollY>20)});

// Modal
function openModal(){document.getElementById('loginModal').classList.add('open')}
function closeModal(){document.getElementById('loginModal').classList.remove('open')}
document.getElementById('loginModal').addEventListener('click',function(e){if(e.target===this)closeModal()});

// Auto-open modal if there's a login error/success
<?php if ($showModal): ?>openModal();<?php endif; ?>
</script>
</body>
</html>
