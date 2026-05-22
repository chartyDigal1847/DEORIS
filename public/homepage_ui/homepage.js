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
 *   - iframe starts with [hidden] in HTML (never visible without a src)
 *   - showPanel(moduleFrame) is called BEFORE setting src
 *   - src is only set when moduleUrl is non-empty and passes validation
 *   - On dashboard: src is cleared so no stale document stays loaded
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
      hidePanel(moduleFrame);
      if (moduleFrame) moduleFrame.removeAttribute("src");
      console.log("[portal] Dashboard activated.");
    } else {
      hidePanel(dashboardHome);
      showPanel(moduleFrame);
      if (moduleFrame) {
        const src = buildModuleUrl(moduleUrl);
        moduleFrame.src = src;
        console.log(`[portal] Module "${moduleName}" → ${src}`);
      }
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
      hidePanel(moduleFrame);
      if (moduleFrame) moduleFrame.removeAttribute("src");
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

})();
