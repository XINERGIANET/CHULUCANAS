@extends('template.app')

@section('title', 'Inicio')

@section('content')

    <div class="gap-2 mb-6 mt-2">
        <div class="row g-3 mb-4">
            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit') || auth()->user()->hasRole('operations'))
                <div class="col-6 col-md-3">
                    <a href="#" class="card dashboard-card text-decoration-none text-reset h-100 shadow-sm"
                        data-section="efectivo" role="button" aria-controls="content-efectivo" aria-expanded="false">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="ti ti-cash fs-3 text-primary"></span>
                            <div>
                                <div class="fw-semibold">Efectivo por asesor</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endif

            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
                <div class="col-6 col-md-3">
                    <a href="#" class="card dashboard-card text-decoration-none text-reset h-100 shadow-sm"
                        data-section="cuadre" role="button" aria-controls="content-cuadre" aria-expanded="false">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="ti ti-wallet fs-3 text-success"></span>
                            <div>
                                <div class="fw-semibold">Cuadre general</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit') || auth()->user()->hasRole('operations'))
        <div class="row" id="content-efectivo">
            <h2>Efectivo por asesor</h2>
            <div class="col-md-9">
                <form class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Fecha desde</label>
                                <input type="date" class="form-control" name="start_date_4"
                                    value="{{ request()->start_date_4 }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" class="form-control" name="end_date_4"
                                    value="{{ request()->end_date_4 }}">
                            </div>
                        </div>
                        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit') || auth()->user()->hasRole('operations'))
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Asesor comercial</label>
                                    <select class="form-select" name="seller_id_1">
                                        <option value="">Seleccionar</option>
                                        @foreach ($sellers as $seller)
                                            <option value="{{ $seller->id }}"
                                                @if ($seller->id == request()->seller_id_1) selected @endif>{{ $seller->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                    <input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
                    <input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
                    <input type="hidden" name="start_date_2" value="{{ request()->start_date_2 }}">
                    <input type="hidden" name="end_date_2" value="{{ request()->end_date_2 }}">
                    <input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
                    <input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
                    <input type="hidden" name="seller_id_2" value="{{ request()->seller_id_2 }}">
                    <input type="hidden" name="section" class="js-section-input"
                        value="{{ $section ?? (request()->section ?? 'efectivo') }}">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
                </form>
            </div>
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            Efectivo
                        </h5>
                        @if (request()->seller_id_1)
                            <span
                                class="block fs-1 text-center fw-semibold">S/{{ number_format($home_sales_1, 2) }}</span>
                        @else
                            <span class="block fs-1 text-center fw-semibold">S/ -</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif


    @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
        <div id="content-cuadre">
            <h2>Cuadre general</h2>
            <form class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Fecha desde</label>
                            <input type="date" class="form-control" name="start_date_3"
                                value="{{ request()->start_date_3 }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Fecha hasta</label>
                            <input type="date" class="form-control" name="end_date_3"
                                value="{{ request()->end_date_3 }}">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
                <input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
                <input type="hidden" name="start_date_2" value="{{ request()->start_date_2 }}">
                <input type="hidden" name="end_date_2" value="{{ request()->end_date_2 }}">
                <input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
                <input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
                <input type="hidden" name="seller_id_1" value="{{ request()->seller_id_1 }}">
                <input type="hidden" name="seller_id_2" value="{{ request()->seller_id_2 }}">
                <input type="hidden" name="section" class="js-section-input"
                    value="{{ $section ?? (request()->section ?? 'efectivo') }}">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
            </form>

            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                Dinero en cuentas
                            </h5>
                            <ul>
                                @foreach ($payment_methods as $pm)
                                    <li class="fs-3 fw-semibold">
                                        {{ $pm->name ?? 'Método' }}:
                                        S/{{ number_format($pm->acumulado ?? 0, 2) }}
                                    </li>
                                @endforeach
                            </ul>
                            <!-- <ul>
                  <li class="fs-3 fw-semibold">Efectivo: S/{{-- number_format($sales_1, 2) --}}</li>
                  <li class="fs-3 fw-semibold">Banco de la Nación: S/{{-- number_format($sales_2, 2) --}}</li>
                  <li class="fs-3 fw-semibold">Caja Piura: S/{{-- number_format($sales_3, 2) --}}</li>
                  <li class="fs-3 fw-semibold">BCP: S/{{-- number_format($sales_4, 2) --}}</li>
                  <li class="fs-3 fw-semibold">BBVA: S/{{-- number_format($sales_5, 2) --}}</li>
                 </ul> -->
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                Total
                            </h5>
                            <span
                                class="block fs-1 text-center fw-semibold">S/{{ number_format(collect($payment_methods)->sum('acumulado'), 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')

    <script>

        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit') || auth()->user()->hasRole('operations'))
        window.INIT_SECTION = "{{ $section ?? (request()->section ?? 'efectivo') }}";
        @elseif (auth()->user()->hasRole('credit_manager') || auth()->user()->hasRole('seller') || auth()->user()->hasRole('admin_credit'))
        window.INIT_SECTION = "{{ $section ?? (request()->section ?? 'rentabilidad') }}";
        @endif

        (function() {
            function showSection(name) {
                const sections = ['efectivo', 'cuadre'];
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


            (function handleInitial() {
                const params = new URL(window.location.href).searchParams;
                const sectionFromUrl = params.get('section');
                const initial = sectionFromUrl || window.INIT_SECTION || 'efectivo';
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
                    ['efectivo', 'cuadre'].forEach(s => {
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

                function filterSellers() {
                    var manager = $cm.val();
                    if (!manager) {
                        $s.find('option').show();
                    } else {
                        $s.find('option').each(function() {
                            var $opt = $(this);
                            if (!$opt.val()) { $opt.show(); return; }
                            var dm = String($opt.data('manager') || '');
                            $opt.toggle(dm === manager);
                        });
                        if ($s.find('option:selected').is(':hidden')) {
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
