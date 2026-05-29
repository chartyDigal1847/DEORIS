<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ── Live stat cards ──────────────────────────────────────────── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="adminStatCards">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Students</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900" id="stat-total-students">—</p>
                    <p class="text-xs text-gray-400 mt-1">Registered in portal</p>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Instructors</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900" id="stat-total-instructors">—</p>
                    <p class="text-xs text-gray-400 mt-1">Active faculty</p>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending Admissions</p>
                    <p class="mt-1 text-3xl font-bold text-orange-600" id="stat-pending-admissions">—</p>
                    <p class="text-xs text-gray-400 mt-1">Awaiting review</p>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Cleared Students</p>
                    <p class="mt-1 text-3xl font-bold text-green-600" id="stat-cleared-students">—</p>
                    <p class="text-xs text-gray-400 mt-1">ClearCheck passed</p>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Enrolled Students</p>
                    <p class="mt-1 text-3xl font-bold text-blue-600" id="stat-enrolled-students">—</p>
                    <p class="text-xs text-gray-400 mt-1">Currently enrolled</p>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Events Today</p>
                    <p class="mt-1 text-3xl font-bold text-purple-600" id="stat-events-today">—</p>
                    <p class="text-xs text-gray-400 mt-1">Ecosystem events received</p>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Failed Events</p>
                    <p class="mt-1 text-3xl font-bold text-red-600" id="stat-events-failed">—</p>
                    <p class="text-xs text-gray-400 mt-1">Require attention</p>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Users</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900" id="stat-total-users">—</p>
                    <p class="text-xs text-gray-400 mt-1">All roles combined</p>
                </div>
            </div>

            {{-- ── Service Registry ─────────────────────────────────────────── --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6" id="serviceRegistry">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Service Registry</h3>
                        <p class="text-sm text-gray-500">Live status of all registered DEORIS ecosystem services.</p>
                    </div>
                    <button id="refreshServices" type="button" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Refresh
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Service</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">URL</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">API</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Status</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Health</th>
                            </tr>
                        </thead>
                        <tbody id="serviceRegistryTableBody" class="divide-y divide-gray-100 bg-white"></tbody>
                    </table>
                </div>
                <p id="serviceRegistryEmpty" class="hidden text-sm text-gray-500 py-4">No services registered.</p>
            </div>

            {{-- ── Event Hub Monitor ────────────────────────────────────────── --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6" id="eventMonitoring">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Event Hub Monitor</h3>
                        <p class="text-sm text-gray-500">Live view of ecosystem events ingested by the portal.</p>
                    </div>
                    <div class="flex gap-2">
                        <select id="eventStatusFilter" class="rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">All statuses</option>
                            <option value="received">Received</option>
                            <option value="processing">Processing</option>
                            <option value="processed">Processed</option>
                            <option value="failed">Failed</option>
                        </select>
                        <button id="refreshEventLogs" type="button" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Refresh
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Event</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Module</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Status</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Received</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Error</th>
                            </tr>
                        </thead>
                        <tbody id="eventLogTableBody" class="divide-y divide-gray-100 bg-white"></tbody>
                    </table>
                </div>
                <p id="eventLogEmpty" class="hidden text-sm text-gray-500 py-4">No event logs yet.</p>
            </div>

        </div>
    </div>

    @push('scripts')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            function portalHeaders() {
                return {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                };
            }

            // ── Admin stats ──────────────────────────────────────────────────
            async function loadAdminStats() {
                const response = await fetch('/api/v1/admin/stats', {
                    headers: portalHeaders(),
                    credentials: 'include',
                });
                if (!response.ok) return;
                const { data } = await response.json();
                if (!data) return;

                const map = {
                    'stat-total-students':    data.total_students,
                    'stat-total-instructors': data.total_instructors,
                    'stat-pending-admissions': data.pending_admissions,
                    'stat-cleared-students':  data.cleared_students,
                    'stat-enrolled-students': data.enrolled_students,
                    'stat-events-today':      data.events_today,
                    'stat-events-failed':     data.events_failed,
                    'stat-total-users':       data.total_users,
                };

                for (const [id, value] of Object.entries(map)) {
                    const el = document.getElementById(id);
                    if (el) el.textContent = value ?? '—';
                }
            }

            // ── Service registry ─────────────────────────────────────────────
            const serviceTableBody = document.getElementById('serviceRegistryTableBody');
            const serviceEmpty     = document.getElementById('serviceRegistryEmpty');
            const refreshServices  = document.getElementById('refreshServices');

            function statusBadge(status) {
                const classes = {
                    active:      'bg-green-100 text-green-800',
                    inactive:    'bg-gray-100 text-gray-800',
                    degraded:    'bg-yellow-100 text-yellow-800',
                    maintenance: 'bg-blue-100 text-blue-800',
                };
                return `<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${classes[status] || 'bg-gray-100 text-gray-800'}">${status}</span>`;
            }

            async function loadServices() {
                const response = await fetch('/api/v1/services', {
                    headers: portalHeaders(),
                    credentials: 'include',
                });
                if (!response.ok) return;
                const { data } = await response.json();
                if (!serviceTableBody) return;

                serviceTableBody.innerHTML = (data || []).map((s) => `
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900">${s.label}</td>
                        <td class="px-3 py-2 text-gray-500 text-xs">${s.url}</td>
                        <td class="px-3 py-2 text-gray-500">${s.api_version}</td>
                        <td class="px-3 py-2">${statusBadge(s.status)}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center gap-1 text-xs ${s.health_ok ? 'text-green-600' : 'text-red-600'}">
                                <span>${s.health_ok ? '●' : '●'}</span>
                                ${s.health_ok ? 'Healthy' : 'Unhealthy'}
                            </span>
                        </td>
                    </tr>
                `).join('');

                if (serviceEmpty) {
                    serviceEmpty.classList.toggle('hidden', (data || []).length > 0);
                }
            }

            refreshServices?.addEventListener('click', loadServices);

            // ── Event logs ───────────────────────────────────────────────────
            const tableBody    = document.getElementById('eventLogTableBody');
            const emptyState   = document.getElementById('eventLogEmpty');
            const statusFilter = document.getElementById('eventStatusFilter');
            const refreshBtn   = document.getElementById('refreshEventLogs');

            async function loadEventLogs() {
                const params = new URLSearchParams();
                if (statusFilter?.value) params.set('status', statusFilter.value);

                const response = await fetch(`/portal/event-logs?${params.toString()}`, {
                    headers: portalHeaders(),
                    credentials: 'include',
                });
                if (!response.ok) return;

                const payload = await response.json();
                const rows = payload.data || [];

                if (!tableBody) return;

                tableBody.innerHTML = rows.map((row) => `
                    <tr>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900">${row.event_name}</div>
                            <div class="text-xs text-gray-500">${row.event_id}</div>
                        </td>
                        <td class="px-3 py-2 text-gray-700">${row.source_module}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusClass(row.status)}">${row.status}</span>
                        </td>
                        <td class="px-3 py-2 text-gray-500">${row.received_at ?? '—'}</td>
                        <td class="px-3 py-2 text-red-600">${row.error ?? ''}</td>
                    </tr>
                `).join('');

                if (emptyState) {
                    emptyState.classList.toggle('hidden', rows.length > 0);
                }
            }

            function statusClass(status) {
                return {
                    received:   'bg-blue-100 text-blue-800',
                    processing: 'bg-yellow-100 text-yellow-800',
                    processed:  'bg-green-100 text-green-800',
                    failed:     'bg-red-100 text-red-800',
                }[status] || 'bg-gray-100 text-gray-800';
            }

            statusFilter?.addEventListener('change', loadEventLogs);
            refreshBtn?.addEventListener('click', loadEventLogs);

            // ── Real-time event monitoring via Reverb ────────────────────────
            if (window.Echo) {
                window.Echo.private('event-monitoring')
                    .listen('.event.processed', () => {
                        loadEventLogs();
                        loadAdminStats();
                    });
            }

            // ── Initial load ─────────────────────────────────────────────────
            loadAdminStats();
            loadServices();
            loadEventLogs();

            // Refresh stats every 60 seconds
            setInterval(loadAdminStats, 60_000);
        })();
    </script>
    @endpush
</x-app-layout>
