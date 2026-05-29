# =============================================================================
# setup-https.ps1
#
# One-time HTTPS setup for the DEORIS portal + module local dev environment.
# Run this script ONCE from an elevated (Administrator) PowerShell terminal:
#
#   Right-click PowerShell → "Run as Administrator"
#   cd C:\xampp\htdocs\DEORIS
#   .\setup-https.ps1
#
# What this script does:
#   0. Adds deoris.test + *.deoris.test entries to the Windows hosts file
#   1. Installs mkcert via winget (if not already installed)
#   2. Installs the mkcert root CA into the Windows/browser trust store
#   3. Generates a wildcard TLS certificate for deoris.test + *.deoris.test
#   4. Copies the certificate and key into XAMPP's ssl.crt / ssl.key folders
#   5. Enables the Apache modules/includes needed for HTTPS vhosts
#   6. Writes the Apache virtual host configuration
#   7. Validates and restarts Apache
#
# After running, open https://deoris.test in your browser.
# =============================================================================

[CmdletBinding()]
param(
    [string] $XamppRoot = "C:\xampp",
    [string] $ProjectRoot = $PSScriptRoot,
    [switch] $SkipApacheRestart
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Write-Step {
    param(
        [string] $Text,
        [string] $Color = "Cyan"
    )

    Write-Host $Text -ForegroundColor $Color
}

function Assert-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = [Security.Principal.WindowsPrincipal]::new($identity)

    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        throw "setup-https.ps1 must be run from an elevated Administrator PowerShell window."
    }
}

function Invoke-NativeCommand {
    param(
        [Parameter(Mandatory = $true)]
        [string] $FilePath,

        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]] $Arguments
    )

    & $FilePath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Command failed with exit code ${LASTEXITCODE}: $FilePath $($Arguments -join ' ')"
    }
}

function Refresh-Path {
    $machinePath = [System.Environment]::GetEnvironmentVariable("PATH", "Machine")
    $userPath = [System.Environment]::GetEnvironmentVariable("PATH", "User")
    $env:PATH = "$machinePath;$userPath"
}

function Find-Mkcert {
    $command = Get-Command mkcert -ErrorAction SilentlyContinue
    if ($command) {
        return $command.Source
    }

    $candidates = @(
        "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\FiloSottile.mkcert_Microsoft.Winget.Source_8wekyb3d8bbwe\mkcert.exe",
        "$env:ProgramFiles\mkcert\mkcert.exe",
        "$env:LOCALAPPDATA\Programs\mkcert\mkcert.exe"
    )

    foreach ($candidate in $candidates) {
        if (Test-Path -LiteralPath $candidate) {
            return $candidate
        }
    }

    return $null
}

function Ensure-ApacheModule {
    param(
        [string] $HttpdConf,
        [string] $ModuleLine
    )

    $content = Get-Content -LiteralPath $HttpdConf -Raw
    $moduleName = ($ModuleLine -split "\s+")[1]
    $escaped = [regex]::Escape($moduleName)

    if ($content -match "(?m)^\s*LoadModule\s+$escaped\s+") {
        return
    }

    if ($content -match "(?m)^\s*#\s*LoadModule\s+$escaped\s+") {
        $updated = [regex]::Replace(
            $content,
            "(?m)^\s*#\s*(LoadModule\s+$escaped\s+.*)$",
            '$1',
            1
        )
        Set-Content -LiteralPath $HttpdConf -Value $updated -Encoding ASCII
        Write-Host "     Enabled Apache module: $moduleName" -ForegroundColor Yellow
        return
    }

    Add-Content -LiteralPath $HttpdConf -Value $ModuleLine
    Write-Host "     Added Apache module: $moduleName" -ForegroundColor Yellow
}

function Ensure-ApacheInclude {
    param(
        [string] $HttpdConf,
        [string] $IncludeLine
    )

    $content = Get-Content -LiteralPath $HttpdConf -Raw
    $escaped = [regex]::Escape($IncludeLine)

    if ($content -match "(?m)^\s*$escaped\s*$") {
        return
    }

    if ($content -match "(?m)^\s*#\s*$escaped\s*$") {
        $updated = [regex]::Replace(
            $content,
            "(?m)^\s*#\s*($escaped)\s*$",
            '$1',
            1
        )
        Set-Content -LiteralPath $HttpdConf -Value $updated -Encoding ASCII
        Write-Host "     Enabled Apache include: $IncludeLine" -ForegroundColor Yellow
        return
    }

    Add-Content -LiteralPath $HttpdConf -Value $IncludeLine
    Write-Host "     Added Apache include: $IncludeLine" -ForegroundColor Yellow
}

