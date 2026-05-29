/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: MODULE-SIDE SSO BRIDGE                             ║
 * ║ DO NOT MODIFY WITHOUT SECURITY REVIEW                                 ║
 * ║                                                                        ║
 * ║ This module-side bridge implements:                                   ║
 * ║  • Single-use SSO token handoff (memory only)                         ║
 * ║  • Strict origin validation (portal ONLY)                             ║
 * ║  • RequestId tracking (prevents cross-module interference)            ║
 * ║  • Unload cleanup (revokes pending tokens)                            ║
 * ║  • Timeout protection (prevents hung SSO)                             ║
 * ║                                                                        ║
 * ║ Critical security properties:                                         ║
 * ║  1. No localStorage/sessionStorage (memory only)                      ║
 * ║  2. No credentials stored across reloads                              ║
 * ║  3. Strict postMessage origin checking                                ║
 * ║  4. RequestId prevents cross-iframe token injection                   ║
 * ║  5. Auto-cleanup on page unload                                       ║
 * ║                                                                        ║
 * ║ Required delivery:                                                    ║
 * ║   <script src="https://deoris.test/module-bridge.js"></script>       ║
 * ║                                                                        ║
 * ║ The portal serves this from /public/module-bridge.js and updates      ║
 * ║ it centrally, ensuring all modules always get the latest SSO logic.  ║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */
