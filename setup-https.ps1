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
#   5. Writes the Apache virtual host configuration
#   6. Restarts Apache
#
# After running, open https://deoris.test in your browser.
# =============================================================================

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# ---------------------------------------------------------------------------
# Paths
# ---------------------------------------------------------------------------
$XAMPP_APACHE   = "C:\xampp\apache"
$CERT_DIR       = "$XAMPP_APACHE\conf\ssl.crt"
$KEY_DIR        = "$XAMPP_APACHE\conf\ssl.key"
$VHOSTS_FILE    = "$XAMPP_APACHE\conf\extra\httpd-vhosts.conf"
$DOCROOT        = "C:\xampp\htdocs\DEORIS\public"

$CERT_NAME      = "deoris.test"
$CERT_FILE      = "$CERT_DIR\deoris.test.crt"
$KEY_FILE       = "$KEY_DIR\deoris.test.key"

# ---------------------------------------------------------------------------
# 0. Add hosts file entries
# ---------------------------------------------------------------------------
Write-Host "`n[0/6] Checking hosts file entries..." -ForegroundColor Cyan

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

$hostsContent = Get-Content $HOSTS_FILE -Raw
$addedAny = $false

foreach ($entry in $hostsEntries) {
    $domain = $entry.Split(" ")[1]
    if ($hostsContent -notmatch [regex]::Escape($domain)) {
        Add-Content -Path $HOSTS_FILE -Value $entry
        Write-Host "     Added: $entry" -ForegroundColor Yellow
        $addedAny = $true
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
Write-Host "`n[1/6] Checking mkcert..." -ForegroundColor Cyan

if (-not (Get-Command mkcert -ErrorAction SilentlyContinue)) {
    Write-Host "     Installing mkcert via winget..." -ForegroundColor Yellow
    winget install --id FiloSottile.mkcert --source winget --accept-package-agreements --accept-source-agreements
    # Refresh PATH so mkcert is available in this session
    $env:PATH = [System.Environment]::GetEnvironmentVariable("PATH", "Machine") + ";" +
                [System.Environment]::GetEnvironmentVariable("PATH", "User")
} else {
    Write-Host "     mkcert already installed: $(mkcert --version)" -ForegroundColor Green
}

# ---------------------------------------------------------------------------
# 2. Install the local CA into the trust store
# ---------------------------------------------------------------------------
Write-Host "`n[2/6] Installing local CA into trust store..." -ForegroundColor Cyan
mkcert -install
Write-Host "     CA installed." -ForegroundColor Green

# ---------------------------------------------------------------------------
# 3. Generate the wildcard certificate
# ---------------------------------------------------------------------------
Write-Host "`n[3/6] Generating TLS certificate for deoris.test + *.deoris.test..." -ForegroundColor Cyan

# Generate into a temp location first, then copy to XAMPP dirs
$TMP = "$env:TEMP\mkcert-portal"
New-Item -ItemType Directory -Force -Path $TMP | Out-Null

Push-Location $TMP
mkcert deoris.test "*.deoris.test"
Pop-Location

# mkcert names the files predictably
$generatedCert = "$TMP\deoris.test+1.pem"
$generatedKey  = "$TMP\deoris.test+1-key.pem"

if (-not (Test-Path $generatedCert)) {
    # Fallback: single-domain cert name
    $generatedCert = "$TMP\deoris.test.pem"
    $generatedKey  = "$TMP\deoris.test-key.pem"
}

Write-Host "     Certificate generated." -ForegroundColor Green

# ---------------------------------------------------------------------------
# 4. Copy certs into XAMPP
# ---------------------------------------------------------------------------
Write-Host "`n[4/6] Copying certificates into XAMPP..." -ForegroundColor Cyan

Copy-Item -Force $generatedCert $CERT_FILE
Copy-Item -Force $generatedKey  $KEY_FILE

Write-Host "     Cert : $CERT_FILE" -ForegroundColor Green
Write-Host "     Key  : $KEY_FILE"  -ForegroundColor Green

# ---------------------------------------------------------------------------
# 5. Write Apache virtual host configuration
# ---------------------------------------------------------------------------
Write-Host "`n[5/6] Writing Apache virtual host configuration..." -ForegroundColor Cyan

# Each module maps to its own DocumentRoot on disk.
# Key = subdomain name, Value = path to the module's /public folder.
$moduleDocRoots = [ordered]@{
    "entryease"     = "C:/xampp/htdocs/entryEase/public"
    "enrollease"    = "C:/xampp/htdocs/EnrollEase/public"
    "gradetrack"    = "C:/xampp/htdocs/GradeTrack/public"
    "meditrack"     = "C:/xampp/htdocs/MediTrack/public"
    "librarysys"    = "C:/xampp/htdocs/LibrarySys/public"
    "taskflow"      = "C:/xampp/htdocs/taskflow/public"
    "careerconnect" = "C:/xampp/htdocs/carrerConnect/public"
    "assesspay"     = "C:/xampp/htdocs/asssesspay/public"
    "votesys"       = "C:/xampp/htdocs/VoteSys/public"
    "clearcheck"    = "C:/xampp/htdocs/ClearCheck/public"
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
    DocumentRoot "$docroot"

    SSLEngine on
    SSLCertificateFile    "$CERT_FILE"
    SSLCertificateKeyFile "$KEY_FILE"

    <Directory "$docroot">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  "$XAMPP_APACHE/logs/$sub.deoris.test-error.log"
    CustomLog "$XAMPP_APACHE/logs/$sub.deoris.test-access.log" combined
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
    DocumentRoot "$DOCROOT"

    SSLEngine on
    SSLCertificateFile    "$CERT_FILE"
    SSLCertificateKeyFile "$KEY_FILE"

    <Directory "$DOCROOT">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  "$XAMPP_APACHE/logs/deoris.test-error.log"
    CustomLog "$XAMPP_APACHE/logs/deoris.test-access.log" combined
</VirtualHost>
$moduleVhosts
"@

Set-Content -Path $VHOSTS_FILE -Value $vhostsContent -Encoding UTF8
Write-Host "     Written: $VHOSTS_FILE" -ForegroundColor Green

# ---------------------------------------------------------------------------
# Restart Apache
# ---------------------------------------------------------------------------
Write-Host "`nRestarting Apache..." -ForegroundColor Cyan

$apacheService = Get-Service -Name "Apache*" -ErrorAction SilentlyContinue
if ($apacheService) {
    Restart-Service $apacheService.Name
    Write-Host "Apache service restarted." -ForegroundColor Green
} else {
    # XAMPP control panel manages Apache as a process, not a service
    $apacheExe = "$XAMPP_APACHE\bin\httpd.exe"
    Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 1
    Start-Process -FilePath $apacheExe -WindowStyle Hidden
    Write-Host "Apache process restarted." -ForegroundColor Green
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

