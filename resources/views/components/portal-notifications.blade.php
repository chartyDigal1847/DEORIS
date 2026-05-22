<div class="notificationHost" id="notificationHost">
    <button class="iconButton notificationButton" id="notificationButton" type="button" aria-label="Notifications" aria-expanded="false">
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
            <path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 7h18s-3 0-3-7" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>
        <span class="notificationButton__badge" id="notificationBadge" hidden aria-hidden="true"></span>
    </button>
    <div class="notificationPanel" id="notificationPanel" hidden>
        <div class="notificationPanel__head">
            <strong>Notifications</strong>
            <button id="markAllNotificationsRead" type="button">Mark all read</button>
        </div>
        <div class="notificationPanel__list" id="notificationList"></div>
    </div>
</div>
