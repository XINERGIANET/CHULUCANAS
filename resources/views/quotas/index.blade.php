@extends('template.app')

@section('title', 'Gestión de cuotas')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Gestión de cuotas</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div></div>
            <div>
                <a class="btn btn-success" href="{{ route('quotas.excel', request()->all()) }}">
                    <i class="ti ti-file-spreadsheet icon"></i> Exportar Excel
                </a>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form>
                <div class="row">
                    @if (auth()->user()->hasRole('admin') ||
                            auth()->user()->hasRole('credit') ||
                            auth()->user()->hasRole('operations') ||
                            auth()->user()->hasRole('admin_credit'))
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Jefe de Crédito</label>
                                <select class="form-select" name="credit_manager_id">
                                    <option value="">Seleccionar</option>
                                    @foreach ($credit_managers as $cm)
                                        <option value="{{ $cm->id }}"
                                            @if ($cm->id == request()->credit_manager_id) selected @endif>
                                            {{ $cm->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-5">
                        <div class="mb-5">
                            <label class="form-label">Cliente</label>
                            <select class="form-select js-quota-client" name="client_id">
                                <option value="">Seleccionar</option>
                                @if (!empty($selectedClient))
                                    <option value="{{ $selectedClient->id }}" selected>
                                        {{ $selectedClient->client_type === 'Personal'
                                            ? $selectedClient->name.' - '.$selectedClient->document
                                            : $selectedClient->group_name }}
                                    </option>
                                @endif
                            </select>
                        </div>
                    </div>
                    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit_manager') || auth()->user()->hasRole('admin_credit') || auth()->user()->hasRole('operations'))
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Asesor comercial</label>
                                <select class="form-select" name="seller_id">
                                    <option value="">Seleccionar</option>
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}"
                                            @if ($seller->id == request()->seller_id) selected @endif>{{ $seller->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-2">
                        <div class="mb-2">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="paid">
                                <option value="">Todos</option>
                                <option value="0" @selected((string) request()->paid === '0')>Pendiente</option>
                                <option value="1" @selected((string) request()->paid === '1')>Pagado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-2">
                            <label class="form-label">Inicio de cuota</label>
                            <input type="date" class="form-control" name="start_date"
                                value="{{ request()->start_date }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-2">
                            <label class="form-label">Fin de cuota</label>
                            <input type="date" class="form-control" name="end_date"
                                value="{{ request()->end_date }}">
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary">Filtrar</button>
                <a href="{{ route('quotas.index') }}" class="btn btn-danger">Limpiar</a>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente/Grupo</th>
                        <th>Persona</th>
                        <th>Documento</th>
                        <th>Asesor C.</th>
                        <th>N° cuota</th>
                        <th>Monto</th>
                        <th>Deuda</th>
                        <th>Fecha de cuota</th>
                        <th>Estado</th>
                        <th>Fecha de pago</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($quotas->count() > 0)
                        @foreach ($quotas as $quota)
                            @php
                                $contract = $quota->contract;
                            @endphp
                            @php
                                $lastPayment = $quota->payments->sortByDesc('date')->first();
                                $paymentDate = $lastPayment ? $lastPayment->date : null;
                                $paymentMethod = $lastPayment ? optional($lastPayment->payment_method)->name : null;
                                $paymentImage = $lastPayment && $lastPayment->image ? asset('storage/' . $lastPayment->image) : null;
                            @endphp
                            <tr>
                                <td>{{ $quota->id }}</td>
                                <td>{{ $contract ? $contract->client() : 'N/A' }}</td>
                                <td>{{ $quota->person_name }}</td>
                                <td>{{ $quota->person_document }}</td>
                                <td>{{ optional($contract)->seller->name }}</td>
                                <td>{{ $quota->number }}</td>
                                <td>{{ $quota->amount }}</td>
                                <td>{{ $quota->debt }}</td>
                                <td>{{ $quota->date ? $quota->date->format('d/m/Y') : '' }}</td>
                                <td>
                                    @if ($quota->paid)
                                        <span class="badge bg-success">Pagado</span>
                                    @else
                                        <span class="badge bg-danger">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $paymentDate ? $paymentDate->format('d/m/Y') : '-' }}
                                </td>
                                <td>
                                    @if ($quota->payments->count() > 0)
                                        <button class="btn btn-sm btn-primary js-view-payment-history"
                                            data-url="{{ route('quotas.payments', $quota) }}">
                                            Ver pagos
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="12" align="center">No se han encontrado resultados</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($quotas->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $quotas->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div class="modal modal-blur fade" id="quotaPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Historial de pagos de cuota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Cliente</div>
                            <div id="quotaPaymentClient" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Cuota</div>
                            <div id="quotaPaymentQuota" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Monto</div>
                            <div id="quotaPaymentAmount" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Método</div>
                            <div id="quotaPaymentMethod" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Fecha de pago</div>
                            <div id="quotaPaymentDate" class="fw-semibold"></div>
                        </div>
                    </div>
                    <div id="quotaPaymentImageWrapper" class="text-center">
                        <img id="quotaPaymentImage" src="" alt="Comprobante" class="img-fluid rounded d-none" />
                        <div id="quotaPaymentNoImage" class="text-muted">No hay comprobante disponible.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="quotaPaymentHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Historial de pagos de cuota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="text-muted small">ID cuota</div>
                            <div id="quotaHistoryId" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-5">
                            <div class="text-muted small">Cliente</div>
                            <div id="quotaHistoryClient" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-muted small">Cuota</div>
                            <div id="quotaHistoryQuota" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-muted small">Saldo</div>
                            <div id="quotaHistoryDebt" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Persona</div>
                            <div id="quotaHistoryPerson" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Monto cuota</div>
                            <div id="quotaHistoryAmount" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Total pagado</div>
                            <div id="quotaHistoryPaidTotal" class="fw-semibold text-success"></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>ID pago</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Metodo</th>
                                    <th>Dias mora</th>
                                    <th>Comprobante</th>
                                </tr>
                            </thead>
                            <tbody id="quotaHistoryTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            var $clientSelect = $('.js-quota-client');
            if (!$clientSelect.length) return;

            new TomSelect($clientSelect[0], {
                create: false,
                maxItems: 1,
                valueField: 'id',
                labelField: 'text',
                searchField: ['text'],
                copyClassesToDropdown: false,
                dropdownClass: 'dropdown-menu ts-dropdown',
                optionClass: 'dropdown-item',
                hideSelected: true,
                load: function(query, callback) {
                    if (!query || query.length < 2) {
                        return callback([]);
                    }
                    var cmId = $('select[name="credit_manager_id"]').val() || '';
                    $.ajax({
                        url: '{{ route('quotas.clients') }}?q=' + encodeURIComponent(query) + '&credit_manager_id=' + encodeURIComponent(cmId),
                        method: 'GET',
                        success: function(data) {
                            callback(data.items || []);
                        },
                        error: function() {
                            callback();
                        }
                    });
                },
                render: {
                    option: function(data, escape) {
                        return '<div>' + escape(data.text) + '</div>';
                    },
                    item: function(data, escape) {
                        return '<div>' + escape(data.text) + '</div>';
                    },
                    no_results: function() {
                        return '<div class="no-results">No se encontraron resultados</div>';
                    }
                }
            });
        })();
    </script>
    <script>
        (function() {
            function money(value) {
                return 'S/' + parseFloat(value || 0).toFixed(2);
            }

            function paymentRows(items) {
                if (!items || items.length === 0) {
                    return '<tr><td colspan="6" class="text-center">No hay pagos registrados</td></tr>';
                }

                return items.map(function(item) {
                    return `
                        <tr>
                            <td>${item.id || '-'}</td>
                            <td>${money(item.amount)}</td>
                            <td>${item.date || '-'}</td>
                            <td>${item.payment_method || '-'}</td>
                            <td>${item.due_days !== null && item.due_days !== undefined ? item.due_days : '-'}</td>
                            <td>
                                ${item.image_url
                                    ? `<a href="${item.image_url}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Ver foto</a>`
                                    : '<span class="text-muted">Sin foto</span>'}
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            $(document).on('click', '.js-view-payment-history', function() {
                var url = $(this).data('url');
                $('#quotaHistoryTableBody').html('<tr><td colspan="6" class="text-center">Cargando...</td></tr>');
                $('#quotaPaymentHistoryModal').modal('show');

                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(data) {
                        var quota = data.quota || {};
                        $('#quotaHistoryId').text(quota.id || '');
                        $('#quotaHistoryClient').text(quota.client || '');
                        $('#quotaHistoryQuota').text(quota.number || '');
                        $('#quotaHistoryPerson').text(quota.person_name || quota.person_document || '-');
                        $('#quotaHistoryAmount').text(money(quota.amount));
                        $('#quotaHistoryDebt').text(money(quota.debt));
                        $('#quotaHistoryPaidTotal').text(money(data.payments_total));
                        $('#quotaHistoryTableBody').html(paymentRows(data.payments || []));
                    },
                    error: function() {
                        $('#quotaHistoryTableBody').html('<tr><td colspan="6" class="text-center">No se pudo cargar el historial de pagos</td></tr>');
                    }
                });
            });
        })();
    </script>
@endsection
