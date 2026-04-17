@extends('template.app')

@section('title', 'Clientes')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Clientes</li>
        </ol>
    </nav>

    <div class="card">
        @if (auth()->user()->hasRole('admin') ||
                auth()->user()->hasRole('credit') ||
                auth()->user()->hasRole('operations') ||
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
                        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit') || auth()->user()->hasRole('operations'))
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Asesor comercial</label>
                                    <select class="form-select" name="seller_id">
                                        <option value="">Seleccionar</option>
                                        @foreach ($sellers as $seller)
                                            <option value="{{ $seller->id }}"
                                                @if ($seller->id == request()->seller_id) selected @endif>{{ $seller->name }}
                                            </option>
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
                    <a href="{{ route('clients.index') }}" class="btn btn-danger">Limpiar</a>
                </form>
            </div>
        @endif
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th>Cliente/Grupo</th>
                        <th>Tipo de cliente</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($clients->count() > 0)
                        @foreach ($clients as $client)
                            <tr>
                                <td title="{!! $client->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">
                                    {{ $client->client_type == 'Personal' ? $client->name : $client->group_name }}</td>
                                <td>{{ $client->type() }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <div class="d-flex gap-2">
											<button class="btn btn-primary btn-icon btn-details"
												data-client-type="{{ $client->client_type }}"
												data-document="{{ $client->document }}"
												data-group-name="{{ $client->group_name }}"
												data-contract-id="{{ $client->id }}"
												title="Detalles">
												<i class="ti ti-search icon"></i>
											</button>  {{-- ✅ cerrar aquí --}}
											<button class="btn btn-primary btn-icon btn-contracts"
												data-client-type="{{ $client->client_type }}"
												data-document="{{ $client->document }}"
												data-group-name="{{ $client->group_name }}"
												data-contract-id="{{ $client->id }}"
												title="Contratos">
												<i class="ti ti-list icon"></i>
											</button>
										</div>
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
        @if ($clients->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $clients->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div class="modal modal-blur fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead id="thead-details"></thead>

                        <tbody id="tbl-details"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="contractsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contratos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Monto solicitado</th>
                                <th>Cuotas</th>
                                <th>Interes</th>
                                <th>Monto a pagar</th>
                                <th>Fecha de prestamo</th>
                                <th></th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-contracts"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="quotasModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cuotas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Monto</th>
                                <th>Saldo</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tbl-quotas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal modal-blur fade" id="editPersonModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="editPersonForm" method="POST" action="{{ route('clients.update-person') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_client_type" name="client_type">
                    <input type="hidden" id="edit_group_name" name="group_name">
                    <input type="hidden" id="edit_person_document" name="person_document">
					<input type="hidden" id="edit_contract_id" name="contract_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar persona</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Número de documento</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="document" id="edit_document"
                                        placeholder="Ingrese documento" disabled>
                                    <button class="btn btn-outline-secondary disabled" type="button"
                                        id="btn-search-dni-edit" disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control disabled" name="name" id="edit_name"
                                    placeholder="Nombre completo" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="phone" id="edit_phone"
                                    placeholder="Teléfono">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="address" id="edit_address"
                                    placeholder="Dirección">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-person">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        var currentContractId = '';
        $(document).on('click', '.btn-details', function() {

            var client_type = $(this).data('client-type');
            var doc = $(this).data('document');
            var group_name = $(this).data('group-name');
            var person_document = $(this).data('person-document');
            var contract_id = $(this).data('contract-id');
            console.log(contract_id);
            currentContractId = contract_id;
            $.ajax({
                url: '{{ route('clients.details') }}',
                method: 'GET',
                data: (client_type == 'Personal' ? {
                    client_type: client_type,
                    document: doc,
                    person_document: person_document,
                    contract_id: contract_id
                } : {
                    client_type: client_type,
                    group_name: group_name,
                    person_document: person_document,
                    contract_id: contract_id
                }),
                success: function(data) {
                    var head = '';

                    var html = '';

                    if (client_type == 'Personal') {
                        head += `
							<th>DNI</th>
							<th>Nombre</th>
							<th>Teléfono</th>
							<th>Dirección</th>
							<th>Estado civil</th>
							<th>Acción</th>
						`;
                        html += `
						<tr>
							<td>${data.document || ''}</td>
							<td>${data.name || ''}</td>
							<td>${data.phone || ''}</td>
		  					<td>${data.address || ''}</td>
		  					<td>${data.civil_status || ''}</td>
							<td>
								<button class="btn btn-primary btn-icon btn-edit-person"
									data-client-type="${client_type}"
									data-document="${data.document}"
									data-group-name="${data.group_name}"
									data-contract-id="${data.id}"
									data-person-document="${data.document}"
									title="Editar">
									<i class="ti ti-edit icon"></i>
								</button>
						</tr>
	  					`;
                    } else {
                        head += `
							<th>DNI</th>
							<th>Nombre</th>
							<th>Teléfono</th>
							<th>Dirección</th>
							<th>Acciones</th>
						`;
                        (data.people || []).forEach(function(person) {
                            html += `
		  				<tr>
							<td>${person.document || ''}</td>
							<td>${person.name || ''}</td>
							<td>${person.phone || ''}</td>
							<td>${person.address || ''}</td>
							<td>
								<button class="btn btn-primary btn-icon btn-edit-person"
  								data-client-type="${client_type}"
  								data-group-name="${data.group_name}"
  								data-contract-id="${data.id}"
  								data-person-document="${person.document}"
  								title="Editar">
  								<i class="ti ti-edit icon"></i>
								</button>
							</td>
						</tr>
					`;
                        });
                    }
                    $('#thead-details').html(head);
                    $('#tbl-details').html(html);
                    $('#detailsModal').modal('show');
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });

        });
        $(document).on('click', '.btn-edit-person', function() {
            var client_type = $(this).data('client-type');
            var person_document = $(this).data('person-document');
            var contract_id = $(this).data('contract-id');

            $.ajax({
                url: '{{ route('clients.edit') }}',
                method: 'GET',
                data: {
                    client_type: client_type,
                    contract_id: contract_id,
                    person_document: person_document,
                },
                success: function(res) {
                    if (!res || res.status === false) {
                        ToastError.fire({
                            text: (res && res.error) ? res.error : 'No se pudo cargar'
                        });
                        return;
                    }

                    if (client_type === 'Grupo') {
                        var p = res.person || {};
                        $('#edit_document').val(p.document || '');
                        $('#edit_name').val(p.name || '');
                        $('#edit_phone').val(p.phone || '');
                        $('#edit_address').val(p.address || '');
                    } else {
                        $('#edit_name').val(res.name || '');
                        $('#edit_phone').val(res.phone || '');
                        $('#edit_address').val(res.address || '');
                    }

                    $('#edit_client_type').val(client_type);
                    $('#edit_contract_id').val(contract_id);
                    $('#edit_person_document').val(person_document);

                    // OJO: en tu archivo vi ambos IDs; usa el que realmente existe en tu HTML:
                    // - Si tu modal es #editPersonGroupModal, cámbialo aquí.
                    $('#editPersonModal').modal('show');
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });
        });

        $('#editPersonForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: '{{ route('clients.update-person') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    client_type: $('#edit_client_type').val(),
                    contract_id: $('#edit_contract_id').val(),
                    person_document: $('#edit_person_document').val(),
                    name: $('#edit_name').val(),
                    phone: $('#edit_phone').val(),
                    address: $('#edit_address').val()
                },
                success: function(res) {
                    if (res.status) {
                        $('#editPersonModal').modal('hide');
                        ToastMessage.fire({
                            text: res.message
                        }).then(() => {
                            window.location.href = '{{ route('clients.index') }}';
                        });
                    } else {
                        ToastError.fire({
                            text: res.error
                        });
                    }
                },
                error: function(xhr) {
					console.log(xhr.responseJSON);
                    ToastError.fire({
                        text: 'Ocurrió un error al guardar'
                    });
                }
            });
        });

		$(document).on('click', '.btn-contracts', function(){

var client_type = $(this).data('client-type');
var doc = $(this).data('document');
var group_name = $(this).data('group-name');

$.ajax({
	url: '{{ route('clients.contracts') }}',
	method: 'GET',
	data: { client_type, document: doc, group_name },
	success: function(data){
		var html = '';

		data.forEach(function(contract){

			html += `
				<tr>
					<td>${contract.requested_amount}</td>
					<td>${contract.quotas_number}</td>
					<td>${contract.interest}</td>
					<td>${contract.payable_amount}</td>
					<td>${contract.date}</td>
					<td>${ contract.paid ? '<span class="badge bg-success"></span>' : '<span class="badge bg-danger"></span>' }</td>
					<td>
						<button class="btn btn-primary btn-icon btn-quotas" data-contract="${contract.id}" title="Quotas">
							<i class="ti ti-list icon"></i>
						</button>
					</td>
				</tr>
			`;
		
		});

		$('#tbl-contracts').html(html);
		$('#contractsModal').modal('show');
	},
	error: function(err){
		ToastError.fire({ text: 'Ocurrió un error' });
	}
});

});

        $(document).on('click', '.btn-quotas', function() {

            var contract_id = $(this).data('contract');

            $.ajax({
                url: '{{ route('clients.quotas') }}',
                method: 'GET',
                data: {
                    contract_id
                },
                success: function(data) {
                    var html = '';

                    data.forEach(function(quota) {

                        html += `
						<tr>
							<td>${quota.number}</td>
							<td>${quota.amount}</td>
							<td>${quota.debt}</td>
							<td>${quota.date}</td>
							<td>${ quota.paid ? '<span class="badge bg-success"></span>' : '<span class="badge bg-danger"></span>' }</td>
						</tr>
					`;

                    });

                    $('#tbl-quotas').html(html);
                    $('#quotasModal').modal('show');
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });

        });
    </script>
@endsection
