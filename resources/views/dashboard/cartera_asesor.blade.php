@extends('template.app')

@section('title', 'Indicadores - Analisis de cartera')

@section('styles')
    <style>
        .portfolio-cards-grid > [class*="col-"] {
            display: flex;
        }

        .portfolio-metric-card {
            width: 100%;
        }

        .portfolio-metric-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1.25rem 0.75rem;
            height: 100%;
        }

        .portfolio-metric-card .card-title {
            min-height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 0.75rem;
        }

        .portfolio-metric-card .metric-value {
            word-break: break-word;
        }

        .portfolio-metric-card .metric-value .metric-icon {
            font-size: 0.85em;
            vertical-align: middle;
            margin-right: 0.15rem;
        }
    </style>
@endsection

@section('content')

    @if (
            auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('admin_credit') ||
            auth()->user()->hasRole('credit_manager') ||
            auth()->user()->hasRole('seller')
        )
        <div class="row mb-4" id="content-analisis">
            <div class="col-12">
                <form class="mb-4">
                    <div class="row align-items-end g-2">
                        @if (
                                auth()->user()->hasRole('admin') ||
                                auth()->user()->hasRole('credit') ||
                                auth()->user()->hasRole('admin_credit') ||
                                auth()->user()->hasRole('credit_manager') ||
                                auth()->user()->hasRole('seller')
                            )
                            @if (auth()->user()->hasRole('admin_credit'))
                                <div class="col-12 col-md-6 col-lg-3">
                                    <div class="mb-0">
                                        <label class="form-label">Jefe de Crédito</label>
                                        <select class="form-select js-credit-manager" name="credit_manager_id">
                                            <option value="">Todos</option>
                                            @foreach ($admincredits as $admincredit)
                                                <option value="{{ $admincredit->id }}" @if ($admincredit->id == request()->credit_manager_id)
                                                selected @endif>{{ $admincredit->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <div class="mb-0">
                                        <label class="form-label">Asesor comercial</label>
                                        <select class="form-select js-seller-select" name="seller_id_2">
                                            <option value="">Seleccionar</option>
                                            @foreach ($sellers as $seller)
                                                <option value="{{ $seller->id }}" data-manager="{{ $seller->credit_manager_id ?? '' }}" @if ($seller->id == request()->seller_id_2) selected @endif>{{ $seller->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @elseif (auth()->user()->hasRole('seller'))
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="mb-0">
                                        <label class="form-label">Asesor comercial</label>
                                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                                        <input type="hidden" name="seller_id_2" value="{{ auth()->user()->id }}">
                                    </div>
                                </div>
                            @elseif (auth()->user()->hasRole('credit_manager'))
                                <div class="col-12 col-md-6 col-lg-3">
                                    <div class="mb-0">
                                        <label class="form-label">Jefe de crédito</label>
                                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                                        <input type="hidden" name="credit_manager_id" value="{{ auth()->user()->id }}">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <div class="mb-0">
                                        <label class="form-label">Asesor comercial</label>
                                        <select class="form-select" name="seller_id_2">
                                            <option value="">Todos</option>
                                            @foreach ($sellers->where('credit_manager_id', auth()->user()->id) as $seller)
                                                <option value="{{ $seller->id }}" @if ($seller->id == request()->seller_id_2) selected @endif>{{ $seller->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @else
                                <div class="col-12 col-md-6 col-lg-3">
                                    <div class="mb-0">
                                        <label class="form-label">Jefe de crédito</label>
                                        <select class="form-select js-credit-manager" name="credit_manager_id">
                                            <option value="">Seleccionar</option>
                                            @foreach ($admincredits as $admincredit)
                                                <option value="{{ $admincredit->id }}" @if ($admincredit->id == request()->credit_manager_id) selected @endif>{{ $admincredit->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <div class="mb-0">
                                        <label class="form-label">Asesor comercial</label>
                                        <select class="form-select js-seller-select" name="seller_id_2">
                                            <option value="">Seleccionar</option>
                                            @foreach ($sellers as $seller)
                                                <option value="{{ $seller->id }}" data-manager="{{ $seller->credit_manager_id ?? '' }}" @if ($seller->id == request()->seller_id_2) selected @endif>{{ $seller->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                        @endif
                        <div class="col-12 col-md-6 col-lg-2">
                            <div class="mb-0">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" class="form-control" name="end_date_2" value="{{ request()->end_date_2 }}">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-auto">
                            <input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
                            <input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
                            <input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
                            <input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
                            <input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
                            <input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
                            <input type="hidden" name="section" class="js-section-input"
                                value="{{ $section ?? (request()->section ?? 'efectivo') }}">
                            <div class="d-flex gap-2 mb-0">
                                <button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
                                <button type="button" class="btn btn-danger" onclick="resetForm()"><i class="ti ti-eraser icon"></i> Limpiar</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="row g-3 portfolio-cards-grid">
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="gross_portfolio" data-title="Detalle de cartera bruta">
                            <div class="card-body text-center">
                                <h5 class="card-title">Cartera bruta</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="ti ti-briefcase metric-icon"></i>S/{{ number_format($cartera_bruta, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="current_portfolio" data-title="Detalle de cartera actual">
                            <div class="card-body text-center">
                                <h5 class="card-title">Cartera actual</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="ti ti-wallet metric-icon"></i>S/{{ number_format($active_clients, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="arrears_1_120" data-title="Detalle de mora 1 a 120 dias">
                            <div class="card-body text-center">
                                <h5 class="card-title">Mora(&lt;120 dias)</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="ti ti-clock-exclamation metric-icon"></i>S/{{ number_format($due_clients, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="arrears_over_120" data-title="Detalle de mora mayor a 120 dias">
                            <div class="card-body text-center">
                                <h5 class="card-title">Mora(&gt;121 dias)</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="ti ti-alert-triangle metric-icon"></i>S/{{ number_format($seller_wallet, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="arrears_total" data-title="Detalle de mora total">
                            <div class="card-body text-center">
                                <h5 class="card-title">Mora total</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="ti ti-sum metric-icon"></i>S/{{ number_format($requested_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="arrears_percent" data-title="Detalle de porcentaje de mora">
                            <div class="card-body text-center">
                                <h5 class="card-title">% de mora</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="ti ti-percentage metric-icon"></i>{{ number_format($due_quotas, 2) }}%</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="active_clients" data-title="Detalle de clientes activos">
                            <div class="card-body text-center">
                                <h5 class="card-title">Clientes activos</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="bi bi-person-circle metric-icon"></i>{{ number_format($cutoff['active_clients'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="clients_over_120" data-title="Detalle de clientes con deuda mayor a 120 dias">
                            <div class="card-body text-center">
                                <h5 class="card-title">Clientes con deuda (&gt;120 dias)</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="bi bi-person-circle metric-icon"></i>{{ number_format($cutoff['clients_over_120'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="individual_group_clients" data-title="Detalle de clientes individuales y grupales">
                            <div class="card-body text-center">
                                <h5 class="card-title">Clientes individuales / grupales</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="bi bi-person-circle metric-icon"></i>{{ number_format($cutoff['individual_clients'] ?? 0) }} / {{ number_format($cutoff['group_clients'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="finished_clients_with_arrears_1_120" data-title="Detalle de clientes finalizados con mora 1 a 120 dias">
                            <div class="card-body text-center">
                                <h5 class="card-title">Clientes finalizados con mora (1-120 dias)</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="bi bi-person-circle metric-icon"></i>{{ number_format($cutoff['finished_clients_with_arrears_1_120'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="disbursed_amount" data-title="Detalle de monto desembolsado">
                            <div class="card-body text-center">
                                <h5 class="card-title">Monto desembolsado</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="ti ti-cash-banknote metric-icon"></i>S/{{ number_format($cutoff['disbursed_amount'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                            data-card="pending_quotas_count" data-title="Detalle de cuotas por pagar">
                            <div class="card-body text-center">
                                <h5 class="card-title"># de cuotas por pagar</h5>
                                <span class="d-block fs-1 text-center fw-semibold metric-value"><i class="ti ti-list-numbers metric-icon"></i>{{ number_format($cutoff['pending_quotas_count'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @if ($evolution)
                    <h3 class="mb-3">Evolucion de cartera</h3>
                    <div class="row g-3 portfolio-cards-grid">
                        <div class="col-6 col-md-6 col-lg-3">
                            <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                                data-card="evolution_initial" data-title="Detalle de saldo inicial">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Saldo inicial</h5>
                                    <span class="d-block fs-1 text-center fw-semibold metric-value">S/{{ number_format($evolution['initial_balance'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 col-lg-3">
                            <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                                data-card="evolution_increments" data-title="Detalle de incrementos">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Incrementos</h5>
                                    <span class="d-block fs-1 text-center fw-semibold metric-value">S/{{ number_format($evolution['increments'], 2) }}</span>
                                    <div class="text-muted small">Capital S/{{ number_format($evolution['disbursed_capital'], 2) }} / Interes S/{{ number_format($evolution['generated_interest'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 col-lg-3">
                            <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                                data-card="evolution_reductions" data-title="Detalle de reducciones">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Reducciones</h5>
                                    <span class="d-block fs-1 text-center fw-semibold metric-value">S/{{ number_format($evolution['reductions'], 2) }}</span>
                                    <div class="text-muted small">Pagos S/{{ number_format($evolution['payments'], 2) }} / Deterioro S/{{ number_format($evolution['deteriorated_over_120'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 col-lg-3">
                            <div class="card portfolio-metric-card js-portfolio-card h-100 mb-0" role="button" tabindex="0"
                                data-card="evolution_final" data-title="Detalle de cartera actual calculada">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Cartera actual calculada</h5>
                                    <span class="d-block fs-1 text-center fw-semibold metric-value">S/{{ number_format($evolution['final_balance'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="modal fade" id="portfolioCardModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="portfolioCardModalTitle">Detalle</h5>
                            <div class="text-muted small">Registros: <span id="portfolioCardTotal">0</span></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead id="portfolioCardTableHead"></thead>
                                <tbody id="portfolioCardTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="portfolioQuotaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="portfolioQuotaModalTitle">Detalle de cuotas y pagos</h5>
                            <div class="text-muted small" id="portfolioQuotaModalSubtitle"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div id="portfolioQuotaContent"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection

@section('scripts')
    <script>
        (function() {
            $('.js-portfolio-card').css('cursor', 'pointer');

            function escapeHtml(value) {
                if (value === null || value === undefined) return '';
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function money(value) {
                return 'S/' + parseFloat(value || 0).toFixed(2);
            }

            function clientName(item) {
                return item.client_type === 'Grupo'
                    ? (item.group_name || '-')
                    : (item.name || item.person_name || '-');
            }

            function setLoading() {
                $('#portfolioCardTableHead').html('');
                $('#portfolioCardTableBody').html(`
                    <tr>
                        <td class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                `);
            }

            function emptyRow(cols) {
                return `<tr><td colspan="${cols}" class="text-center">No se encontraron registros</td></tr>`;
            }

            function quotaStatusBadge(status) {
                return status === 'Pagado'
                    ? '<span class="badge bg-success">Pagado</span>'
                    : '<span class="badge bg-danger">Pendiente</span>';
            }

            function quotaList(values) {
                if (!values || values.length === 0) {
                    return '-';
                }

                return values.map(function(value) {
                    return 'C' + escapeHtml(value);
                }).join(', ');
            }

            function setQuotaLoading() {
                $('#portfolioQuotaContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                `);
            }

            function renderPendingQuotas(data) {
                var contract = data.contract || {};
                var summary = data.summary || {};
                var quotaItems = data.quotas || [];
                var memberItems = data.members || [];
                var paymentItems = data.payments || [];

                $('#portfolioQuotaModalTitle').text('Detalle de cuotas y pagos');
                $('#portfolioQuotaModalSubtitle').text(clientName(contract));

                var summaryHtml = `
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Total programado</div>
                                <div class="fw-semibold fs-4">${money(summary.quotas_total)}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Total pagado</div>
                                <div class="fw-semibold fs-4 text-success">${money(summary.paid_total)}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Saldo pendiente</div>
                                <div class="fw-semibold fs-4 text-danger">${money(summary.debt_total)}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Corte</div>
                                <div class="fw-semibold">${escapeHtml(summary.as_of || 'Hoy')}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Cuotas</div>
                                <div class="fw-semibold">${escapeHtml(summary.quotas_count || 0)}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Cuotas pagadas</div>
                                <div class="fw-semibold text-success">${escapeHtml(summary.paid_quotas_count || 0)}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Cuotas pendientes</div>
                                <div class="fw-semibold text-danger">${escapeHtml(summary.pending_quotas_count || 0)}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Integrantes</div>
                                <div class="fw-semibold">${escapeHtml(summary.members_count || 0)}</div>
                            </div>
                        </div>
                    </div>
                `;

                var quotaRows = quotaItems.map(function(item) {
                    var members = (item.members || []).map(function(member) {
                        return `
                            <div class="mb-2">
                                <div class="fw-semibold">${escapeHtml(member.name || '-')}</div>
                                <div class="small text-muted">${escapeHtml(member.document || '-')}</div>
                                <div class="small">Programado: ${money(member.amount)} | Pagado: ${money(member.paid_total)} | Debe: ${money(member.debt)}</div>
                            </div>
                        `;
                    }).join('');

                    return `
                        <tr>
                            <td>${escapeHtml(item.number || '-')}</td>
                            <td>${escapeHtml(item.date || '-')}</td>
                            <td>${money(item.amount)}</td>
                            <td>${money(item.paid_total)}</td>
                            <td>${money(item.debt)}</td>
                            <td>${quotaStatusBadge(item.status)}</td>
                            <td>${members || '-'}</td>
                        </tr>
                    `;
                }).join('');

                var memberRows = memberItems.map(function(member) {
                    return `
                        <tr>
                            <td>${escapeHtml(member.name || '-')}</td>
                            <td>${escapeHtml(member.document || '-')}</td>
                            <td>${money(member.total_amount)}</td>
                            <td>${money(member.paid_total)}</td>
                            <td>${money(member.debt_total)}</td>
                            <td>${quotaList(member.paid_quotas)}</td>
                            <td>${quotaList(member.pending_quotas)}</td>
                        </tr>
                    `;
                }).join('');

                var paymentRows = paymentItems.map(function(payment) {
                    return `
                        <tr>
                            <td>${escapeHtml(payment.quota_number || '-')}</td>
                            <td>${escapeHtml(payment.member_name || '-')}</td>
                            <td>${money(payment.amount)}</td>
                            <td>${escapeHtml(payment.date || '-')}</td>
                            <td>${escapeHtml(payment.payment_method || '-')}</td>
                            <td>${escapeHtml(payment.due_days !== null && payment.due_days !== undefined ? payment.due_days : '-')}</td>
                            <td>
                                ${payment.image_url
                                    ? `<a href="${escapeHtml(payment.image_url)}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Ver foto</a>`
                                    : '<span class="text-muted">Sin foto</span>'}
                            </td>
                        </tr>
                    `;
                }).join('');

                $('#portfolioQuotaContent').html(`
                    ${summaryHtml}
                    <div class="mb-4">
                        <h6 class="mb-3">Estado por cuota</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Cuota</th>
                                        <th>Fecha</th>
                                        <th>Programado</th>
                                        <th>Pagado</th>
                                        <th>Debe</th>
                                        <th>Estado</th>
                                        <th>Detalle por integrante</th>
                                    </tr>
                                </thead>
                                <tbody>${quotaRows || emptyRow(7)}</tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="mb-3">Resumen por integrante</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Integrante</th>
                                        <th>Documento</th>
                                        <th>Programado</th>
                                        <th>Pagado</th>
                                        <th>Debe</th>
                                        <th>Cuotas pagadas</th>
                                        <th>Cuotas pendientes</th>
                                    </tr>
                                </thead>
                                <tbody>${memberRows || emptyRow(7)}</tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <h6 class="mb-3">Historial de pagos registrados</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Cuota</th>
                                        <th>Integrante</th>
                                        <th>Monto</th>
                                        <th>Fecha pago</th>
                                        <th>Metodo</th>
                                        <th>Dias mora</th>
                                        <th>Comprobante</th>
                                    </tr>
                                </thead>
                                <tbody>${paymentRows || emptyRow(7)}</tbody>
                            </table>
                        </div>
                    </div>
                `);
            }

            function openPendingQuotas(contractId, clientLabel) {
                if (!contractId) {
                    return;
                }

                $('#portfolioQuotaModalTitle').text('Cuotas pendientes');
                $('#portfolioQuotaModalSubtitle').text(clientLabel || '');
                setQuotaLoading();
                $('#portfolioQuotaModal').modal('show');

                $.ajax({
                    url: "{{ route('quotas.api') }}",
                    method: 'GET',
                    data: {
                        contract_id: contractId,
                        as_of: $('[name="end_date_2"]').val() || '',
                        detail_mode: 'portfolio'
                    },
                    success: function(data) {
                        renderPendingQuotas(data || {});
                    },
                    error: function() {
                        $('#portfolioQuotaContent').html('<div class="text-center py-4">No se pudo cargar el detalle</div>');
                    }
                });
            }

            function renderQuotas(items) {
                $('#portfolioCardTableHead').html(`
                    <tr>
                        <th>Pagare</th>
                        <th>Cliente / Grupo</th>
                        <th>Persona</th>
                        <th>Asesor</th>
                        <th>Fecha contrato</th>
                        <th>Cuota</th>
                        <th>Fecha cuota</th>
                        <th>Dias mora</th>
                        <th>Monto cuota</th>
                        <th>Pagado al corte</th>
                        <th>Saldo</th>
                    </tr>
                `);

                var rows = (items || []).map(function(item) {
                    return `
                        <tr>
                            <td>${escapeHtml(item.number_pagare || '-')}</td>
                            <td>${escapeHtml(clientName(item))}</td>
                            <td>${escapeHtml(item.person_name || item.person_document || '-')}</td>
                            <td>${escapeHtml(item.seller_name || '-')}</td>
                            <td>${escapeHtml(item.contract_date || '-')}</td>
                            <td>${escapeHtml(item.quota_number || '-')}</td>
                            <td>${escapeHtml(item.quota_date || '-')}</td>
                            <td>${escapeHtml(item.due_days || 0)}</td>
                            <td>${money(item.quota_amount)}</td>
                            <td>${money(item.paid_to_cutoff)}</td>
                            <td>${money(item.balance)}</td>
                        </tr>
                    `;
                }).join('');

                $('#portfolioCardTableBody').html(rows || emptyRow(11));
            }

            function renderClients(items) {
                $('#portfolioCardTableHead').html(`
                    <tr>
                        <th>Pagare</th>
                        <th>Cliente / Grupo</th>
                        <th>Tipo</th>
                        <th>Clientes</th>
                        <th>Asesor</th>
                        <th>Fecha contrato</th>
                        <th>Capital</th>
                        <th>Saldo</th>
                        <th>Mora 1-120</th>
                        <th>Mora >120</th>
                        <th>Cuotas pendientes</th>
                        <th>Accion</th>
                    </tr>
                `);

                var rows = (items || []).map(function(item) {
                    return `
                        <tr>
                            <td>${escapeHtml(item.number_pagare || '-')}</td>
                            <td>${escapeHtml(clientName(item))}</td>
                            <td>${escapeHtml(item.client_type || '-')}</td>
                            <td>${escapeHtml(item.client_count || 0)}</td>
                            <td>${escapeHtml(item.seller_name || '-')}</td>
                            <td>${escapeHtml(item.date || '-')}</td>
                            <td>${money(item.requested_amount)}</td>
                            <td>${money(item.balance)}</td>
                            <td>${money(item.arrears_1_120)}</td>
                            <td>${money(item.arrears_over_120)}</td>
                            <td>${escapeHtml(item.pending_quotas_count || 0)}</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary js-view-pending-quotas"
                                    data-contract-id="${escapeHtml(item.contract_id || '')}"
                                    data-client-label="${escapeHtml(clientName(item))}">
                                    Ver
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                $('#portfolioCardTableBody').html(rows || emptyRow(12));
            }

            function renderContracts(items) {
                $('#portfolioCardTableHead').html(`
                    <tr>
                        <th>Pagare</th>
                        <th>Cliente / Grupo</th>
                        <th>Tipo</th>
                        <th>Asesor</th>
                        <th>Fecha</th>
                        <th>Capital</th>
                        <th>Interes</th>
                        <th>Total</th>
                        <th>Max mora</th>
                    </tr>
                `);

                var rows = (items || []).map(function(item) {
                    return `
                        <tr>
                            <td>${escapeHtml(item.number_pagare || '-')}</td>
                            <td>${escapeHtml(clientName(item))}</td>
                            <td>${escapeHtml(item.client_type || '-')}</td>
                            <td>${escapeHtml(item.seller_name || '-')}</td>
                            <td>${escapeHtml(item.date || '-')}</td>
                            <td>${money(item.requested_amount)}</td>
                            <td>${money(item.interest)}</td>
                            <td>${money(item.payable_amount || item.requested_amount)}</td>
                            <td>${escapeHtml(item.max_due_days || '-')}</td>
                        </tr>
                    `;
                }).join('');

                $('#portfolioCardTableBody').html(rows || emptyRow(9));
            }

            function renderEvolution(items) {
                $('#portfolioCardTableHead').html(`
                    <tr>
                        <th>Fecha</th>
                        <th>Incrementos</th>
                        <th>Pagos</th>
                        <th>Deterioro >120</th>
                        <th>Saldo</th>
                    </tr>
                `);

                var rows = (items || []).map(function(item) {
                    return `
                        <tr>
                            <td>${escapeHtml(item.date || '-')}</td>
                            <td>${money(item.increments)}</td>
                            <td>${money(item.payments)}</td>
                            <td>${money(item.deteriorated_over_120)}</td>
                            <td>${money(item.balance)}</td>
                        </tr>
                    `;
                }).join('');

                $('#portfolioCardTableBody').html(rows || emptyRow(5));
            }

            function renderSummary(items) {
                $('#portfolioCardTableHead').html(`
                    <tr>
                        <th>Concepto</th>
                        <th>Detalle</th>
                        <th>Monto</th>
                    </tr>
                `);

                var rows = (items || []).map(function(item) {
                    return `
                        <tr>
                            <td>${escapeHtml(item.concept || '-')}</td>
                            <td>${escapeHtml(item.detail || '-')}</td>
                            <td>${money(item.amount)}</td>
                        </tr>
                    `;
                }).join('');

                $('#portfolioCardTableBody').html(rows || emptyRow(3));
            }

            function loadPortfolioCard(card, title) {
                $('#portfolioCardModalTitle').text(title || 'Detalle');
                $('#portfolioCardTotal').text('0');
                setLoading();
                $('#portfolioCardModal').modal('show');

                $.ajax({
                    url: "{{ route('dashboard.cartera_asesor.card-details') }}",
                    method: 'GET',
                    data: {
                        card: card,
                        credit_manager_id: $('[name="credit_manager_id"]').val() || '',
                        seller_id_2: $('[name="seller_id_2"]').val() || '',
                        end_date_2: $('[name="end_date_2"]').val() || ''
                    },
                    success: function(data) {
                        if (!data || !data.status) {
                            $('#portfolioCardTableBody').html('<tr><td class="text-center">No se pudo cargar el detalle</td></tr>');
                            return;
                        }

                        $('#portfolioCardTotal').text(data.total || 0);

                        if (data.type === 'quotas') {
                            renderQuotas(data.items || []);
                        } else if (data.type === 'clients') {
                            renderClients(data.items || []);
                        } else if (data.type === 'contracts') {
                            renderContracts(data.items || []);
                        } else if (data.type === 'evolution') {
                            renderEvolution(data.items || []);
                        } else {
                            renderSummary(data.items || []);
                        }
                    },
                    error: function() {
                        $('#portfolioCardTableBody').html('<tr><td class="text-center">No se pudo cargar el detalle</td></tr>');
                    }
                });
            }

            $(document).on('click', '.js-portfolio-card', function() {
                loadPortfolioCard($(this).data('card'), $(this).data('title'));
            });

            $(document).on('keypress', '.js-portfolio-card', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });

            $(document).on('click', '.js-view-pending-quotas', function() {
                openPendingQuotas($(this).data('contract-id'), $(this).data('client-label'));
            });
        })();
    </script>
@endsection
