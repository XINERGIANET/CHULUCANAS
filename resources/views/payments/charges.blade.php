@extends('template.app')

@section('title', 'Gestión de cobranza')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item">Cobranzas</li>
            <li class="breadcrumb-item active">Gestión de cobranza</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            <a class="btn btn-success" href="{{ route('payments.charges.excel', request()->all()) }}"
                target="_blank">Excel</a>
        </div>
        <div class="card-body border-bottom">
            <form>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" class="form-control" name="name" value="{{ request()->name }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Asesor comercial</label>
                            <select class="form-select" name="seller_id">
                                <option value="">Seleccionar</option>
                                @foreach ($sellers as $seller)
                                    <option value="{{ $seller->id }}" @if ($seller->id == request()->seller_id) selected @endif>
                                        {{ $seller->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Fecha inicial</label>
                            <input type="date" class="form-control" name="start_date"
                                value="{{ request()->start_date }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Fecha final</label>
                            <input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Jefe de credito</label>
                            <select class="form-select" name="credit_manager_id">
                                <option value="">Seleccionar</option>
                                @foreach ($credit_managers as $credit_manager)
                                    <option value="{{ $credit_manager->id }}" @if ($credit_manager->id == request()->credit_manager_id) selected @endif>
                                        {{ $credit_manager->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Número de cuota</label>
                            <input type="number" class="form-control" name="quota_number" value="{{ request()->quota_number }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Registros por página</label>
                            <select class="form-select" name="per_page" onchange="this.form.submit()">
                                @foreach ([20, 50, 100] as $pageSize)
                                    <option value="{{ $pageSize }}" @if (($perPage ?? 20) == $pageSize) selected @endif>{{ $pageSize }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('payments.charges') }}" class="btn btn-danger">Limpiar</a>
                    <!--Suma de pagos pendientes-->
                    <p class="text-primary ms-auto mb-0">Total:  S/{{ number_format($total, 2, '.', ',') }}</p>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Asesor - Jefe de crédito</th>
                        <th>Número de cuota</th>
                        <th>Monto</th>
                        <th>Saldo</th>
                        <th>Fecha de pago</th>
                      
                    </tr>
                </thead>
                <tbody>
                    @if ($quotas->count() > 0)
                        @foreach ($quotas as $quota)
                            <tr>
                                <td>
                                    {{ optional($quota->contract)->client() }}
                                    @if ($quota->person_name || $quota->person_document)
                                        <br>
                                        <small class="text-muted">
                                            {{ $quota->person_name ?? $quota->person_document }}
                                            @if ($quota->person_name && $quota->person_document)
                                                - {{ $quota->person_document }}
                                            @endif
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $seller = optional(optional($quota->contract)->seller);
                                        $creditManager = optional($seller->creditManager);
                                    @endphp
                                    @if ($seller->name)
                                        {{ $seller->name }}@if ($creditManager->name) - {{ $creditManager->name }}@endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $quota->number }}</td>
                                <td>{{ number_format($quota->amount, 2) }}</td>
                                <td>{{ number_format($quota->debt, 2) }}</td>
                                <td>{{ $quota->date->format('d/m/Y') }}</td>
                               
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" align="center">No se han encontrado resultados</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($quotas->hasPages() || $quotas->total() > 0)
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">Mostrando {{ $quotas->firstItem() ?? 0 }}–{{ $quotas->lastItem() ?? 0 }} de {{ $quotas->total() }}</small>
                @if ($quotas->hasPages())
                    {{ $quotas->withQueryString()->links() }}
                @endif
            </div>
        @endif
    </div>

    <div class="modal fade" id="groupDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Integrantes del grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="groupDetailsLoading" class="text-center py-3">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 mb-0 text-muted">Cargando integrantes...</p>
                    </div>
                    <div id="groupDetailsContent" class="d-none"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.group-details-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    var contractId = this.dataset.contractId;
                    var loading = document.getElementById('groupDetailsLoading');
                    var content = document.getElementById('groupDetailsContent');

                    loading.classList.remove('d-none');
                    content.classList.add('d-none');
                    content.innerHTML = '';

                    fetch('{{ url('payments/charges/group') }}/' + contractId, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('No se pudieron cargar los detalles del grupo');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        if (!data.status) {
                            throw new Error(data.error || 'No se pudieron cargar los detalles del grupo');
                        }

                        var html = '';
                        html += '<div class="mb-3">';
                        html += '<div class="fw-semibold">' + (data.contract.group_name || 'Grupo') + '</div>';
                        html += '<div class="text-muted small">Total de cuotas: ' + data.totals.total_quotas + ' · Deuda total: S/' + Number(data.totals.total_debt).toFixed(2) + '</div>';
                        html += '</div>';

                        if (!data.members || data.members.length === 0) {
                            html += '<div class="alert alert-info mb-0">No hay integrantes registrados para este grupo.</div>';
                        } else {
                            html += '<div class="table-responsive">';
                            html += '<table class="table table-sm align-middle">';
                            html += '<thead><tr><th>Integrante</th><th>DNI</th><th>Cuotas</th><th>Deuda</th><th>Pagadas</th><th>Pendientes</th></tr></thead>';
                            html += '<tbody>';
                            data.members.forEach(function (member) {
                                // Solo mostrar si tiene nombre o documento
                                if ((member.name && member.name.trim() !== '') || (member.document && member.document.trim() !== '')) {
                                    html += '<tr>';
                                    html += '<td>' + (member.name || '') + '</td>';
                                    html += '<td>' + (member.document || '') + '</td>';
                                    html += '<td>' + (member.quotas_count || 0) + '</td>';
                                    html += '<td>S/' + Number(member.debt_total || 0).toFixed(2) + '</td>';
                                    html += '<td>' + (member.paid_quotas || 0) + '</td>';
                                    html += '<td>' + (member.pending_quotas || 0) + '</td>';
                                    html += '</tr>';
                                }
                            });
                            html += '</tbody></table>';
                            html += '</div>';
                        }

                        content.innerHTML = html;
                        loading.classList.add('d-none');
                        content.classList.remove('d-none');
                        new bootstrap.Modal(document.getElementById('groupDetailsModal')).show();
                    })
                    .catch(function (error) {
                        loading.classList.add('d-none');
                        content.classList.remove('d-none');
                        content.innerHTML = '<div class="alert alert-danger mb-0">' + error.message + '</div>';
                        new bootstrap.Modal(document.getElementById('groupDetailsModal')).show();
                    });
                });
            });
        });
    </script>
@endsection
