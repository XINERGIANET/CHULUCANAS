@extends('template.app')

@section('title', 'Indicadores - Analisis de cartera')

@section('content')

    @if (
            auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('admin_credit') ||
            auth()->user()->hasRole('credit_manager') ||
            auth()->user()->hasRole('seller')
        )
        <div class="row mb-4" id="content-analisis">
            <div class="col-md-6">
                <form class="mb-4">
                    <div class="row">
                        @if (
                                auth()->user()->hasRole('admin') ||
                                auth()->user()->hasRole('credit') ||
                                auth()->user()->hasRole('admin_credit') ||
                                auth()->user()->hasRole('credit_manager') ||
                                auth()->user()->hasRole('seller')
                            )
                            @if (auth()->user()->hasRole('admin_credit'))
                                <div class="col-md-6">
                                    <div class="mb-3">
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
                                <div class="col-md-6">
                                    <div class="mb-3">
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
                                {{-- Seller: muestra su propio nombre fijo --}}
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Asesor comercial</label>
                                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                                        <input type="hidden" name="seller_id_2" value="{{ auth()->user()->id }}">
                                    </div>
                                </div>
                            @elseif (auth()->user()->hasRole('credit_manager'))
                                {{-- Jefe de crédito: muestra su propio nombre fijo + selector de asesor filtrado a sus asesores --}}
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jefe de crédito</label>
                                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                                        <input type="hidden" name="credit_manager_id" value="{{ auth()->user()->id }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
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
                                {{-- Admin y otros: dropdowns completos --}}
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jefe de crédito</label>
                                        <select class="form-select js-credit-manager" name="credit_manager_id">
                                            <option value="">Seleccionar</option>
                                            @foreach ($admincredits as $admincredit)
                                                <option value="{{ $admincredit->id }}" @if ($admincredit->id == request()->credit_manager_id) selected @endif>{{ $admincredit->name }}</option>
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
                                                <option value="{{ $seller->id }}" data-manager="{{ $seller->credit_manager_id ?? '' }}" @if ($seller->id == request()->seller_id_2) selected @endif>{{ $seller->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                        @endif
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" class="form-control" name="end_date_2" value="{{ request()->end_date_2 }}">
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
                    <button type="button" class="btn btn-danger ms-2" onclick="resetForm()"> <i class="ti ti-eraser icon"></i>
                        Limpiar</button>
                </form>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">Cartera bruta</h5>
                                <span class="block fs-1 text-center fw-semibold">S/
                                    {{ number_format($cartera_bruta, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">Cartera actual</h5>
                                <span class="block fs-1 text-center fw-semibold">S/
                                    {{ number_format($active_clients, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">Mora(<120 dias)</h5>
                                        <span class="block fs-1 text-center fw-semibold">S/
                                            {{ number_format($due_clients, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">Mora(>121 dias)</h5>
                                <span class="block fs-1 text-center fw-semibold">S/{{ number_format($seller_wallet, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">Mora total</h5>
                                <span
                                    class="block fs-1 text-center fw-semibold">S/{{ number_format($requested_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 ">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <h5 class="card-title">% de mora</h5>
                                <span class="block fs-1 text-center fw-semibold">{{ number_format($due_quotas, 2) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection