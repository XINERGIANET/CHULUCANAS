@extends('template.app')

@section('title', 'Gestion de mora')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item">Cobranzas</li>
            <li class="breadcrumb-item active">Gestion de mora</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            <a class="btn btn-success" href="{{ route('payments.dues.excel', request()->all()) }}" target="_blank">Excel</a>
        </div>
        @if (auth()->user()->hasRole('admin') ||
                auth()->user()->hasRole('credit') ||
                auth()->user()->hasRole('operations') ||
                auth()->user()->hasRole('credit_manager') ||
                auth()->user()->hasRole('admin_credit') ||
                auth()->user()->hasRole('seller'))
            <div class="card-body border-bottom">
                <form>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" name="name" value="{{ request()->name }}">
                            </div>
                        </div>
                        @if (!auth()->user()->hasRole('seller'))
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Asesor comercial</label>
                                    <select class="form-select" name="seller_id">
                                        <option value="">Seleccionar</option>
                                        @foreach ($sellers as $seller)
                                            <option value="{{ $seller->id }}" @if ($seller->id == request()->seller_id) selected @endif>
                                                {{ $seller->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" class="form-control" name="date"
                                    value="{{ request()->date ? request()->date : now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Dias de mora (Rango)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="from_days" min="1"
                                        value="{{ request()->from_days }}">
                                    <input type="number" class="form-control" name="to_days" min="1"
                                        value="{{ request()->to_days }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('payments.dues') }}" class="btn btn-danger">Limpiar</a>
                </form>
            </div>
        @endif
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Numero de cuota</th>
                        <th>Monto Total</th>
                        <th>Saldo Total</th>
                        <th>Fecha de pago (Ref.)</th>
                        <th>Dias de mora (Ref.)</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $refDate = isset($referenceDate) ? $referenceDate : now();
                        $groupedQuotas = $quotas->groupBy('number')->sortKeys();
                    @endphp

                    @if ($groupedQuotas->count() > 0)
                        @foreach ($groupedQuotas as $quotaNumber => $group)
                            @php
                                $firstQuota = $group->first();
                                $totalAmount = $group->sum('amount');
                                $totalDebt = $group->sum('debt');

                                $detailsHtml = '';
                                foreach ($group as $quota) {
                                    $diasMora = max(0, $quota->date->diffInDays($refDate, false));
                                    $moraText = $diasMora > 0 ? '<span style="color: #dc3545; font-weight: bold;">(' . $diasMora . ' dias de mora)</span>' : '';

                                    $line = '<div style="margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.2);">';
                                    $line .= '<div><strong>Cuota N°:</strong> ' . $quota->number . '</div>';
                                    $line .= '<div><strong>Monto:</strong> S/ ' . number_format($quota->amount, 2) . '</div>';
                                    $line .= '<div><strong>Saldo:</strong> S/ ' . number_format($quota->debt, 2) . '</div>';
                                    $line .= '<div><strong>Fecha Pago:</strong> ' . $quota->date->format('d/m/Y') . ' ' . $moraText . '</div>';
                                    $line .= '</div>';
                                    $detailsHtml .= $line;
                                }

                                $detailsHtml = str_replace('"', '&quot;', $detailsHtml);
                            @endphp

                            <tr>
                                <td>{{ optional($firstQuota->contract)->client() }}</td>
                                <td>{{ $quotaNumber }}</td>
                                <td>{{ number_format($totalAmount, 2) }}</td>
                                <td>{{ number_format($totalDebt, 2) }}</td>
                                <td>{{ $firstQuota->date->format('d/m/Y') }}</td>
                                <td>{{ max(0, $firstQuota->date->diffInDays($refDate, false)) }}</td>
                                <td>
                                    <button class="btn btn-primary btn-icon quota-details-btn" data-bs-toggle="tooltip"
                                        data-bs-placement="left" data-bs-html="true" data-bs-title="{{ $detailsHtml }}">
                                        <i class="ti ti-eye icon"></i>
                                    </button>
                                </td>
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
        @if ($quotas->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $quotas->withQueryString()->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, {
                        html: true,
                        sanitize: false,
                        trigger: 'hover focus',
                        container: 'body',
                        template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner" style="max-width: 300px; text-align: left;"></div></div>'
                    });
                });
            });
        </script>
    @endpush
@endsection
