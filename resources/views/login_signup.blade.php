<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In | DEORIS Portal</title>
  <link rel="icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6" />
  <link rel="shortcut icon" type="image/png" href="{{ asset('login_ui/assets/logo.png') }}?v=6" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="{{ asset('login_ui/login_signup.css') }}?v={{ filemtime(public_path('login_ui/login_signup.css')) }}" />
</head>
<body>

  <!-- Background -->
  <div class="auth-bg" aria-hidden="true">
    <div class="auth-bg-img" style="background-image:url('{{ asset('landing/assets/images/background.jpg') }}')"></div>
    <div class="auth-bg-overlay"></div>
    <div class="auth-bg-particles">
      <span></span><span></span><span></span>
      <span></span><span></span><span></span>
    </div>
  </div>

  <!-- Back to landing -->
  <a href="{{ url('/') }}" class="auth-back-link">
    <i class="fas fa-arrow-left"></i> Back to Home
  </a>

  <main
    class="auth-page"
    data-initial-auth-mode="{{ $mode ?? (request()->routeIs('register') ? 'signup' : 'login') }}"
  >
    <section class="auth-card" aria-label="Authentication card">

      <!-- Brand -->
      <header class="brand">
        <div class="brand__badge">
          <img src="{{ asset('login_ui/assets/logo.png') }}" alt="DEORIS Portal logo" />
        </div>
        <h1>DEORIS Portal</h1>
        <p>Deor &amp; Dune Academe Inc. Information System</p>
      </header>

      <!-- Tabs -->
      <nav class="auth-tabs" aria-label="Auth mode tabs">
        <button type="button" class="tab-btn is-active" data-tab="login">
          <i class="fas fa-sign-in-alt"></i> Log In
        </button>
        <button type="button" class="tab-btn" data-tab="signup">
          <i class="fas fa-user-plus"></i> Sign Up
        </button>
      </nav>

      @if ($errors->any())
        <div class="form-alert" role="alert">
          <i class="fas fa-circle-exclamation"></i>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @session('status')
        <div class="form-alert form-alert--success" role="status">
          <i class="fas fa-circle-check"></i> {{ $value }}
        </div>
      @endsession

      <!-- Login form -->
      <form id="loginForm" class="auth-form is-visible" method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field-group">
          <label for="loginEmail">
            <i class="fas fa-envelope"></i> Email Address
          </label>
          <input id="loginEmail" name="email" type="email"
            placeholder="Enter your email address"
            value="{{ old('email') }}"
            autocomplete="username" required autofocus />
        </div>

        <div class="field-group">
          <label for="loginPassword">
            <i class="fas fa-lock"></i> Password
          </label>
          <div class="input-wrap">
            <input id="loginPassword" name="password" type="password"
              placeholder="Enter your account password"
              autocomplete="current-password" required />
            <button type="button" class="toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="row-between">
          <label class="remember">
            <input type="checkbox" name="remember" />
            <span>Remember me</span>
          </label>
          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="mini-link">Forgot Password?</a>
          @endif
        </div>

        <button type="submit" class="primary-btn">
          <i class="fas fa-rocket"></i> Sign In
        </button>
      </form>

      <!-- Sign-up form -->
      <form id="signupForm" class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field-group">
          <label for="signupName">
            <i class="fas fa-user"></i> Full Name
          </label>
          <input id="signupName" name="name" type="text"
            placeholder="Enter your full name"
            value="{{ old('name') }}"
            autocomplete="name" required />
        </div>

        <div class="field-group">
          <label for="signupEmail">
            <i class="fas fa-envelope"></i> Email Address
          </label>
          <input id="signupEmail" name="email" type="email"
            placeholder="Enter your email address"
            value="{{ old('email') }}"
            autocomplete="username" required />
        </div>

        <div class="field-group">
          <label for="signupPassword">
            <i class="fas fa-lock"></i> Password
          </label>
          <div class="input-wrap">
            <input id="signupPassword" name="password" type="password"
              placeholder="Create a secure password"
              autocomplete="new-password" minlength="12" required />
            <button type="button" class="toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <p class="field-help">Use at least 12 characters with uppercase, lowercase, and a number.</p>
        </div>

        <div class="field-group">
          <label for="signupConfirm">
            <i class="fas fa-lock"></i> Confirm Password
          </label>
          <div class="input-wrap">
            <input id="signupConfirm" name="password_confirmation" type="password"
              placeholder="Confirm your password"
              autocomplete="new-password" minlength="12" required />
            <button type="button" class="toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
          <label class="remember terms-check" for="terms">
            <input id="terms" type="checkbox" name="terms" required />
            <span>
              {!! __('I agree to the :terms_of_service and :privacy_policy', [
                  'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="mini-link">'.__('Terms of Service').'</a>',
                  'privacy_policy'   => '<a target="_blank" href="'.route('policy.show').'" class="mini-link">'.__('Privacy Policy').'</a>',
              ]) !!}
            </span>
          </label>
        @endif

        <button type="submit" class="primary-btn">
          <i class="fas fa-user-plus"></i> Create Account
        </button>
      </form>

      <!-- Footer switch -->
      <p id="switchText" class="switch-text">
        Don't have an account?
        <button type="button" class="switch-btn" data-switch="signup">Sign Up</button>
      </p>

    </section>
  </main>

  <script src="{{ asset('login_ui/login_signup.js') }}?v={{ filemtime(public_path('login_ui/login_signup.js')) }}"></script>
</body>
</html>
