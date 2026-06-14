<?php
declare(strict_types=1);
session_start();

// Include navbar if present (optional)
if (file_exists(__DIR__ . '/back/navbar.php')) {
    include __DIR__ . '/back/navbar.php';
}

// Include DB connection but suppress any accidental output from conn.php
ob_start();
require_once __DIR__ . '/back/conn.php';
ob_end_clean();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mode = 'login';
if (!empty($_GET['q']) && in_array($_GET['q'], ['login','signup'], true)) {
    $mode = $_GET['q'];
}

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Bookify — Sign in / Sign up</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    :root{--beige:#f4f1ea;--char:#2b2b2b;--gold:#c9a84e;--muted:#6b6b6b}
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Arial;background:linear-gradient(180deg,var(--beige),#efe9df);color:var(--char)}
    .container{min-height:100vh;display:flex;flex-direction:column}
    main{flex:1;display:flex;align-items:center;justify-content:center;padding:28px}
    .card{width:100%;max-width:960px;background:rgba(255,255,255,0.95);border-radius:14px;box-shadow:0 12px 40px rgba(43,43,43,0.12);display:grid;grid-template-columns:1fr 1fr;overflow:hidden}
    .left{background:linear-gradient(180deg,rgba(0,0,0,0.02),rgba(0,0,0,0.04));padding:44px;display:flex;flex-direction:column;align-items:flex-start;justify-content:center}
    .brand h2{font-family:'Playfair Display',serif;margin:0;font-size:34px;color:var(--char)}
    .brand p{margin-top:8px;color:var(--muted)}
    .right{padding:34px}
    .toggle{display:flex;gap:8px;margin-bottom:18px}
    .tab{flex:1;padding:10px;border-radius:8px;border:1px solid #eee;background:transparent;color:var(--char);cursor:pointer}
    .tab.active{background:var(--char);color:var(--beige);border-color:transparent}
    form{display:none;transition:opacity .28s ease,transform .28s ease}
    form.active{display:block}
    label{display:block;font-size:13px;color:var(--muted);margin:12px 0 6px}
    input,select{width:100%;padding:10px;border-radius:8px;border:1px solid #eee}
    .submit{margin-top:14px;padding:12px;border-radius:8px;background:var(--gold);border:none;color:#111;font-weight:600;cursor:pointer}
    .note{font-size:13px;color:var(--muted);margin-top:10px}
    .msg{padding:10px;border-radius:8px;margin-bottom:12px}
    .error{background:#fff2f0;color:#7a1a15;border:1px solid #f8d7da}
    .success{background:#f2fff4;color:#0b6b36;border:1px solid #d4f5dd}
    @media (max-width:900px){.card{grid-template-columns:1fr;}.left{padding:28px;text-align:center}}
  </style>
</head>
<body>
  <div class="container">
    <main>
      <div class="card" role="main">
        <div class="left">
          <div class="brand">
            <h2>Bookify</h2>
            <p>Enter a modern scholarly library — read, write, and inspire.</p>
          </div>
          <div style="margin-top:18px;color:var(--muted)">Welcome back to the stacks. Be courteous; share knowledge.</div>
        </div>
        <div class="right">
          <a href="index.php">&larr; Back to Library</a>

          <div id="messages"></div>

          <div class="toggle" role="tablist">
            <button id="tab-login" class="tab<?php echo $mode === 'login' ? ' active' : ''; ?>" type="button" data-page="login" aria-controls="login">Login</button>
            <button id="tab-signup" class="tab<?php echo $mode === 'signup' ? ' active' : ''; ?>" type="button" data-page="signup" aria-controls="signup">Sign Up</button>
          </div>

          <form id="login" action="back/auth_action.php" class="<?php echo $mode === 'login' ? 'active' : ''; ?>" onsubmit="return submitForm(event,'login')">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?php echo esc($_SESSION['csrf_token']); ?>">
            <label for="identifier">Email or Username</label>
            <input id="identifier" name="identifier" type="text" required>
            <label for="lpassword">Password</label>
            <input id="lpassword" name="password" type="password" required>
            <button class="submit" type="submit">Sign in</button>
          </form>

          <form id="signup" action="back/auth_action.php" class="<?php echo $mode === 'signup' ? 'active' : ''; ?>" onsubmit="return submitForm(event,'signup')">
            <input type="hidden" name="action" value="signup">
            <input type="hidden" name="csrf_token" value="<?php echo esc($_SESSION['csrf_token']); ?>">
            <label for="susername">Username</label>
            <input id="susername" name="username" type="text" required>
            <label for="semail">Email</label>
            <input id="semail" name="email" type="email" required>
            <label for="spassword">Password</label>
            <input id="spassword" name="password" type="password" required>
            <label for="user_type">I am a</label>
            <select id="user_type" name="user_type"><option value="reader">Reader</option><option value="author">Author</option></select>
            <label for="profile_pic">Profile Picture (optional)</label>
            <input id="profile_pic" name="profile_pic" type="file" accept="image/*">
            <button class="submit" type="submit">Create account</button>
          </form>

        </div>
      </div>
    </main>
  </div>

  <script>
    const loginTab = document.getElementById('tab-login');
    const signupTab = document.getElementById('tab-signup');
    const loginForm = document.getElementById('login');
    const signupForm = document.getElementById('signup');

    function show(which){
      if(which==='login'){
        loginTab.classList.add('active'); signupTab.classList.remove('active');
        loginForm.classList.add('active'); signupForm.classList.remove('active');
      } else {
        signupTab.classList.add('active'); loginTab.classList.remove('active');
        signupForm.classList.add('active'); loginForm.classList.remove('active');
      }
    }
    loginTab.addEventListener('click',()=>window.location.search='?q=login');
    signupTab.addEventListener('click',()=>window.location.search='?q=signup');

    function submitForm(e, mode){
      e.preventDefault();
      const form = mode === 'login' ? loginForm : signupForm;
      const actionUrl = form.getAttribute('action') || 'back/auth_action.php';
      
      // Basic client-side validation
      if (mode === 'signup'){
        const u = $(form).find('[name="username"]').val().trim();
        const em = $(form).find('[name="email"]').val().trim();
        const p = $(form).find('[name="password"]').val();
        if (u.length < 3 || !/^\S+@\S+\.\S+$/.test(em) || p.length < 6) { 
          showMessage('Please provide valid signup details.', false); 
          return false;
        }
      }

      // Use FormData to handle file uploads
      const formData = new FormData(form);

      $.ajax({
        url: actionUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response){
          if(response.success){
            showMessage('Success! Redirecting...', true);
            setTimeout(function(){
              window.location.href = response.redirect || 'dashboard.php';
            }, 700);
          } else {
            showMessage(response.message || 'Error', false);
          }
        },
        error: function(){
          showMessage('Network error. Try again.', false);
        }
      });
      return false;
    }

    function showMessage(msg, ok){
      $('#messages').html('<div class="msg '+(ok? 'success':'error')+'">'+(msg)+'</div>');
    }
  </script>
</body>
</html>
