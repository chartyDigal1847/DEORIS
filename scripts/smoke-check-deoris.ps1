param(
    [string]$PortalUrl = "https://deoris.net"
)

$ErrorActionPreference = "Stop"

$moduleHosts = @(
    "entryease.deoris.net",
    "enrollease.deoris.net",
    "gradetrack.deoris.net",
    "meditrack.deoris.net",
    "librarysys.deoris.net",
    "taskflow.deoris.net",
    "careerconnect.deoris.net",
    "assesspay.deoris.net",
    "votesys.deoris.net",
    "clearcheck.deoris.net"
)

function Test-Endpoint {
    param(
        [Parameter(Mandatory=$true)][string]$Url,
        [int[]]$AllowedStatus = @(200,301,302,401,403)
    )

    try {
        $resp = Invoke-WebRequest -Uri $Url -Method GET -MaximumRedirection 0 -SkipHttpErrorCheck
        $status = [int]$resp.StatusCode
        $ok = $AllowedStatus -contains $status
        [pscustomobject]@{
            Url = $Url
            Status = $status
            Result = if ($ok) { "OK" } else { "FAIL" }
        }
    } catch {
        [pscustomobject]@{
            Url = $Url
            Status = "ERR"
            Result = "FAIL"
        }
    }
}

Write-Host "== DEORIS Smoke Check =="
Write-Host "Portal: $PortalUrl"
Write-Host ""

$checks = @()
$checks += Test-Endpoint -Url "$PortalUrl/"
$checks += Test-Endpoint -Url "$PortalUrl/up" -AllowedStatus @(200)
$checks += Test-Endpoint -Url "$PortalUrl/login-redirect"
$checks += Test-Endpoint -Url "$PortalUrl/api/v1/sso/check" -AllowedStatus @(200,401)
$checks += Test-Endpoint -Url "$PortalUrl/api/v1/sso/token" -AllowedStatus @(200,401)
$checks += Test-Endpoint -Url "$PortalUrl/api/events" -AllowedStatus @(404,405,401,403)

foreach ($host in $moduleHosts) {
    $checks += Test-Endpoint -Url "https://$host/" -AllowedStatus @(200,301,302,401,403)
}

$checks | Format-Table -AutoSize

$failed = $checks | Where-Object { $_.Result -eq "FAIL" }
if ($failed.Count -gt 0) {
    Write-Host ""
    Write-Host "Smoke check failed on $($failed.Count) endpoint(s)." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Smoke check passed." -ForegroundColor Green
