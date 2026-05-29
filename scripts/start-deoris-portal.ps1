# ============================================================
#  start-deoris-portal.ps1
#  Starts all DEORIS portal + module background services.
#
#  Queue driver matrix:
#
#  [REDIS]    DEORIS        — queue:work redis  (events, notifications)
#                             reverb:start       (WebSocket bell)
#  [DATABASE] EntryEase     — queue:work database (default, deoris-events)
#                             reverb:start --port=8080 (domain events)
#  [REDIS]    EnrollEase    — queue:work redis  (default)
#  [DATABASE] GradeTrack    — queue:work database (default)
#  [DATABASE] ClearCheck    — queue:work database (default)
#  [DATABASE] MediTrack     — queue:work database (default)
#  [DATABASE] LibrarySys    — queue:work database (default)
#  [DATABASE] TaskFlow      — queue:work database (default)
#  [REDIS]    VoteSys       — queue:work redis  (default)
#  [DATABASE] CareerConnect — queue:work database (default)
#
#  Prerequisites (start in XAMPP Control Panel first):
#    Apache  — serves all *.deoris.test vhosts (port 443)
#    MySQL   — all modules use MySQL (port 3306)
#    Redis   — required for DEORIS, EnrollEase, VoteSys (port 6379)
#              Memurai (recommended): https://www.memurai.com/
#              Redis for Windows:     https://github.com/tporadowski/redis/releases
#
#  Usage:
#    Double-click: C:\xampp\htdocs\DEORIS\start-deoris.bat
#    Or run:       powershell -ExecutionPolicy Bypass -File scripts\start-deoris-portal.ps1
# ============================================================

param(
    [string]$PhpPath = $env:DEORIS_PHP_PATH
)

$ErrorActionPreference = "Stop"

# ── Module definitions ────────────────────────────────────────
# Queue:   redis | database
# Events:  $true = has full DEORIS event integration (like EntryEase)
# Reverb:  $true = needs its own Reverb WebSocket server
# Color:   terminal window title color
$MODULES = @(
    [PSCustomObject]@{ Name="DEORIS";        Path="";                                Queue="redis";    Events=$true;  Reverb=$true;  Color="Cyan"    },
    [PSCustomObject]@{ Name="EntryEase";     Path="C:\xampp\htdocs\entryEase";       Queue="database"; Events=$true;  Reverb=$true;  Color="Magenta" },
    [PSCustomObject]@{ Name="EnrollEase";    Path="C:\xampp\htdocs\EnrollEase";      Queue="redis";    Events=$false; Reverb=$false; Color="Yellow"  },
    [PSCustomObject]@{ Name="GradeTrack";    Path="C:\xampp\htdocs\gradeTrack";      Queue="database"; Events=$false; Reverb=$false; Color="Green"   },
    [PSCustomObject]@{ Name="ClearCheck";    Path="C:\xampp\htdocs\ClearCheck";      Queue="database"; Events=$false; Reverb=$false; Color="Green"   },
    [PSCustomObject]@{ Name="MediTrack";     Path="C:\xampp\htdocs\MediTrack";       Queue="database"; Events=$false; Reverb=$false; Color="Green"   },
    [PSCustomObject]@{ Name="LibrarySys";    Path="C:\xampp\htdocs\LibrarySys";      Queue="database"; Events=$false; Reverb=$false; Color="Green"   },
    [PSCustomObject]@{ Name="TaskFlow";      Path="C:\xampp\htdocs\taskflow";        Queue="database"; Events=$false; Reverb=$false; Color="Green"   },
    [PSCustomObject]@{ Name="VoteSys";       Path="C:\xampp\htdocs\VoteSys";         Queue="redis";    Events=$false; Reverb=$false; Color="Green"   },
    [PSCustomObject]@{ Name="CareerConnect"; Path="C:\xampp\htdocs\carrerConnect";   Queue="database"; Events=$false; Reverb=$true;  Color="Green"   }
)

# Fix DEORIS path from script location
$DEORIS_ROOT = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
($MODULES | Where-Object { $_.Name -eq "DEORIS" }).Path = $DEORIS_ROOT

