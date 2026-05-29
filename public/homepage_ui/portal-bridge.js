/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: PORTAL-SIDE SSO BRIDGE                             ║
 * ║ DO NOT MODIFY WITHOUT SECURITY REVIEW                                 ║
 * ║                                                                        ║
 * ║ This portal-side bridge implements:                                   ║
 * ║  • Strict HTTPS origin whitelist (no wildcards)                       ║
 * ║  • Single-use SSO token issuance                                       ║
 * ║  • Memory-only token tracking (no storage)                            ║
 * ║  • Session-based authentication check                                  ║
 * ║  • postMessage response targeting (exact origin)                       ║
 * ║                                                                        ║
 * ║ Critical security properties:                                         ║
 * ║  1. Origin validation is EXACT match (not pattern)                    ║
 * ║  2. Tokens stored only in memory (Map, not persistent)                ║
 * ║  3. postMessage responses target requesting origin (not *)            ║
 * ║  4. Tokens auto-revoked on page unload (cleanup)                      ║
 * ║  5. Only authenticated users can issue tokens                         ║
 * ║                                                                        ║
 * ║ Security contract:                                                    ║
 * ║ - Portal is the ONLY identity provider                                ║
 * ║ - Modules are identity consumers (never issue tokens)                 ║
 * ║ - Tokens are single-use (issued once, exchanged once)                 ║
 * ║ - Origin validation happens BEFORE token issuance                     ║
 * ║ - Responses never target wildcard "*" origin                          ║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */
(function () {
  "use strict";

  // ── Explicit whitelist of allowed module origins ──────────────────────────
  // NO WILDCARDS. Each origin must be listed explicitly.
  // This prevents hostile subdomains from participating in SSO.
  var ALLOWED_ORIGINS = Object.freeze([
    "https://entryease.deoris.test",
    "https://enrollease.deoris.test",
    "https://gradetrack.deoris.test",
    "https://meditrack.deoris.test",
    "https://librarysys.deoris.test",
    "https://taskflow.deoris.test",
    "https://careerconnect.deoris.test",
    "https://assesspay.deoris.test",
    "https://votesys.deoris.test",
    "https://clearcheck.deoris.test",
  ]);

  var outstandingTokens = new Map();
  var tokenIssueQueue = Promise.resolve();
  var MAX_ISSUE_ATTEMPTS = 5;

  function isAllowedOrigin(origin) {
    return ALLOWED_ORIGINS.indexOf(origin) !== -1;
  }

  function delay(ms) {
    return new Promise(function (resolve) {
      window.setTimeout(resolve, ms);
    });
  }

  function isRetryableSsoError(error) {
    var message = String(error && error.message || "");
    return message === "sso_token_issue_failed" ||
      message === "sso_validation_failed" ||
      message === "unauthenticated" ||
      message === "http_401" ||
      message === "http_419" ||
      message === "http_429" ||
      message.indexOf("http_5") === 0 ||
      message === "Failed to fetch" ||
      message === "NetworkError when attempting to fetch resource.";
  }

  function jsonFetch(url, options) {
    return fetch(url, Object.assign({
      credentials: "include",
      headers: { Accept: "application/json" },
    }, options || {})).then(function (response) {
      return response.json()
        .catch(function () { return {}; })
        .then(function (body) {
          if (!response.ok || body.success === false) {
            throw new Error(body.error || ("http_" + response.status));
          }

          return body;
        });
    });
  }

  function issueTokenOnce() {
    return jsonFetch("/api/v1/sso/token", { method: "GET" }).then(function (body) {
      if (typeof body.token !== "string" || body.token.length === 0) {
        throw new Error("missing_sso_token");
      }

      return body.token;
    });
  }

  function issueTokenWithRetry(attempt) {
    attempt = attempt || 1;

    return issueTokenOnce().catch(function (error) {
      if (attempt >= MAX_ISSUE_ATTEMPTS || !isRetryableSsoError(error)) {
        throw error;
      }

      return delay(250 * attempt).then(function () {
        return issueTokenWithRetry(attempt + 1);
      });
    });
  }

  function issueToken() {
    var nextIssue = tokenIssueQueue
      .catch(function () {
        // A previous caller failed; keep the queue usable for the next iframe.
      })
      .then(function () {
        return issueTokenWithRetry(1);
      });

    tokenIssueQueue = nextIssue.catch(function () {});

    return nextIssue;
  }

  function revokeToken(token) {
    if (!token) return;

    var body = JSON.stringify({ token: token });
    outstandingTokens.delete(token);

    fetch("/api/v1/sso/revoke", {
      method: "POST",
      credentials: "include",
      keepalive: true,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: body,
    }).catch(function () {
      // Cleanup is best-effort; exchanged tokens are already deleted server-side.
    });
  }

  function postError(targetWindow, targetOrigin, error, requestId) {
    targetWindow.postMessage({
      type: "SSO_ERROR",
      success: false,
      error: error,
      requestId: requestId || null,
    }, targetOrigin);
  }

  window.addEventListener("message", function (event) {
    if (!isAllowedOrigin(event.origin)) return;
    if (!event.source || !event.data || event.data.type !== "REQUEST_SSO") return;

    var requestId = typeof event.data.requestId === "string" ? event.data.requestId : null;

    issueToken()
      .then(function (token) {
        outstandingTokens.set(token, event.origin);

        event.source.postMessage({
          type: "SSO_TOKEN",
          success: true,
          token: token,
          requestId: requestId,
        }, event.origin);
      })
      .catch(function (error) {
        postError(event.source, event.origin, error.message || "sso_failed", requestId);
      });
  });

  window.addEventListener("pagehide", function () {
    outstandingTokens.forEach(function (_, token) {
      revokeToken(token);
    });
  });
}());
