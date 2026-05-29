<?php
// Simulate the full assesspay SSO flow:
// 1. GET / to get a session cookie + CSRF token
// 2. POST /sso/exchange with a real token (we'll use a fake to see the session behavior)
// 3. GET /sso/debug with the same cookie to see what's in the session

$cookieJar = tempnam(sys_get_temp_dir(), 'ap_cookies_');

// Step 1: GET / to establish session
$ch = curl_init('https://assesspay.deoris.test/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_HEADER, true);
$raw1 = curl_exec($ch);
$s1   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Extract CSRF token
preg_match('/csrf-token.*?content="([^"]+)"/s', $raw1, $csrf);
$csrfToken = $csrf[1] ?? '';
echo "Step 1 GET /  : HTTP $s1, CSRF=" . substr($csrfToken, 0, 10) . "..." . PHP_EOL;

// Step 2: GET /sso/debug BEFORE exchange — should be empty
$ch = curl_init('https://assesspay.deoris.test/sso/debug');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$body = curl_exec($ch);
curl_close($ch);
$data = json_decode($body, true);
echo "Step 2 debug before exchange: sso_role=" . ($data['sso_role'] ?? 'null') . ", driver=" . ($data['session_driver'] ?? '?') . ", cookie=" . ($data['session_cookie'] ?? '?') . PHP_EOL;

// Step 3: We can't do a real exchange without a valid token, but let's check
// what happens when we POST with a fake token — the exchange will fail with 401
// but we can see if the session config is correct
$ch = curl_init('https://assesspay.deoris.test/sso/exchange');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['token' => 'fake', 'embedded' => true]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-CSRF-TOKEN: ' . $csrfToken,
    'X-Requested-With: XMLHttpRequest',
]);
$body3 = curl_exec($ch);
$s3    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Step 3 POST /sso/exchange: HTTP $s3, body=" . substr($body3, 0, 80) . PHP_EOL;

// Step 4: GET /sso/debug AFTER exchange — session should still be intact
$ch = curl_init('https://assesspay.deoris.test/sso/debug');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$body4 = curl_exec($ch);
curl_close($ch);
$data4 = json_decode($body4, true);
echo "Step 4 debug after exchange: sso_role=" . ($data4['sso_role'] ?? 'null') . ", driver=" . ($data4['session_driver'] ?? '?') . ", cookie=" . ($data4['session_cookie'] ?? '?') . PHP_EOL;
echo "  session_id=" . ($data4['session_id'] ?? '?') . PHP_EOL;
echo "  all_keys=" . implode(', ', $data4['all_keys'] ?? []) . PHP_EOL;

unlink($cookieJar);