# ── Find PHP ─────────────────────────────────────────────────
$PHP = $null
if ($PhpPath -and (Test-Path $PhpPath)) { $PHP = $PhpPath }
if (-not $PHP) {
    foreach ($c in @(
        "C:\xampp\php\php.exe",
        "C:\php\php.exe",
        "C:\laragon\bin\php\php-8.3.12-Win32-vs16-x64\php.exe",
        "C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe"
    )) { if (Test-Path $c) { $PHP = $c; break } }
}
if (-not $PHP) {
    $found = Get-Command php -ErrorAction SilentlyContinue
    if ($found) { $PHP = $found.Source }
}
if (-not $PHP) {
    Write-Host "  ERROR: PHP not found. Install XAMPP or add PHP to PATH." -ForegroundColor Red
    Read-Host "Press Enter to exit"; exit 1
}

# ── Helpers ──────────────────────────────────────────────────
function Test-Port([int]$Port) {
    try {
        $tcp = New-Object System.Net.Sockets.TcpClient
        $result = $tcp.BeginConnect("127.0.0.1", $Port, $null, $null)
        $wait = $result.AsyncWaitHandle.WaitOne(500, $false)
        if ($wait -and $tcp.Connected) { $tcp.Close(); return $true }
        $tcp.Close(); return $false
    } catch { return $false }
}

function Test-Redis {
    try {
        foreach ($cli in @("memurai-cli", "redis-cli")) {
            $cmd = Get-Command $cli -ErrorAction SilentlyContinue
            if ($cmd) { $r = & $cmd ping 2>&1; if ($r -match "PONG") { return $true } }
        }
        return (Test-Port 6379)
    } catch { return $false }
}

function Module-Ready([string]$Path) {
    return (Test-Path "$Path\artisan") -and (Test-Path "$Path\.env")
}

function Start-Worker {
    param(
        [string]$Title,
        [string]$WorkDir,
        [string]$ArtisanArgs,
        [string]$Color = "White"
    )
    $cmd = "Set-Location -LiteralPath '$WorkDir'; " +
           "`$host.UI.RawUI.WindowTitle = '$Title'; " +
           "Write-Host ''; Write-Host '  $Title' -ForegroundColor $Color; " +
           "Write-Host '  $WorkDir' -ForegroundColor DarkGray; Write-Host ''; " +
           "& '$PHP' artisan $ArtisanArgs"
    $enc = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($cmd))
    Start-Process powershell.exe `
        -ArgumentList @("-NoProfile", "-NoExit", "-EncodedCommand", $enc) `
        -WindowStyle Normal | Out-Null
    Write-Host ("  [started] {0}" -f $Title) -ForegroundColor Green
}

# ── Banner ───────────────────────────────────────────────────
Clear-Host
Write-Host ""
Write-Host "  +====================================================+" -ForegroundColor Cyan
Write-Host "  |   DEORIS -- Door and Dune Academe Inc.            |" -ForegroundColor Cyan
Write-Host "  |   Information System Startup                      |" -ForegroundColor Cyan
Write-Host "  +====================================================+" -ForegroundColor Cyan
Write-Host ""
Write-Host "  PHP : $PHP" -ForegroundColor DarkGray
Write-Host ""

# ── Infrastructure checks ─────────────────────────────────────
Write-Host "  Infrastructure" -ForegroundColor Cyan
Write-Host "  --------------"

Write-Host "  Apache  (443) ........." -NoNewline
if (Test-Port 443) { Write-Host " OK" -ForegroundColor Green }
else { Write-Host " NOT RUNNING  <- Start in XAMPP Control Panel" -ForegroundColor Red }

Write-Host "  MySQL   (3306) ........" -NoNewline
if (Test-Port 3306) { Write-Host " OK" -ForegroundColor Green }
else { Write-Host " NOT RUNNING  <- Start in XAMPP Control Panel" -ForegroundColor Red }

Write-Host "  Redis   (6379) ........" -NoNewline
if (Test-Redis) { Write-Host " OK" -ForegroundColor Green }
else {
    Write-Host " NOT RUNNING" -ForegroundColor Red
    Write-Host ""
    Write-Host "  Redis is required for DEORIS, EnrollEase, and VoteSys." -ForegroundColor Yellow
    Write-Host "  Memurai (recommended): https://www.memurai.com/" -ForegroundColor Yellow
    Write-Host "  Redis for Windows:     https://github.com/tporadowski/redis/releases" -ForegroundColor Yellow
    Write-Host "  Start the service in services.msc then re-run." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "  Press Enter to exit"; exit 1
}