Assert-Administrator

# ---------------------------------------------------------------------------
# Paths
# ---------------------------------------------------------------------------
$XamppRoot      = (Resolve-Path -LiteralPath $XamppRoot -ErrorAction Stop).Path
$ProjectRoot    = (Resolve-Path -LiteralPath $ProjectRoot -ErrorAction Stop).Path
$XAMPP_APACHE   = Join-Path $XamppRoot "apache"
$HTDOCS_ROOT    = Join-Path $XamppRoot "htdocs"
$CERT_DIR       = Join-Path $XAMPP_APACHE "conf\ssl.crt"
$KEY_DIR        = Join-Path $XAMPP_APACHE "conf\ssl.key"
$VHOSTS_FILE    = Join-Path $XAMPP_APACHE "conf\extra\httpd-vhosts.conf"
$HTTPD_SSL_CONF = Join-Path $XAMPP_APACHE "conf\extra\httpd-ssl.conf"
$HTTPD_CONF     = Join-Path $XAMPP_APACHE "conf\httpd.conf"
$APACHE_EXE     = Join-Path $XAMPP_APACHE "bin\httpd.exe"
$DOCROOT        = Join-Path $ProjectRoot "public"

$CERT_NAME      = "deoris.test"
$CERT_FILE      = Join-Path $CERT_DIR "$CERT_NAME.crt"
$KEY_FILE       = Join-Path $KEY_DIR "$CERT_NAME.key"

foreach ($requiredPath in @($XAMPP_APACHE, $HTTPD_CONF, $HTTPD_SSL_CONF, $APACHE_EXE, $DOCROOT)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "Required path was not found: $requiredPath"
    }
}

New-Item -ItemType Directory -Force -Path $CERT_DIR, $KEY_DIR, (Split-Path -Parent $VHOSTS_FILE) | Out-Null

# ---------------------------------------------------------------------------
# 0. Add hosts file entries
# ---------------------------------------------------------------------------
Write-Step "`n[0/7] Checking hosts file entries..."

$HOSTS_FILE = "C:\Windows\System32\drivers\etc\hosts"

$hostsEntries = @(
    "127.0.0.1 deoris.test",
    "127.0.0.1 entryease.deoris.test",
    "127.0.0.1 enrollease.deoris.test",
    "127.0.0.1 gradetrack.deoris.test",
    "127.0.0.1 meditrack.deoris.test",
    "127.0.0.1 librarysys.deoris.test",
    "127.0.0.1 taskflow.deoris.test",
    "127.0.0.1 careerconnect.deoris.test",
    "127.0.0.1 assesspay.deoris.test",
    "127.0.0.1 votesys.deoris.test",
    "127.0.0.1 clearcheck.deoris.test"
)

$hostsContent = Get-Content -LiteralPath $HOSTS_FILE -Raw
$addedAny = $false

foreach ($entry in $hostsEntries) {
    $parts = $entry -split "\s+", 2
    $ip = $parts[0]
    $domain = $parts[1]
    $domainPattern = "(?m)^\s*$([regex]::Escape($ip))\s+.*\b$([regex]::Escape($domain))\b"

    if ($hostsContent -notmatch $domainPattern) {
        Add-Content -LiteralPath $HOSTS_FILE -Value $entry
        Write-Host "     Added: $entry" -ForegroundColor Yellow
        $addedAny = $true
        $hostsContent = "$hostsContent`r`n$entry"
    }
}

if (-not $addedAny) {
    Write-Host "     All hosts entries already present." -ForegroundColor Green
} else {
    Write-Host "     Hosts file updated." -ForegroundColor Green
}

# ---------------------------------------------------------------------------
# 1. Install mkcert
# ---------------------------------------------------------------------------
Write-Step "`n[1/7] Checking mkcert..."

$mkcert = Find-Mkcert

if (-not $mkcert) {
    Write-Host "     Installing mkcert via winget..." -ForegroundColor Yellow
    $winget = Get-Command winget -ErrorAction SilentlyContinue
    if (-not $winget) {
        throw "mkcert is not installed and winget was not found. Install winget/App Installer or install mkcert manually, then re-run this script."
    }

    Invoke-NativeCommand $winget.Source install --id FiloSottile.mkcert --exact --source winget --accept-package-agreements --accept-source-agreements
    Refresh-Path
    $mkcert = Find-Mkcert

    if (-not $mkcert) {
        throw "mkcert was installed, but setup-https.ps1 could not find mkcert.exe on this session's PATH."
    }
} else {
    $mkcertVersion = & $mkcert --version
    Write-Host "     mkcert already installed: $mkcertVersion" -ForegroundColor Green
}

