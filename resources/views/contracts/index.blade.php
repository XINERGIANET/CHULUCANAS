@extends('template.app')

@section('title', 'Contratos')

@section('styles')
    <style>
        .table-responsive {
            overflow: visible !important;
        }
    </style>
@endsection

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Contratos</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('operations'))
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="ti ti-plus icon"></i> Crear nuevo
                </button>
            @endif
        </div>
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit_manager') || auth()->user()->hasRole('admin_credit') || auth()->user()->hasRole('operations') || auth()->user()->hasRole('seller'))
            <div class="card-body border-bottom">
                <form>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" name="name" value="{{ request()->name }}">
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
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Inicio del préstamo</label>
                                <input type="date" class="form-control" name="start_date"
                                    value="{{ request()->start_date }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Fin del préstamo</label>
                                <input type="date" class="form-control" name="end_date"
                                    value="{{ request()->end_date }}">
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-danger">Limpiar</a>
                </form>
            </div>
        @endif
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th># de pagaré</th>
                        <th>Cliente/Grupo</th>
                        <th>Asesor C.</th>
                        <th>Monto solicitado</th>
                        <th>Cuotas</th>
                        <th>Interés</th>
                        <th>Monto a pagar</th>
                        <th>Fecha de préstamo</th>
                        <th></th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($contracts->count() > 0)
                        @foreach ($contracts as $contract)
                            <tr>
                                <td>{{ $contract->number_pagare }}</td>
                                <td title="{!! $contract->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">
                                    {{ $contract->client_type == 'Personal' ? $contract->name : $contract->group_name }}
                                </td>
                                <td>{{ optional($contract)->seller->name }}</td>
                                <td>{{ $contract->requested_amount }}</td>
                                <td>{{ $contract->quotas_number }}</td>
                                <td>{{ $contract->interest }}</td>
                                <td>{{ $contract->payable_amount }}</td>
                                <td>{{ $contract->date->format('d/m/Y') }}</td>
                                <td>
                                    @if ($contract->paid)
                                        <span class="badge bg-success"></span>
                                    @else
                                        <span class="badge bg-danger"></span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <div class="dropdown">
                                            <button class="btn btn-primary btn-icon" data-id="{{ $contract->id }}"
                                                data-bs-toggle="dropdown" data-bs-auto-close="true">
                                                <i class="ti ti-file-text icon"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @if($contract->client_type == 'Personal')
                                                <a class="dropdown-item" href="{{ route('contracts.pdf1', $contract) }}"
                                                    target="_blank">CONTRATO DE PRÉSTAMO PERSONAL</a>
                                                <a class="dropdown-item" href="{{ route('contracts.pdf2', $contract) }}"
                                                    target="_blank">CONTRATO DE PRÉSTAMO PERSONAL CON AVAL</a>
                                                @endif
                                                @if ($contract->client_type == 'Grupo')
                                                <a class="dropdown-item" href="{{ route('contracts.pdf3', $contract) }}"
                                                    target="_blank">CONTRATO DE PRÉSTAMO GRUPAL</a>
                                                @endif
                                            </div>
                                        </div>
                                        <button class="btn btn-icon btn-info btn-number"
                                            data-id="{{ $contract->id }}"
                                            data-number="{{ $contract->number_pagare }}">
                                            <i class="ti ti-hash icon"></i>
                                        </button>
                                        @if ($contract->has_quota_overdue_1220 == 1)
                                            <button class="btn btn-icon btn-warning btn-transfer"
                                                data-id="{{ $contract->id }}"
                                                data-seller="{{ $contract->seller_id }}">
                                                <i class="ti ti-pencil icon"></i>
                                            </button>
                                        @endif
                                        @if (auth()->user()->hasRole('admin'))
                                            <button class="btn btn-icon btn-danger btn-delete"
                                                data-id="{{ $contract->id }}">
                                                <i class="ti ti-x icon"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" align="center">No se han encontrado resultados</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($contracts->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $contracts->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="storeForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear nuevo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Tipo de cliente</label>
                                    <select class="form-select" name="client_type" id="client_type">
                                        <option value="Personal">Personal</option>
                                        <option value="Grupo">Grupo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divGroupName" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label required">Nombre de grupo</label>
                                    <input type="text" class="form-control" name="group_name" id="group_name"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divQuantity" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label required">Cantidad</label>
                                    <div class="w-100 btn-group">
                                        <button type="button" class="btn btn-primary w-50"
                                            id="btn-add">Agregar</button>
                                        <button type="button" class="btn btn-danger w-50"
                                            id="btn-remove">Quitar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divDocument">
                                <div class="mb-3">
                                    <label class="form-label required">DNI</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control ts-document" name="document"
                                            id="document" autocomplete="off">
                                        <button type="button" class="btn btn-primary btn-icon" id="btn-search">
                                            <i class="ti ti-search icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divName">
                                <div class="mb-3">
                                    <label class="form-label required">Nombre</label>
                                    <input type="text" class="form-control" name="name" id="name"
                                        autocomplete="off" readonly>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divPhone">
                                <div class="mb-3">
                                    <label class="form-label required">Teléfono</label>
                                    <input type="text" class="form-control" name="phone" id="phone"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divAddress">
                                <div class="mb-3">
                                    <label class="form-label required">Dirección</label>
                                    <input type="text" class="form-control" name="address" id="address"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divReference">
                                <div class="mb-3">
                                    <label class="form-label">Referencia</label>
                                    <input type="text" class="form-control" name="reference" id="reference"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHomeType">
                                <div class="mb-3">
                                    <label class="form-label required">Tipo de vivienda</label>
                                    <select class="form-select" name="home_type" id="home_type">
                                        <option value="">Seleccionar</option>
                                        <option value="Propia">Propia</option>
                                        <option value="Alquilada">Alquilada</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessLine">
                                <div class="mb-3">
                                    <label class="form-label">Rubro de negocio</label>
                                    <input type="text" class="form-control" name="business_line" id="business_line"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessAddress">
                                <div class="mb-3">
                                    <label class="form-label">Dirección de negocio</label>
                                    <input type="text" class="form-control" name="business_address"
                                        id="business_address" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessStartDate">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de inicio de negocio</label>
                                    <input type="date" class="form-control" name="business_start_date"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divCivilStatus">
                                <div class="mb-3">
                                    <label class="form-label required">Estado civil</label>
                                    <select class="form-select" name="civil_status" id="civil_status">
                                        <option value="">Seleccionar</option>
                                        <option value="Soltero">Soltero</option>
                                        <option value="Casado">Casado</option>
                                        <option value="Divorciado">Divorciado</option>
                                        <option value="Viudo">Viudo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHusbandName" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label">Nombre de esposo (a)</label>
                                    <input type="text" class="form-control" name="husband_name" id="husband_name"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHusbandDocument" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label">DNI de esposo (a)</label>
                                    <input type="text" class="form-control" name="husband_document"
                                        id="husband_document" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div id="divGroup" style="display:none">
                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label class="form-label required">DNI</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control ts-document" name="documents[]"
                                                autocomplete="off">
                                            <button type="button" class="btn btn-primary btn-icon btn-group-search">
                                                <i class="ti ti-search icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Nombre</label>
                                        <input type="text" class="form-control" name="names[]" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label class="form-label required">Dirección</label>
                                        <input type="text" class="form-control" name="addresses[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label class="form-label required">Teléfono</label>
                                        <input type="text" class="form-control ts-phone" name="phones[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label class="form-label required">S/. Cuota</label>
                                        <input type="text" class="form-control" name="quotas[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label class="form-label required">DNI</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control ts-document" name="documents[]"
                                                autocomplete="off">
                                            <button type="button" class="btn btn-primary btn-icon btn-group-search">
                                                <i class="ti ti-search icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Nombre</label>
                                        <input type="text" class="form-control" name="names[]" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label class="form-label required">Dirección</label>
                                        <input type="text" class="form-control" name="addresses[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label class="form-label required">Teléfono</label>
                                        <input type="text" class="form-control ts-phone" name="phones[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <label class="form-label required">S/. Cuota</label>
                                        <input type="text" class="form-control" name="quotas[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Asesor comercial</label>
                                    <select class="form-select" name="seller_id">
                                        <option value="">Seleccionar</option>
                                        @foreach ($sellers as $seller)
                                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Monto solicitado</label>
                                    <input type="text" class="form-control" name="requested_amount"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Número de meses</label>
                                    <input type="text" class="form-control" name="months_number" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha de préstamo</label>
                                    @if (auth()->user()->hasRole('operations'))
                                        <input type="date" class="form-control" name="date_disabled"
                                            value="{{ now()->format('Y-m-d') }}" autocomplete="off" disabled>
                                        {{-- input hidden para asegurar que la fecha se envíe en el formulario aun cuando el campo esté disabled --}}
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                    @else
                                        <input type="date" class="form-control" name="date"
                                            value="{{ now()->format('Y-m-d') }}" autocomplete="off">
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-4" id="divDistrict">
                                <div class="mb-3">
                                    <label class="form-label required">Distrito</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control ts-district" name="district_id"
                                            id="district_id" autocomplete="off">
                                        <button type="button" class="btn btn-primary btn-icon" id="btn-search">
                                            <i class="ti ti-search icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @if (auth()->user()->hasRole('admin'))
                                <div class="col-lg-4 d-flex align-items-center">
                                    <div class="mb-3 w-100 mb-0">
                                        <label class="form-label required d-block mb-1">
                                            <input type="checkbox" name="edit_interest" id="editInterest"
                                                value="1"> Editar tasa de interés (15% por defecto)
                                        </label>
                                        <input type="text" class="form-control" name="interest" id="interest"
                                            autocomplete="off" style="display:none">
                                    </div>
                                </div>
                            @endif
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
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" name="name" id="editName"
                                        autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" id="editId">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                            Cerrar</button>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i>
                            Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="numberModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="numberForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Número de pagaré</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="numberContractId" name="contract_id">
                        <div class="mb-3">
                            <label class="form-label">Número de pagaré</label>
                            <input class="form-control" id="numberPagare" name="number_pagare">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="quotasModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cuotas pendientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Contrato</th>
                                <th>Número</th>
                                <th>Monto</th>
                                <th>Saldo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-quotas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="transferModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="transferForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Transferencia de asesores</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="transferContractId" name="contract_id">
                        <div class="mb-3">
                            <label class="form-label">Transferir a</label>
                            <select class="form-select" id="transferSeller" name="seller_id">
                                <option value="">Seleccionar asesor</option>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Transferir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {

            var queryString = window.location.search;
            var parametros = new URLSearchParams(queryString);

            if (parametros.get('modal') == 'create') {
                $('#createModal').modal('show');
            }



            // Helper: inicializa TomSelect en un input concreto (evita duplicados)
            function initTomSelect($input) {
                if (!$input || $input.data('ts-initialized')) return;
                var el = $input[0];
                var isMain = $input.is('#document');

                new TomSelect(el, {
                    create: true,
                    maxItems: 1,
                    valueField: 'document',
                    labelField: ['name', 'document'],
                    searchField: ['name', 'document'],
                    copyClassesToDropdown: false,
                    dropdownClass: 'dropdown-menu ts-dropdown',
                    optionClass: 'dropdown-item',
                    hideSelected: true,
                    load: function(query, callback) {
                        $.ajax({
                            url: '{{ route('clients.api') }}?q=' + encodeURIComponent(query),
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
                            return `<div data-name="${escape(data.name)}" data-phone="${escape(data.phone)}" data-address="${escape(data.address)}" data-reference="${escape(data.reference)}" data-business-line="${escape(data.business_line)}" data-business-address="${escape(data.business_address)}" data-home-type="${escape(data.home_type)}" data-civil-status="${escape(data.civil_status)}">${escape(data.document)}</div>`;
                        },
                        option: function(data, escape) {
                            return `<div>${escape(data.document)} - ${ data.name ? escape(data.name) : ''}</div>`;
                        },
                        no_results: function(data, escape) {
                            return '<div class="no-results">No se encontraron resultados</div>'
                        },
                        option_create: function(data, escape) {
                            return '<div class="create">Agregar <strong>' + escape(data.input) +
                                '</strong>&hellip;</div>';
                        }
                    },
                    onItemAdd: function(value, item) {
                        var dataset = item.dataset || {};

                        function normalizeDataValue(v) {
                            if (v === undefined || v === null) return '';
                            if (typeof v !== 'string') return v;
                            v = v.trim();
                            if (v === '') return '';
                            var low = v.toLowerCase();
                            if (low === 'null' || low === 'undefined') return '';
                            return v;
                        }
                        if (isMain) {
                            // llenar campos globales
                            if (dataset.name == 'undefined') {
                                $('#name').val('');
                            } else {
                                console.log('onItemAdd dataset:', dataset);
                                $('#name').val(dataset.name || '');
                                $('#phone').val(dataset.phone == 'null' ? '' : (dataset.phone || ''));
                                $('#address').val(dataset.address == 'null' ? '' : (dataset.address ||
                                    ''));
                                var reference = dataset.reference || dataset.ref || dataset
                                    .referencia || '';
                                $('#reference').val(reference == 'null' ? '' : reference);
                                var businessLine = dataset.businessLine || dataset.business_line ||
                                    dataset.rubro || '';
                                $('#business_line').val(businessLine == 'null' ? '' : businessLine);
                                var businessAddress = dataset.businessAddress || dataset
                                    .business_address || '';
                                $('#business_address').val(businessAddress == 'null' ? '' :
                                    businessAddress);
                                $('#home_type').val(dataset.homeType == 'null' ? '' : (dataset
                                    .homeType || dataset.home_type || ''));
                                $('#civil_status').val(dataset.civilStatus == 'null' ? '' : (dataset
                                    .civilStatus || dataset.civil_status || ''));

                                if (dataset.civil_status == 'Casado' || dataset.civilStatus ==
                                    'Casado') {
                                    $('#divHusbandName').show();
                                    $('#divHusbandDocument').show();
                                } else {
                                    $('#husband_name').val('');
                                    $('#husband_document').val('');
                                    $('#divHusbandName').hide();
                                    $('#divHusbandDocument').hide();
                                }
                            }


                        } else {
                            // si es un DNI de grupo, rellenar solo los campos de la fila correspondiente
                            var $row = $($input).closest('.row');
                            $row.find('input[name="names[]"]').val(normalizeDataValue(dataset.name));
                            $row.find('input[name="addresses[]"]').val(normalizeDataValue(dataset
                                .address));
                        }
                        // comprobar cuotas pendientes para el documento seleccionado (solo para principal)
                        $.ajax({
                            url: '{{ route('clients.check') }}',
                            method: 'GET',
                            data: {
                                document: value
                            },
                            success: function(data) {
                                if (data.status) {
                                    ToastMessage.fire({
                                        text: 'El cliente no tiene cuotas pendientes'
                                    });
                                } else {
                                    var html = '';
                                    data.quotas.forEach(function(quota) {
                                        html += `
										<tr>
											<td>${quota.contract_id}</td>
											<td>${quota.number}</td>
											<td>${quota.amount}</td>
											<td>${quota.debt}</td>
											<td>${quota.date}</td>
										</tr>
									`;
                                    });
                                    $('#tbl-quotas').html(html);
                                    $('#quotasModal').modal('show');
                                }
                            }
                        });

                    }
                });

                $input.data('ts-initialized', true);
            }

            // inicializar TomSelect en los inputs existentes
            $('.ts-document, #divGroup input[name="documents[]"]').each(function() {
                initTomSelect($(this));
            });
            window.initContractDocumentSelect = initTomSelect;
            $(document).on('focus', '#divGroup input[name="documents[]"]', function() {
                initTomSelect($(this));
            });


            function initTomSelectDistrict($input) {
                if (!$input || $input.data('ts-initialized')) return;
                var el = $input[0];

                new TomSelect(el, {
                    create: false,
                    maxItems: 1,
                    valueField: 'id',
                    labelField: 'text',
                    searchField: ['text', 'department', 'province', 'district'],
                    copyClassesToDropdown: false,
                    dropdownClass: 'dropdown-menu ts-dropdown',
                    optionClass: 'dropdown-item',
                    hideSelected: true,
                    load: function(query, callback) {
                        $.ajax({
                            url: '{{ route("districts.api") }}?q=' + encodeURIComponent(query || ''),
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
                        item: function(data, escape) {
                            // store department/province/district in dataset for onItemAdd
                            var dept = escape(data.department || '');
                            var prov = escape(data.province || '');
                            var dist = escape(data.district || '');
                            return '<div data-department="' + dept + '" data-province="' + prov + '" data-district="' + dist + '">' + escape(data.text) + '</div>';
                        },
                        option: function(data, escape) {
                            return '<div>' + escape(data.text) + '</div>';
                        },
                        no_results: function() {
                            return '<div class="no-results">No se encontraron resultados</div>';
                        }
                    },
                    onItemAdd: function(value, item) {
                        var dataset = item.dataset || {};
                        // set visible input to the full text (TomSelect already does it) and fill hidden/related fields if exist
                        if ($('#district_id').length) $('#district_id').val(value);
                        if ($('#department').length) $('#department').val(dataset.department || '');
                        if ($('#province').length) $('#province').val(dataset.province || '');
                        // also set plain text fields if you use them
                        if ($('#district_text').length) $('#district_text').val(item.textContent || '');
                    }
                });

                $input.data('ts-initialized', true);
            }



            $('.ts-district').each(function() {
                initTomSelectDistrict($(this));
            });
        });

        $('#btn-search').click(function() {

            var dni = $('#document').val().trim();

            if (dni.length != 8) {
                return;
            }

            Swal.showLoading();

            $.ajax({
                url: '{{ route('api.reniec') }}',
                method: 'GET',
                data: {
                    dni
                },
                success: function(data) {

                    Swal.close();

                    if (data.status) {
                        $('#name').attr('readonly', true);
                        $('#name').val(data.name);
                    } else {
                        $('#name').val('');
                        $('#name').attr('readonly', false);
                        $('#name').focus();
                    }
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            })
        });

        $(document).on('click', '.btn-group-search, #btn-group-search', function() {
            var $row = $(this).closest('.row');
            var $dniInput = $row.find('input[name="documents[]"]');
            var $nameInput = $row.find('input[name="names[]"]');
            var dni = $dniInput.val().trim();

            if (dni.length != 8) {
                $nameInput.val('');
                return;
            }

            Swal.showLoading();

            $.ajax({
                url: '{{ route('api.reniec') }}',
                method: 'GET',
                data: {
                    dni
                },
                success: function(data) {
                    Swal.close();
                    if (data.status) {
                        $nameInput.attr('readonly', true);
                        $nameInput.val(data.name);
                    } else {
                        $nameInput.val('');
                        $nameInput.attr('readonly', false);
                        $nameInput.focus();
                    }
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });
        });

        $('#storeForm').submit(function(e) {
            $('#btn-save').prop('disabled', true);

            e.preventDefault();
            // Validación cliente: la suma de los montos de cuotas debe ser igual al monto solicitado
            function normalizeNumber(v) {
                if (v === undefined || v === null) return 0;
                v = String(v).trim();
                if (v === '') return 0;
                v = v.replace(/\s+/g, ''); // quitar espacios
                v = v.replace(/,/g, '.'); // normalizar coma a punto decimal
                return parseFloat(v) || 0;
            }

            var requestedRaw = $('#storeForm').find('input[name="requested_amount"]').val();
            var requestedAmount = normalizeNumber(requestedRaw);

            var clientType = $('#storeForm').find('select[name="client_type"]').val();

            // Tomar solo inputs visibles de cuotas (si existe la sección grupal) para evitar validar cuando es Personal
            var quotasInputs = $('#storeForm').find('input[name="quotas[]"]:visible');
            var sumQuotas = 0;
            quotasInputs.each(function() {
                sumQuotas += normalizeNumber($(this).val());
            });

            // Validar solo si es grupo
            if (clientType === 'Grupo' && quotasInputs.length > 0) {
                if (Math.abs(sumQuotas - requestedAmount) > 0.01) {
                    ToastError.fire({
                        text: 'La suma de los montos de cuotas debe ser igual al monto total del contrato.'
                    });
                    $('#btn-save').prop('disabled', false);
                    return;
                }
            }

            $.ajax({
                url: '{{ route('contracts.store') }}',
                method: 'POST',
                data: $(this).serialize(),
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
                url: '{{ route('contracts.index') }}' + '/' + id + '/edit/',
                method: 'GET',
                success: function(data) {
                    $('#editName').val(data.name);
                    $('#editId').val(data.id);
                    $('#editModal').modal('show');
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

            var id = $('#editId').val();

            $.ajax({
                url: '{{ route('contracts.index') }}' + '/' + id + '',
                method: 'PATCH',
                data: $(this).serialize(),
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
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });

        });

        $(document).on('click', '.btn-delete', function() {

            var id = $(this).data('id');

            ToastConfirm.fire({
                text: '¿Estás seguro que deseas borrar el registro?',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('contracts.index') }}' + '/' + id,
                        method: 'DELETE',
                        success: function(data) {
                            ToastMessage.fire({
                                    text: 'Registro eliminado'
                                })
                                .then(() => location.reload());
                        },
                        error: function(err) {
                            ToastError.fire({
                                text: 'Ocurrió un error'
                            });
                        }
                    });
                }
            });

        });

        $('#civil_status').change(function() {
            var civil_status = $(this).val();

            if (civil_status == 'Casado') {
                $('#divHusbandName').show();
                $('#divHusbandDocument').show();
            } else {
                $('#husband_name').val('');
                $('#husband_document').val('');
                $('#divHusbandName').hide();
                $('#divHusbandDocument').hide();
            }
        });

        $('#client_type').change(function() {
            var client_type = $(this).val();

            if (client_type == 'Personal') {

                $('#storeForm')[0].reset();

                $('#divDocument').show();
                $('#divName').show();
                $('#divPhone').show();
                $('#divAddress').show();
                $('#divReference').show();
                $('#divHomeType').show();
                $('#divBusinessLine').show();
                $('#divBusinessAddress').show();
                $('#divBusinessStartDate').show();
                $('#divCivilStatus').show();

                $('#divGroupName').hide();
                $('#divQuantity').hide();
                $('#divGroup').hide();

            } else if (client_type == 'Grupo') {

                $('#divDocument').hide();
                $('#divName').hide();
                $('#divPhone').hide();
                $('#divAddress').hide();
                $('#divReference').hide();
                $('#divHomeType').hide();
                $('#divBusinessLine').hide();
                $('#divBusinessAddress').hide();
                $('#divBusinessStartDate').hide();
                $('#divCivilStatus').hide();

                $('#divGroupName').show();
                $('#divQuantity').show();
                $('#divGroup').show();
            }
        });

        $('#btn-add').click(function() {
            var $baseRow = $('#divGroup .row').first();
            if (!$baseRow.length) return;

            var $newRow = $baseRow.clone(false, false);
            $newRow.find('.ts-wrapper').remove();
            $newRow.find('input[name="documents[]"], input[name="names[]"], input[name="addresses[]"], input[name="quotas[]"]').each(function() {
                $(this).val('').removeAttr('readonly');
            });

            var $dniInput = $newRow.find('input[name="documents[]"]').first();
            $dniInput
                .removeClass('tomselected ts-hidden-accessible')
                .addClass('ts-document')
                .removeAttr('id tabindex hidden')
                .removeData('ts-initialized');

            $newRow.find('.btn-group-search, #btn-group-search').removeAttr('id').addClass('btn-group-search');

            $('#divGroup').append($newRow);

            var $newInput = $('#divGroup .row').last().find('input[name="documents[]"]').first();
            if (window.initContractDocumentSelect) {
                window.initContractDocumentSelect($newInput);
            }
        });

        $('#btn-remove').click(function() {
            if ($('#divGroup').children().length > 2) {
                $('#divGroup').children().last().remove();
            } else {
                console.log('Deben haber 2 personas mínimo para grupo');
            }
        });

        $('#editInterest').change(function() {
            if ($(this).prop('checked')) {
                $('#interest').show();
            } else {
                $('#interest').val('');
                $('#interest').hide();
            }
        });

        $(document).on('click', '.btn-number', function() {
            var id = $(this).data('id');
            var number = $(this).data('number');
            $('#numberContractId').val(id);
            $('#numberPagare').val(number);
            $('#numberModal').modal('show');
        });

        $(document).on('click', '.btn-transfer', function() {
            var id = $(this).data('id');
            var seller = $(this).data('seller') || '';
            $('#transferContractId').val(id);
            $('#transferSeller').val(seller);
            $('#transferModal').modal('show');
        });

        $('#transferForm').submit(function(e) {
            e.preventDefault();
            var id = $('#transferContractId').val();
            var sellerId = $('#transferSeller').val();
            if (!sellerId) {
                ToastError.fire({ text: 'Selecciona un asesor' });
                return;
            }

            $.ajax({
                url: '{{ route('contracts.index') }}' + '/' + id, // llama a update
                method: 'POST',
                data: {
                    _method: 'PATCH',
                    seller_id: sellerId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.status) {
                        $('#transferModal').modal('hide');
                        ToastMessage.fire({ text: 'Transferencia realizada' }).then(function() {
                            location.reload();
                        });
                    } else {
                        ToastError.fire({ text: data.error || 'Ocurrió un error' });
                    }
                },
                error: function() {
                    ToastError.fire({ text: 'Ocurrió un error' });
                }
            });
        });

        $('#numberForm').submit(function(e) {
            e.preventDefault();
            var id = $('#numberContractId').val();
            var number = $('#numberPagare').val();
            if (!number) {
                ToastError.fire({ text: 'Ingresse un Número' });
                return;
            }

            $.ajax({
                url: '{{ route('contracts.index') }}' + '/' + id, // llama a update
                method: 'POST',
                data: {
                    _method: 'PATCH',
                    number_pagare: number,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.status) {
                        $('#numberModal').modal('hide');
                        ToastMessage.fire({ text: 'Número actualizado' }).then(function() {
                            location.reload();
                        });
                    } else {
                        ToastError.fire({ text: data.error || 'Ocurrió un error' });
                    }
                },
                error: function() {
                    ToastError.fire({ text: 'Ocurrió un error' });
                }
            });
        });
    </script>
@endsection



