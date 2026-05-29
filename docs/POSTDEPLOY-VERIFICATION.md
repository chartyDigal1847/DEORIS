# Post-Deploy Verification Checklist

Run this after DEORIS + module deployments complete.

## 1) Automated Smoke Test

```powershell
powershell -ExecutionPolicy Bypass -File scripts\smoke-check-deoris.ps1 -PortalUrl "https://deoris.net"
```

## 2) Portal + SSO Checks

- Login at `https://deoris.net`.
- Confirm homepage loads with module cards.
- Open browser DevTools:
  - no CSP errors
  - no CORS errors
  - no repeated `401`/`419` loops
- Open at least three modules from homepage and verify iframe load.

## 3) Role-Based Verification

Test with real accounts for:

- `admin`
- `student`
- `instructor`
- `cashier`
- `librarian`
- `admission_officer`
- `career_officer`
- `nurse`
- `election_officer`

Per-role minimum checks:

- portal login succeeds
- correct modules are visible
- restricted modules are hidden
- protected API endpoints return expected status for allowed/denied paths

## 4) Core Business Flow Checks

- EntryEase: student apply + officer review
- EnrollEase: enrollment status flow
- GradeTrack: attendance/grade write path
- MediTrack: clinic visit and medical record APIs
- AssessPay: payment write path
- VoteSys: voting access and candidate visibility
- ClearCheck: clearance status propagation
- CareerConnect: opportunity listing and recruitment actions

## 5) Realtime + Queue Health

From DEORIS container:

```bash
php artisan deoris:events:health
php artisan deoris:services:health-check
php artisan queue:failed
```

## 6) Log Spot Check

Inspect for the first 15-30 minutes:

- `storage/logs/laravel.log`
- Nginx error logs
- queue worker logs
- reverb logs

No sustained spike of auth/cookie/CORS errors should remain before declaring go-live stable.