# ── Module readiness ─────────────────────────────────────────
Write-Host ""
Write-Host "  Modules" -ForegroundColor Cyan
Write-Host "  -------"

foreach ($m in $MODULES) {
    $ok = Module-Ready $m.Path
    $m | Add-Member -NotePropertyName Ready -NotePropertyValue $ok -Force
    $icon  = if ($ok) { "OK  " } else { "SKIP" }
    $color = if ($ok) { "Green" } else { "DarkGray" }
    $tag   = "({0})" -f $m.Queue.ToUpper().PadRight(8)
    $extra = if ($m.Events) { " + event integration" } else { "" }
    $extra += if ($m.Reverb) { " + reverb" } else { "" }
    Write-Host ("  {0} {1,-14} {2}  {3}{4}" -f $tag, $m.Name, $icon, $m.Path, $extra) -ForegroundColor $color
}

# ── Migrations ───────────────────────────────────────────────
Write-Host ""
Write-Host "  Migrations" -ForegroundColor Cyan
Write-Host "  ----------"

foreach ($m in $MODULES) {
    if (-not $m.Ready) { continue }
    Write-Host ("  {0,-14} ..." -f $m.Name) -NoNewline
    & $PHP "$($m.Path)\artisan" migrate --force 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) { Write-Host " OK" -ForegroundColor Green }
    else { Write-Host " WARN (check manually)" -ForegroundColor Yellow }
}

# ── Cache clear ───────────────────────────────────────────────
Write-Host ""
Write-Host "  Cache clear" -ForegroundColor Cyan
Write-Host "  -----------"

foreach ($m in $MODULES) {
    if (-not $m.Ready) { continue }
    Write-Host ("  {0,-14} ..." -f $m.Name) -NoNewline
    & $PHP "$($m.Path)\artisan" config:clear 2>&1 | Out-Null
    & $PHP "$($m.Path)\artisan" cache:clear  2>&1 | Out-Null
    & $PHP "$($m.Path)\artisan" view:clear   2>&1 | Out-Null
    Write-Host " OK" -ForegroundColor Green
}

# ── Start workers ─────────────────────────────────────────────
Write-Host ""
Write-Host "  Starting workers" -ForegroundColor Cyan
Write-Host "  ----------------"
$count = 0

foreach ($m in $MODULES) {
    if (-not $m.Ready) { continue }

    switch ($m.Name) {

        # ── DEORIS: Redis queue + Reverb ─────────────────────
        "DEORIS" {
            Start-Worker "DEORIS Queue Worker" $m.Path `
                "queue:work redis --queue=events,notifications,default --tries=3 --backoff=15 --timeout=90" `
                $m.Color
            $count++; Start-Sleep -Milliseconds 500

            Start-Worker "DEORIS Reverb (WebSockets)" $m.Path `
                "reverb:start --port=8081" `
                $m.Color
            $count++; Start-Sleep -Milliseconds 500
        }

        # ── EntryEase: database queue + deoris-events queue + Reverb ──
        "EntryEase" {
            Start-Worker "EntryEase Queue Worker" $m.Path `
                "queue:work database --queue=default,deoris-events --tries=5 --backoff=10 --timeout=60" `
                $m.Color
            $count++; Start-Sleep -Milliseconds 500

            Start-Worker "EntryEase Reverb (WebSockets)" $m.Path `
                "reverb:start --host=127.0.0.1 --port=8080" `
                $m.Color
            $count++; Start-Sleep -Milliseconds 500
        }

        # ── EnrollEase: Redis queue (no event integration yet) ──
        "EnrollEase" {
            Start-Worker "EnrollEase Queue Worker" $m.Path `
                "queue:work redis --queue=default --tries=3 --backoff=10 --timeout=60" `
                $m.Color
            $count++; Start-Sleep -Milliseconds 400
        }

        # ── VoteSys: Redis queue ──────────────────────────────
        "VoteSys" {
            Start-Worker "VoteSys Queue Worker" $m.Path `
                "queue:work redis --queue=default --tries=3 --backoff=10 --timeout=60" `
                $m.Color
            $count++; Start-Sleep -Milliseconds 400
        }

        # ── All other database-queue modules ─────────────────
        default {
            Start-Worker "$($m.Name) Queue Worker" $m.Path `
                "queue:work database --queue=default --tries=3 --backoff=10 --timeout=60" `
                $m.Color
            $count++; Start-Sleep -Milliseconds 400

            # Start Reverb if this module needs it
            if ($m.Reverb) {
                # Read port from .env
                $envFile = Join-Path $m.Path ".env"
                $reverbPort = 8083
                if (Test-Path $envFile) {
                    $portLine = Select-String -Path $envFile -Pattern "^REVERB_SERVER_PORT=" | Select-Object -First 1
                    if ($portLine) { $reverbPort = ($portLine.Line -split "=")[1].Trim() }
                }
                Start-Worker "$($m.Name) Reverb (WebSockets)" $m.Path `
                    "reverb:start --host=0.0.0.0 --port=$reverbPort" `
                    $m.Color
                $count++; Start-Sleep -Milliseconds 400
            }
        }
    }
}

