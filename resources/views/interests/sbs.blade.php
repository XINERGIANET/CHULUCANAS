@extends('template.app')

@section('title', 'Intereses')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Reporte SBS</li>
  </ol>
</nav>

<div class="card">
    <div class="card-body">
        <form id="sbsForm" method="GET" action="{{ route('interests.excel_sbs') }}">
            @php
                $monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                $currentYear = date('Y');
                $startYear = $currentYear - 5;
            @endphp

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Mes</label>
                    <select name="month" id="month-select" class="form-select">
                        <option value="">Seleccionar mes</option>
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ (string) request()->month === (string) $m ? 'selected' : '' }}>
                                {{ $monthNames[$m-1] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Año</label>
                    <select name="year" id="year-select" class="form-select">
                        <option value="">Seleccionar año</option>
                        @foreach(range($startYear, $currentYear) as $y)
                            <option value="{{ $y }}" {{ (string) (request()->year ?? '') === (string) $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" id="generateBtn" class="btn btn-primary" disabled>Generar</button>
                    <a href="{{ url()->current() }}" class="btn btn-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const month = document.getElementById('month-select');
        const year = document.getElementById('year-select');
        const btn = document.getElementById('generateBtn');

        function updateButton() {
            btn.disabled = !(month.value && year.value);
        }

        month.addEventListener('change', updateButton);
        year.addEventListener('change', updateButton);

        // estado inicial
        updateButton();
    });
</script>
@endsection