# ---------------------------------------------------------------------------
# 2. Install the local CA into the trust store
# ---------------------------------------------------------------------------
Write-Step "`n[2/7] Installing local CA into trust store..."
Invoke-NativeCommand $mkcert -install
Write-Host "     CA installed." -ForegroundColor Green

# ---------------------------------------------------------------------------
# 3. Generate the wildcard certificate
# ---------------------------------------------------------------------------
Write-Step "`n[3/7] Generating TLS certificate for deoris.test + *.deoris.test..."

# Generate into a temp location first, then copy to XAMPP dirs
$TMP = Join-Path $env:TEMP "mkcert-deoris"
New-Item -ItemType Directory -Force -Path $TMP | Out-Null

Push-Location $TMP
try {
    Remove-Item -Path (Join-Path $TMP "*.pem") -Force -ErrorAction SilentlyContinue
    Invoke-NativeCommand $mkcert deoris.test "*.deoris.test"
} finally {
    Pop-Location
}

# mkcert names the files predictably
$generatedCert = Join-Path $TMP "deoris.test+1.pem"
$generatedKey  = Join-Path $TMP "deoris.test+1-key.pem"

if (-not (Test-Path $generatedCert)) {
    $certCandidates = Get-ChildItem -LiteralPath $TMP -Filter "*.pem" |
        Where-Object { $_.Name -notlike "*-key.pem" } |
        Sort-Object LastWriteTime -Descending
    $keyCandidates = Get-ChildItem -LiteralPath $TMP -Filter "*-key.pem" |
        Sort-Object LastWriteTime -Descending

    if (-not $certCandidates -or -not $keyCandidates) {
        throw "mkcert did not create the expected certificate files in $TMP."
    }

    $generatedCert = $certCandidates[0].FullName
    $generatedKey = $keyCandidates[0].FullName
}

Write-Host "     Certificate generated." -ForegroundColor Green

# ---------------------------------------------------------------------------
# 4. Copy certs into XAMPP
# ---------------------------------------------------------------------------
Write-Step "`n[4/7] Copying certificates into XAMPP..."

Copy-Item -Force -LiteralPath $generatedCert -Destination $CERT_FILE
Copy-Item -Force -LiteralPath $generatedKey -Destination $KEY_FILE

Write-Host "     Cert : $CERT_FILE" -ForegroundColor Green
Write-Host "     Key  : $KEY_FILE"  -ForegroundColor Green

# ---------------------------------------------------------------------------
# 5. Ensure Apache can load vhosts
# ---------------------------------------------------------------------------
Write-Step "`n[5/7] Checking Apache modules and includes..."

Ensure-ApacheModule -HttpdConf $HTTPD_CONF -ModuleLine "LoadModule rewrite_module modules/mod_rewrite.so"
Ensure-ApacheModule -HttpdConf $HTTPD_CONF -ModuleLine "LoadModule ssl_module modules/mod_ssl.so"
Ensure-ApacheInclude -HttpdConf $HTTPD_CONF -IncludeLine "Include conf/extra/httpd-vhosts.conf"
Ensure-ApacheInclude -HttpdConf $HTTPD_CONF -IncludeLine "Include conf/extra/httpd-ssl.conf"

Write-Host "     Apache configuration prerequisites are ready." -ForegroundColor Green

# ---------------------------------------------------------------------------
# 6. Write Apache virtual host configuration
# ---------------------------------------------------------------------------
Write-Step "`n[6/7] Writing Apache virtual host configuration..."

# Each module maps to its own DocumentRoot on disk.
# Key = subdomain name, Value = path to the module's /public folder.
$moduleDocRoots = [ordered]@{
    "entryease"     = Join-Path $HTDOCS_ROOT "entryEase\public"
    "enrollease"    = Join-Path $HTDOCS_ROOT "EnrollEase\public"
    "gradetrack"    = Join-Path $HTDOCS_ROOT "GradeTrack\public"
    "meditrack"     = Join-Path $HTDOCS_ROOT "MediTrack\public"
    "librarysys"    = Join-Path $HTDOCS_ROOT "LibrarySys\public"
    "taskflow"      = Join-Path $HTDOCS_ROOT "taskflow\public"
    "careerconnect" = Join-Path $HTDOCS_ROOT "carrerConnect\public"
    "assesspay"     = Join-Path $HTDOCS_ROOT "asssesspay\public"
    "votesys"       = Join-Path $HTDOCS_ROOT "VoteSys\public"
    "clearcheck"    = Join-Path $HTDOCS_ROOT "ClearCheck\public"
}

