/**
 * portal-notifications.js — Notifications + optional Reverb updates (portal shell only).
 */
(() => {
  "use strict";

  const notificationHost = document.getElementById("notificationHost");
  const notificationButton = document.getElementById("notificationButton");
  const notificationPanel = document.getElementById("notificationPanel");
  const notificationList = document.getElementById("notificationList");
  const notificationBadge = document.getElementById("notificationBadge");
  const markAllNotificationsRead = document.getElementById("markAllNotificationsRead");
  const userId = document.querySelector('meta[name="deoris-user-id"]')?.content;
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
  const reverbEnabled = document.querySelector('meta[name="deoris-reverb-enabled"]')?.content !== "false";

  if (!notificationButton || !notificationPanel) {
    return;
  }

  if (!userId || !csrfToken) {
    setUnreadCount(0);
    return;
  }

  function portalHeaders() {
    return {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": csrfToken,
    };
  }

  function escapeHtml(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;");
  }

  function setUnreadCount(count) {
    if (!notificationBadge) return;
    const value = Number(count || 0);

    if (value <= 0) {
      notificationBadge.textContent = "";
      notificationBadge.hidden = true;
      notificationBadge.classList.remove("is-visible");
      notificationBadge.setAttribute("aria-hidden", "true");
      return;
    }

    notificationBadge.textContent = value > 99 ? "99+" : String(value);
    notificationBadge.hidden = false;
    notificationBadge.classList.add("is-visible");
    notificationBadge.setAttribute("aria-hidden", "false");
  }

  function notificationMarkup(item) {
    const readClass = item.read_at ? "" : " is-unread";
    const url = item.action_url || "#";
    return `
      <a class="notificationItem${readClass}" href="${url}" data-id="${item.id}">
        <strong>${escapeHtml(item.title || "Notification")}</strong>
        <span>${escapeHtml(item.body || "")}</span>
        <small>${escapeHtml(item.source_module || "")}</small>
      </a>
    `;
  }

  function renderNotifications(items) {
    if (!notificationList) return;
    notificationList.innerHTML = items.length
      ? items.map(notificationMarkup).join("")
      : '<div class="notificationEmpty">No notifications yet.</div>';
  }

  async function ensurePortalSession() {
    const res = await fetch("/api/v1/sso/check", {
      method: "GET",
      headers: { Accept: "application/json" },
      credentials: "include",
    });
    if (!res.ok) return false;
    const data = await res.json();
    return Boolean(data.success && data.authenticated);
  }

  async function loadNotifications() {
    if (!notificationList) return false;

    const response = await fetch("/portal/notifications", {
      method: "GET",
      headers: portalHeaders(),
      credentials: "include",
    });

    if (response.status === 401) {
      return false;
    }

    if (!response.ok) {
      return false;
    }

    const payload = await response.json();
    setUnreadCount(payload.unread_count);
    renderNotifications(payload.data || []);
    return true;
  }

  function closeNotifications() {
    notificationPanel.hidden = true;
    notificationButton.setAttribute("aria-expanded", "false");
  }

  async function toggleNotifications() {
    const opening = notificationPanel.hidden;
    notificationPanel.hidden = !opening;
    notificationButton.setAttribute("aria-expanded", opening ? "true" : "false");
    if (opening) {
      const authed = await ensurePortalSession();
      if (!authed) {
        setUnreadCount(0);
        renderNotifications([]);
        return;
      }

      await loadNotifications();
    }
  }

  notificationButton.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    toggleNotifications();
  });

  document.addEventListener(
    "click",
    (e) => {
      const host = notificationHost || notificationButton;
      if (host.contains(e.target)) {
        return;
      }
      closeNotifications();
    },
    true,
  );

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeNotifications();
    }
  });

  notificationList?.addEventListener("click", async (e) => {
    const item = e.target.closest(".notificationItem");
    if (!item?.dataset.id) return;
    await fetch(`/portal/notifications/${item.dataset.id}/read`, {
      method: "PATCH",
      headers: portalHeaders(),
      credentials: "include",
    });
  });

  markAllNotificationsRead?.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();
    const response = await fetch("/portal/notifications/read-all", {
      method: "PATCH",
      headers: portalHeaders(),
      credentials: "include",
    });
    if (response.ok) {
      setUnreadCount(0);
      await loadNotifications();
    }
  });

  function connectRealtimeNotifications() {
    if (!reverbEnabled || !userId || typeof window.initDeorisEcho !== "function") {
      // Fallback to polling if Reverb is not available
      startPolling();
      return;
    }

    const echo = window.initDeorisEcho();
    if (!echo) {
      // Fallback to polling if Echo initialization fails
      startPolling();
      return;
    }

    const channel = echo.private(`users.${userId}.notifications`);

    channel.listen(".notification.created", (event) => {
      setUnreadCount(event.unread_count);
      // Prepend the new notification to the list without a full reload
      if (notificationList) {
        const item = event.notification;
        const existing = notificationList.querySelector(`[data-id="${item.id}"]`);
        if (!existing) {
          const emptyEl = notificationList.querySelector(".notificationEmpty");
          if (emptyEl) emptyEl.remove();
          notificationList.insertAdjacentHTML("afterbegin", notificationMarkup(item));
        }
      }
    });

    channel.error(() => {
      // Reverb not running — fallback to polling
      startPolling();
    });
  }

  let pollingInterval = null;

  function startPolling() {
    if (pollingInterval) return; // Already polling
    // Poll every 30 seconds for new notifications
    pollingInterval = setInterval(async () => {
      const authed = await ensurePortalSession();
      if (authed) {
        await loadNotifications();
      }
    }, 30000);
  }

  async function boot() {
    const authed = await ensurePortalSession();
    if (!authed) {
      return;
    }

    // Subscribe to the real-time channel FIRST — before any async fetches —
    // so we never miss a notification that arrives while loadNotifications() is in flight.
    connectRealtimeNotifications();

    // Load initial notification count on page load
    await loadNotifications();
  }

  boot();
})();
