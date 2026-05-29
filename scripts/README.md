# DEORIS scripts

| File | Use |
|------|-----|
| `start-deoris-portal.ps1` | **Main launcher** — starts all 4 background services |
| `smoke-check-deoris.ps1` | Production smoke checks for portal and module hosts |

## What it starts

| Window | Command | Purpose |
|--------|---------|---------|
| DEORIS Queue Worker | `queue:work redis` | Processes events, creates notifications |
| DEORIS Reverb | `reverb:start` | WebSocket server for portal notification bell |
| EntryEase Queue Worker | `queue:work database` | Sends signed events to DEORIS portal |
| EntryEase Reverb | `reverb:start --port=8080` | WebSocket server for EntryEase real-time broadcasts |

## Usage

Double-click `start-deoris.bat` in the DEORIS root, or run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-deoris-portal.ps1
```

## Optional parameters

- `-EntryEasePath "C:\path\to\entryEase"` — custom EntryEase path (default: `C:\xampp\htdocs\entryEase`)
- `-PhpPath "C:\xampp\php\php.exe"` — custom PHP path (default: auto-detect)

## Prerequisites (start in XAMPP Control Panel first)

- **Apache** — serves `https://deoris.test` and `https://entryease.deoris.test`
- **MySQL** — both apps use MySQL
- **Redis / Memurai** — Windows service on port 6379

Full setup: [../SETUP.md](../SETUP.md)
