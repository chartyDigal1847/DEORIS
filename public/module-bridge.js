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
  
  // ── Runtime state (memory only, lost on reload) ───────────────────────────
  var requestId = String(Date.now()) + "-" + Math.random().toString(36).slice(2);
  var resolved = false;
  var timeoutId = null;
  var pendingToken = null;  // NO localStorage, NO sessionStorage

  // ── Public API (modules call these) ───────────────────────────────────────
  window.PORTAL_ORIGIN = PORTAL_ORIGIN;
  window.PORTAL_USER = null;
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
    pendingToken = null;
    window.PORTAL_USER = user;
    if (timeoutId) clearTimeout(timeoutId);

    window.__DEORIS_MODULE_READY_DETAIL__ = {
      success: true,
      user: user,
      embedded: isEmbedded(),
      portalOrigin: PORTAL_ORIGIN,
    };

    emit("module:ready", window.__DEORIS_MODULE_READY_DETAIL__);

    emit("sso:ready", {
      success: true,
      user: user,
      token: null,
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
      finishError(event.data.error || "sso_failed", "portal_sso_error");
      return;
    }

    if (event.data.type !== "SSO_TOKEN") return;

    if (typeof event.data.token !== "string" || event.data.token.length === 0) {
      finishError("missing_sso_token", "missing_sso_token");
      return;
    }

    pendingToken = event.data.token;

    exchangeToken(pendingToken)
      .then(finishReady)
      .catch(function (error) {
        revokePendingToken();
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
    finishError("missing_iframe_context", "missing_iframe_context");
    return;
  }

  timeoutId = window.setTimeout(function () {
    finishError("sso_timeout", "sso_timeout");
  }, SSO_TIMEOUT_MS);

  window.parent.postMessage({ type: "REQUEST_SSO", requestId: requestId }, PORTAL_ORIGIN);
}());
