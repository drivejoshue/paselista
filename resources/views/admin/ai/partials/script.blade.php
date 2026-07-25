<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('sp-ai-root');

    if (!root) {
        return;
    }

    const messages = document.getElementById('ai-messages');
    const form = document.getElementById('ai-chat-form');
    const textarea = document.getElementById('ai-question');
    const sendButton = document.getElementById('ai-send-button');
    const conversationIdInput = document.getElementById('ai-conversation-id');
    const scopeType = document.getElementById('ai-scope-type');
    const scopeId = document.getElementById('ai-scope-id');
    const groupField = document.getElementById('ai-group-field');
    const studentField = document.getElementById('ai-student-field');
    const groupSelect = document.getElementById('ai-group-select');
    const studentSelect = document.getElementById('ai-student-select');
    const periodFrom = document.getElementById('ai-period-from');
    const periodTo = document.getElementById('ai-period-to');
    const scopeLabel = document.getElementById('ai-scope-label');
    const periodLabel = document.getElementById('ai-period-label');
    const quotaUsed = document.getElementById('ai-quota-used');
    const quotaLimit = document.getElementById('ai-quota-limit');
    const proMode = document.getElementById('ai-pro-mode');
    const proModeHint = document.getElementById('ai-pro-mode-hint');

    const fastUnits = Math.max(
        1,
        Number(root.dataset.fastUnits ?? 1)
    );

    const proUnits = Math.max(
        1,
        Number(root.dataset.proUnits ?? 6)
    );

    let usedCredits = Math.max(
        0,
        Number(root.dataset.usedCredits ?? 0)
    );

    let creditLimit = Math.max(
        1,
        Number(root.dataset.creditLimit ?? 1)
    );

    const proAllowed =
        root.dataset.proAllowed === '1';

    const pollingIntervalMs = 2500;

    let pollingTimer = null;
    let pollingFailures = 0;

    const processingModelTier =
        root.dataset.processingTier === 'pro'
            ? 'pro'
            : 'fast';

    let currentRunId = root.dataset.processingRun
        ? Number(root.dataset.processingRun)
        : null;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const routeForRun = (template, runId) => template.replace(
        '__RUN__',
        String(runId)
    );

    const scrollToBottom = () => {
        messages.scrollTop = messages.scrollHeight;
    };

    const formatDate = (value) => {
        if (!value) {
            return '—';
        }

        const [year, month, day] = value.split('-');
        return `${day}/${month}/${year}`;
    };

    const autoResize = () => {
        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, 160)}px`;
    };

    const syncScope = () => {
        const type = scopeType.value;

        groupField.hidden = type !== 'group';
        studentField.hidden = type !== 'student';

        if (type === 'group') {
            scopeId.value = groupSelect.value;
            scopeLabel.textContent = groupSelect.options[
                groupSelect.selectedIndex
            ]?.textContent.trim() || 'Grupo';
        } else if (type === 'student') {
            scopeId.value = studentSelect.value;
            scopeLabel.textContent = studentSelect.options[
                studentSelect.selectedIndex
            ]?.textContent.trim() || 'Alumno';
        } else {
            scopeId.value = '';
            scopeLabel.textContent = 'Toda la escuela';
        }

        periodLabel.textContent = `${formatDate(periodFrom.value)} – ${formatDate(periodTo.value)}`;
    };

    const remainingCredits = () =>
        Math.max(
            0,
            creditLimit - usedCredits
        );

    const requestedModelTier = () =>
        proAllowed
        && proMode
        && proMode.checked
            ? 'pro'
            : 'fast';

    const requestedUnits = () =>
        requestedModelTier() === 'pro'
            ? proUnits
            : fastUnits;

    const syncQuotaControls = () => {
        if (quotaUsed) {
            quotaUsed.textContent =
                String(usedCredits);
        }

        if (quotaLimit) {
            quotaLimit.textContent =
                String(creditLimit);
        }

        if (proMode) {
            const unavailable =
                remainingCredits()
                < proUnits;

            proMode.disabled = unavailable;

            if (unavailable) {
                proMode.checked = false;
            }
        }

        if (proModeHint) {
            proModeHint.textContent =
                remainingCredits() < proUnits
                    ? `Requiere ${proUnits} créditos; quedan ${remainingCredits()}`
                    : `${proUnits} créditos · hasta 90 s`;
        }

        if (!currentRunId) {
            sendButton.disabled =
                remainingCredits()
                < requestedUnits();
        }
    };

    const removeWelcome = () => {
        document.getElementById('ai-welcome')?.remove();
    };

    const appendUserMessage = (question) => {
        removeWelcome();

        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex justify-content-end mb-4';
        wrapper.innerHTML = `
            <div class="bg-primary text-white rounded-3 p-3 sp-ai-user-bubble">
                ${escapeHtml(question)}
            </div>
        `;

        messages.appendChild(wrapper);
        scrollToBottom();
    };

    const appendThinkingMessage = (
        runId,
        modelTier = 'fast'
    ) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex align-items-start gap-3 mb-4';
        wrapper.dataset.thinkingRun = String(runId);
        wrapper.innerHTML = `
            <span class="avatar avatar-sm bg-blue-lt flex-shrink-0">
                <i class="ti ti-brain"></i>
            </span>

            <div class="card card-sm sp-ai-assistant-content">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-2 fw-bold">
                        <span>PaseLista IA está pensando</span>
                        ${modelTier === 'pro'
                            ? `<span class="badge bg-purple-lt">
                                <i class="ti ti-sparkles me-1"></i>
                                Avanzado · ${proUnits} créditos
                               </span>`
                            : `<span class="badge bg-blue-lt">
                                Rápido · ${fastUnits} crédito
                               </span>`
                        }
                        <span>
                            <span class="sp-ai-thinking-dot">•</span>
                            <span class="sp-ai-thinking-dot">•</span>
                            <span class="sp-ai-thinking-dot">•</span>
                        </span>
                    </div>
                    <div class="list-group list-group-flush mt-2 ai-stage-list"></div>
                </div>
            </div>
        `;

        messages.appendChild(wrapper);
        scrollToBottom();
        return wrapper;
    };

    const renderStages = (wrapper, events) => {
        const container = wrapper.querySelector('.ai-stage-list');

        container.innerHTML = (events ?? []).map((event) => {
            const icon = event.status === 'completed'
                ? 'ti-circle-check text-success'
                : event.status === 'failed'
                    ? 'ti-circle-x text-danger'
                    : 'ti-loader-2 text-primary';

            return `
                <div class="list-group-item px-0 py-2 border-0">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ${icon} mt-1"></i>
                        <div>
                            <div>${escapeHtml(event.label)}</div>
                            ${event.public_detail
                                ? `<div class="text-secondary small">${escapeHtml(event.public_detail)}</div>`
                                : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        scrollToBottom();
    };

    const renderListSection = (title, icon, items) => {
        if (!Array.isArray(items) || !items.length) {
            return '';
        }

        return `
            <div class="mt-4">
                <div class="fw-bold mb-2">
                    <i class="ti ${icon} me-1"></i>
                    ${escapeHtml(title)}
                </div>
                <ul class="mb-0">
                    ${items.map((item) => `<li class="mb-2">${escapeHtml(item)}</li>`).join('')}
                </ul>
            </div>
        `;
    };

    const renderChartCards = (charts, runId) => {
        if (!Array.isArray(charts) || !charts.length) {
            return '';
        }

        return `
            <div class="row g-3 mt-3">
                ${charts.map((chart, index) => {
                    const chartId = `dynamic-chart-${runId}-${index}`;
                    const encoded = escapeHtml(
                        JSON.stringify(chart)
                    );

                    return `
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div>
                                        <h3 class="card-title">
                                            ${escapeHtml(chart.title ?? 'Gráfica')}
                                        </h3>
                                        ${chart.description
                                            ? `<div class="text-secondary small">${escapeHtml(chart.description)}</div>`
                                            : ''}
                                    </div>
                                    <div class="card-actions">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-ghost-secondary"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#${chartId}"
                                            aria-label="Contraer gráfica"
                                        >
                                            <i class="ti ti-chevron-up"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="${chartId}" class="collapse show">
                                    <div class="card-body">
                                        <div
                                            class="sp-ai-chart-host"
                                            data-ai-chart="${encoded}"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    };

    const chartValue = (value, suffix = '') => {
        const number = Number(value ?? 0);

        return `${Number.isInteger(number)
            ? number
            : number.toFixed(1)}${suffix ?? ''}`;
    };

    const renderFallbackBarChart = (host, chart) => {
        const data = Array.isArray(chart.data)
            ? chart.data
            : [];

        const series = Array.isArray(chart.series)
            ? chart.series
            : [];

        const maximum = Math.max(
            1,
            ...data.flatMap((row) =>
                series.map((item) =>
                    Number(row[item.key] ?? 0)
                )
            )
        );

        host.innerHTML = `
            <div class="vstack gap-3">
                ${data.map((row) => `
                    <div>
                        <div class="fw-semibold small mb-1">
                            ${escapeHtml(row[chart.x_key] ?? '—')}
                        </div>
                        ${series.map((item, index) => {
                            const value = Number(row[item.key] ?? 0);
                            const width = Math.max(
                                0,
                                Math.min(
                                    100,
                                    (value / maximum) * 100
                                )
                            );

                            const progressClass = [
                                'bg-primary',
                                'bg-success',
                                'bg-warning',
                                'bg-info',
                            ][index % 4];

                            return `
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between text-secondary small mb-1">
                                        <span>${escapeHtml(item.label ?? item.key)}</span>
                                        <span>${escapeHtml(chartValue(value, item.suffix ?? ''))}</span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div
                                            class="progress-bar ${progressClass}"
                                            style="width: ${width}%"
                                        ></div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `).join('')}
            </div>
        `;
    };

    const renderFallbackLineChart = (host, chart) => {
        const data = Array.isArray(chart.data)
            ? chart.data
            : [];

        const series = Array.isArray(chart.series)
            ? chart.series
            : [];

        if (!data.length || !series.length) {
            host.innerHTML = `
                <div class="text-secondary text-center py-5">
                    No hay datos suficientes para graficar.
                </div>
            `;
            return;
        }

        const width = 760;
        const height = 285;
        const left = 48;
        const right = 18;
        const top = 24;
        const bottom = 52;
        const plotWidth = width - left - right;
        const plotHeight = height - top - bottom;

        const values = data.flatMap((row) =>
            series.map((item) =>
                Number(row[item.key] ?? 0)
            )
        );

        const maximum = Math.max(
            100,
            ...values,
            1
        );

        const colors = [
            'var(--tblr-primary)',
            'var(--tblr-success)',
            'var(--tblr-warning)',
            'var(--tblr-info)',
        ];

        const x = (index) => {
            if (data.length <= 1) {
                return left + plotWidth / 2;
            }

            return left
                + (
                    index
                    / (data.length - 1)
                ) * plotWidth;
        };

        const y = (value) =>
            top
            + plotHeight
            - (
                Number(value ?? 0)
                / maximum
            ) * plotHeight;

        const grid = [0, 25, 50, 75, 100]
            .map((percentage) => {
                const gridY = top
                    + plotHeight
                    - (
                        percentage
                        / 100
                    ) * plotHeight;

                return `
                    <line
                        x1="${left}"
                        y1="${gridY}"
                        x2="${width - right}"
                        y2="${gridY}"
                        stroke="var(--tblr-border-color)"
                        stroke-width="1"
                    />
                    <text
                        x="${left - 8}"
                        y="${gridY + 4}"
                        text-anchor="end"
                        font-size="11"
                        fill="currentColor"
                        opacity=".65"
                    >${percentage}%</text>
                `;
            })
            .join('');

        const lines = series.map((item, seriesIndex) => {
            const points = data
                .map((row, index) =>
                    `${x(index)},${y(row[item.key])}`
                )
                .join(' ');

            const circles = data.map((row, index) => `
                <circle
                    cx="${x(index)}"
                    cy="${y(row[item.key])}"
                    r="3.5"
                    fill="${colors[seriesIndex % colors.length]}"
                >
                    <title>
                        ${escapeHtml(row[chart.x_key] ?? '')}: ${escapeHtml(chartValue(row[item.key], item.suffix ?? ''))}
                    </title>
                </circle>
            `).join('');

            return `
                <polyline
                    points="${points}"
                    fill="none"
                    stroke="${colors[seriesIndex % colors.length]}"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                ${circles}
            `;
        }).join('');

        const labelStep = Math.max(
            1,
            Math.ceil(data.length / 8)
        );

        const labels = data.map((row, index) => {
            if (
                index % labelStep !== 0
                && index !== data.length - 1
            ) {
                return '';
            }

            return `
                <text
                    x="${x(index)}"
                    y="${height - 23}"
                    text-anchor="middle"
                    font-size="10"
                    fill="currentColor"
                    opacity=".72"
                >${escapeHtml(row[chart.x_key] ?? '')}</text>
            `;
        }).join('');

        const legend = series.map((item, index) => `
            <span class="d-inline-flex align-items-center gap-1 me-3">
                <span
                    class="rounded-circle d-inline-block"
                    style="width: 8px; height: 8px; background: ${colors[index % colors.length]}"
                ></span>
                ${escapeHtml(item.label ?? item.key)}
            </span>
        `).join('');

        host.innerHTML = `
            <svg
                class="sp-ai-chart-svg"
                viewBox="0 0 ${width} ${height}"
                role="img"
                aria-label="${escapeHtml(chart.title ?? 'Gráfica')}"
            >
                ${grid}
                ${lines}
                ${labels}
            </svg>
            <div class="sp-ai-chart-legend text-secondary text-center mt-2">
                ${legend}
            </div>
        `;
    };

    const apexOptions = (chart) => {
        const data = Array.isArray(chart.data)
            ? chart.data
            : [];

        const series = Array.isArray(chart.series)
            ? chart.series
            : [];

        return {
            chart: {
                type: chart.type === 'line'
                    ? 'line'
                    : 'bar',
                height: 300,
                toolbar: {
                    show: true,
                },
                animations: {
                    enabled: true,
                },
            },
            series: series.map((item) => ({
                name: item.label ?? item.key,
                data: data.map((row) =>
                    Number(row[item.key] ?? 0)
                ),
            })),
            xaxis: {
                categories: data.map((row) =>
                    row[chart.x_key] ?? '—'
                ),
            },
            yaxis: {
                min: 0,
                max: series.every((item) =>
                    item.suffix === '%'
                )
                    ? 100
                    : undefined,
                labels: {
                    formatter(value) {
                        return Number.isInteger(value)
                            ? value
                            : Number(value).toFixed(1);
                    },
                },
            },
            stroke: {
                curve: 'smooth',
                width: chart.type === 'line'
                    ? 3
                    : 0,
            },
            plotOptions: {
                bar: {
                    horizontal: Boolean(
                        chart.horizontal
                    ),
                    borderRadius: 4,
                },
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                position: 'bottom',
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter(value, options) {
                        const item =
                            series[
                                options.seriesIndex
                            ] ?? {};

                        return chartValue(
                            value,
                            item.suffix ?? ''
                        );
                    },
                },
            },
        };
    };

    const mountCharts = (scope = document) => {
        scope
            .querySelectorAll(
                '[data-ai-chart]'
            )
            .forEach((host) => {
                if (host.dataset.chartMounted) {
                    return;
                }

                let chart;

                try {
                    chart = JSON.parse(
                        host.dataset.aiChart
                    );
                } catch (error) {
                    console.error(
                        'Invalid chart payload',
                        error
                    );

                    return;
                }

                host.dataset.chartMounted = '1';

                if (
                    window.ApexCharts
                    && typeof window.ApexCharts
                        === 'function'
                ) {
                    const apex = new window.ApexCharts(
                        host,
                        apexOptions(chart)
                    );

                    apex.render();
                    return;
                }

                if (chart.type === 'line') {
                    renderFallbackLineChart(
                        host,
                        chart
                    );
                    return;
                }

                renderFallbackBarChart(
                    host,
                    chart
                );
            });
    };

    const resultActions = (
        runId,
        targetId,
        hasCharts
    ) => {
        const printUrl = routeForRun(
            root.dataset.printUrlTemplate,
            runId
        );

        const pdfUrl = routeForRun(
            root.dataset.pdfUrlTemplate,
            runId
        );

        return `
            <div class="btn-list mt-3 pt-2 border-top">
                <button
                    type="button"
                    class="btn btn-sm btn-ghost-secondary"
                    data-ai-copy
                    data-copy-target="${escapeHtml(targetId)}"
                >
                    <i class="ti ti-copy me-1"></i>
                    Copiar
                </button>

                ${hasCharts ? '' : `
                    <button
                        type="button"
                        class="btn btn-sm btn-ghost-secondary"
                        data-ai-generate-chart
                        data-run-id="${Number(runId)}"
                    >
                        <i class="ti ti-chart-bar me-1"></i>
                        Generar gráfica
                    </button>
                `}

                <a
                    href="${escapeHtml(printUrl)}"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-sm btn-ghost-secondary"
                >
                    <i class="ti ti-printer me-1"></i>
                    Imprimir
                </a>

                <a
                    href="${escapeHtml(pdfUrl)}"
                    class="btn btn-sm btn-ghost-secondary"
                >
                    <i class="ti ti-file-type-pdf me-1"></i>
                    PDF
                </a>
            </div>
        `;
    };

    const renderResult = (wrapper, result, runId) => {
        const facts = Array.isArray(result.facts) ? result.facts : [];
        const methodology = result.methodology ?? {};
        const targetId = `ai-dynamic-result-${runId}`;

        wrapper.innerHTML = `
            <span class="avatar avatar-sm bg-blue-lt flex-shrink-0">
                <i class="ti ti-brain"></i>
            </span>

            <div class="sp-ai-assistant-content">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <div class="fw-bold">PaseLista IA</div>
                    ${result.model_tier === 'pro'
                        ? `<span class="badge bg-purple-lt">
                            <i class="ti ti-sparkles me-1"></i>
                            Avanzado · ${Number(result.quota_units ?? proUnits)} créditos
                           </span>`
                        : result.model_tier
                            ? `<span class="badge bg-blue-lt">
                                Rápido · ${Number(result.quota_units ?? fastUnits)} crédito
                               </span>`
                            : ''
                    }
                </div>

                <div id="${targetId}" class="sp-ai-answer-content">
                    <div class="sp-ai-answer" data-typewriter></div>

                    ${facts.length ? `
                        <div class="row g-2 mt-3">
                            ${facts.map((fact) => `
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card card-sm h-100">
                                        <div class="card-body">
                                            <div class="text-secondary small">
                                                ${escapeHtml(fact.label ?? 'Indicador')}
                                            </div>
                                            <div class="h3 mb-1">
                                                ${escapeHtml(fact.value ?? '—')}
                                            </div>
                                            ${fact.detail
                                                ? `<div class="text-secondary small">${escapeHtml(fact.detail)}</div>`
                                                : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}

                    <div
                        data-ai-charts-container
                        data-run-id="${Number(runId)}"
                    >
                        ${renderChartCards(result.charts, runId)}
                    </div>

                    ${renderListSection('Patrones', 'ti-chart-dots', result.patterns)}
                    ${renderListSection('Comparaciones', 'ti-arrows-diff', result.comparisons)}
                    ${renderListSection('Hallazgos', 'ti-bulb', result.findings)}
                    ${renderListSection('Recomendaciones', 'ti-checkbox', result.recommendations)}
                    ${renderListSection('Advertencias', 'ti-alert-triangle', result.warnings)}

                    ${(Array.isArray(result.analysis_basis) && result.analysis_basis.length)
                        || Object.keys(methodology).length ? `
                        <div class="accordion mt-4">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button
                                        class="accordion-button collapsed py-2"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#method-${runId}"
                                    >
                                        <i class="ti ti-list-details me-2"></i>
                                        Cómo se obtuvo
                                    </button>
                                </h2>
                                <div id="method-${runId}" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        ${Array.isArray(result.analysis_basis) ? `
                                            <ul>
                                                ${result.analysis_basis.map((item) => `<li class="mb-2">${escapeHtml(item)}</li>`).join('')}
                                            </ul>
                                        ` : ''}
                                        <div class="text-secondary small">
                                            ${Number(methodology.students_considered ?? 0)} alumnos ·
                                            ${Number(methodology.expected_student_days ?? 0)} jornadas-alumno esperadas ·
                                            ${Number(methodology.period_days ?? 0)} días de periodo
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                </div>

                ${resultActions(
                    runId,
                    targetId,
                    Array.isArray(result.charts)
                        && result.charts.length > 0
                )}
            </div>
        `;

        mountCharts(wrapper);

        typewriter(
            wrapper.querySelector('[data-typewriter]'),
            String(result.answer ?? 'Análisis completado.')
        );
    };

    const generateChart = async (
        button
    ) => {
        const runId = Number(
            button.dataset.runId
        );

        if (!runId) {
            return;
        }

        const originalHtml =
            button.innerHTML;

        button.disabled = true;
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1"></span>
            Generando
        `;

        try {
            const response = await fetch(
                routeForRun(
                    root.dataset.chartsUrlTemplate,
                    runId
                ),
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN':
                            root.dataset.csrf,
                    },
                }
            );

            if (!response.ok) {
                throw new Error(
                    await responseError(response)
                );
            }

            const data =
                await response.json();

            const container =
                document.querySelector(
                    `[data-ai-charts-container][data-run-id="${runId}"]`
                );

            if (!container) {
                throw new Error(
                    'No se encontró el contenedor de la gráfica.'
                );
            }

            container.innerHTML =
                renderChartCards(
                    data.charts ?? [],
                    runId
                );

            mountCharts(container);

            button.innerHTML = `
                <i class="ti ti-circle-check me-1"></i>
                Gráfica lista
            `;

            button.disabled = true;

            scrollToBottom();
        } catch (error) {
            console.error(error);

            button.disabled = false;
            button.innerHTML =
                originalHtml;

            window.alert(
                error.message
                || 'No se pudo generar la gráfica.'
            );
        }
    };

    const typewriter = (element, text) => {
        let index = 0;
        element.textContent = '';

        const step = () => {
            const chunk = text.slice(index, index + 6);
            element.textContent += chunk;
            index += chunk.length;
            scrollToBottom();

            if (index < text.length) {
                window.setTimeout(step, 12);
            }
        };

        step();
    };

    const renderError = (wrapper, message) => {
        wrapper.innerHTML = `
            <span class="avatar avatar-sm bg-red-lt flex-shrink-0">
                <i class="ti ti-alert-circle"></i>
            </span>
            <div class="alert alert-danger mb-0 flex-fill">
                ${escapeHtml(message)}
            </div>
        `;
    };

    const schedulePoll = (
        runId,
        wrapper,
        delay = pollingIntervalMs
    ) => {
        window.clearTimeout(pollingTimer);

        pollingTimer = window.setTimeout(
            () => pollRun(runId, wrapper),
            delay
        );
    };

    const pollRun = async (runId, wrapper) => {
        currentRunId = runId;
        window.clearTimeout(pollingTimer);

        try {
            const response = await fetch(
                routeForRun(
                    root.dataset.statusUrlTemplate,
                    runId
                ),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    cache: 'no-store',
                }
            );

            if (response.status === 429) {
                const retryAfter = Math.max(
                    5,
                    Number(
                        response.headers.get(
                            'Retry-After'
                        )
                        ?? 5
                    )
                );

                schedulePoll(
                    runId,
                    wrapper,
                    retryAfter * 1000
                );

                return;
            }

            if (!response.ok) {
                throw new Error(
                    await responseError(response)
                );
            }

            pollingFailures = 0;

            const data = await response.json();
            renderStages(wrapper, data.events);

            if (data.quota) {
                usedCredits = Number(
                    data.quota.used
                    ?? usedCredits
                );

                creditLimit = Number(
                    data.quota.limit
                    ?? creditLimit
                );

                syncQuotaControls();
            }

            if (data.run.status === 'success') {
                if (
                    data.run.period_from
                    && data.run.period_to
                ) {
                    periodFrom.value =
                        data.run.period_from;

                    periodTo.value =
                        data.run.period_to;

                    syncScope();
                }

                renderResult(
                    wrapper,
                    data.run.result ?? {},
                    runId
                );

                currentRunId = null;
                syncQuotaControls();
                return;
            }

            if (data.run.status === 'error') {
                renderError(
                    wrapper,
                    data.run.error_message
                    || 'No se pudo completar el análisis.'
                );

                currentRunId = null;
                syncQuotaControls();
                return;
            }

            schedulePoll(
                runId,
                wrapper
            );
        } catch (error) {
            console.error(error);

            pollingFailures += 1;

            const delay = Math.min(
                15000,
                2500 * (2 ** Math.min(
                    pollingFailures,
                    3
                ))
            );

            schedulePoll(
                runId,
                wrapper,
                delay
            );
        }
    };

    const responseError = async (response) => {
        const data = await response.json().catch(() => ({}));

        if (data.errors) {
            return Object.values(data.errors).flat()[0];
        }

        return data.message || `Error HTTP ${response.status}`;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (currentRunId) {
            return;
        }

        syncScope();
        const question = textarea.value.trim();

        if (!question) {
            return;
        }

        if (['group', 'student'].includes(scopeType.value) && !scopeId.value) {
            window.alert('Selecciona el grupo o alumno en Contexto.');
            return;
        }

        const modelTier =
            requestedModelTier();

        const units =
            requestedUnits();

        if (
            remainingCredits()
            < units
        ) {
            window.alert(
                `No hay créditos suficientes. Quedan ${remainingCredits()} y esta consulta requiere ${units}.`
            );

            syncQuotaControls();
            return;
        }

        currentRunId = -1;

        appendUserMessage(question);
        textarea.value = '';
        autoResize();
        sendButton.disabled = true;

        const temporaryThinking =
            appendThinkingMessage(
                'pending',
                modelTier
            );

        try {
            const response = await fetch(root.dataset.analyzeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': root.dataset.csrf,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: conversationIdInput.value || null,
                    scope_type: scopeType.value,
                    scope_id: scopeId.value || null,
                    period_from: periodFrom.value,
                    period_to: periodTo.value,
                    model_tier: modelTier,
                    question,
                }),
            });

            if (!response.ok) {
                throw new Error(await responseError(response));
            }

            const data = await response.json();
            const conversation = data.conversation;
            const run = data.run;

            conversationIdInput.value = conversation.id;
            temporaryThinking.dataset.thinkingRun = String(run.id);
            document.getElementById('ai-chat-title').textContent = conversation.title;

            const url = new URL(window.location.href);
            url.searchParams.set('conversation', conversation.id);
            window.history.replaceState({}, '', url);

            if (data.resolved_period) {
                periodFrom.value =
                    data.resolved_period.from;

                periodTo.value =
                    data.resolved_period.to;

                syncScope();
            }

            if (data.resolved_scope?.resolved_automatically) {
                scopeType.value = data.resolved_scope.type;

                if (data.resolved_scope.type === 'student') {
                    studentSelect.value = String(data.resolved_scope.id);
                } else if (data.resolved_scope.type === 'group') {
                    groupSelect.value = String(data.resolved_scope.id);
                }

                syncScope();
            }

            if (proMode) {
                proMode.checked = false;
            }

            pollRun(run.id, temporaryThinking);
        } catch (error) {
            renderError(
                temporaryThinking,
                error.message || 'No se pudo enviar la consulta.'
            );
            currentRunId = null;
            syncQuotaControls();
        }
    });

    textarea.addEventListener('input', autoResize);
    textarea.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    proMode?.addEventListener(
        'change',
        syncQuotaControls
    );

    scopeType.addEventListener('change', syncScope);
    groupSelect.addEventListener('change', syncScope);
    studentSelect.addEventListener('change', syncScope);
    periodFrom.addEventListener('change', syncScope);
    periodTo.addEventListener('change', syncScope);

    document.querySelectorAll('.ai-suggestion').forEach((button) => {
        button.addEventListener('click', () => {
            textarea.value = button.dataset.question;
            autoResize();
            textarea.focus();
        });
    });

    document.addEventListener('click', async (event) => {
        const chartButton = event.target.closest(
            '[data-ai-generate-chart]'
        );

        if (chartButton) {
            await generateChart(
                chartButton
            );

            return;
        }

        const button = event.target.closest('[data-ai-copy]');

        if (!button) {
            return;
        }

        const target = document.getElementById(button.dataset.copyTarget);

        if (!target) {
            return;
        }

        const text = target.innerText.trim();

        try {
            await navigator.clipboard.writeText(text);
        } catch (error) {
            const helper = document.createElement('textarea');
            helper.value = text;
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            document.body.appendChild(helper);
            helper.select();
            document.execCommand('copy');
            helper.remove();
        }

        const original = button.innerHTML;
        button.innerHTML = '<i class="ti ti-check me-1"></i>Copiado';
        window.setTimeout(() => {
            button.innerHTML = original;
        }, 1400);
    });

    const search = document.getElementById('ai-conversation-search');
    search?.addEventListener('input', () => {
        const value = search.value.trim().toLowerCase();
        document.querySelectorAll('[data-conversation-item]').forEach((item) => {
            item.hidden = !item.dataset.title.includes(value);
        });
    });

    document.querySelectorAll('.ai-rename-conversation').forEach((button) => {
        button.addEventListener('click', async () => {
            const title = window.prompt(
                'Nuevo nombre de la conversación:',
                button.dataset.title
            );

            if (!title?.trim()) {
                return;
            }

            const response = await fetch(button.dataset.url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': root.dataset.csrf,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ title: title.trim() }),
            });

            if (response.ok) {
                window.location.reload();
            }
        });
    });

    document.querySelectorAll('.ai-archive-conversation').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!window.confirm('¿Archivar esta conversación?')) {
                return;
            }

            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': root.dataset.csrf,
                },
            });

            if (response.ok) {
                window.location.href = root.dataset.indexUrl;
            }
        });
    });

    syncScope();
    autoResize();
    syncQuotaControls();
    mountCharts(document);
    scrollToBottom();

    if (currentRunId) {
        let wrapper = document.querySelector(
            `[data-thinking-run="${currentRunId}"]`
        );

        if (!wrapper) {
            removeWelcome();
            wrapper = appendThinkingMessage(
                currentRunId,
                processingModelTier
            );
        }

        sendButton.disabled = true;
        pollRun(currentRunId, wrapper);
    }
});
</script>
