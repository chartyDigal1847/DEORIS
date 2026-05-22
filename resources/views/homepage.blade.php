<!doctype html>
<html lang="en">
  @php
    $selectedModule  = $selectedModule ?? 'dashboard';
    $visibleModules  = $visibleModules ?? [];
    $electionActive  = $electionActive ?? false;
    $moduleLinks = $moduleLinks ?? [];
    $selectedModuleUrl = $selectedModule !== 'dashboard'
      ? ($moduleLinks[$selectedModule]['url'] ?? '')
      : '';
    $user = auth()->user();
    $userName = $user?->name ?? 'DEORIS User';
    $userEmail = $user?->email ?? '';
    $userRole = $user?->role ? ucfirst($user->role) : 'User';
    $initials = collect(explode(' ', $userName))
      ->filter()
      ->take(2)
      ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
      ->implode('') ?: 'DU';
  @endphp
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="deoris-user-id" content="{{ $user?->id }}" />
    <meta name="deoris-reverb-enabled" content="{{ config('broadcasting.default') === 'reverb' ? 'true' : 'false' }}" />
    <title>DEORIS Homepage</title>
    <link rel="stylesheet" href="{{ asset('homepage_ui/homepage.css') }}">
    @vite(['resources/js/app.js'])
  </head>
  <body>
    <div class="portal" id="portalShell">
      <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <a class="brand" href="{{ route('homepage') }}" aria-label="DEORIS Home">
          <div class="brand__logo">
            <img src="{{ asset('login_ui/assets/logo.png') }}" alt="DEORIS Logo" class="brand__logo-img" />
          </div>
          <div class="brand__copy">
            <div class="brand__title">DEORIS</div>
            <div class="brand__subtitle">Deor &amp; Dune Academe Inc.<br />Information System</div>
          </div>
        </a>

        <button class="sidebar__collapse" id="collapseSidebar" type="button" aria-label="Collapse sidebar">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="m15 18-6-6 6-6" />
          </svg>
        </button>

        {{-- Role badge --}}
        @php
          $roleBadgeLabel = match($user?->role) {
            'admin'            => 'Administrator',
            'student'          => 'Student',
            'instructor'       => 'Instructor',
            'cashier'          => 'Cashier',
            'librarian'        => 'Librarian',
            'admission_officer'=> 'Admission Officer',
            'nurse'            => 'Nurse / Health Officer',
            'election_officer' => 'Election Officer',
            'candidate'        => 'Candidate',
            default            => 'User',
          };
          $allModuleNav = [
            'entryease'     => ['label' => 'EntryEase',     'icon' => '<path d="M8 3h8"/><path d="M12 3v18"/><path d="M6 7h12"/><path d="M7 21h10"/>'],
            'enrollease'    => ['label' => 'EnrollEase',    'icon' => '<path d="M4 4h16v16H4z"/><path d="M4 12h16"/><path d="M12 4v16"/>'],
            'gradetrack'    => ['label' => 'GradeTrack',    'icon' => '<path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/>'],
            'meditrack'     => ['label' => 'MediTrack',     'icon' => '<path d="M12 3v18"/><path d="M3 12h18"/>'],
            'librarysys'    => ['label' => 'LibrarySys',    'icon' => '<path d="M5 4h14v17H7a2 2 0 0 1-2-2z"/><path d="M8 4v17"/>'],
            'taskflow'      => ['label' => 'TaskFlow',      'icon' => '<path d="M20 6 9 17l-5-5"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
            'careerconnect' => ['label' => 'CareerConnect', 'icon' => '<path d="M16 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M6 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M2 21c0-4 3-7 7-7"/><path d="M22 21c0-4-3-7-7-7"/>'],
            'assesspay'     => ['label' => 'AssessPay',     'icon' => '<path d="M12 2v20"/><path d="M17 5H9a4 4 0 0 0 0 8h6a4 4 0 0 1 0 8H7"/>'],
            'votesys'       => ['label' => 'VoteSys',       'icon' => '<path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/>'],
            'clearcheck'    => ['label' => 'ClearCheck',    'icon' => '<path d="M4 4h16v16H4z"/><path d="M8 12h8"/><path d="M12 8v8"/>'],
          ];
        @endphp

        <div class="sidebar__roleBadge">
          <span class="sidebar__roleLabel">{{ $roleBadgeLabel }}</span>
        </div>

        <nav class="sidebar__nav">
          {{-- Dashboard home link --}}
          <a class="navItem {{ $selectedModule === 'dashboard' ? 'is-active' : '' }}"
             href="{{ route('homepage') }}"
             data-module="dashboard">
            <span class="navItem__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            </span>
            <span class="navItem__label">Dashboard</span>
          </a>

          {{-- Role-filtered module links --}}
          @foreach ($allModuleNav as $moduleKey => $moduleNav)
            @if (in_array($moduleKey, $visibleModules))
              <a class="navItem {{ $selectedModule === $moduleKey ? 'is-active' : '' }}"
                 href="{{ url('/' . $moduleKey) }}"
                 data-module="{{ $moduleKey }}"
                 data-module-url="{{ $moduleLinks[$moduleKey]['url'] ?? '' }}">
                <span class="navItem__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">{!! $moduleNav['icon'] !!}</svg>
                </span>
                <span class="navItem__label">{{ $moduleNav['label'] }}</span>
              </a>
            @endif
          @endforeach
        </nav>
      </aside>

      <header class="topbar">
        <button class="mobileMenu" id="openSidebar" type="button" aria-label="Open menu">
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
            <path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h16" />
          </svg>
        </button>

        <label class="search" for="moduleSearch">
          <svg class="search__icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
          </svg>
          <input id="moduleSearch" class="search__input" type="search" placeholder="Search for modules, grades, courses..." />
        </label>
        <div class="searchPanel" id="searchPanel" hidden></div>

        <x-portal-notifications />
        <div class="profile" id="profileMenu">
          <button class="profile__button" id="profileButton" type="button" aria-haspopup="true" aria-expanded="false">
            <span class="profile__avatar" aria-hidden="true">{{ $initials }}</span>
            <span class="profile__text">
              <span class="profile__name">{{ $userName }}</span>
              <span class="profile__meta">{{ $userRole }}</span>
            </span>
            <svg class="profile__chevron" viewBox="0 0 24 24" aria-hidden="true">
              <path d="m6 9 6 6 6-6" />
            </svg>
          </button>

          <div class="profile__dropdown" id="profileDropdown" role="menu" aria-label="Profile menu" hidden>
            <a class="profile__menuItem" href="{{ route('profile.show') }}" role="menuitem">Profile</a>
            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
              <a class="profile__menuItem" href="{{ route('api-tokens.index') }}" role="menuitem">API Tokens</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button class="profile__menuItem profile__menuItem--danger" type="submit" role="menuitem">Sign out</button>
            </form>
          </div>
        </div>
      </header>

      <main class="moduleArea" id="moduleArea" data-selected-module="{{ $selectedModule }}">
        {{-- Flash error from module access denial --}}
        @if (session('error'))
          <div class="flashError" id="flashError" role="alert">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span>{{ session('error') }}</span>
            <button type="button" class="flashError__dismiss" aria-label="Dismiss">
              <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
          </div>
        @endif
        <section class="dashboardHome" id="dashboardHome" {{ $selectedModule !== 'dashboard' ? 'hidden' : '' }}>
          @php
            $role      = $user?->role ?? 'student';
            $firstName = explode(' ', $userName)[0];

            // Module descriptions used in module cards
            $moduleDesc = [
              'entryease'     => 'Entrance exams & admissions',
              'enrollease'    => 'Enrollment & registration',
              'gradetrack'    => 'Grades & academic records',
              'meditrack'     => 'Medical records & health',
              'librarysys'    => 'Library & borrowing',
              'taskflow'      => 'Tasks & workflows',
              'careerconnect' => 'Career opportunities',
              'assesspay'     => 'Assessments & payments',
              'votesys'       => 'School elections',
              'clearcheck'    => 'Clearance status',
            ];
            $moduleAbbr = [
              'entryease'     => 'EE',
              'enrollease'    => 'EN',
              'gradetrack'    => 'GT',
              'meditrack'     => 'MT',
              'librarysys'    => 'LB',
              'taskflow'      => 'TF',
              'careerconnect' => 'CC',
              'assesspay'     => 'AP',
              'votesys'       => 'VS',
              'clearcheck'    => 'CK',
            ];
          @endphp


          {{-- ═══════════════════════════════════════════════════════════════
               ADMIN DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @if ($role === 'admin')

            {{-- Hero banner --}}
            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Administrator · DEORIS Portal</p>
                <h1 class="dashHero__title">Welcome back, {{ $firstName }}.</h1>
                <p class="dashHero__sub">Full system control — monitor, manage, and oversee all modules.</p>
              </div>
              <div class="dashHero__badge">
                <span>System Status</span>
                <strong>All Modules Active</strong>
                <small>{{ count($visibleModules) }} modules accessible</small>
              </div>
            </div>

            {{-- Stat row --}}
            <div class="dashStatRow">
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--purple">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="dashStat__body">
                  <span>Total Students</span>
                  <strong>—</strong>
                  <small>Enrolled this SY</small>
                </div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--blue">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/></svg>
                </div>
                <div class="dashStat__body">
                  <span>Total Instructors</span>
                  <strong>—</strong>
                  <small>Active faculty</small>
                </div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--orange">
                  <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="dashStat__body">
                  <span>Pending Admissions</span>
                  <strong>—</strong>
                  <small>Awaiting review</small>
                </div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--green">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div class="dashStat__body">
                  <span>Cleared Students</span>
                  <strong>—</strong>
                  <small>ClearCheck passed</small>
                </div>
              </div>
            </div>

            {{-- Module cards grid --}}
            <div class="dashSection">
              <div class="dashSection__head">
                <h2 class="dashSection__title">All Modules</h2>
                <span class="dashSection__sub">Click any module to open it</span>
              </div>
              <div class="moduleCardGrid">
                @foreach ($visibleModules as $mk)
                  <a href="{{ url('/' . $mk) }}" class="moduleCard" data-module="{{ $mk }}" data-module-url="{{ $moduleLinks[$mk]['url'] ?? '' }}">
                    <div class="moduleCard__mark">{{ $moduleAbbr[$mk] ?? strtoupper(substr($mk,0,2)) }}</div>
                    <div class="moduleCard__body">
                      <strong>{{ $moduleLinks[$mk]['label'] ?? ucfirst($mk) }}</strong>
                      <small>{{ $moduleDesc[$mk] ?? '' }}</small>
                    </div>
                    <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                  </a>
                @endforeach
              </div>
            </div>

            {{-- Bottom panels --}}
            <div class="dashColumns">
              <div class="dashPanel">
                <div class="dashPanel__head">
                  <h3 class="dashPanel__title">Quick Actions</h3>
                </div>
                <div class="quickActions">
                  <a href="{{ url('/entryease') }}"  class="quickAction quickAction--primary">Manage Admissions</a>
                  <a href="{{ url('/enrollease') }}" class="quickAction quickAction--outline">View Enrollment</a>
                  <a href="{{ url('/clearcheck') }}" class="quickAction quickAction--green">ClearCheck Monitor</a>
                  <a href="{{ url('/votesys') }}"    class="quickAction quickAction--outline">VoteSys Control</a>
                  <a href="{{ url('/assesspay') }}"  class="quickAction quickAction--outline">Payment Summary</a>
                  <a href="{{ url('/gradetrack') }}" class="quickAction quickAction--outline">Grade Reports</a>
                </div>
              </div>
              <div class="dashPanel">
                <div class="dashPanel__head">
                  <h3 class="dashPanel__title">Announcements</h3>
                  <a href="#" class="dashPanel__viewAll">View All</a>
                </div>
                <ul class="announcementList">
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#3b82f6"></span><div><strong>Midterm Exam Schedule</strong><span>Posted in GradeTrack</span></div></li>
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#ef4444"></span><div><strong>Tuition Payment Deadline</strong><span>May 15 — AssessPay</span></div></li>
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#f59e0b"></span><div><strong>Summer Class Registration</strong><span>EnrollEase now open</span></div></li>
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#10b981"></span><div><strong>Library Book Return</strong><span>Due April 30 — LibrarySys</span></div></li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               INSTRUCTOR DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'instructor')

            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Instructor · Faculty Portal</p>
                <h1 class="dashHero__title">Welcome, {{ $firstName }}.</h1>
                <p class="dashHero__sub">Manage grades, tasks, and career resources for your classes.</p>
              </div>
              <div class="dashHero__badge">
                <span>Your Access</span>
                <strong>Faculty Modules</strong>
                <small>GradeTrack · TaskFlow · CareerConnect</small>
              </div>
            </div>

            <div class="dashStatRow">
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--blue">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/></svg>
                </div>
                <div class="dashStat__body"><span>GradeTrack</span><strong>Grade Input</strong><small>Instructor-only access</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--green">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M20 6 9 17l-5-5"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div class="dashStat__body"><span>TaskFlow</span><strong>Task Management</strong><small>Assign & track</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--purple">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M16 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M6 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M2 21c0-4 3-7 7-7"/><path d="M22 21c0-4-3-7-7-7"/></svg>
                </div>
                <div class="dashStat__body"><span>CareerConnect</span><strong>Faculty Only</strong><small>Career resources</small></div>
              </div>
            </div>

            <div class="dashSection">
              <div class="dashSection__head">
                <h2 class="dashSection__title">Your Modules</h2>
                <span class="dashSection__sub">Faculty-restricted access</span>
              </div>
              <div class="moduleCardGrid">
                @foreach ($visibleModules as $mk)
                  <a href="{{ url('/' . $mk) }}" class="moduleCard" data-module="{{ $mk }}" data-module-url="{{ $moduleLinks[$mk]['url'] ?? '' }}">
                    <div class="moduleCard__mark">{{ $moduleAbbr[$mk] ?? strtoupper(substr($mk,0,2)) }}</div>
                    <div class="moduleCard__body">
                      <strong>{{ $moduleLinks[$mk]['label'] ?? ucfirst($mk) }}</strong>
                      <small>{{ $moduleDesc[$mk] ?? '' }}</small>
                    </div>
                    <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                  </a>
                @endforeach
              </div>
            </div>

            <div class="dashColumns">
              <div class="dashPanel">
                <div class="dashPanel__head"><h3 class="dashPanel__title">Quick Actions</h3></div>
                <div class="quickActions">
                  <a href="{{ url('/gradetrack') }}"    class="quickAction quickAction--primary">Input Grades</a>
                  <a href="{{ url('/taskflow') }}"      class="quickAction quickAction--outline">Manage Tasks</a>
                  <a href="{{ url('/careerconnect') }}" class="quickAction quickAction--green">CareerConnect</a>
                </div>
              </div>
              <div class="dashPanel">
                <div class="dashPanel__head">
                  <h3 class="dashPanel__title">Announcements</h3>
                  <a href="#" class="dashPanel__viewAll">View All</a>
                </div>
                <ul class="announcementList">
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#3b82f6"></span><div><strong>Grade submission deadline</strong><span>Check GradeTrack for schedule</span></div></li>
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#f59e0b"></span><div><strong>Faculty meeting</strong><span>See TaskFlow for details</span></div></li>
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#10b981"></span><div><strong>New career postings</strong><span>CareerConnect updated</span></div></li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               CASHIER DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'cashier')

            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Cashier · Finance Office</p>
                <h1 class="dashHero__title">Welcome, {{ $firstName }}.</h1>
                <p class="dashHero__sub">Process and manage student payment records via AssessPay.</p>
              </div>
              <div class="dashHero__badge">
                <span>Your Access</span>
                <strong>AssessPay</strong>
                <small>Payment records only</small>
              </div>
            </div>

            <div class="dashStatRow">
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--green">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 2v20"/><path d="M17 5H9a4 4 0 0 0 0 8h6a4 4 0 0 1 0 8H7"/></svg>
                </div>
                <div class="dashStat__body"><span>AssessPay</span><strong>Payments</strong><small>Cashier access only</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--orange">
                  <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="dashStat__body"><span>Pending Payments</span><strong>—</strong><small>Awaiting processing</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--blue">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div class="dashStat__body"><span>Today's Collections</span><strong>—</strong><small>Processed today</small></div>
              </div>
            </div>

            <div class="dashSection">
              <div class="dashSection__head">
                <h2 class="dashSection__title">Your Module</h2>
              </div>
              <div class="moduleCardGrid">
                <a href="{{ url('/assesspay') }}" class="moduleCard moduleCard--featured" data-module="assesspay" data-module-url="{{ $moduleLinks['assesspay']['url'] ?? '' }}">
                  <div class="moduleCard__mark">AP</div>
                  <div class="moduleCard__body">
                    <strong>AssessPay</strong>
                    <small>Assessments & payments</small>
                  </div>
                  <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
              </div>
            </div>

            <div class="dashPanel">
              <div class="dashPanel__head"><h3 class="dashPanel__title">Quick Actions</h3></div>
              <div class="quickActions">
                <a href="{{ url('/assesspay') }}" class="quickAction quickAction--primary">Open AssessPay</a>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               LIBRARIAN DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'librarian')

            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Librarian · Library Services</p>
                <h1 class="dashHero__title">Welcome, {{ $firstName }}.</h1>
                <p class="dashHero__sub">Manage library records, borrowing, and book inventory via LibrarySys.</p>
              </div>
              <div class="dashHero__badge">
                <span>Your Access</span>
                <strong>LibrarySys</strong>
                <small>Library operations only</small>
              </div>
            </div>

            <div class="dashStatRow">
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--blue">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M5 4h14v17H7a2 2 0 0 1-2-2z"/><path d="M8 4v17"/></svg>
                </div>
                <div class="dashStat__body"><span>LibrarySys</span><strong>Library Ops</strong><small>Librarian access only</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--orange">
                  <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="dashStat__body"><span>Overdue Books</span><strong>—</strong><small>Pending returns</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--purple">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </div>
                <div class="dashStat__body"><span>Books Borrowed</span><strong>—</strong><small>Currently checked out</small></div>
              </div>
            </div>

            <div class="dashSection">
              <div class="dashSection__head">
                <h2 class="dashSection__title">Your Module</h2>
              </div>
              <div class="moduleCardGrid">
                <a href="{{ url('/librarysys') }}" class="moduleCard moduleCard--featured" data-module="librarysys" data-module-url="{{ $moduleLinks['librarysys']['url'] ?? '' }}">
                  <div class="moduleCard__mark">LB</div>
                  <div class="moduleCard__body">
                    <strong>LibrarySys</strong>
                    <small>Library & borrowing</small>
                  </div>
                  <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
              </div>
            </div>

            <div class="dashPanel">
              <div class="dashPanel__head"><h3 class="dashPanel__title">Quick Actions</h3></div>
              <div class="quickActions">
                <a href="{{ url('/librarysys') }}" class="quickAction quickAction--primary">Open LibrarySys</a>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               ADMISSION OFFICER DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'admission_officer')

            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Admission Officer · Registrar</p>
                <h1 class="dashHero__title">Welcome, {{ $firstName }}.</h1>
                <p class="dashHero__sub">Review applications, manage enrollment, and monitor clearance status.</p>
              </div>
              <div class="dashHero__badge">
                <span>Your Access</span>
                <strong>Admissions</strong>
                <small>EntryEase · EnrollEase · ClearCheck</small>
              </div>
            </div>

            <div class="dashStatRow">
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--orange">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M8 3h8"/><path d="M12 3v18"/><path d="M6 7h12"/><path d="M7 21h10"/></svg>
                </div>
                <div class="dashStat__body"><span>EntryEase</span><strong>Applications</strong><small>Review & approve</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--purple">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 4h16v16H4z"/><path d="M4 12h16"/><path d="M12 4v16"/></svg>
                </div>
                <div class="dashStat__body"><span>EnrollEase</span><strong>Enrollment</strong><small>Manage registrations</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--green">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div class="dashStat__body"><span>ClearCheck</span><strong>Clearance</strong><small>Monitor status</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--blue">
                  <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="dashStat__body"><span>Pending</span><strong>—</strong><small>Awaiting review</small></div>
              </div>
            </div>

            <div class="dashSection">
              <div class="dashSection__head">
                <h2 class="dashSection__title">Your Modules</h2>
              </div>
              <div class="moduleCardGrid">
                @foreach ($visibleModules as $mk)
                  <a href="{{ url('/' . $mk) }}" class="moduleCard" data-module="{{ $mk }}" data-module-url="{{ $moduleLinks[$mk]['url'] ?? '' }}">
                    <div class="moduleCard__mark">{{ $moduleAbbr[$mk] ?? strtoupper(substr($mk,0,2)) }}</div>
                    <div class="moduleCard__body">
                      <strong>{{ $moduleLinks[$mk]['label'] ?? ucfirst($mk) }}</strong>
                      <small>{{ $moduleDesc[$mk] ?? '' }}</small>
                    </div>
                    <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                  </a>
                @endforeach
              </div>
            </div>

            <div class="dashColumns">
              <div class="dashPanel">
                <div class="dashPanel__head"><h3 class="dashPanel__title">Quick Actions</h3></div>
                <div class="quickActions">
                  <a href="{{ url('/entryease') }}"  class="quickAction quickAction--primary">Review Applications</a>
                  <a href="{{ url('/enrollease') }}" class="quickAction quickAction--outline">Manage Enrollment</a>
                  <a href="{{ url('/clearcheck') }}" class="quickAction quickAction--green">ClearCheck</a>
                </div>
              </div>
              <div class="dashPanel">
                <div class="dashPanel__head">
                  <h3 class="dashPanel__title">Announcements</h3>
                  <a href="#" class="dashPanel__viewAll">View All</a>
                </div>
                <ul class="announcementList">
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#f59e0b"></span><div><strong>Enrollment period open</strong><span>EnrollEase — SY 2025–2026</span></div></li>
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#3b82f6"></span><div><strong>New applications received</strong><span>Review in EntryEase</span></div></li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               NURSE DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'nurse')

            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Nurse / Health Officer · Clinic</p>
                <h1 class="dashHero__title">Welcome, {{ $firstName }}.</h1>
                <p class="dashHero__sub">Manage student medical records and health data via MediTrack.</p>
              </div>
              <div class="dashHero__badge">
                <span>Your Access</span>
                <strong>MediTrack</strong>
                <small>Medical records only</small>
              </div>
            </div>

            <div class="dashStatRow">
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--green">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 3v18"/><path d="M3 12h18"/></svg>
                </div>
                <div class="dashStat__body"><span>MediTrack</span><strong>Medical Records</strong><small>Nurse access only</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--orange">
                  <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                </div>
                <div class="dashStat__body"><span>Pending Records</span><strong>—</strong><small>Awaiting input</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--blue">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <div class="dashStat__body"><span>Students Checked</span><strong>—</strong><small>This month</small></div>
              </div>
            </div>

            <div class="dashSection">
              <div class="dashSection__head">
                <h2 class="dashSection__title">Your Module</h2>
              </div>
              <div class="moduleCardGrid">
                <a href="{{ url('/meditrack') }}" class="moduleCard moduleCard--featured" data-module="meditrack" data-module-url="{{ $moduleLinks['meditrack']['url'] ?? '' }}">
                  <div class="moduleCard__mark">MT</div>
                  <div class="moduleCard__body">
                    <strong>MediTrack</strong>
                    <small>Medical records & health</small>
                  </div>
                  <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
              </div>
            </div>

            <div class="dashPanel">
              <div class="dashPanel__head"><h3 class="dashPanel__title">Quick Actions</h3></div>
              <div class="quickActions">
                <a href="{{ url('/meditrack') }}" class="quickAction quickAction--primary">Open MediTrack</a>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               ELECTION OFFICER DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'election_officer')

            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Election Officer · COMELEC</p>
                <h1 class="dashHero__title">Welcome, {{ $firstName }}.</h1>
                <p class="dashHero__sub">Manage school elections, voter eligibility, and results via VoteSys.</p>
              </div>
              <div class="dashHero__badge">
                <span>Election Status</span>
                <strong>{{ $electionActive ? 'Active' : 'Inactive' }}</strong>
                <small>{{ $electionActive ? 'Election is currently running' : 'No election in progress' }}</small>
              </div>
            </div>

            <div class="dashStatRow">
              <div class="dashStat">
                <div class="dashStat__icon {{ $electionActive ? 'dashStat__icon--green' : 'dashStat__icon--orange' }}">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/></svg>
                </div>
                <div class="dashStat__body">
                  <span>VoteSys</span>
                  <strong>{{ $electionActive ? 'Election Live' : 'Standby' }}</strong>
                  <small>{{ $electionActive ? 'Voting is open' : 'Set ELECTION_ACTIVE=true' }}</small>
                </div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--blue">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <div class="dashStat__body"><span>Registered Voters</span><strong>—</strong><small>Eligible students</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--green">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div class="dashStat__body"><span>ClearCheck</span><strong>Voter Eligibility</strong><small>Clearance monitor</small></div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon dashStat__icon--purple">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <div class="dashStat__body"><span>Votes Cast</span><strong>—</strong><small>Total ballots</small></div>
              </div>
            </div>

            <div class="dashSection">
              <div class="dashSection__head">
                <h2 class="dashSection__title">Your Modules</h2>
              </div>
              <div class="moduleCardGrid">
                @foreach ($visibleModules as $mk)
                  <a href="{{ url('/' . $mk) }}" class="moduleCard" data-module="{{ $mk }}" data-module-url="{{ $moduleLinks[$mk]['url'] ?? '' }}">
                    <div class="moduleCard__mark">{{ $moduleAbbr[$mk] ?? strtoupper(substr($mk,0,2)) }}</div>
                    <div class="moduleCard__body">
                      <strong>{{ $moduleLinks[$mk]['label'] ?? ucfirst($mk) }}</strong>
                      <small>{{ $moduleDesc[$mk] ?? '' }}</small>
                    </div>
                    <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                  </a>
                @endforeach
              </div>
            </div>

            <div class="dashColumns">
              <div class="dashPanel">
                <div class="dashPanel__head"><h3 class="dashPanel__title">Quick Actions</h3></div>
                <div class="quickActions">
                  <a href="{{ url('/votesys') }}"    class="quickAction quickAction--primary">Open VoteSys</a>
                  <a href="{{ url('/clearcheck') }}" class="quickAction quickAction--outline">ClearCheck</a>
                </div>
              </div>
              <div class="dashPanel">
                <div class="dashPanel__head">
                  <h3 class="dashPanel__title">Election Info</h3>
                </div>
                <ul class="announcementList">
                  <li class="announcementItem">
                    <span class="announcementItem__dot" style="background:{{ $electionActive ? '#10b981' : '#9ca3af' }}"></span>
                    <div>
                      <strong>Election Status</strong>
                      <span>{{ $electionActive ? 'Currently active — voting is open' : 'No active election. Toggle ELECTION_ACTIVE in .env to start.' }}</span>
                    </div>
                  </li>
                  <li class="announcementItem"><span class="announcementItem__dot" style="background:#3b82f6"></span><div><strong>Voter eligibility</strong><span>Managed via ClearCheck</span></div></li>
                </ul>
              </div>
            </div>


          {{-- ═══════════════════════════════════════════════════════════════
               CANDIDATE DASHBOARD
          ════════════════════════════════════════════════════════════════ --}}
          @elseif ($role === 'candidate')

            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Candidate · School Election</p>
                <h1 class="dashHero__title">Welcome, {{ $firstName }}.</h1>
                <p class="dashHero__sub">Your candidate portal for the school election. Access VoteSys when an election is active.</p>
              </div>
              <div class="dashHero__badge">
                <span>Election Status</span>
                <strong>{{ $electionActive ? 'Active' : 'Inactive' }}</strong>
                <small>{{ $electionActive ? 'VoteSys is now accessible' : 'Waiting for election to start' }}</small>
              </div>
            </div>

            @if ($electionActive)
              <div class="dashStatRow">
                <div class="dashStat">
                  <div class="dashStat__icon dashStat__icon--green">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/></svg>
                  </div>
                  <div class="dashStat__body"><span>VoteSys</span><strong>Election Live</strong><small>Voting is open</small></div>
                </div>
              </div>
              <div class="dashSection">
                <div class="dashSection__head">
                  <h2 class="dashSection__title">Your Module</h2>
                </div>
                <div class="moduleCardGrid">
                  <a href="{{ url('/votesys') }}" class="moduleCard moduleCard--featured" data-module="votesys" data-module-url="{{ $moduleLinks['votesys']['url'] ?? '' }}">
                    <div class="moduleCard__mark">VS</div>
                    <div class="moduleCard__body">
                      <strong>VoteSys</strong>
                      <small>School elections</small>
                    </div>
                    <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                  </a>
                </div>
              </div>
              <div class="dashPanel">
                <div class="dashPanel__head"><h3 class="dashPanel__title">Quick Actions</h3></div>
                <div class="quickActions">
                  <a href="{{ url('/votesys') }}" class="quickAction quickAction--primary">Open VoteSys</a>
                </div>
              </div>
            @else
              <div class="dashEmptyState">
                <div class="dashEmptyState__icon">
                  <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/></svg>
                </div>
                <h3>No Active Election</h3>
                <p>VoteSys will become available once an election is started by the Election Officer.</p>
              </div>
            @endif


          {{-- ═══════════════════════════════════════════════════════════════
               STUDENT DASHBOARD — progressive unlock
          ════════════════════════════════════════════════════════════════ --}}
          @else
            @php
              $admissionStatus  = $user?->admission_status  ?? 'pending';
              $enrollmentStatus = $user?->enrollment_status ?? 'not_enrolled';
              $clearCheckPassed = $user?->clearcheck_passed ?? false;
              // Step mapping:
              // 1 = registered, pending admission (taking entrance exam)
              // 2 = application under review by registrar (same DB step as 1)
              // 3 = admission approved, not yet enrolled
              // 4 = enrolled, clearcheck pending
              // 5 = enrolled + clearcheck passed (full access)
              $step = 1;
              if ($admissionStatus === 'rejected') $step = 1;
              elseif ($admissionStatus === 'under_review') $step = 1;
              elseif ($admissionStatus === 'approved' && $enrollmentStatus === 'not_enrolled') $step = 3;
              elseif ($enrollmentStatus === 'enrolled' && !$clearCheckPassed) $step = 4;
              elseif ($clearCheckPassed) $step = 5;

              $stepLabel = match(true) {
                $admissionStatus === 'rejected'                                          => 'Application Not Approved',
                $admissionStatus === 'under_review'                                      => 'Application Under Review',
                $admissionStatus === 'pending'                                           => 'Pending Admission',
                $admissionStatus === 'approved' && $enrollmentStatus === 'not_enrolled'  => 'Approved — Enroll Now',
                $enrollmentStatus === 'enrolled' && !$clearCheckPassed                   => 'Enrolled — Complete Clearance',
                $clearCheckPassed                                                         => 'Fully Cleared',
                default                                                                  => 'In Progress',
              };
            @endphp

            <div class="dashHero">
              <div class="dashHero__left">
                <p class="dashHero__eyebrow">Student · {{ $stepLabel }}</p>
                <h1 class="dashHero__title">Welcome, {{ $firstName }}.</h1>
                <p class="dashHero__sub">
                  @if ($step === 1) 
                    @if($admissionStatus === 'under_review') Your application is currently under review by the admissions team.
                    @elseif($admissionStatus === 'rejected') Your application was not approved. Please contact the admissions office.
                    @else Complete your entrance exam in EntryEase to begin the admission process.
                    @endif
                  @elseif ($step === 3) Your admission is approved. Proceed to EnrollEase to complete enrollment.
                  @elseif ($step === 4) You are enrolled. Complete your clearance requirements to unlock all modules.
                  @else You have full access to all your modules. Welcome to DEORIS!
                  @endif
                </p>
              </div>
              <div class="dashHero__badge">
                <span>Progress</span>
                <strong>Step {{ $step }} of 5</strong>
                <small>{{ round(($step / 5) * 100) }}% complete</small>
              </div>
            </div>

            {{-- Progress stepper --}}
            <div class="dashStepper">
              @php
                $steps = [
                  1 => ['label' => 'Registration',    'sub' => 'Account created'],
                  2 => ['label' => 'Entrance Exam',   'sub' => 'EntryEase'],
                  3 => ['label' => 'Admission Review','sub' => match($admissionStatus) {
                    'approved' => 'Approved ✓',
                    'rejected' => 'Rejected',
                    default    => 'Pending review',
                  }],
                  4 => ['label' => 'Enrollment',      'sub' => $enrollmentStatus === 'enrolled' ? 'Enrolled' : 'Not enrolled'],
                  5 => ['label' => 'ClearCheck',      'sub' => $clearCheckPassed ? 'Cleared' : 'Pending'],
                ];
              @endphp
              @foreach ($steps as $n => $s)
                @if ($n > 1)
                  <div class="dashStepper__line {{ $step >= $n ? 'is-done' : '' }}"></div>
                @endif
                <div class="dashStepper__item {{ $step > $n ? 'is-done' : '' }} {{ $step === $n ? 'is-current' : '' }}">
                  <div class="dashStepper__dot">
                    @if ($step > $n)
                      <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    @else
                      {{ $n }}
                    @endif
                  </div>
                  <div class="dashStepper__body">
                    <strong>{{ $s['label'] }}</strong>
                    <span>{{ $s['sub'] }}</span>
                  </div>
                </div>
              @endforeach
            </div>

            {{-- Status cards --}}
            <div class="dashStatRow">
              <div class="dashStat">
                <div class="dashStat__icon {{ $admissionStatus === 'approved' ? 'dashStat__icon--green' : ($admissionStatus === 'rejected' ? 'dashStat__icon--red' : 'dashStat__icon--orange') }}">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M8 3h8"/><path d="M12 3v18"/><path d="M6 7h12"/><path d="M7 21h10"/></svg>
                </div>
                <div class="dashStat__body">
                  <span>Admission</span>
                  <strong>{{ ucfirst(str_replace('_', ' ', $admissionStatus)) }}</strong>
                  <small>EntryEase</small>
                </div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon {{ $enrollmentStatus === 'enrolled' ? 'dashStat__icon--green' : 'dashStat__icon--orange' }}">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 4h16v16H4z"/><path d="M4 12h16"/><path d="M12 4v16"/></svg>
                </div>
                <div class="dashStat__body">
                  <span>Enrollment</span>
                  <strong>{{ $enrollmentStatus === 'enrolled' ? 'Enrolled' : 'Not Enrolled' }}</strong>
                  <small>EnrollEase</small>
                </div>
              </div>
              <div class="dashStat">
                <div class="dashStat__icon {{ $clearCheckPassed ? 'dashStat__icon--green' : 'dashStat__icon--orange' }}">
                  <svg viewBox="0 0 24 24" width="18" height="18"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div class="dashStat__body">
                  <span>Clearance</span>
                  <strong>{{ $clearCheckPassed ? 'Cleared' : 'Pending' }}</strong>
                  @if (!$clearCheckPassed && $enrollmentStatus === 'enrolled')
                    <div class="dashStat__progress"><div class="dashStat__progressBar" style="width:40%"></div></div>
                  @else
                    <small>ClearCheck</small>
                  @endif
                </div>
              </div>
              @if ($clearCheckPassed)
                <div class="dashStat">
                  <div class="dashStat__icon dashStat__icon--blue">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/></svg>
                  </div>
                  <div class="dashStat__body">
                    <span>Grades</span>
                    <strong>GradeTrack</strong>
                    <small>View your grades</small>
                  </div>
                </div>
              @endif
            </div>

            {{-- Available modules --}}
            @if (count($visibleModules) > 0)
              <div class="dashSection">
                <div class="dashSection__head">
                  <h2 class="dashSection__title">Available Modules</h2>
                  <span class="dashSection__sub">More unlock as you progress</span>
                </div>
                <div class="moduleCardGrid">
                  @foreach ($visibleModules as $mk)
                    <a href="{{ url('/' . $mk) }}" class="moduleCard" data-module="{{ $mk }}" data-module-url="{{ $moduleLinks[$mk]['url'] ?? '' }}">
                      <div class="moduleCard__mark">{{ $moduleAbbr[$mk] ?? strtoupper(substr($mk,0,2)) }}</div>
                      <div class="moduleCard__body">
                        <strong>{{ $moduleLinks[$mk]['label'] ?? ucfirst($mk) }}</strong>
                        <small>{{ $moduleDesc[$mk] ?? '' }}</small>
                      </div>
                      <svg class="moduleCard__arrow" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                  @endforeach
                </div>
              </div>

              <div class="dashColumns">
                <div class="dashPanel">
                  <div class="dashPanel__head"><h3 class="dashPanel__title">Quick Actions</h3></div>
                  <div class="quickActions">
                    @foreach ($visibleModules as $mk)
                      <a href="{{ url('/' . $mk) }}" class="quickAction quickAction--outline">
                        {{ $moduleLinks[$mk]['label'] ?? ucfirst($mk) }}
                      </a>
                    @endforeach
                  </div>
                </div>
                <div class="dashPanel">
                  <div class="dashPanel__head">
                    <h3 class="dashPanel__title">Announcements</h3>
                    <a href="#" class="dashPanel__viewAll">View All</a>
                  </div>
                  <ul class="announcementList">
                    <li class="announcementItem"><span class="announcementItem__dot" style="background:#3b82f6"></span><div><strong>Midterm Exam Schedule</strong><span>Check GradeTrack for details</span></div></li>
                    <li class="announcementItem"><span class="announcementItem__dot" style="background:#ef4444"></span><div><strong>Tuition Payment Deadline</strong><span>Pay via AssessPay</span></div></li>
                    @if ($electionActive)
                      <li class="announcementItem"><span class="announcementItem__dot" style="background:#7c3aed"></span><div><strong>Election is now open</strong><span>Cast your vote in VoteSys</span></div></li>
                    @endif
                  </ul>
                </div>
              </div>
            @else
              <div class="dashEmptyState">
                <div class="dashEmptyState__icon">
                  <svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>
                  @if ($admissionStatus === 'rejected') Application Not Approved
                  @else No Modules Available Yet
                  @endif
                </h3>
                <p>
                  @if ($admissionStatus === 'rejected') Your application was not approved. Please contact the Admission Office for assistance.
                  @else Complete your admission process in EntryEase to unlock your modules.
                  @endif
                </p>
                @if ($admissionStatus !== 'rejected')
                  <a href="{{ url('/entryease') }}" class="quickAction quickAction--primary" style="display:inline-flex;width:auto;padding:0 24px;margin-top:16px">Go to EntryEase</a>
                @endif
              </div>
            @endif
          @endif

        </section>


        {{-- Trusted first-party modules only.
             Do not set sandbox here: these modules need scripts and their real
             subdomain origins for strict postMessage validation. A sandbox
             would either break that contract or create a misleading partial
             restriction. Isolation is enforced by per-module origins plus each
             module's frame-ancestors CSP allowing only https://deoris.test to
             embed it. --}}
        <iframe
          class="moduleFrame"
          id="moduleFrame"
          title="DEORIS module"
          hidden
          referrerpolicy="strict-origin-when-cross-origin"
          allow="clipboard-read; clipboard-write"
        ></iframe>
      </main>
    </div>

    <script src="{{ asset('homepage_ui/portal-notifications.js') }}?v={{ filemtime(public_path('homepage_ui/portal-notifications.js')) }}"></script>
    <script src="{{ asset('homepage_ui/homepage.js') }}?v={{ filemtime(public_path('homepage_ui/homepage.js')) }}"></script>
    <script src="{{ asset('homepage_ui/portal-bridge.js') }}?v={{ filemtime(public_path('homepage_ui/portal-bridge.js')) }}"></script>
  </body>
</html>