(function () {
  "use strict";

  // ── Guard against multiple bridge instances ──────────────────────────────
  // Only one bridge should run per module iframe to prevent token collision.
  if (window.__DEORIS_MODULE_BRIDGE_RUNNING__) return;
  window.__DEORIS_MODULE_BRIDGE_RUNNING__ = true;

  // ── Configuration (from parent or defaults) ──────────────────────────────
  var PORTAL_ORIGIN = "https://deoris.test";  // EXACT origin, not pattern
  var SSO_TIMEOUT_MS = Number(window.SSO_TIMEOUT_MS || 8000);
  // Migration toggle:
  // - "portal" (default): bridge exchanges token with DEORIS and emits user
  // - "module": bridge emits token; module backend must exchange server-side
  var SSO_MODE = (window.DEORIS_SSO_MODE === "module") ? "module" : "portal";
  
  // ── Runtime state (memory only, lost on reload) ───────────────────────────
  var requestId = String(Date.now()) + "-" + Math.random().toString(36).slice(2);
  var resolved = false;
  var timeoutId = null;
  var pendingToken = null;  // NO localStorage, NO sessionStorage
  var ssoRequestAttempts = 0;
  var MAX_SSO_REQUEST_ATTEMPTS = Number(window.SSO_MAX_ATTEMPTS || 3);

  // ── Public API (modules call these) ───────────────────────────────────────
  window.PORTAL_ORIGIN = PORTAL_ORIGIN;
  window.SSO_TOKEN = null; // memory-only; used only in SSO_MODE="module"
  window.PORTAL_USER = null;
  window.__DEORIS_SSO_MODE__ = SSO_MODE;
  window.__DEORIS_MODULE_READY_DETAIL__ = null;
  window.__DEORIS_MODULE_ERROR_DETAIL__ = null;

  // ── Helper: Check if we're embedded in an iframe ──────────────────────────
  function isEmbedded() {
    try {
      return window.self !== window.top;
    } catch (error) {
      // X-Frame-Options or same-origin policy may block access.
      // If we can't check, assume we're embedded (safest assumption).
      return true;
    }
  }

  // ── Helper: Emit custom events (modules listen for these) ──────────────────
  function emit(name, detail) {
    window.dispatchEvent(new CustomEvent(name, { detail: detail }));
  }

  // ── Helper: Clear all memory references ──────────────────────────────────
  // CRITICAL: Called on page unload to ensure token isn't leaked.
  function cleanupMemory() {
    pendingToken = null;
    window.SSO_TOKEN = null;
    window.PORTAL_USER = null;
    window.__DEORIS_MODULE_READY_DETAIL__ = null;
  }

  function finishError(error, code) {
    if (resolved) return;
    resolved = true;
    pendingToken = null;
    if (timeoutId) clearTimeout(timeoutId);

    emit("module:error", {
      success: false,
      error: error,
      code: code || "sso_failed",
      embedded: isEmbedded(),
      portalOrigin: PORTAL_ORIGIN,
    });

    window.__DEORIS_MODULE_ERROR_DETAIL__ = {
      success: false,
      error: error,
      code: code || "sso_failed",
      embedded: isEmbedded(),
      portalOrigin: PORTAL_ORIGIN,
    };

    emit("sso:error", {
      success: false,
      error: error,
      code: code || "sso_failed",
    });
  }

  function finishReady(user) {
    if (resolved) return;
    resolved = true;
    window.PORTAL_USER = user;
    if (timeoutId) clearTimeout(timeoutId);

    window.__DEORIS_MODULE_READY_DETAIL__ = {
      success: true,
      user: user,
      token: window.SSO_TOKEN,
      embedded: isEmbedded(),
      portalOrigin: PORTAL_ORIGIN,
    };

    emit("module:ready", window.__DEORIS_MODULE_READY_DETAIL__);

    emit("sso:ready", {
      success: true,
      user: user,
      token: window.SSO_TOKEN,
    });
  }

  function revokePendingToken() {
    var token = pendingToken;
    pendingToken = null;

    if (!token || resolved) return;

    fetch(PORTAL_ORIGIN + "/api/v1/sso/revoke", {
      method: "POST",
      credentials: "include",
      keepalive: true,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ token: token }),
    }).catch(function () {
      // Best-effort cleanup during iframe navigation or portal tab close.
    });
  }

  function requestSsoToken(reason) {
    if (resolved) return;

    ssoRequestAttempts += 1;

    if (ssoRequestAttempts > MAX_SSO_REQUEST_ATTEMPTS) {
      finishError(reason || "sso_failed", "sso_retry_exhausted");
      return;
    }

    window.parent.postMessage({
      type: "REQUEST_SSO",
      requestId: requestId,
      attempt: ssoRequestAttempts,
      reason: reason || null,
    }, PORTAL_ORIGIN);
  }

  function isRetryableExchangeError(error) {
    var message = String(error && error.message || "");
    return message === "invalid_sso_token" ||
      message === "sso_exchange_failed" ||
      message === "sso_validation_failed" ||
      message === "http_419" ||
      message === "http_429" ||
      message.indexOf("http_5") === 0 ||
      message === "Failed to fetch" ||
      message === "NetworkError when attempting to fetch resource.";
  }

  function exchangeToken(token) {
    return fetch(PORTAL_ORIGIN + "/api/v1/sso/exchange", {
      method: "POST",
      credentials: "include",
      headers: {
        Authorization: "Bearer " + token,
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ token: token }),
    }).then(function (response) {
      return response.json()
        .catch(function () { return {}; })
        .then(function (body) {
          if (!response.ok || body.success === false) {
            throw new Error(body.error || ("http_" + response.status));
          }

          if (!body.user || !body.user.id) {
            throw new Error("missing_user");
          }

          return body.user;
        });
    });
  }

  window.addEventListener("message", function (event) {
    if (event.origin !== PORTAL_ORIGIN) {
      console.warn("[module-bridge] Ignored message from untrusted origin:", event.origin);
      return;
    }

    if (!event.data || event.data.requestId !== requestId) return;

    if (event.data.type === "SSO_ERROR") {
      requestSsoToken(event.data.error || "portal_sso_error");
      return;
    }

    if (event.data.type !== "SSO_TOKEN") return;

    if (typeof event.data.token !== "string" || event.data.token.length === 0) {
      finishError("missing_sso_token", "missing_sso_token");
      return;
    }

    pendingToken = event.data.token;

    if (SSO_MODE === "module") {
      // Token-only mode: module backend will exchange the token server-side.
      // Keep token in memory only long enough for boot JS to read it.
      window.SSO_TOKEN = pendingToken;
      pendingToken = null;
      finishReady(null);
      return;
    }

    // Portal mode: exchange with DEORIS immediately, then emit the verified user.
    exchangeToken(pendingToken)
      .then(function (user) {
        pendingToken = null;
        finishReady(user);
      })
      .catch(function (error) {
        revokePendingToken();
        if (isRetryableExchangeError(error)) {
          requestSsoToken(error.message || "exchange_failed");
          return;
        }

        finishError(error.message || "exchange_failed", "exchange_failed");
      });
  });

  window.addEventListener("pagehide", function () {
    revokePendingToken();
    cleanupMemory();
  });

  window.addEventListener("beforeunload", function () {
    revokePendingToken();
    cleanupMemory();
  });

  if (!isEmbedded()) {
    window.location.replace(PORTAL_ORIGIN + "/login-redirect");
    return;
  }

  timeoutId = window.setTimeout(function () {
    finishError("sso_timeout", "sso_timeout");
  }, SSO_TIMEOUT_MS);

  requestSsoToken();
}());
