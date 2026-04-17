@extends('template.app')

@section('title', 'Pagos')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item">Cobranzas</li>
            <li class="breadcrumb-item active">Pagos</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                @if (auth()->user()->hasRole('operations') ||
                        (auth()->user()->hasRole('admin') ||
                            ($day <= 5 && $hour >= 8 && $hour <= 19 && auth()->user()->hasRole('seller'))))
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="ti ti-plus icon"></i> Crear nuevo
                    </button>
                @endif
            </div>
            <div class="text-center">
                <span class="d-block small">
                    Tienes un total de:
                </span>
                <span class="fs-2 fw-bold text-primary">
                    S/{{ number_format($total, 2) }}
                </span>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form>
                @if (request()->rentabilidad_card)
                    <input type="hidden" name="rentabilidad_card" value="{{ request()->rentabilidad_card }}">
                @endif
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
                    @if (auth()->user()->hasRole('admin') ||
                            auth()->user()->hasRole('credit') ||
                            auth()->user()->hasRole('operations') ||
                            auth()->user()->hasRole('credit_manager') ||
                            auth()->user()->hasRole('admin_credit'))
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" name="name" value="{{ request()->name }}">
                            </div>
                        </div>
                    @endif
                    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit') || auth()->user()->hasRole('operations'))
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Método de pago</label>
                                <select class="form-select" name="payment_method_id">
                                    <option value="">Seleccionar</option>
                                    @foreach ($payment_methods as $payment_method)
                                        <option value="{{ $payment_method->id }}"
                                            @if ($payment_method->id == request()->payment_method_id) selected @endif>
                                            @if ($payment_method->id == 1) Retanqueo @else {{ $payment_method->name }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit') || auth()->user()->hasRole('credit_manager') || auth()->user()->hasRole('admin_credit'))

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
                </div>
                <button class="btn btn-primary">Filtrar</button>
                <a href="{{ route('payments.index') }}" class="btn btn-danger">Limpiar</a>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Número de cuota</th>
                        <th>Monto</th>
                        <th>Método de pago</th>
                        <th>Fecha de pago</th>
                        <th>Días de mora</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($payments->count() > 0)
                        @foreach ($payments as $payment)
                            @php
                                $contract = optional(optional($payment->quota)->contract);
                            @endphp
                            <tr>
                                <td>
                                    {{ $contract->client() . ' - S/' . number_format($contract->requested_amount, 2) . ' - ' . optional($contract->date)->format('d/m/Y') }}
                                </td>
                                <td>
                                    @php
                                        $quotaNumber = optional($payment->quota)->number;
                                        $quotaDate = optional($payment->quota)->date;
                                    @endphp
                                    {{ $quotaNumber }}
                                    @if ($quotaDate)
                                        ({{ $quotaDate->format('d/m/Y') }})
                                    @endif
                                </td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    @if (optional($payment->payment_method)->id == 1 || strtoupper(optional($payment->payment_method)->name) == 'EFECTIVO')
                                        Retanqueo
                                    @else
                                        {{ optional($payment->payment_method)->name }}
                                    @endif
                                </td>

                                <td>{{ $payment->date->format('d/m/Y') }}</td>
                                <td>{{ $payment->due_days }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <div class="d-flex gap-2">
                                            @if (isset($payment->grouped_details))
                                                @php
                                                    $detailsHtml = '';
                                                    foreach ($payment->grouped_details as $detail) {
                                                        $line = '';
                                                        
                                                        // Mostrar nombre de la persona
                                                        if (isset($detail['person_name']) && $detail['person_name'] !== 'N/A') {
                                                            $line .= '<strong>' . $detail['person_name'] . '</strong>';
                                                        }
                                                        
                                                        // Agregar método de pago (Retanqueo si es Efectivo)
                                                        if (isset($detail['method'])) {
                                                            $methodName = $detail['method'] == 'EFECTIVO' ? 'Retanqueo' : $detail['method'];
                                                            $line .= ($line ? ' - ' : '') . $methodName;
                                                        }
                                                        
                                                        // Agregar monto
                                                        $line .= ': S/' . number_format($detail['amount'], 2);
                                                        
                                                        $detailsHtml .= '- ' . $line . '<br>';
                                                    }
                                                @endphp
                                                <button class="btn btn-primary btn-icon" title="{!! $detailsHtml !!}"
                                                    data-bs-toggle="tooltip" data-bs-html="true">
                                                    <i class="ti ti-eye icon"></i>
                                                </button>
                                            @elseif (method_exists($payment, 'people') && $payment->people)
                                                <button class="btn btn-primary btn-icon" title="{!! $payment->people() !!}"
                                                    data-bs-toggle="tooltip" data-bs-html="true">
                                                    <i class="ti ti-eye icon"></i>
                                                </button>
                                            @endif

                                            @if (auth()->user()->hasRole('admin'))
                                                <button class="btn btn-primary btn-icon btn-edit "
                                                    data-id="{{ $payment->id }}">
                                                    <i class="ti ti-pencil icon"></i>
                                                </button>
                                            @endif

                                            @if (auth()->user()->hasRole('admin'))
                                                <button class="btn btn-danger btn-icon btn-delete"
                                                    data-id="{{ $payment->id }}"
                                                    data-contract-id="{{ optional($payment->quota->contract)->id }}"
                                                    data-contract-type="{{ $contract->client_type }}"
                                                    data-quota-number="{{ optional($payment->quota)->number }}">
                                                    <i class="ti ti-x icon"></i>
                                                </button>
                                            @endif

                                            @if (isset($payment->images) && is_array($payment->images) && count($payment->images) > 0)
                                                {{-- Múltiples imágenes agrupadas --}}
                                                @foreach ($payment->images as $img)
                                                    <a class="btn btn-primary btn-icon"
                                                        href="{{ asset('storage/' . $img) }}" target="_blank"
                                                        title="Ver imagen">
                                                        <i class="ti ti-photo icon"></i>
                                                    </a>
                                                @endforeach
                                            @elseif (isset($payment->image) && !empty($payment->image))
                                                {{-- Imagen individual --}}
                                                <a class="btn btn-primary btn-icon"
                                                    href="{{ asset('storage/' . $payment->image) }}" target="_blank"
                                                    title="Ver imagen">
                                                    <i class="ti ti-photo icon"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" align="center">No se han encontrado resultados</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $payments->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="storeForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear nuevo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label required">Cliente/Grupo</label>
                                    <select class="form-select ts-contracts" name="contract_id" id="contract_id">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label required">Cuota</label>
                                    <select class="form-select" name="quota_id" id="quota_id">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>
                            {{-- <div class="col-lg-12" id="divGroupType" style="display: none;">
                                <div class="alert alert-info">
                                    <strong>Tipo de grupo:</strong> <span id="groupTypeLabel"></span>
                                </div>
                            </div> --}}
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label required">Monto</label>
                                    <input type="text" class="form-control" name="amount" id="amount">
                                    <small class="text-muted">Monto acumulado: <span id="totalAccumulated">0.00</span></small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label required">Método de pago</label>
                                    <select class="form-select" name="payment_method_id" id="payment_method_id">
                                        <option value="">Seleccionar</option>
                                        @foreach ($payment_methods as $payment_method)
                                            <option value="{{ $payment_method->id }}">
                                                @if ($payment_method->id == 1)
                                                    Retanqueo
                                                @else
                                                    {{ $payment_method->name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha</label>
                                    @if (auth()->user()->hasRole('admin'))
                                        <input type="date" class="form-control" name="date" id="date"
                                            value="{{ now()->format('Y-m-d') }}">
                                    @else
                                        <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}"
                                            disabled>
                                        <input type="hidden" name="date" id="date"
                                            value="{{ now()->format('Y-m-d') }}">
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Imagen</label>
                                    <input type="file" class="form-control" name="image" id="image"
                                        accept=".jpg,.jpeg,.png,.webp">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Personas del pago</label>
                            <div id="divPeople"></div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                            Cerrar</button>
                        <button type="submit" class="btn btn-primary" id="btn-save"><i
                                class="ti ti-device-floppy icon"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label required">Cliente/Grupo</label>
                                    <input type="text" class="form-control" disabled id="editClient">
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label required">Cuota</label>
                                    <input type="text" class="form-control" disabled id="editQuota">
                                </div>
                            </div>
                            <div class="col-lg-6" id="editIndividualAmount">
                                <div class="mb-3">
                                    <label class="form-label required">Monto</label>
                                    <input type="text" class="form-control" disabled id="editAmount">
                                </div>
                            </div>
                            <div class="col-lg-6" id="editIndividualPaymentMethod">
                                <div class="mb-3">
                                    <label class="form-label required">Método de pago</label>
                                    <select class="form-select" name="payment_method_id" id="editPaymentMethodId">
                                        <option value="">Seleccionar</option>
                                        @foreach ($payment_methods as $payment_method)
                                            <option value="{{ $payment_method->id }}">
                                                @if ($payment_method->id == 1)
                                                    Retanqueo
                                                @else
                                                    {{ $payment_method->name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6" id="editIndividualDate">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha</label>
                                    <input type="date" class="form-control" name="date" id="editDate">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección para pagos agrupados -->
                        <div id="editGroupedPayments" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Métodos de pago por persona</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Persona</th>
                                                <th>Monto</th>
                                                <th>Método de pago</th>
                                            </tr>
                                        </thead>
                                        <tbody id="editGroupedPaymentsTable">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha</label>
                                    <input type="date" class="form-control" name="date_grouped" id="editDateGrouped">
                                    <small class="text-muted">Esta fecha se aplicará a todos los pagos del grupo</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" id="editId">
                        <input type="hidden" id="editIsGrouped">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                            Cerrar</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-edit"><i
                                class="ti ti-device-floppy icon"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="deletePaymentsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seleccionar pagos a eliminar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle icon me-2"></i>
                        Selecciona los pagos que deseas eliminar. Esta acción no se puede deshacer.
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllPayments">
                            <label class="form-check-label fw-bold" for="selectAllPayments">
                                Seleccionar todos
                            </label>
                        </div>
                    </div>

                    <div id="paymentsListContainer" class="border rounded p-3">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <strong>Total a eliminar: S/<span id="totalDeleteAmount">0.00</span></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="deletePaymentId">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">
                        <i class="ti ti-x icon"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete" disabled>
                        <i class="ti ti-trash icon"></i> Eliminar seleccionados
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        let currentContract = null;
        let currentGroupType = null; // 'separated' o 'unified'
        let groupPaymentsData = [];

        $(document).ready(function() {

            new TomSelect('.ts-contracts', {
                valueField: 'id',
                labelField: ['name', 'group_name'],
                searchField: ['name', 'group_name'],
                copyClassesToDropdown: false,
                dropdownClass: 'dropdown-menu ts-dropdown',
                optionClass: 'dropdown-item',
                load: function(query, callback) {
                    $.ajax({
                        url: '{{ route('contracts.api') }}?q=' + encodeURIComponent(query),
                        method: 'GET',
                        success: function(data) {
                            callback(data.items);
                        },
                        error: function(err) {
                            console.log(err);
                        }
                    })
                },
                render: {
                    item: function(data, escape) {
                        return `<div>${ data.client_type == 'Personal' ? escape(data.name) : escape(data.group_name) } - S/${escape(data.requested_amount)} - ${escape(data.date)}</div>`;
                    },
                    option: function(data, escape) {
                        return `<div>${ data.client_type == 'Personal' ? escape(data.name) : escape(data.group_name) } - S/${escape(data.requested_amount)} - ${escape(data.date)}</div>`;
                    },
                    no_results: function(data, escape) {
                        return '<div class="no-results">No se encontraron resultados</div>'
                    }
                },
                onItemAdd: function(value, $item) {
                    getQuotas(value);
                }
            });

        });

        function formatMoney(value) {
            var n = parseFloat(value);
            if (isNaN(n)) return '0.00';
            return n.toFixed(2);
        }

        function getQuotas(contract_id) {
            $.ajax({
                url: '{{ route('quotas.api') }}?contract_id=' + contract_id,
                method: 'GET',
                success: function(data) {
                    var html = '';
                    currentContract = data.contract;

                    // Ordenar las cuotas por número
                    data.quotas.sort(function(a, b) {
                        return parseInt(a.number) - parseInt(b.number);
                    });

                    if (currentContract.client_type == 'Grupo') {
                        // Para grupos: determinar el tipo
                        detectGroupType(data.quotas);
                        
                        data.quotas.forEach(function(quota) {
                            var amount = formatMoney(quota.amount);
                            var debt = formatMoney(quota.debt);
                            html += `<option value="${quota.number}" data-people='${JSON.stringify(quota.people)}' data-debt='${quota.debt}'>Cuota ${quota.number} - Monto Total: ${amount} - Saldo: ${debt} - Fecha: ${quota.date}</option>`;
                        });
                        
                        $('#quota_id').html(html);
                        
                        if (data.quotas.length > 0) {
                            handleQuotaSelection(data.quotas[0].people, data.quotas[0].debt);
                        }
                        
                        $('#quota_id').off('change').on('change', function() {
                            var selectedOption = $(this).find('option:selected');
                            var people = JSON.parse(selectedOption.attr('data-people'));
                            var debt = parseFloat(selectedOption.attr('data-debt'));
                            handleQuotaSelection(people, debt);
                        });
                        
                    } else {
                        // Para individuales
                        currentGroupType = null;
                        $('#divGroupType').hide();
                        data.quotas.forEach(function(quota) {
                            var amount = formatMoney(quota.amount);
                            var debt = formatMoney(quota.debt);
                            html += `<option value="${quota.id}">Cuota ${quota.number} - Monto ${amount} - Saldo: ${debt} - Fecha: ${quota.date}</option>`;
                        });
                        
                        $('#quota_id').html(html);
                        $('#divPeople').html('');
                        $('#amount').val('').prop('readonly', false);
                        $('#totalAccumulated').text('0.00');
                    }
                }
            });
        }

        function detectGroupType(quotas) {
            if (quotas.length > 0) {
                // Buscar una cuota que tenga múltiples personas para detectar correctamente el tipo
                let quotaWithMultiplePeople = quotas.find(q => q.people && q.people.length > 1);
                
                if (quotaWithMultiplePeople) {
                    let people = quotaWithMultiplePeople.people;
                    
                    // Verificar si hay múltiples quota_ids (cuotas separadas por persona)
                    let uniqueQuotaIds = new Set();
                    
                    people.forEach(person => {
                        uniqueQuotaIds.add(person.quota_id);
                    });
                    
                    if (uniqueQuotaIds.size > 1) {
                        // GRUPO 1: Cada persona tiene su propia cuota (múltiples quota_ids)
                        currentGroupType = 'separated';
                        $('#groupTypeLabel').text('Cuotas separadas por persona');
                    } else {
                        // GRUPO 2: Todas las personas comparten la misma cuota (un solo quota_id)
                        currentGroupType = 'unified';
                        $('#groupTypeLabel').text('Cuotas agrupadas (pago único)');
                    }
                } else {
                    // Si no hay cuotas con múltiples personas, asumir que es GRUPO 2 (unificado)
                    currentGroupType = 'unified';
                    $('#groupTypeLabel').text('Cuotas agrupadas (pago único)');
                }
                
                $('#divGroupType').show();
            }
        }

        function handleQuotaSelection(people, totalDebt) {
            if (currentGroupType === 'separated') {
                showSeparatedGroupPeople(people);
            } else if (currentGroupType === 'unified') {
                // Para GRUPO 2, obtener personas del contrato
                var contractPeople = [];
                if (currentContract.people) {
                    try {
                        contractPeople = JSON.parse(currentContract.people);
                    } catch (e) {
                        contractPeople = [];
                    }
                }
                showUnifiedGroupPeople(contractPeople, totalDebt);
            }
        }

        function showSeparatedGroupPeople(people) {
            // GRUPO 1: Cuotas separadas - mostrar input de monto para cada persona
            var html = '<div class="table-responsive"><table class="table table-bordered">';
            html += '<thead><tr><th>Seleccionar</th><th>Persona</th><th>Deuda</th><th>Monto a pagar</th></tr></thead><tbody>';
            
            if (people && people.length > 0) {
                people.forEach(function(person) {
                    html += `
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input person-checkbox-separated" type="checkbox" 
                                       data-quota-id="${person.quota_id}" 
                                       data-debt="${person.debt}"
                                       data-person='${JSON.stringify(person)}'>
                            </td>
                            <td>${person.name} (${person.document})</td>
                            <td>S/${person.debt}</td>
                            <td>
                                <input type="number" class="form-control person-amount" 
                                       data-quota-id="${person.quota_id}"
                                       min="0" 
                                       max="${person.debt}" 
                                       step="0.01" 
                                       placeholder="0.00"
                                       disabled>
                            </td>
                        </tr>
                    `;
                });
            }
            html += '</tbody></table></div>';

            $('#divPeople').html(html);
            $('#amount').val('0.00').prop('readonly', true);
            $('#totalAccumulated').text('0.00');
            
            // Habilitar/deshabilitar input de monto según checkbox
            $('.person-checkbox-separated').on('change', function() {
                var $row = $(this).closest('tr');
                var $input = $row.find('.person-amount');
                
                if ($(this).is(':checked')) {
                    $input.prop('disabled', false);
                } else {
                    $input.val('').prop('disabled', true);
                    calculateTotalSeparated();
                }
            });
            
            // Calcular total cuando cambia el monto
            $('.person-amount').on('input', function() {
                var maxAmount = parseFloat($(this).attr('max'));
                var currentAmount = parseFloat($(this).val()) || 0;
                
                if (currentAmount > maxAmount) {
                    $(this).val(maxAmount);
                    ToastError.fire({
                        text: 'El monto no puede ser mayor a la deuda'
                    });
                }
                
                calculateTotalSeparated();
            });
        }

        function calculateTotalSeparated() {
            var total = 0;
            $('.person-checkbox-separated:checked').each(function() {
                var $row = $(this).closest('tr');
                var amount = parseFloat($row.find('.person-amount').val()) || 0;
                total += amount;
            });
            
            $('#amount').val(total.toFixed(2));
            $('#totalAccumulated').text(total.toFixed(2));
        }

        function showUnifiedGroupPeople(people, totalDebt) {
            // GRUPO 2: Cuotas unificadas - solo checkboxes para seleccionar personas
            var html = '<div class="alert alert-info mb-3">';
            html += 'Lista de Personas';
            html += '</div>';
            
            if (people && people.length > 0) {
                people.forEach(function(person) {
                    // Usar la dirección del contrato si está disponible
                    var displayInfo = person.name;
                    
                    html += `
                        <div class="form-check mb-2">
                            <input class="form-check-input person-checkbox-unified" type="checkbox" 
                                   data-document="${person.document}"
                                   id="person_${person.document}">
                            <label class="form-check-label" for="person_${person.document}">
                                ${displayInfo}
                            </label>
                        </div>
                    `;
                });
            } else {
                html += '<div class="alert alert-warning">No se encontraron personas en el contrato</div>';
            }

            $('#divPeople').html(html);
            $('#amount').val('').prop('readonly', false).attr('max', totalDebt);
            $('#totalAccumulated').text(totalDebt.toFixed(2));
            
            // Validar el monto contra la deuda total
            $('#amount').on('input', function() {
                var amount = parseFloat($(this).val()) || 0;
                if (amount > totalDebt) {
                    $(this).val(totalDebt.toFixed(2));
                    ToastError.fire({
                        text: 'El monto no puede ser mayor al saldo pendiente'
                    });
                }
            });
        }

        $('#storeForm').submit(function(e) {
            $('#btn-save').prop('disabled', true);
            e.preventDefault();

            var fd = new FormData();

            fd.append('payment_method_id', $('#payment_method_id').val());
            fd.append('date', $('#date').val());
            
            if ($('#image')[0].files[0]) {
                fd.append('image', $('#image')[0].files[0]);
            }

            if (currentContract && currentContract.client_type == 'Grupo') {
                
                if (currentGroupType === 'separated') {
                    // GRUPO 1: Enviar payments_data con quota_id y amount por persona
                    var paymentsData = [];
                    var hasSelection = false;
                    
                    $('.person-checkbox-separated:checked').each(function() {
                        hasSelection = true;
                        var quotaId = $(this).attr('data-quota-id');
                        var personData = JSON.parse($(this).attr('data-person'));
                        var $row = $(this).closest('tr');
                        var amount = parseFloat($row.find('.person-amount').val()) || 0;
                        
                        if (amount > 0) {
                            paymentsData.push({
                                quota_id: quotaId,
                                amount: amount,
                                person_data: {
                                    document: personData.document,
                                    name: personData.name,
                                    address: personData.address || ''
                                }
                            });
                        }
                    });
                    
                    if (!hasSelection || paymentsData.length === 0) {
                        ToastError.fire({
                            text: 'Debe seleccionar al menos una persona y especificar el monto'
                        });
                        $('#btn-save').prop('disabled', false);
                        return;
                    }
                    
                    fd.append('payments_data', JSON.stringify(paymentsData));
                    
                } else if (currentGroupType === 'unified') {
                    // GRUPO 2: Enviar quota_id, amount y people (documentos)
                    var selectedQuotaNumber = $('#quota_id').val();
                    var selectedOption = $('#quota_id').find('option:selected');
                    var peopleData = JSON.parse(selectedOption.attr('data-people'));
                    
                    // Obtener el primer quota_id (ya que todos comparten el mismo)
                    var quotaId = peopleData[0].quota_id;
                    
                    var selectedPeople = [];
                    $('.person-checkbox-unified:checked').each(function() {
                        selectedPeople.push($(this).attr('data-document'));
                    });
                    
                    if (selectedPeople.length === 0) {
                        ToastError.fire({
                            text: 'Debe seleccionar al menos una persona'
                        });
                        $('#btn-save').prop('disabled', false);
                        return;
                    }
                    
                    var amount = parseFloat($('#amount').val()) || 0;
                    if (amount <= 0) {
                        ToastError.fire({
                            text: 'Debe especificar un monto válido'
                        });
                        $('#btn-save').prop('disabled', false);
                        return;
                    }
                    
                    fd.append('quota_id', quotaId);
                    fd.append('amount', amount);
                    selectedPeople.forEach(doc => {
                        fd.append('people[]', doc);
                    });
                }
                
            } else {
                // Pago individual
                fd.append('quota_id', $('#quota_id').val());
                fd.append('amount', $('#amount').val());
            }

            $.ajax({
                url: '{{ route('payments.store') }}',
                method: 'POST',
                processData: false,
                contentType: false,
                data: fd,
                success: function(data) {
                    if (data.status) {
                        $('#createModal').modal('hide');
                        $('#storeForm')[0].reset();

                        ToastMessage.fire({
                                text: 'Registro guardado'
                            })
                            .then(() => location.reload());

                    } else {
                        ToastError.fire({
                            text: data.error ? data.error : 'Ocurrió un error'
                        });
                        $('#btn-save').prop('disabled', false);
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                    $('#btn-save').prop('disabled', false);
                }
            });

        });

        $(document).on('click', '.btn-edit', function() {

            var id = $(this).data('id');

            $.ajax({
                url: '{{ route('payments.index') }}' + '/' + id + '/edit/',
                method: 'GET',
                success: function(data) {
                    $('#editClient').val(data.client);
                    $('#editId').val(data.id);
                    
                    // Verificar si el contrato es de tipo Grupo
                    if (data.quota && data.quota.contract && data.quota.contract.client_type === 'Grupo') {
                        // Es un pago de grupo, obtener todos los pagos relacionados
                        $.ajax({
                            url: '{{ route('payments.index') }}/' + id + '/group-payments',
                            method: 'GET',
                            success: function(groupData) {
                                if (groupData.status && groupData.payments && groupData.payments.length > 0) {
                                    // Hay múltiples pagos agrupados
                                    $('#editIsGrouped').val('1');
                                    $('#editIndividualAmount').hide();
                                    $('#editIndividualPaymentMethod').hide();
                                    $('#editIndividualDate').hide();
                                    $('#editGroupedPayments').show();
                                    $('#editDateGrouped').val(data.date_iso);
                                    
                                    // Debug: Verificar estructura de datos
                                    console.log('Pagos recibidos:', groupData.payments);
                                    
                                    // Calcular monto total de todos los pagos agrupados
                                    var totalAmount = 0;
                                    groupData.payments.forEach(function(payment) {
                                        totalAmount += parseFloat(payment.amount.replace(',', ''));
                                    });
                                    
                                    // Mostrar cuota con monto total agrupado
                                    $('#editQuota').val(`Cuota ${data.quota.number} - Monto Total: S/${totalAmount.toFixed(2)}`);
                                    
                                    // Construir tabla de personas
                                    var tableHtml = '';
                                    groupData.payments.forEach(function(payment) {
                                        var paymentAmount = parseFloat(payment.amount.replace(',', ''));
                                        var paymentMethodId = payment.payment_method_id || '';
                                        
                                        console.log('Payment:', payment.person_name, 'Method ID:', paymentMethodId);
                                        
                                        tableHtml += `
                                            <tr>
                                                <td>${payment.person_name || 'N/A'}</td>
                                                <td>S/${paymentAmount.toFixed(2)}</td>
                                                <td>
                                                    <select class="form-select grouped-payment-method" 
                                                            data-payment-id="${payment.id}"
                                                            data-selected-method="${paymentMethodId}">
                                                        <option value="">Seleccionar</option>
                                                        @foreach ($payment_methods as $payment_method)
                                                            <option value="{{ $payment_method->id }}">
                                                                @if ($payment_method->id == 1)
                                                                    Retanqueo
                                                                @else
                                                                    {{ $payment_method->name }}
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        `;
                                    });
                                    
                                    $('#editGroupedPaymentsTable').html(tableHtml);
                                    
                                    // Establecer valores seleccionados después de insertar el HTML
                                    $('.grouped-payment-method').each(function() {
                                        var selectedMethod = $(this).data('selected-method');
                                        console.log('Setting method:', selectedMethod, 'for payment:', $(this).data('payment-id'));
                                        if (selectedMethod) {
                                            $(this).val(selectedMethod);
                                        }
                                    });
                                } else {
                                    // No hay pagos agrupados o solo hay uno, mostrar como individual
                                    $('#editIsGrouped').val('0');
                                    $('#editIndividualAmount').show();
                                    $('#editIndividualPaymentMethod').show();
                                    $('#editIndividualDate').show();
                                    $('#editGroupedPayments').hide();
                                    
                                    $('#editQuota').val(`Cuota ${data.quota.number} - Monto ${data.quota.amount}`);
                                    $('#editAmount').val(data.amount);
                                    $('#editPaymentMethodId').val(data.payment_method_id);
                                    $('#editDate').val(data.date_iso);
                                }
                                
                                $('#editModal').modal('show');
                            },
                            error: function(err) {
                                // Si falla la carga de pagos agrupados, mostrar como individual
                                $('#editIsGrouped').val('0');
                                $('#editIndividualAmount').show();
                                $('#editIndividualPaymentMethod').show();
                                $('#editIndividualDate').show();
                                $('#editGroupedPayments').hide();
                                
                                $('#editQuota').val(`Cuota ${data.quota.number} - Monto ${data.quota.amount}`);
                                $('#editAmount').val(data.amount);
                                $('#editPaymentMethodId').val(data.payment_method_id);
                                $('#editDate').val(data.date_iso);
                                
                                $('#editModal').modal('show');
                            }
                        });
                    } else {
                        // Es pago individual
                        $('#editIsGrouped').val('0');
                        $('#editIndividualAmount').show();
                        $('#editIndividualPaymentMethod').show();
                        $('#editIndividualDate').show();
                        $('#editGroupedPayments').hide();
                        
                        $('#editQuota').val(`Cuota ${data.quota.number} - Monto ${data.quota.amount}`);
                        $('#editAmount').val(data.amount);
                        $('#editPaymentMethodId').val(data.payment_method_id);
                        $('#editDate').val(data.date_iso);
                        
                        $('#editModal').modal('show');
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });

        });

        $('#editForm').submit(function(e) {
            e.preventDefault();
            $('#btn-save-edit').prop('disabled', true);

            var id = $('#editId').val();
            var isGrouped = $('#editIsGrouped').val() === '1';
            var formData = new FormData();
            
            if (isGrouped) {
                // Recopilar métodos de pago de cada persona
                var groupedPayments = [];
                var hasError = false;
                
                $('.grouped-payment-method').each(function() {
                    var paymentId = $(this).data('payment-id');
                    var methodId = $(this).val();
                    
                    if (!methodId) {
                        hasError = true;
                        return false;
                    }
                    
                    groupedPayments.push({
                        payment_id: paymentId,
                        payment_method_id: methodId
                    });
                });
                
                if (hasError) {
                    ToastError.fire({
                        text: 'Debe seleccionar un método de pago para cada persona'
                    });
                    $('#btn-save-edit').prop('disabled', false);
                    return;
                }
                
                // Enviar como JSON string y luego decodificar en el backend
                formData.append('grouped_payments', JSON.stringify(groupedPayments));
                // Enviar la fecha para actualizar todos los pagos agrupados
                formData.append('date', $('#editDateGrouped').val());
                formData.append('_method', 'PATCH');
            } else {
                // Pago individual
                formData.append('payment_method_id', $('#editPaymentMethodId').val());
                // Enviar la fecha
                formData.append('date', $('#editDate').val());
                formData.append('_method', 'PATCH');
            }

            $.ajax({
                url: '{{ route('payments.index') }}' + '/' + id + '',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.status) {
                        $('#editModal').modal('hide');
                        $('#editForm')[0].reset();

                        ToastMessage.fire({
                                text: 'Registro actualizado'
                            })
                            .then(() => location.reload());

                    } else {
                        ToastError.fire({
                            text: data.error ? data.error : 'Ocurrió un error'
                        });
                        $('#btn-save-edit').prop('disabled', false);
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                    $('#btn-save-edit').prop('disabled', false);
                }
            });

        });

        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            var contractType = $(this).data('contract-type');
            var quotaNumber = $(this).data('quota-number');
            
            $('#deletePaymentId').val(id);
            
            if (contractType === 'Grupo') {
                // Abrir modal para seleccionar pagos
                loadGroupPayments(id);
            } else {
                // Confirmar eliminación directa para pagos individuales
                ToastConfirm.fire({
                    text: '¿Estás seguro que deseas borrar el registro?',
                }).then((result) => {
                    if (result.isConfirmed) {
                        deletePayments([id]);
                    }
                });
            }
        });

        // Seleccionar/deseleccionar todos
        $('#selectAllPayments').on('change', function() {
            $('.payment-checkbox').prop('checked', $(this).is(':checked'));
            calculateDeleteTotal();
        });

        // Actualizar total cuando cambia selección
        $(document).on('change', '.payment-checkbox', function() {
            calculateDeleteTotal();
            updateSelectAllCheckbox();
        });

        // Confirmar eliminación
        $('#btnConfirmDelete').on('click', function() {
            var selectedIds = [];
            $('.payment-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                ToastError.fire({
                    text: 'Debe seleccionar al menos un pago'
                });
                return;
            }

            var count = selectedIds.length;
            ToastConfirm.fire({
                text: `¿Estás seguro que deseas eliminar ${count} pago(s) seleccionado(s)?`,
            }).then((result) => {
                if (result.isConfirmed) {
                    deletePayments(selectedIds);
                }
            });
        });

        function loadGroupPayments(paymentId) {
            groupPaymentsData = [];
            $('#paymentsListContainer').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `);
            
            $('#deletePaymentsModal').modal('show');

            $.ajax({
                url: '{{ route('payments.index') }}/' + paymentId + '/group-payments',
                method: 'GET',
                success: function(data) {
                    if (data.status && data.payments.length > 0) {
                        groupPaymentsData = data.payments;
                        renderPaymentsList(data.payments);
                    } else {
                        $('#paymentsListContainer').html(`
                            <div class="alert alert-info">
                                No se encontraron pagos para este grupo
                            </div>
                        `);
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Error al cargar los pagos'
                    });
                    $('#deletePaymentsModal').modal('hide');
                }
            });
        }

        function renderPaymentsList(payments) {
            var html = '<div class="list-group">';
            
            payments.forEach(function(payment) {
                html += `
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <input class="form-check-input payment-checkbox" 
                                    type="checkbox" 
                                    value="${payment.id}" 
                                    data-amount="${parseFloat(payment.amount.replace(',', ''))}">
                            </div>
                            <div class="col">
                                <div class="fw-bold">${payment.person_name}</div>
                                <div class="text-muted small">
                                    <span class="badge bg-primary me-2">${payment.payment_method}</span>
                                    <span>S/${payment.amount}</span>
                                </div>
                            </div>
                            ${payment.image ? `
                            <div class="col-auto">
                                <a href="${payment.image}" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="ti ti-photo icon"></i>
                                </a>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            $('#paymentsListContainer').html(html);
            $('#selectAllPayments').prop('checked', false);
            calculateDeleteTotal();
        }

        function calculateDeleteTotal() {
            var total = 0;
            var count = 0;
            
            $('.payment-checkbox:checked').each(function() {
                total += parseFloat($(this).data('amount'));
                count++;
            });
            
            $('#totalDeleteAmount').text(total.toFixed(2));
            $('#btnConfirmDelete').prop('disabled', count === 0);
        }

        function updateSelectAllCheckbox() {
            var totalCheckboxes = $('.payment-checkbox').length;
            var checkedCheckboxes = $('.payment-checkbox:checked').length;
            
            $('#selectAllPayments').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
        }

        function deletePayments(paymentIds) {
            var mainPaymentId = $('#deletePaymentId').val();
            
            $.ajax({
                url: '{{ route('payments.index') }}/' + mainPaymentId,
                method: 'DELETE',
                data: {
                    payment_ids: paymentIds
                },
                success: function(data) {
                    if (data.status) {
                        $('#deletePaymentsModal').modal('hide');
                        ToastMessage.fire({
                            text: data.message || 'Pago(s) eliminado(s) correctamente'
                        }).then(() => location.reload());
                    } else {
                        ToastError.fire({
                            text: data.error || 'Ocurrió un error'
                        });
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error al eliminar'
                    });
                }
            });
        }
    </script>
@endsection
