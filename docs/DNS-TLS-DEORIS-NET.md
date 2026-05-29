# DNS and TLS Checklist (`deoris.net`)

Use this checklist before first production cutover.

## Required DNS Records

Point these hostnames to your Contabo server public IP:

- `deoris.net`
- `www.deoris.net`
- `entryease.deoris.net`
- `enrollease.deoris.net`
- `gradetrack.deoris.net`
- `meditrack.deoris.net`
- `librarysys.deoris.net`
- `taskflow.deoris.net`
- `careerconnect.deoris.net`
- `assesspay.deoris.net`
- `votesys.deoris.net`
- `clearcheck.deoris.net`

Recommended record type:

- `A` records for IPv4.
- `AAAA` records only if your server has working IPv6.

## TLS Options

## Option A: Wildcard (preferred)

- `*.deoris.net`
- `deoris.net`

Pros:

- Simple management for all module subdomains.
- Easy when adding future modules.

## Option B: SAN certificate

Include every hostname above as SAN entries.

## Certificate Placement

The Docker Nginx config expects:

- `docker/nginx/certs/deoris.net.crt`
- `docker/nginx/certs/deoris.net.key`

## Nginx Validation

After placing cert files:

```bash
docker compose up -d nginx
docker compose exec -T nginx nginx -t
docker compose restart nginx
```

## Browser Validation

Confirm:

- `https://deoris.net` has valid certificate chain.
- no mixed-content warnings.
- websocket handshake works to `wss://deoris.net/app`.
