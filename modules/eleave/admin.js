// eleave/admin.js
EventManager.on('router:initialized', () => {
    RouterManager.register('/', {
        template: 'eleave/dashboard.html',
        title: 'Dashboard',
        requireAuth: true
    });

    RouterManager.register('/my-leaves', {
        template: 'eleave/my-leaves.html',
        title: 'My Leave Requests',
        requireAuth: true
    });

    RouterManager.register('/leave-request', {
        template: 'eleave/request.html',
        title: 'Leave Request',
        menuPath: '/my-leaves',
        requireAuth: true
    });

    RouterManager.register('/leave-approvals', {
        template: 'eleave/approvals.html',
        title: 'Leave Approvals',
        requireAuth: true
    });

    RouterManager.register('/leave-review', {
        template: 'eleave/review.html',
        title: 'Review Leave Request',
        requireAuth: true
    });

    RouterManager.register('/leave-statistics', {
        template: 'eleave/statistics.html',
        title: 'Leave Statistics',
        requireAuth: true
    });

    RouterManager.register('/leave-user-statistics', {
        template: 'eleave/statistics.html',
        title: 'Leave Statistics',
        requireAuth: true
    });

    RouterManager.register('/leave-types', {
        template: 'eleave/leave-types.html',
        title: 'Leave types',
        requireAuth: true
    });

    RouterManager.register('/leave-settings', {
        template: 'eleave/settings.html',
        title: 'Settings',
        requireAuth: true
    });
});

function formatLeaveDaysValue(days) {
    const numericDays = Number(days);
    if (!Number.isFinite(numericDays)) {
        return '0';
    }

    return numericDays
        .toFixed(1)
        .replace(/\.0$/, '')
        .replace(/(\.\d*?[1-9])0+$/, '$1');
}

function formatMyLeavesTotalDaysFooter(rows, cell, table) {
    const summary = table?.dataOptions?.summary;
    if (summary && summary.total_days_text !== undefined && summary.total_days_text !== '') {
        return summary.total_days_text;
    }

    const totalDays = Array.isArray(rows)
        ? rows.reduce((sum, row) => sum + (parseFloat(row?.days) || 0), 0)
        : 0;

    return formatLeaveDaysValue(totalDays);
}

function initLeaveTypes(element, data) {
    const countElement = element.querySelector('[data-leave-type-count]');
    if (!countElement || !window.EventManager) {
        return undefined;
    }

    const updateCount = (event) => {
        if (event?.tableId !== 'leave-types') {
            return;
        }
        const count = Array.isArray(event.data) ? event.data.length : 0;
        countElement.textContent = String(count);
    };

    const initialRows = Array.isArray(data?.options?.data) ? data.options.data : [];
    countElement.textContent = String(initialRows.length);
    EventManager.on('table:render', updateCount);

    return () => {
        EventManager.off('table:render', updateCount);
    };
}

function initEleaveSettings(element, payload) {
    const approveLevel = element.querySelector('#eleave_approve_level');
    if (!approveLevel) {
        return () => {};
    }

    const approveChange = () => {
        element.querySelectorAll('.can-approve').forEach(el => {
            const level = parseInt(el.dataset.level || '0', 10);
            el.style.display = level > 0 && level <= parseInt(approveLevel.value || '0', 10) ? 'flex' : 'none';
        });
    };
    approveLevel.addEventListener('change', approveChange);
    approveChange();

    return () => {
        approveLevel.removeEventListener('change', approveChange);
    };
}

function renderGraphElement(graphElement, options) {
    if (!graphElement || !window.GraphComponent) {
        return false;
    }

    GraphComponent.destroy(graphElement);
    graphElement.innerHTML = '';

    const data = Array.isArray(options?.data) ? options.data : [];
    const hasValues = data.some((series) => Array.isArray(series?.data) && series.data.some((point) => Number(point?.value) > 0));
    if (!hasValues) {
        return false;
    }

    GraphComponent.create(graphElement, {
        autoload: false,
        animation: false,
        maxDataPoints: 0,
        ...options,
        data
    });

    return true;
}

function initLeaveStatistics(element, context) {
    const state = context?.state || {};
    const renderGraph = (selector, options) => {
        renderGraphElement(element.querySelector(selector), options);
    };

    renderGraph('[data-statistics-graph="by-type"]', {
        type: 'bar',
        data: state?.charts?.by_type || [],
        showLegend: true,
        showGrid: true,
        showDataLabels: true,
        legendPosition: 'bottom'
    });

    renderGraph('[data-statistics-graph="by-status"]', {
        type: 'donut',
        data: state?.charts?.by_status || [],
        showLegend: true,
        showGrid: false,
        showAxis: false,
        showAxisLabels: false,
        showDataLabels: true,
        showValueInsteadOfPercent: true,
        legendPosition: 'bottom',
        centerText: String(state?.summary?.total_requests || 0),
        donutThickness: 36
    });

    return () => {
        if (!window.GraphComponent) {
            return;
        }

        GraphComponent.destroy(element.querySelector('[data-statistics-graph="by-type"]'));
        GraphComponent.destroy(element.querySelector('[data-statistics-graph="by-status"]'));
    };
}

window.initLeaveTypes = initLeaveTypes;
window.initEleaveSettings = initEleaveSettings;
window.initLeaveStatistics = initLeaveStatistics;
window.formatMyLeavesTotalDaysFooter = formatMyLeavesTotalDaysFooter;

