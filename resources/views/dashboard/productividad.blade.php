@extends('template.app')

@section('title', 'Indicadores - Productividad')

@section('content')

    @if (auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('admin_credit') ||
            auth()->user()->hasRole('credit_manager') ||
            auth()->user()->hasRole('credit_manager') ||
            auth()->user()->hasRole('seller'))
        <div class="row mb-4" id="content-productividad">
            <!-- <div class="col-md-6">
                      <h3>Evolución de ventas vs egresos</h3>
                      <div class="card">
                       <div class="card-body">
                        <canvas id="chart2"></canvas>
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
                                        <label class="form-label">Jefe de credito</label>
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
                                <input type="date" class="form-control" name="start_date_2"
                                    value="{{ request()->start_date_2 }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" class="form-control" name="end_date_2"
                                    value="{{ request()->end_date_2 }}">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
                    <input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
                    <input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
                    <input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
                    <input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
                    <input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
                    <input type="hidden" name="section" class="js-section-input"
                        value="{{ $section ?? (request()->section ?? 'efectivo') }}">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
                    <button type="button" class="btn btn-danger ms-2" onclick="resetForm()"> <i class="ti ti-eraser icon"></i> Limpiar</button>
                </form>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Clientes activos
                                </h5>
                                <span class="block fs-1 text-center fw-semibold">{{ $active_clients }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Clientes con deuda (>120 días)
                                </h5>
                                <span class="block fs-1 text-center fw-semibold">{{ $due_clients }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Cartera del asesor
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold">S/{{ number_format($seller_wallet, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    Monto desembolsado
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold">S/{{ number_format($requested_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 offset-md-3">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    # de cuotas por pagar
                                </h5>
                                <span
                                    class="block fs-1 text-center fw-semibold">{{ number_format($due_quotas, 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        window.INIT_SECTION = "{{ $section ?? (request()->section ?? 'productividad') }}";

        (function() {
            function showSection(name) {
                const sections = ['productividad'];
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
                window.location.href = baseUrl + '?section=productividad';
            };

            (function handleInitial() {
                const params = new URL(window.location.href).searchParams;
                const sectionFromUrl = params.get('section');
                const initial = sectionFromUrl || window.INIT_SECTION || 'productividad';
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
                    ['productividad'].forEach(s => {
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
@endsection
