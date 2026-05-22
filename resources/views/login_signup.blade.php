<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Deoris Academe Auth</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('login_ui/login_signup.css') }}" />
</head>
<body>
  <main
    class="auth-page"
    data-initial-auth-mode="{{ $mode ?? (request()->routeIs('register') ? 'signup' : 'login') }}"
  >
    <section class="auth-card" aria-label="Authentication card">
      <!-- Brand -->
      <header class="brand">
        <div class="brand__badge">
          <img src="{{ asset('login_ui/assets/logo.png') }}" alt="Deor&Dune Academe School of Technology logo" />
        </div>
        <h1>Deoris Academe</h1>
        <p>Excellence in Education</p>
      </header>

      <!-- Auth mode tabs -->
      <nav class="auth-tabs" aria-label="Auth mode tabs">
        <button type="button" class="tab-btn is-active" data-tab="login">Login</button>
        <button type="button" class="tab-btn" data-tab="signup">Sign Up</button>
      </nav>

      @if ($errors->any())
        <div class="form-alert" role="alert">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @session('status')
        <div class="form-alert form-alert--success" role="status">
          {{ $value }}
        </div>
      @endsession

      <!-- Login form -->
      <form id="loginForm" class="auth-form is-visible" method="POST" action="{{ route('login') }}">
        @csrf

        <label for="loginEmail">Email</label>
        <input id="loginEmail" name="email" type="email" placeholder="Enter your email" value="{{ old('email') }}" autocomplete="username" required autofocus />

        <label for="loginPassword">Password</label>
        <input id="loginPassword" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required />

        <div class="row-between">
          <label class="remember">
            <input type="checkbox" name="remember" />
            <span>Remember me</span>
          </label>
          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="mini-link">Forgot Password?</a>
          @endif
        </div>

        <button type="submit" class="gold-btn">Sign In</button>
      </form>

      <!-- Sign-up form -->
      <form id="signupForm" class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf

        <label for="signupName">Full Name</label>
        <input id="signupName" name="name" type="text" placeholder="Enter your full name" value="{{ old('name') }}" autocomplete="name" required />

        <label for="signupEmail">Email</label>
        <input id="signupEmail" name="email" type="email" placeholder="Enter your email" value="{{ old('email') }}" autocomplete="username" required />

        <label for="signupPassword">Password</label>
        <input id="signupPassword" name="password" type="password" placeholder="Create a password" autocomplete="new-password" minlength="12" required />
        <p class="field-help">Use at least 12 characters with uppercase, lowercase, and a number.</p>

        <label for="signupConfirm">Confirm Password</label>
        <input id="signupConfirm" name="password_confirmation" type="password" placeholder="Confirm your password" autocomplete="new-password" minlength="12" required />

        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
          <label class="remember terms-check" for="terms">
            <input id="terms" type="checkbox" name="terms" required />
            <span>
              {!! __('I agree to the :terms_of_service and :privacy_policy', [
                  'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="mini-link">'.__('Terms of Service').'</a>',
                  'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="mini-link">'.__('Privacy Policy').'</a>',
              ]) !!}
            </span>
          </label>
        @endif

        <button type="submit" class="gold-btn">Create Account</button>
      </form>

      <!-- Divider -->
      <div class="divider"><span>OR CONTINUE WITH</span></div>

      <!-- Social auth -->
      <button type="button" class="google-btn">
        <svg class="google-icon" viewBox="0 0 48 48" aria-hidden="true">
          <path fill="#EA4335" d="M24 9.5c3.15 0 5.99 1.08 8.22 2.86l6.14-6.14C34.68 2.9 29.7 1 24 1 14.62 1 6.64 6.94 2.68 15.09l7.19 5.58C11.52 13.01 17.27 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.1 24.5c0-1.57-.14-3.08-.4-4.5H24v8.51h12.45c-.54 2.9-2.18 5.35-4.64 7.01l7.19 5.58c4.2-3.87 6.6-9.57 6.6-16.6z"/>
          <path fill="#FBBC05" d="M9.87 28.67A14.5 14.5 0 0 1 9.5 24c0-1.63.28-3.2.77-4.67l-7.19-5.58A23.98 23.98 0 0 0 1 24c0 3.87.93 7.52 2.58 10.75l7.29-6.08z"/>
          <path fill="#34A853" d="M24 47c6.48 0 11.92-2.14 15.89-5.82l-7.19-5.58c-2 1.35-4.56 2.15-8.7 2.15-6.73 0-12.48-3.51-14.53-8.67l-7.29 6.08C6.64 41.06 14.62 47 24 47z"/>
        </svg>
        <span>Continue with Google</span>
      </button>

      <!-- Footer switch text -->
      <p id="switchText" class="switch-text">
        Don't have an account?
        <button type="button" class="switch-btn" data-switch="signup">Sign Up</button>
      </p>
    </section>
  </main>
  <script src="{{ asset('login_ui/login_signup.js') }}"></script>
</body>
</html>