foreach ($docroot in @($DOCROOT) + $moduleDocRoots.Values) {
    New-Item -ItemType Directory -Force -Path $docroot | Out-Null
}

# Build the list of ServerAlias entries for the wildcard HTTP→HTTPS redirect
$httpAliases = ($moduleDocRoots.Keys | ForEach-Object { "    ServerAlias $_.deoris.test" }) -join "`n"

# Build individual HTTPS VirtualHost blocks — each module gets its own DocumentRoot.
$moduleVhosts = ($moduleDocRoots.GetEnumerator() | ForEach-Object {
    $sub     = $_.Key
    $docroot = $_.Value
@"

# --- $sub.deoris.test ---
<VirtualHost *:443>
    ServerName $sub.deoris.test
    DocumentRoot "$($docroot -replace '\\', '/')"

    SSLEngine on
    SSLCertificateFile    "$($CERT_FILE -replace '\\', '/')"
    SSLCertificateKeyFile "$($KEY_FILE -replace '\\', '/')"

    <Directory "$($docroot -replace '\\', '/')">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  "$(($XAMPP_APACHE -replace '\\', '/'))/logs/$sub.deoris.test-error.log"
    CustomLog "$(($XAMPP_APACHE -replace '\\', '/'))/logs/$sub.deoris.test-access.log" combined
</VirtualHost>
"@
}) -join "`n"

$vhostsContent = @"
# =============================================================================
# DEORIS Portal Shell — Virtual Hosts
# Generated by setup-https.ps1
# =============================================================================

# ---------------------------------------------------------------------------
# HTTP → HTTPS redirect for deoris.test and all *.deoris.test subdomains
# ---------------------------------------------------------------------------
<VirtualHost *:80>
    ServerName deoris.test
$httpAliases

    RewriteEngine On
    RewriteRule ^(.*)$ https://%{HTTP_HOST}`$1 [R=301,L]
</VirtualHost>

# ---------------------------------------------------------------------------
# HTTPS — deoris.test (main portal shell)
# ---------------------------------------------------------------------------
<VirtualHost *:443>
    ServerName deoris.test
    DocumentRoot "$($DOCROOT -replace '\\', '/')"

    SSLEngine on
    SSLCertificateFile    "$($CERT_FILE -replace '\\', '/')"
    SSLCertificateKeyFile "$($KEY_FILE -replace '\\', '/')"

    <Directory "$($DOCROOT -replace '\\', '/')">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  "$(($XAMPP_APACHE -replace '\\', '/'))/logs/deoris.test-error.log"
    CustomLog "$(($XAMPP_APACHE -replace '\\', '/'))/logs/deoris.test-access.log" combined
</VirtualHost>
$moduleVhosts
"@

Set-Content -LiteralPath $VHOSTS_FILE -Value $vhostsContent -Encoding ASCII
Write-Host "     Written: $VHOSTS_FILE" -ForegroundColor Green

# ---------------------------------------------------------------------------
# 7. Validate and restart Apache
# ---------------------------------------------------------------------------
Write-Step "`n[7/7] Validating Apache configuration..."

Invoke-NativeCommand $APACHE_EXE -t
Write-Host "     Apache configuration syntax is OK." -ForegroundColor Green

if ($SkipApacheRestart) {
    Write-Host "     Skipped Apache restart because -SkipApacheRestart was supplied." -ForegroundColor Yellow
} else {
    Write-Step "`nRestarting Apache..."

    $apacheService = Get-Service -Name "Apache*" -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($apacheService) {
        Restart-Service $apacheService.Name
        Write-Host "Apache service restarted." -ForegroundColor Green
    } else {
        # XAMPP control panel manages Apache as a process, not a service
        Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 1
        Start-Process -FilePath $APACHE_EXE -WindowStyle Hidden
        Write-Host "Apache process restarted." -ForegroundColor Green
    }
}

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
Write-Host @"

=============================================================================
 Setup complete.

 Open your browser and navigate to:
   https://deoris.test

 If you see a certificate warning, the CA was not trusted correctly.
 Re-run:  mkcert -install
 Then restart your browser completely (all windows).
=============================================================================
"@ -ForegroundColor Green

