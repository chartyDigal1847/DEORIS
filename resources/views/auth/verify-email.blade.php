<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verify Email | DEORIS Portal</title>
  <link rel="icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6" />
  <link rel="shortcut icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('login_ui/login_signup.css') }}" />
</head>
<body>
  <main class="auth-page">
    <section class="auth-card auth-card--message" aria-label="Email verification">
      <header class="brand">
        <div class="brand__badge">
          <img src="{{ asset('login_ui/assets/logo.png') }}" alt="DEORIS Portal logo" />
        </div>
        <h1>Check Your Email</h1>
        <p>Secure your DEORIS account</p>
      </header>

      <p class="auth-message">
        Before opening the portal, verify your email address using the link we sent to your inbox.
      </p>

      @if (session('status') == 'verification-link-sent')
        <div class="form-alert form-alert--success" role="status">
          A new verification link has been sent to your email address.
        </div>
      @endif

      <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="gold-btn">Resend Verification Email</button>
      </form>

      <div class="auth-actions">
        <a href="{{ route('profile.show') }}" class="mini-link">Edit Profile</a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="switch-btn">Log Out</button>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
