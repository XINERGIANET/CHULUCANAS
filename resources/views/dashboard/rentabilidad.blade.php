@extends('template.app')

@section('title', 'Indicadores - Rentabilidad')

@section('content')

    @if (auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('credit') ||
            auth()->user()->hasRole('credit_manager') ||
            auth()->user()->hasRole('seller') ||
            auth()->user()->hasRole('admin_credit'))
        <div class="row" id="content-rentabilidad">
            <!-- <div class="col-md-6">
                                      <h3>Evolución de ventas vs egresos</h3>
                                      <div class="card">
                                       <div class="card-body">
                                        <canvas id="chart1"></canvas>
                                       </div>
                                      </div>
                                     </div> -->
            <div class="col-md-6">
                <form class="mb-4">
                    <div class="row">
                        @if (auth()->user()->hasRole('admin') ||
                                auth()->user()->hasRole('credit') ||
                                auth()->user()->hasRole('admin_credit') ||
                                auth()->user()->hasRole('credit_manager'))
                            @if (auth()->user()->hasRole('admin_credit'))
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jefe de Crédito</label>
                                        <select class="form-select js-credit-manager" name="credit_manager_id">
                                            <option value="">Todos</option>
                                            @foreach ($admincredits as $admincredit)
                                                <option value="{{ $admincredit->id }}"
                                                    @if ($admincredit->id == request()->credit_manager_id) selected @endif>
                                                    {{ $admincredit->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Asesor comercial</label>
                                        <select class="form-select js-seller-select" name="seller_id_2">
                                            <option value="">Seleccionar</option>
                                            @foreach ($sellers as $seller)
                                                <option value="{{ $seller->id }}"
                                                    data-manager="{{ $seller->credit_manager_id ?? '' }}"
                                                    @if ($seller->id == request()->seller_id_2) selected @endif>{{ $seller->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jefe de Crédito</label>
                                        <select class="form-select js-credit-manager" name="credit_manager_id">
                                            <option value="">Seleccionar</option>
                                            @foreach ($admincredits as $admincredit)
                                                <option value="{{ $admincredit->id }}"
                                                    @if ($admincredit->id == request()->credit_manager_id) selected @endif>
                                                    {{ $admincredit->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Asesor comercial</label>
                                        <select class="form-select js-seller-select" name="seller_id_2">
                                            <option value="">Seleccionar</option>
                                            @foreach ($sellers as $seller)
                                                <option value="{{ $seller->id }}"
                                                    data-manager="{{ $seller->credit_manager_id ?? '' }}"
                                                    @if ($seller->id == request()->seller_id_2) selected @endif>{{ $seller->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                        @endif
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha desde</label>
                                <input type="date" class="form-control" name="start_date_1"
                                    value="{{ request()->start_date_1 }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" class="form-control" name="end_date_1"
                                    value="{{ request()->end_date_1 }}">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="start_date_2" value="{{ request()->start_date_2 }}">
                    <input type="hidden" name="end_date_2" value="{{ request()->end_date_2 }}">
                    <input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
                    <input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
                    <input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
                    <input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
                    <input type="hidden" name="section" class="js-section-input"
                        value="{{ $section ?? (request()->section ?? 'rentabilidad') }}">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
                    <button type="button" class="btn btn-danger ms-2" onclick="resetForm()"> <i class="ti ti-eraser icon"></i> Limpiar</button>
                </form>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Cartera total
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold">S/{{ number_format($wallet_total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Total de deuda (morosos)
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold">S/{{ number_format($due_total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 js-rentabilidad-card" role="button" tabindex="0"
                            data-card="advance" data-title="Pagos adelantados de hoy">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Pagos adelantados de hoy
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold"><i class="bi bi-person-circle"></i>
                                    {{ $today_advance_payments_people ?? 0 }} </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 js-rentabilidad-card" role="button" tabindex="0"
                            data-card="today" data-title="Pagos de hoy">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Pagos de hoy
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold"><i class="bi bi-person-circle"></i> {{ $today_payments_people ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 js-rentabilidad-card" role="button" tabindex="0"
                            data-card="timely" data-title="Pagos puntuales de hoy">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Pagos puntuales de hoy
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold"><i class="bi bi-person-circle"></i> {{ $today_timely_payments_people ?? 0 }} </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 js-rentabilidad-card" role="button" tabindex="0"
                            data-card="projected" data-title="Proyectado para hoy">
                            <div class="card-body text-center">
                                <h5 class="card-title text-center">
                                    Proyectado para hoy
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold"><i class="bi bi-person-circle"></i> {{ $today_projected_people ?? 0 }} </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Pago puntual
                                </h5>
                                      <span class="d-block fs-1 text-center fw-semibold text-center">
                                    {{ number_format($today_punctual_percent ?? 0, 2) . '%' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal modal-blur fade" id="rentabilidadCardModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rentabilidadCardModalTitle">Detalle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="text-muted">Registros encontrados:</div>
                        <div class="fw-semibold" id="rentabilidadCardTotal">0</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead id="rentabilidadCardTableHead"></thead>
                            <tbody id="rentabilidadCardTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        window.INIT_SECTION = "{{ $section ?? (request()->section ?? 'rentabilidad') }}";

        (function() {
            function showSection(name) {
                const sections = ['rentabilidad'];
                sections.forEach(s => {
                    const el = document.getElementById('content-' + s);
                    if (!el) return;
                    el.style.display = (s === name) ? '' : 'none';
                });
                document.querySelectorAll('.dashboard-card').forEach(card => {
                    card.setAttribute('aria-expanded', card.dataset.section === name ? 'true' : 'false');
                });

                // sincronizar los inputs hidden para que al enviar el formulario se incluya la sección actual
                document.querySelectorAll('.js-section-input').forEach(i => i.value = name);
            }

            function setQueryParam(key, value) {
                const url = new URL(window.location.href);
                const params = url.searchParams;
                if (!value) params.delete(key);
                else params.set(key, value);
                url.search = params.toString();
                history.pushState(null, '', url.toString());
            }

            // Exponer resetForm globalmente para que pueda ser llamada desde onclick
            window.resetForm = function() {
                // Limpiar todos los campos del formulario
                var form = document.querySelector('form');
                if (form) {
                    // Limpiar selects
                    form.querySelectorAll('select').forEach(function(select) {
                        select.value = '';
                    });
                    // Limpiar inputs de fecha
                    form.querySelectorAll('input[type="date"]').forEach(function(input) {
                        input.value = '';
                    });
                }
                
                // Redirigir a la URL limpia (solo con la sección)
                var baseUrl = window.location.origin + window.location.pathname;
                window.location.href = baseUrl + '?section=rentabilidad';
            };

            // click handler for dashboard cards (uniform behavior)
            document.querySelectorAll('.dashboard-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.dataset.section;
                    showSection(section);
                    setQueryParam('section', section); // unified param for all cards
                    const target = document.getElementById('content-' + section);
                    if (target) target.focus();
                });
            });


            (function handleInitial() {
                const params = new URL(window.location.href).searchParams;
                const sectionFromUrl = params.get('section');
                const initial = sectionFromUrl || window.INIT_SECTION || 'rentabilidad';
                showSection(initial);

                // sincroniza inputs hidden en todos los forms para que el submit incluya la sección
                document.querySelectorAll('.js-section-input').forEach(i => i.value = initial);
                // opcional: mantener URL consistente
                setQueryParam('section', initial);
            })();

            // handle back/forward
            window.addEventListener('popstate', function() {
                const params = new URL(window.location.href).searchParams;
                const section = params.get('section');
                if (section) showSection(section);
                else {
                    // restore original visibility (show all)
                    ['rentabilidad'].forEach(s => {
                        const el = document.getElementById('content-' + s);
                        if (el) el.style.display = '';
                    });
                    document.querySelectorAll('.dashboard-card').forEach(card => card.setAttribute(
                        'aria-expanded', 'false'));
                }
            });

        })();
    </script>
    <script>
        // Filtrar sellers en cliente para cada par credit-manager / seller-select dentro del mismo formulario
        $(function() {
            $('.js-credit-manager').each(function() {
                var $cm = $(this);
                var $form = $cm.closest('form');
                var $s = $form.find('.js-seller-select');
                if (!$s.length) return; // nada que hacer

                // Obtener el seller_id_2 del request
                var selectedSellerIdFromRequest = '{{ request()->seller_id_2 ?? '' }}';
                var managerFromRequest = '{{ request()->credit_manager_id ?? '' }}';

                function filterSellers() {
                    var manager = $cm.val();
                    var currentSelected = $s.val();
                    
                    if (!manager) {
                        // Si no hay jefe de crédito, mostrar todas las opciones
                        $s.find('option').show();
                    } else {
                        // Si hay jefe de crédito, mostrar solo los asesores de ese jefe
                        var selectedBelongsToManager = false;
                        
                        $s.find('option').each(function() {
                            var $opt = $(this);
                            if (!$opt.val()) {
                                // La opción vacía siempre visible
                                $opt.show();
                                return;
                            }
                            
                            var dm = String($opt.data('manager') || '');
                            var optValue = String($opt.val());
                            var belongsToManager = (dm === manager);
                            
                            // Mostrar si pertenece al manager
                            $opt.toggle(belongsToManager);
                            
                            // Verificar si la opción seleccionada pertenece al manager
                            if (optValue === currentSelected && belongsToManager) {
                                selectedBelongsToManager = true;
                            }
                        });
                        
                        // Si el asesor seleccionado no pertenece al manager actual, resetearlo
                        if (!selectedBelongsToManager && currentSelected) {
                            $s.val('');
                        }
                    }
                }

                $cm.on('change', filterSellers);
                // inicializar según el valor actual (request())
                filterSellers();    
            });
        });
    </script>
    <script>
        (function() {
            var rentabilidadFilters = {
                start_date_1: "{{ request()->start_date_1 }}",
                end_date_1: "{{ request()->end_date_1 }}",
                credit_manager_id: "{{ request()->credit_manager_id }}",
                seller_id_2: "{{ request()->seller_id_2 }}"
            };

            function escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function renderPayments(items) {
                var rows = items.map(function(item) {
                    return `
                        <tr>
                            <td>${escapeHtml(item.client)}</td>
                            <td>${escapeHtml(item.quota_number || '')}</td>
                            <td>S/${parseFloat(item.amount || 0).toFixed(2)}</td>
                            <td>${escapeHtml(item.payment_method || '')}</td>
                            <td>${escapeHtml(item.quota_date || '')}</td>
                            <td>${escapeHtml(item.payment_date || '')}</td>
                            <td>${escapeHtml(item.due_days)}</td>
                        </tr>
                    `;
                }).join('');

                if (!rows) {
                    rows = '<tr><td colspan="7" class="text-center">No se encontraron pagos</td></tr>';
                }

                $('#rentabilidadCardTableHead').html(`
                    <tr>
                        <th>Cliente</th>
                        <th>Cuota</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha cuota</th>
                        <th>Fecha pago</th>
                        <th>Mora</th>
                    </tr>
                `);
                $('#rentabilidadCardTableBody').html(rows);

            }

            function renderQuotas(items) {
                var rows = items.map(function(item) {
                    var status = item.paid ? 'Pagado' : 'Pendiente';
                    return `
                        <tr>
                            <td>${escapeHtml(item.client)}</td>
                            <td>${escapeHtml(item.quota_number || '')}</td>
                            <td>S/${parseFloat(item.amount || 0).toFixed(2)}</td>
                            <td>S/${parseFloat(item.debt || 0).toFixed(2)}</td>
                            <td>${escapeHtml(item.due_date || '')}</td>
                            <td>${escapeHtml(item.payment_date || '')}</td>
                            <td>${status}</td>
                        </tr>
                    `;
                }).join('');

                if (!rows) {
                    rows = '<tr><td colspan="7" class="text-center">No se encontraron cuotas</td></tr>';
                }

                $('#rentabilidadCardTableHead').html(`
                    <tr>
                        <th>Cliente</th>
                        <th>Cuota</th>
                        <th>Monto</th>
                        <th>Deuda</th>
                        <th>Fecha cuota</th>
                        <th>Fecha pago</th>
                        <th>Estado</th>
                    </tr>
                `);
                $('#rentabilidadCardTableBody').html(rows);
            }

            function loadCardDetails(card, title) {
                $('#rentabilidadCardModalTitle').text(title || 'Detalle');
                $('#rentabilidadCardTotal').text('0');
                $('#rentabilidadCardTableHead').html('');
                $('#rentabilidadCardTableBody').html(`
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                `);
                $('#rentabilidadCardModal').modal('show');

                $.ajax({
                    url: "{{ route('dashboard.rentabilidad.card-details') }}",
                    method: 'GET',
                    data: Object.assign({}, rentabilidadFilters, { card: card }),
                    success: function(data) {
                        if (!data || !data.status) {
                            $('#rentabilidadCardTableBody').html(
                                '<tr><td colspan="7" class="text-center">No se pudo cargar el detalle</td></tr>'
                            );
                            return;
                        }

                        $('#rentabilidadCardTotal').text(data.total || (data.items && data.items.length) || 0);

                        if (data.type === 'quotas') {
                            renderQuotas(data.items || []);
                        } else {
                            renderPayments(data.items || []);
                        }
                        console.log(data);
                    },
                    error: function() {
                        $('#rentabilidadCardTableBody').html(
                            '<tr><td colspan="7" class="text-center">No se pudo cargar el detalle</td></tr>'
                        );
                    }
                });
            }

            $(document).on('click', '.js-rentabilidad-card', function() {
                var card = $(this).data('card');
                var title = $(this).data('title');
                loadCardDetails(card, title);
            });

            $(document).on('keypress', '.js-rentabilidad-card', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
        })();
    </script>
@endsection








