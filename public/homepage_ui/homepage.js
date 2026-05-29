/**
 * homepage.js — Portal shell controller
 *
 * Responsibilities:
 *   - Sidebar collapse / mobile drawer
 *   - Profile dropdown
 *   - Federated search (debounced, role-scoped via API)
 *   - Module activation: nav highlight, iframe src, history, visibility
 *   - Back/forward navigation (popstate)
 *   - Initial page load routing
 *
 * Notifications are handled entirely by portal-notifications.js (loaded
 * separately) which owns the notificationButton / notificationPanel elements.
 *
 * Visibility contract (single system, no exceptions):
 *   showPanel(el) → removeAttribute("hidden") + style.display = ""
 *   hidePanel(el) → setAttribute("hidden","") + style.display = "none"
 *   Nothing else in this file touches display or hidden.
 *
 * Iframe contract:
 *   - iframes start with [hidden] and are shown only after module activation
 *   - one warm iframe is kept per visited module, so switching back is instant
 *   - src is only set once per module unless a full page reload happens
 *   - dashboard hides module iframes instead of clearing their src
 */

(() => {
  "use strict";

  // ── Element references ──────────────────────────────────────────────────
  const portal             = document.getElementById("portalShell");
  const sidebar            = document.getElementById("sidebar");
  const collapseBtn        = document.getElementById("collapseSidebar");
  const openSidebarBtn     = document.getElementById("openSidebar");
  const profileButton      = document.getElementById("profileButton");
  const profileDropdown    = document.getElementById("profileDropdown");
  const dashboardHome      = document.getElementById("dashboardHome");
  const moduleFrame        = document.getElementById("moduleFrame");
  const moduleFrameHost    = moduleFrame?.parentElement || null;
  const moduleFrames       = new Map();
  const warmedOrigins      = new Set();
  const navItems           = Array.from(document.querySelectorAll(".navItem"));
  // Notification elements — owned by portal-notifications.js, referenced
  // here only to close the panel when other UI opens.
  const notificationButton = document.getElementById("notificationButton");
  const notificationPanel  = document.getElementById("notificationPanel");
  const moduleSearch       = document.getElementById("moduleSearch");
  const searchPanel        = document.getElementById("searchPanel");
  const flashError         = document.getElementById("flashError");
  const flashErrorDismiss  = flashError?.querySelector(".flashError__dismiss");

  // Guard — exit immediately on non-portal pages (profile, API tokens, etc.)
  if (!portal || !moduleFrame) return;

  // ── Helpers ──────────────────────────────────────────────────────────────

  function showPanel(el) {
    if (!el) return;
    el.removeAttribute("hidden");
    el.style.display = "";
  }

  function hidePanel(el) {
    if (!el) return;
    el.setAttribute("hidden", "");
    el.style.display = "none";
  }

  function escapeHtml(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  flashErrorDismiss?.addEventListener("click", () => {
    flashError.remove();
  });

  // ── URL validation ───────────────────────────────────────────────────────

  const ALLOWED_MODULE_HOSTS = [
    "entryease.deoris.test",
    "enrollease.deoris.test",
    "gradetrack.deoris.test",
    "meditrack.deoris.test",
    "librarysys.deoris.test",
    "taskflow.deoris.test",
    "careerconnect.deoris.test",
    "assesspay.deoris.test",
    "votesys.deoris.test",
    "clearcheck.deoris.test",
  ];

  function isValidModuleUrl(url) {
    if (!url) return false;
    try {
      const u = new URL(url);
      return u.protocol === "https:" && ALLOWED_MODULE_HOSTS.includes(u.hostname);
    } catch {
      return false;
    }
  }

  function buildModuleUrl(url) {
    const u = new URL(url);
    u.searchParams.set("embedded", "1");
    return u.toString();
  }

  function warmModuleOrigin(url) {
    if (!isValidModuleUrl(url)) return;

    const origin = new URL(url).origin;
    if (warmedOrigins.has(origin)) return;
    warmedOrigins.add(origin);

    ["dns-prefetch", "preconnect"].forEach((rel) => {
      const link = document.createElement("link");
      link.rel = rel;
      link.href = origin;
      if (rel === "preconnect") link.crossOrigin = "anonymous";
      document.head.appendChild(link);
    });
  }

  function createModuleFrame(moduleName) {
    const frame =
      !moduleFrame.dataset.module && !moduleFrame.getAttribute("src")
        ? moduleFrame
        : document.createElement("iframe");

    frame.className = "moduleFrame";
    frame.dataset.module = moduleName;
    frame.title = `DEORIS ${moduleName} module`;
    frame.hidden = true;
    frame.referrerPolicy = "strict-origin-when-cross-origin";
    frame.allow = "clipboard-read; clipboard-write";
    frame.setAttribute("data-warm-frame", "true");
    frame.dataset.loaded = "false";
    frame.addEventListener("load", () => {
      if (frame.src && frame.src !== "about:blank") {
        frame.dataset.loaded = "true";
      }
    });

    if (frame !== moduleFrame) {
      moduleFrameHost.appendChild(frame);
    }

    moduleFrames.set(moduleName, frame);
    return frame;
  }

  function moduleFrameFor(moduleName, moduleUrl) {
    const src = buildModuleUrl(moduleUrl);
    const frame = moduleFrames.get(moduleName) || createModuleFrame(moduleName);

    if (frame.dataset.src !== src) {
      frame.dataset.src = src;
      frame.dataset.loaded = "false";
      frame.src = src;
    }

    return frame;
  }

  function hideModuleFrames(except = null) {
    moduleFrames.forEach((frame) => {
      if (frame !== except) hidePanel(frame);
    });
  }

  function abortUnfinishedBackgroundFrames(except = null) {
    moduleFrames.forEach((frame) => {
      if (frame === except || frame.dataset.loaded === "true") return;
      if (!frame.src || frame.src === "about:blank") return;

      frame.src = "about:blank";
      frame.dataset.src = "";
    });
  }

  // ── Sidebar ──────────────────────────────────────────────────────────────

  function setCollapsed(collapsed) {
    if (!portal || !collapseBtn) return;
    portal.classList.toggle("is-collapsed", collapsed);
    collapseBtn.setAttribute("aria-expanded", collapsed ? "false" : "true");
    collapseBtn.setAttribute("aria-label",
      collapsed ? "Expand sidebar" : "Collapse sidebar");
  }

  collapseBtn?.addEventListener("click", () => {
    setCollapsed(!portal.classList.contains("is-collapsed"));
  });

  openSidebarBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    portal?.classList.add("is-sidebar-open");
  });

  function closeMobileSidebar() {
    portal?.classList.remove("is-sidebar-open");
  }

  // ── Profile dropdown ─────────────────────────────────────────────────────

  function closeProfile() {
    if (!profileDropdown || !profileButton) return;
    profileDropdown.hidden = true;
    profileButton.setAttribute("aria-expanded", "false");
    profileButton.classList.remove("is-open");
  }

  // Close notification panel (owned by portal-notifications.js)
  function closeNotifications() {
    if (!notificationPanel || !notificationButton) return;
    notificationPanel.hidden = true;
    notificationButton.setAttribute("aria-expanded", "false");
  }

  profileButton?.addEventListener("click", (e) => {
    e.stopPropagation();
    if (!profileDropdown) return;
    const opening = profileDropdown.hidden;
    closeNotifications();
    closeSearch();
    profileDropdown.hidden = !opening;
    profileButton.setAttribute("aria-expanded", opening ? "true" : "false");
    profileButton.classList.toggle("is-open", opening);
  });

  // ── Federated search ──────────────────────────────────────────────────────

  function closeSearch() {
    if (searchPanel) searchPanel.hidden = true;
  }

  function renderSearchResults(payload) {
    if (!searchPanel) return;
    const results = payload.results ?? [];
    searchPanel.innerHTML = results.length
      ? results.map((item) => `
          <a class="searchResult" href="${escapeHtml(item.url ?? "#")}">
            <strong>${escapeHtml(item.title ?? "")}</strong>
            <span>${escapeHtml(item.subtitle ?? item.module_label ?? "")}</span>
            <small>${escapeHtml(item.module_label ?? item.module ?? "")}</small>
          </a>`).join("")
      : '<div class="searchEmpty">No results found.</div>';
    searchPanel.hidden = false;
  }

  let searchTimer = null;
  moduleSearch?.addEventListener("input", () => {
    const q = moduleSearch.value.trim();
    window.clearTimeout(searchTimer);
    if (q.length < 2) { closeSearch(); return; }
    searchTimer = window.setTimeout(async () => {
      try {
        const res = await fetch(`/portal/search?q=${encodeURIComponent(q)}`, {
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
          },
          credentials: "include",
        });
        if (res.ok) renderSearchResults(await res.json());
      } catch { /* network error — silently ignore */ }
    }, 250);
  });

  // ── Global close handlers ─────────────────────────────────────────────────

  document.addEventListener("click", (e) => {
    const inProfile       = profileButton?.contains(e.target) || profileDropdown?.contains(e.target);
    const inNotifications = notificationButton?.contains(e.target) || notificationPanel?.contains(e.target);
    const inSearch        = moduleSearch?.contains(e.target) || searchPanel?.contains(e.target);
    const inSidebar       = sidebar?.contains(e.target) || openSidebarBtn?.contains(e.target);

    if (!inProfile)  closeProfile();
    // Note: notification close is handled by portal-notifications.js's own
    // capture listener. We only close it here when other panels open.
    if (!inSearch)   closeSearch();
    if (!inSidebar && window.matchMedia("(max-width: 820px)").matches) {
      closeMobileSidebar();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    closeProfile();
    closeNotifications();
    closeSearch();
    closeMobileSidebar();
  });

  // ── Core: activate a module or the dashboard ──────────────────────────────

  function activateModule(item, updateHistory = true) {
    if (!item) return;

    const moduleName  = item.dataset.module || "dashboard";
    const moduleUrl   = item.dataset.moduleUrl || "";
    const isDashboard = moduleName === "dashboard";

    // URL validation — fall back to dashboard if URL is missing/invalid
    if (!isDashboard && !isValidModuleUrl(moduleUrl)) {
      console.warn(`[portal] Module "${moduleName}" has no valid URL — showing dashboard.`);
      activateDashboard(updateHistory);
      return;
    }

    // Nav highlight
    navItems.forEach((nav) => nav.classList.toggle("is-active", nav === item));

    // Panel switching
    if (isDashboard) {
      showPanel(dashboardHome);
      hideModuleFrames();
      console.log("[portal] Dashboard activated.");
    } else {
      const frame = moduleFrameFor(moduleName, moduleUrl);
      hidePanel(dashboardHome);
      hideModuleFrames(frame);
      abortUnfinishedBackgroundFrames(frame);
      showPanel(frame);
      warmModuleOrigin(moduleUrl);
      console.log(`[portal] Module "${moduleName}" → ${frame.dataset.src}`);
    }

    // History
    if (updateHistory && item.href) {
      window.history.pushState({ module: moduleName }, "", item.href);
    }

    document.dispatchEvent(new CustomEvent("deoris:module-change", {
      detail: { module: moduleName },
    }));
  }

  function activateDashboard(updateHistory = true) {
    const dashItem = navItems.find((n) => n.dataset.module === "dashboard");
    if (dashItem) {
      activateModule(dashItem, updateHistory);
    } else {
      navItems.forEach((n) => n.classList.remove("is-active"));
      showPanel(dashboardHome);
      hideModuleFrames();
    }
  }

  // ── Nav click handler ─────────────────────────────────────────────────────

  navItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      // Let native links (profile, API tokens) navigate normally
      if (item.dataset.nativeLink === "true" || !dashboardHome || !moduleFrame) return;
      e.preventDefault();
      closeProfile();
      closeNotifications();
      closeMobileSidebar();
      activateModule(item);
    });

    item.addEventListener("pointerenter", () => {
      if (item.dataset.module !== "dashboard") warmModuleOrigin(item.dataset.moduleUrl || "");
    });

    item.addEventListener("focus", () => {
      if (item.dataset.module !== "dashboard") warmModuleOrigin(item.dataset.moduleUrl || "");
    });
  });

  // ── Dashboard home-area module links (homeHero__btn, homeModuleCard, etc.) ─
  // These links live inside .dashboardHome and carry data-module + data-module-url.
  // Intercept them so they open the iframe instead of doing a full page reload.
  dashboardHome?.addEventListener("click", (e) => {
    const link = e.target.closest("a[data-module-url]");
    if (!link || link.dataset.nativeLink === "true") return;
    if (!dashboardHome || !moduleFrame) return;

    const moduleName = link.dataset.module || "";
    const moduleUrl  = link.dataset.moduleUrl || "";

    // Skip if no module key or no valid URL
    if (!moduleName || moduleName === "dashboard") return;
    if (!isValidModuleUrl(moduleUrl)) return;

    e.preventDefault();
    closeProfile();
    closeNotifications();
    closeMobileSidebar();

    // Find the matching nav item and activate it (syncs nav highlight + iframe)
    const navMatch = navItems.find((n) => n.dataset.module === moduleName);
    if (navMatch) {
      activateModule(navMatch);
    } else {
      // Module exists but has no nav item (edge case) — activate directly
      navItems.forEach((n) => n.classList.remove("is-active"));
      hidePanel(dashboardHome);
      const frame = moduleFrameFor(moduleName, moduleUrl);
      hideModuleFrames(frame);
      abortUnfinishedBackgroundFrames(frame);
      showPanel(frame);
      warmModuleOrigin(moduleUrl);
      window.history.pushState({ module: moduleName }, "", `/${moduleName}`);
    }
  });

  // ── Back / forward navigation ─────────────────────────────────────────────

  window.addEventListener("popstate", () => {
    const path  = window.location.pathname.replace(/^\/+/, "") || "dashboard";
    const match = navItems.find((n) => n.dataset.module === path);
    if (match) activateModule(match, false);
    else activateDashboard(false);
  });

  // ── Initial page load ─────────────────────────────────────────────────────

  setCollapsed(false);

  const currentPath = window.location.pathname.replace(/^\/+/, "") || "dashboard";
  const initialItem =
    navItems.find((n) => n.dataset.module === currentPath) ||
    navItems.find((n) => n.classList.contains("is-active")) ||
    null;

  if (initialItem) activateModule(initialItem, false);

  // ── Admin homepage live data ──────────────────────────────────────────────
  // Only runs when the admin stat elements are present in the DOM.

  function portalHeaders() {
    return {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
    };
  }

  async function initAdminHomepage() {
    // Guard — only run when admin stat elements exist
    if (!document.getElementById("hp-stat-students")) return;

    async function loadStats() {
      try {
        const r = await fetch("/api/v1/admin/stats", {
          headers: portalHeaders(),
          credentials: "include",
        });
        if (!r.ok) return;
        const { data: d } = await r.json();
        if (!d) return;
        const set = (id, v) => {
          const el = document.getElementById(id);
          if (el) el.textContent = v ?? "—";
        };
        set("hp-total-students",    d.total_students);
        set("hp-total-instructors", d.total_instructors);
        set("hp-total-users",       d.total_users);
        set("hp-stat-students",     d.total_students);
        set("hp-stat-enrolled",     (d.enrolled_students ?? "—") + " enrolled");
        set("hp-stat-instructors",  d.total_instructors);
        set("hp-stat-pending",      d.pending_admissions);
        set("hp-stat-cleared",      d.cleared_students);
        set("hp-stat-events-today", d.events_today);
        set("hp-stat-events-failed",(d.events_failed ?? "—") + " failed");
        const trend = document.getElementById("hp-events-trend");
        if (trend && d.events_failed > 0) trend.classList.add("homeStat__trend--warn");
      } catch (_) { /* network error — silently ignore */ }
    }

    async function loadActivity() {
      try {
        const r = await fetch("/portal/event-logs?per_page=5", {
          headers: portalHeaders(),
          credentials: "include",
        });
        if (!r.ok) return;
        const { data: rows } = await r.json();
        const ul = document.getElementById("hp-admin-activity");
        if (!ul || !rows?.length) return;
        const statusColor = {
          received: "#3b82f6", processing: "#f59e0b",
          processed: "#16a34a", failed: "#ef4444",
        };
        ul.innerHTML = rows.map((row) => `
          <li class="homeActivity__item">
            <span class="homeActivity__dot" style="background:${statusColor[row.status] || "#9ca3af"}"></span>
            <div class="homeActivity__body">
              <strong>${escapeHtml(row.event_name || "Event")}</strong>
              <span>${escapeHtml(row.source_module || "")} &middot; ${escapeHtml(row.received_at || "")}</span>
            </div>
            <span class="homeActivity__badge homeActivity__badge--${escapeHtml(row.status)}">${escapeHtml(row.status)}</span>
          </li>`).join("");
      } catch (_) { /* silently ignore */ }
    }

    loadStats();
    loadActivity();
    setInterval(loadStats, 60_000);

    // Also refresh when a real-time event comes in via Reverb
    if (window.Echo) {
      window.Echo.private("event-monitoring")
        .listen(".event.processed", () => {
          loadStats();
          loadActivity();
        });
    }
  }

  initAdminHomepage();

})();
