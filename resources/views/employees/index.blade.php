@extends('template.app')

@section('title', 'Recursos Humanos - Control de Vacaciones')

@section('content')
    <nav class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Recursos Humanos & Vacaciones</li>
        </ol>
    </nav>

    <!-- METRIC CARDS -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <i class="ti ti-users icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $totalActive }} Colaboradores
                            </div>
                            <div class="text-muted">
                                Personal activo registrado
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                                <i class="ti ti-calendar-event icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $onVacationCount }} en Descanso
                            </div>
                            <div class="text-muted">
                                De vacaciones actualmente
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-info text-white avatar">
                                <i class="ti ti-clock icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ number_format($totalGeneratedDays, 2) }} días
                            </div>
                            <div class="text-muted">
                                Total generados / acumulados
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <i class="ti ti-beach icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ number_format($totalBalanceDays, 2) }} días
                            </div>
                            <div class="text-muted">
                                Saldo disponible global
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE CARD -->
    <div class="card">
        <div class="card-header d-flex justify-content-between flex-column flex-md-row gap-2">
            <div class="d-flex gap-2">
                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('operations'))
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEmployeeModal">
                        <i class="ti ti-user-plus icon"></i> Registrar colaborador
                    </button>
                @endif
            </div>
            <div>
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Buscar por nombre, DNI o cargo" name="name"
                        value="{{ request()->name }}" style="min-width: 200px;" autocomplete="off">

                    <select name="status" class="form-select form-select-sm" style="width: auto;">
                        <option value="">Todos los estados</option>
                        <option value="1" {{ request()->status === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request()->status === '0' ? 'selected' : '' }}>Cesados</option>
                    </select>

                    <button type="submit" class="btn btn-sm btn-icon btn-secondary">
                        <i class="ti ti-search icon"></i>
                    </button>
                    @if(request()->hasAny(['name', 'status', 'position']))
                        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                            <i class="ti ti-x icon"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table card-table table-vcenter table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Cargo</th>
                        <th>Fecha de Ingreso</th>
                        <th>Tiempo de Servicio</th>
                        <th class="text-center">Días Ganados</th>
                        <th class="text-center">Días Gozados</th>
                        <th class="text-center">Saldo Disponible</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($employees->count() > 0)
                        @foreach ($employees as $emp)
                            <tr>
                                <td>
                                    <div class="d-flex py-1 align-items-center">
                                        <span class="avatar me-2 {{ $emp->is_on_vacation ? 'bg-warning-lt' : 'bg-blue-lt' }}">
                                            <i class="ti {{ $emp->is_on_vacation ? 'ti-beach' : 'ti-user' }} icon"></i>
                                        </span>
                                        <div class="flex-fill">
                                            <div class="font-weight-medium">{{ $emp->name }}</div>
                                            <div class="text-muted small">
                                                DNI: {{ $emp->document ?: 'Sin DNI' }} 
                                                @if($emp->phone) • Tel: {{ $emp->phone }} @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-lt">{{ $emp->position ?: 'General' }}</span>
                                </td>
                                <td>
                                    <div>{{ $emp->entry_date ? \Carbon\Carbon::parse($emp->entry_date)->format('d/m/Y') : '-' }}</div>
                                    <small class="text-muted">{{ $emp->regime }} ({{ $emp->annual_vacation_days }} d/año)</small>
                                </td>
                                <td>
                                    <span class="badge bg-info-lt text-dark">{{ $emp->tenure_text }}</span>
                                </td>
                                <td class="text-center font-weight-bold text-primary">
                                    {{ number_format($emp->vacation_days_generated, 2) }} d
                                </td>
                                <td class="text-center font-weight-bold text-warning">
                                    {{ number_format($emp->vacation_days_taken, 2) }} d
                                </td>
                                <td class="text-center">
                                    @if($emp->vacation_balance > 0)
                                        <span class="badge bg-success font-weight-bold p-2" style="font-size: 0.85rem;">
                                            <i class="ti ti-check icon me-1"></i> {{ number_format($emp->vacation_balance, 2) }} días
                                        </span>
                                    @elseif($emp->vacation_balance == 0)
                                        <span class="badge bg-secondary font-weight-bold p-2" style="font-size: 0.85rem;">
                                            0.00 días
                                        </span>
                                    @else
                                        <span class="badge bg-danger font-weight-bold p-2" style="font-size: 0.85rem;">
                                            {{ number_format($emp->vacation_balance, 2) }} días
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($emp->status == 0)
                                        <span class="badge bg-danger">Cesado</span>
                                    @elseif($emp->is_on_vacation)
                                        <span class="badge bg-warning text-dark"><i class="ti ti-beach icon"></i> En vacaciones</span>
                                    @else
                                        <span class="badge bg-success">Activo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <!-- REGISTRAR SALIDA A VACACIONES (SOLO ADMIN Y OPERACIONES) -->
                                        @if((auth()->user()->hasRole('admin') || auth()->user()->hasRole('operations')) && $emp->status == 1)
                                            <button class="btn btn-icon btn-warning btn-register-vacation" 
                                                data-id="{{ $emp->id }}" 
                                                data-name="{{ $emp->name }}"
                                                data-balance="{{ $emp->vacation_balance }}"
                                                title="Registrar salida de vacaciones">
                                                <i class="ti ti-beach icon"></i>
                                            </button>
                                        @endif

                                        <!-- VER DETALLE E HISTORIAL -->
                                        <button class="btn btn-icon btn-info btn-view-history" 
                                            data-id="{{ $emp->id }}" 
                                            title="Ver historial y liquidación de vacaciones">
                                            <i class="ti ti-eye icon"></i>
                                        </button>

                                        <!-- EDITAR (SOLO ADMIN) -->
                                        @if(auth()->user()->hasRole('admin'))
                                            <button class="btn btn-icon btn-primary btn-edit-employee" 
                                                data-id="{{ $emp->id }}" 
                                                title="Editar colaborador">
                                                <i class="ti ti-pencil icon"></i>
                                            </button>
                                            <button class="btn btn-icon btn-danger btn-delete-employee" 
                                                data-id="{{ $emp->id }}" 
                                                data-name="{{ $emp->name }}"
                                                title="Eliminar colaborador">
                                                <i class="ti ti-trash icon"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="ti ti-users-off icon fs-2 d-block mb-1"></i>
                                No se encontraron colaboradores registrados.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $employees->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: REGISTRAR COLABORADOR -->
    <div class="modal modal-blur fade" id="createEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="createEmployeeForm">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="ti ti-user-plus icon me-1"></i> Registrar Nuevo Colaborador</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">DNI / Documento</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="document" id="create_document" placeholder="8 dígitos" maxlength="20">
                                    <button class="btn btn-outline-secondary" type="button" id="btn_search_reniec_emp" title="Consultar RENIEC">
                                        <i class="ti ti-search icon"></i> RENIEC
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Nombres y Apellidos</label>
                                <input type="text" class="form-control" name="name" id="create_name" required placeholder="Nombre completo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha de Ingreso</label>
                                <input type="date" class="form-control" name="entry_date" id="create_entry_date" required value="{{ date('Y-m-d') }}">
                                <small class="text-muted">A partir de esta fecha se computan los días de vacaciones.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo / Puesto</label>
                                <input type="text" class="form-control" name="position" placeholder="Ej: Asesor Comercial, Operaciones...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono / Celular</label>
                                <input type="text" class="form-control" name="phone" placeholder="999999999">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" name="email" placeholder="correo@ejemplo.com">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="address" placeholder="Domicilio del colaborador">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vincular a Usuario del Sistema (Opcional)</label>
                                <select class="form-select" name="user_id">
                                    <option value="">-- No vincular / Es independiente --</option>
                                    @foreach($systemUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Régimen / Días anuales</label>
                                <select class="form-select" name="annual_vacation_days">
                                    <option value="15" selected>REMYPE (15 días por año - 1.25 d/mes)</option>
                                    <option value="30">Régimen General (30 días por año - 2.5 d/mes)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btn_save_employee">
                            <i class="ti ti-device-floppy icon"></i> Guardar Colaborador
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: EDITAR COLABORADOR -->
    <div class="modal modal-blur fade" id="editEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="editEmployeeForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="ti ti-pencil icon me-1"></i> Editar Datos del Colaborador</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">DNI / Documento</label>
                                <input type="text" class="form-control" name="document" id="edit_document" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Nombres y Apellidos</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha de Ingreso</label>
                                <input type="date" class="form-control" name="entry_date" id="edit_entry_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo / Puesto</label>
                                <input type="text" class="form-control" name="position" id="edit_position">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono / Celular</label>
                                <input type="text" class="form-control" name="phone" id="edit_phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" name="email" id="edit_email">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="address" id="edit_address">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado Laboral</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="1">Activo</option>
                                    <option value="0">Cesado / Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="wrapper_termination_date" style="display: none;">
                                <label class="form-label">Fecha de Cese</label>
                                <input type="date" class="form-control" name="termination_date" id="edit_termination_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Días Vacaciones Anuales</label>
                                <input type="number" class="form-control" name="annual_vacation_days" id="edit_annual_days" min="1" max="60">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btn_update_employee">
                            <i class="ti ti-device-floppy icon"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: REGISTRAR SALIDA A VACACIONES -->
    <div class="modal modal-blur fade" id="registerVacationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="registerVacationForm">
                    @csrf
                    <input type="hidden" name="employee_id" id="vacation_employee_id">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="ti ti-beach icon me-1"></i> Registrar Salida a Vacaciones</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3">
                            <div class="font-weight-bold" id="vacation_employee_name">Colaborador</div>
                            <div>Saldo disponible actual: <strong id="vacation_employee_balance" class="text-success">0.00</strong> días</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Fecha de Inicio</label>
                                <input type="date" class="form-control" name="start_date" id="vacation_start_date" required value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha de Fin</label>
                                <input type="date" class="form-control" name="end_date" id="vacation_end_date" required value="{{ date('Y-m-d', strtotime('+6 days')) }}">
                            </div>
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border">
                                    <span>Total días calendario calculados:</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" step="0.5" min="0.5" class="form-control form-control-sm text-center font-weight-bold text-primary" 
                                            name="days" id="vacation_days" style="width: 90px;" required value="7">
                                        <span class="text-muted">días</span>
                                    </div>
                                </div>
                                <small class="text-muted mt-1 d-block">Por defecto calcula los días entre las fechas seleccionadas (ej: 7 días = 1 semana). Puedes ajustarlo manualmente si corresponde.</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Observaciones / Sustento</label>
                                <textarea class="form-control" name="comments" rows="2" placeholder="Ej: Descanso semanal correspondiente al primer periodo..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning text-dark font-weight-bold" id="btn_save_vacation">
                            <i class="ti ti-check icon"></i> Registrar Salida
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: HISTORIAL Y DETALLE DE VACACIONES / LIQUIDACIÓN -->
    <div class="modal modal-blur fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="ti ti-file-analytics icon me-1"></i> Récord Laboral y Control de Vacaciones</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- HEADER EMPLEADO -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h3 class="mb-1 text-primary" id="history_emp_name">-</h3>
                                    <div class="text-muted mb-2">
                                        <span id="history_emp_doc">DNI: -</span> • 
                                        <span id="history_emp_position">Cargo: -</span> • 
                                        <span id="history_emp_entry">Ingreso: -</span>
                                    </div>
                                    <div class="badge bg-blue text-white p-2" id="history_emp_tenure">Tiempo de servicio: -</div>
                                </div>
                                <div class="col-md-5">
                                    <div class="row text-center g-2">
                                        <div class="col-4">
                                            <div class="border rounded p-2 bg-white">
                                                <small class="text-muted d-block">Generados</small>
                                                <strong class="text-primary fs-3" id="history_stat_generated">0.00</strong>
                                                <small class="d-block text-muted">días</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2 bg-white">
                                                <small class="text-muted d-block">Gozados</small>
                                                <strong class="text-warning fs-3" id="history_stat_taken">0.00</strong>
                                                <small class="d-block text-muted">días</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2 bg-white">
                                                <small class="text-muted d-block">Saldo</small>
                                                <strong class="text-success fs-3" id="history_stat_balance">0.00</strong>
                                                <small class="d-block text-muted">días</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DESGLOSE POR PERIODOS ANUALES (PARA SUSTENTO Y LIQUIDACIÓN) -->
                    <div class="card mb-3">
                        <div class="card-header bg-white">
                            <h4 class="card-title"><i class="ti ti-calendar-stats icon text-primary"></i> Desglose por Periodos Laborales (Acumulación continua)</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered text-center align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Periodo</th>
                                        <th>Rango de Fechas</th>
                                        <th>Condición</th>
                                        <th>Días Ganados</th>
                                    </tr>
                                </thead>
                                <tbody id="history_periods_tbody">
                                    <!-- Dynamic -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- HISTORIAL DE DESPLAZAMIENTOS / SALIDAS A VACACIONES -->
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h4 class="card-title"><i class="ti ti-beach icon text-warning"></i> Historial de Salidas a Vacaciones Registradas</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter table-hover text-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th class="text-center">Días Tomados</th>
                                        <th>Observaciones / Sustento</th>
                                        <th>Registrado por</th>
                                        @if(auth()->user()->hasRole('admin'))
                                            <th class="text-end">Acciones</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody id="history_vacations_tbody">
                                    <!-- Dynamic -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // RECALCULAR DIAS DE VACACIONES AL CAMBIAR FECHAS EN MODAL DE SALIDA
    function recalculateVacationDays() {
        const startVal = $('#vacation_start_date').val();
        const endVal = $('#vacation_end_date').val();

        if (startVal && endVal) {
            const start = new Date(startVal + 'T00:00:00');
            const end = new Date(endVal + 'T00:00:00');

            if (end >= start) {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                $('#vacation_days').val(diffDays);
            }
        }
    }

    $('#vacation_start_date, #vacation_end_date').on('change', recalculateVacationDays);

    // CONSULTAR API RENIEC PARA COLABORADOR
    $('#btn_search_reniec_emp').click(function() {
        const doc = $('#create_document').val().trim();
        if (doc.length !== 8) {
            Swal.fire('Atención', 'Ingresa un DNI válido de 8 dígitos', 'warning');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: "{{ route('api.reniec') }}",
            type: 'GET',
            data: { document: doc },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="ti ti-search icon"></i> RENIEC');
                if (res.name) {
                    $('#create_name').val(res.name);
                } else if (res.data && res.data.name) {
                    $('#create_name').val(res.data.name);
                } else {
                    Swal.fire('Aviso', 'No se encontraron datos para el DNI consultado', 'info');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="ti ti-search icon"></i> RENIEC');
                Swal.fire('Error', 'No se pudo consultar el servicio de RENIEC', 'error');
            }
        });
    });

    // GUARDAR NUEVO COLABORADOR
    $('#createEmployeeForm').submit(function(e) {
        e.preventDefault();
        const btn = $('#btn_save_employee');
        btn.prop('disabled', true).prepend('<span class="spinner-border spinner-border-sm me-1"></span>');

        $.ajax({
            url: "{{ route('employees.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).find('.spinner-border').remove();
                if (res.status) {
                    Swal.fire('¡Éxito!', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.error, 'error');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).find('.spinner-border').remove();
                Swal.fire('Error', 'Ocurrió un error en el servidor', 'error');
            }
        });
    });

    // ABRIR MODAL EDITAR COLABORADOR
    $('.btn-edit-employee').click(function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('employees') }}/" + id + "/details",
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    const emp = res.employee;
                    $('#edit_id').val(emp.id);
                    $('#edit_document').val(emp.document !== '-' ? emp.document : '');
                    $('#edit_name').val(emp.name);
                    $('#edit_entry_date').val(emp.raw_entry_date);
                    $('#edit_position').val(emp.position !== '-' ? emp.position : '');
                    $('#edit_phone').val(emp.phone !== '-' ? emp.phone : '');
                    $('#edit_email').val(emp.email !== '-' ? emp.email : '');
                    $('#edit_address').val(emp.address !== '-' ? emp.address : '');
                    $('#edit_status').val(emp.status);
                    $('#edit_annual_days').val(emp.annual_vacation_days);

                    if (emp.status == 0) {
                        $('#wrapper_termination_date').show();
                    } else {
                        $('#wrapper_termination_date').hide();
                    }

                    $('#editEmployeeModal').modal('show');
                }
            }
        });
    });

    $('#edit_status').change(function() {
        if ($(this).val() == '0') {
            $('#wrapper_termination_date').show();
        } else {
            $('#wrapper_termination_date').hide();
        }
    });

    // ACTUALIZAR COLABORADOR
    $('#editEmployeeForm').submit(function(e) {
        e.preventDefault();
        const id = $('#edit_id').val();
        const btn = $('#btn_update_employee');
        btn.prop('disabled', true).prepend('<span class="spinner-border spinner-border-sm me-1"></span>');

        $.ajax({
            url: "{{ url('employees') }}/" + id,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).find('.spinner-border').remove();
                if (res.status) {
                    Swal.fire('¡Actualizado!', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.error, 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).find('.spinner-border').remove();
                Swal.fire('Error', 'Error de comunicación', 'error');
            }
        });
    });

    // ELIMINAR COLABORADOR
    $('.btn-delete-employee').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: '¿Eliminar colaborador?',
            text: 'Se dará de baja el registro de ' + name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('employees') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            Swal.fire('Eliminado', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.error, 'error');
                        }
                    }
                });
            }
        });
    });

    // ABRIR MODAL REGISTRAR VACACIONES
    $('.btn-register-vacation').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const balance = $(this).data('balance');

        $('#vacation_employee_id').val(id);
        $('#vacation_employee_name').text(name);
        $('#vacation_employee_balance').text(Number(balance).toFixed(2));
        
        // Reset default dates (1 week)
        const today = new Date().toISOString().split('T')[0];
        const nextWeek = new Date(Date.now() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        
        $('#vacation_start_date').val(today);
        $('#vacation_end_date').val(nextWeek);
        $('#vacation_days').val(7);

        $('#registerVacationModal').modal('show');
    });

    // GUARDAR SALIDA A VACACIONES
    $('#registerVacationForm').submit(function(e) {
        e.preventDefault();
        const btn = $('#btn_save_vacation');
        btn.prop('disabled', true).prepend('<span class="spinner-border spinner-border-sm me-1"></span>');

        $.ajax({
            url: "{{ route('employee-vacations.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).find('.spinner-border').remove();
                if (res.status) {
                    Swal.fire('¡Registrado!', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.error, 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).find('.spinner-border').remove();
                Swal.fire('Error', 'Ocurrió un error en el servidor', 'error');
            }
        });
    });

    // ABRIR HISTORIAL Y RECORD DE VACACIONES
    $('.btn-view-history').click(function() {
        const id = $(this).data('id');

        $.ajax({
            url: "{{ url('employees') }}/" + id + "/details",
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    const emp = res.employee;
                    $('#history_emp_name').text(emp.name);
                    $('#history_emp_doc').text('DNI: ' + emp.document);
                    $('#history_emp_position').text('Cargo: ' + emp.position);
                    $('#history_emp_entry').text('Ingreso: ' + emp.entry_date);
                    $('#history_emp_tenure').text('Tiempo de servicio: ' + emp.tenure_text + ' (' + emp.total_days_worked + ' días laborados)');

                    $('#history_stat_generated').text(Number(emp.vacation_days_generated).toFixed(2));
                    $('#history_stat_taken').text(Number(emp.vacation_days_taken).toFixed(2));
                    $('#history_stat_balance').text(Number(emp.vacation_balance).toFixed(2));

                    // Periodos laborales
                    let periodsHtml = '';
                    if (res.periods && res.periods.length > 0) {
                        res.periods.forEach(p => {
                            periodsHtml += `
                                <tr>
                                    <td><strong>Año ${p.index}</strong></td>
                                    <td>${p.period_text}</td>
                                    <td>
                                        <span class="badge ${p.is_complete ? 'bg-success-lt' : 'bg-info-lt'}">${p.status_text}</span>
                                    </td>
                                    <td class="font-weight-bold text-primary">${Number(p.days_earned).toFixed(2)} días</td>
                                </tr>
                            `;
                        });
                    } else {
                        periodsHtml = '<tr><td colspan="4" class="text-center text-muted">Sin periodos registrados</td></tr>';
                    }
                    $('#history_periods_tbody').html(periodsHtml);

                    // Historial de salidas
                    let vacationsHtml = '';
                    if (res.vacations && res.vacations.length > 0) {
                        res.vacations.forEach(v => {
                            let deleteBtn = '';
                            if (res.is_admin) {
                                deleteBtn = `
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-icon btn-danger btn-delete-vacation" data-id="${v.id}" title="Eliminar registro">
                                            <i class="ti ti-trash icon"></i>
                                        </button>
                                    </td>
                                `;
                            }
                            vacationsHtml += `
                                <tr>
                                    <td><i class="ti ti-calendar icon text-muted me-1"></i> ${v.start_date}</td>
                                    <td><i class="ti ti-calendar icon text-muted me-1"></i> ${v.end_date}</td>
                                    <td class="text-center font-weight-bold text-warning">${Number(v.days).toFixed(2)} días</td>
                                    <td>${v.comments}</td>
                                    <td><small class="text-muted">${v.created_by} (${v.created_at})</small></td>
                                    ${deleteBtn}
                                </tr>
                            `;
                        });
                    } else {
                        const colspan = res.is_admin ? 6 : 5;
                        vacationsHtml = `<tr><td colspan="${colspan}" class="text-center text-muted py-3">Aún no registra descansos vacacionales tomados.</td></tr>`;
                    }
                    $('#history_vacations_tbody').html(vacationsHtml);

                    $('#historyModal').modal('show');
                }
            }
        });
    });

    // ELIMINAR SALIDA DE VACACIONES (SOLO ADMIN DESDE MODAL HISTORIAL)
    $(document).on('click', '.btn-delete-vacation', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: '¿Eliminar registro de vacaciones?',
            text: 'Se restaurarán los días al saldo disponible del colaborador.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('employee-vacations') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            Swal.fire('Eliminado', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.error, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>
@endsection
