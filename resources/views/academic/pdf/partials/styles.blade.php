@page {
    margin: 18mm 16mm 18mm;
}

body {
    margin: 0;
    padding: 0;
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #1f2937;
}

/* ENCABEZADO INSTITUCIONAL */

.report-header {
    position: relative;
    height: 68px;
    margin: 0 0 21px;
    border-bottom: 3px solid {{ $branding['primary'] }};
}

.report-logo {
    position: absolute;
    top: 0;
    left: 0;
    width: 50px;
    max-height: 56px;
}

.report-school {
    margin-left: 65px;
    margin-right: 132px;
    padding-top: 5px;
}

.report-school-name {
    font-size: 14px;
    line-height: 1.25;
    font-weight: bold;
    color: {{ $branding['secondary'] }};
}

.report-school-detail {
    margin-top: 4px;
    font-size: 8.5px;
    color: #64748b;
}

.report-classification {
    position: absolute;
    top: 7px;
    right: 0;
    width: 126px;
    text-align: right;
    font-size: 8px;
    line-height: 1.6;
    font-weight: bold;
    color: {{ $branding['primary'] }};
}

/* TÍTULOS */

h1 {
    margin: 0 0 7px;
    font-size: 17px;
    color: {{ $branding['secondary'] }};
}

h2 {
    margin: 20px 0 9px;
    font-size: 12px;
    color: {{ $branding['secondary'] }};
}

.muted {
    font-size: 9px;
    color: #64748b;
}

/* DATOS DEL ALUMNO O GRUPO */

.identity {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin: 16px 0;
}

.identity td {
    padding: 8px 10px;
    background-color: #f3f6fa;
    border: 1px solid #e2e8f0;
    vertical-align: top;
}

.identity .label {
    display: block;
    margin-bottom: 4px;
    font-size: 8px;
    text-transform: uppercase;
    color: #64748b;
}

.identity .value {
    display: block;
    font-size: 10px;
    font-weight: bold;
}

/* TABLAS DE CALIFICACIONES */

.grid-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
}

.grid-table thead {
    display: table-header-group;
}

.grid-table tr {
    page-break-inside: avoid;
}

.grid-table th {
    padding: 8px 9px;
    background: #e9eef5;
    color: #475569;
    font-size: 8px;
    text-align: left;
    text-transform: uppercase;
}

.grid-table td {
    padding: 9px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 9px;
}

.grid-table td:last-child,
.grid-table th:last-child {
    text-align: right;
}

.grade {
    font-weight: bold;
    color: #047857;
}

.low {
    font-weight: bold;
    color: #be123c;
}

/* PROMEDIOS */

.average {
    margin-top: 10px;
    padding: 10px 0;
    text-align: right;
    font-size: 10px;
    font-weight: bold;
    color: {{ $branding['secondary'] }};
}

/* OBSERVACIONES */

.notice {
    margin-top: 18px;
    padding: 10px 12px;
    background-color: #f8fafc;
    border-left: 3px solid {{ $branding['primary'] }};
    color: #475569;
    font-size: 8.5px;
    line-height: 1.5;
}

/* PIE DE DOCUMENTO */

.pdf-footer {
    position: static;
    margin-top: 22px;
    padding-top: 8px;
    border-top: 1px solid #cbd5e1;
    font-size: 8px;
    color: #64748b;
}

.pdf-footer-info {
    margin-top: 5px;
    color: #64748b;
}

/* SALTOS DE PÁGINA */

.page-section {
    page-break-inside: auto;
}