# ── Summary ───────────────────────────────────────────────────
Write-Host ""
Write-Host "  +====================================================+" -ForegroundColor Green
Write-Host ("  |  Done -- {0} terminal windows opened                |" -f $count) -ForegroundColor Green
Write-Host "  +====================================================+" -ForegroundColor Green
Write-Host ""
Write-Host "  Modules" -ForegroundColor Cyan
Write-Host "  -------"
Write-Host "  https://deoris.test              (portal)" -ForegroundColor White
Write-Host "  https://entryease.deoris.test    (admission)" -ForegroundColor White
Write-Host "  https://enrollease.deoris.test   (enrollment)" -ForegroundColor White
Write-Host "  https://gradetrack.deoris.test   (grades)" -ForegroundColor White
Write-Host "  https://clearcheck.deoris.test   (clearance)" -ForegroundColor White
Write-Host "  https://meditrack.deoris.test    (medical)" -ForegroundColor White
Write-Host "  https://librarysys.deoris.test   (library)" -ForegroundColor White
Write-Host "  https://taskflow.deoris.test     (tasks)" -ForegroundColor White
Write-Host "  https://votesys.deoris.test      (voting)" -ForegroundColor White
Write-Host "  https://careerconnect.deoris.test (careers)" -ForegroundColor White
Write-Host ""
Write-Host ("  Workers ({0} windows)" -f $count) -ForegroundColor Cyan
Write-Host "  --------"
Write-Host "  REDIS    DEORIS Queue Worker       events,notifications,default" -ForegroundColor Cyan
Write-Host "  REDIS    DEORIS Reverb             WebSocket real-time bell" -ForegroundColor Cyan
Write-Host "  DATABASE EntryEase Queue Worker    default,deoris-events" -ForegroundColor Magenta
Write-Host "  DATABASE EntryEase Reverb          WebSocket domain events" -ForegroundColor Magenta
Write-Host "  REDIS    EnrollEase Queue Worker   default" -ForegroundColor Yellow
Write-Host "  DATABASE GradeTrack Queue Worker   default" -ForegroundColor Green
Write-Host "  DATABASE ClearCheck Queue Worker   default" -ForegroundColor Green
Write-Host "  DATABASE MediTrack Queue Worker    default" -ForegroundColor Green
Write-Host "  DATABASE LibrarySys Queue Worker   default" -ForegroundColor Green
Write-Host "  DATABASE TaskFlow Queue Worker     default" -ForegroundColor Green
Write-Host "  REDIS    VoteSys Queue Worker      default" -ForegroundColor Green
Write-Host "  DATABASE CareerConnect Queue Worker default" -ForegroundColor Green
Write-Host "  DATABASE CareerConnect Reverb          WebSocket real-time (port 8083)" -ForegroundColor Green
Write-Host ""
Write-Host ("  To stop: close the {0} terminal windows." -f $count) -ForegroundColor DarkGray
Write-Host ""
Start-Sleep -Seconds 2
