<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ChargesExport;
use App\Exports\DuesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $rentabilidadCard = $request->rentabilidad_card;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $paymentsQuery = Payment::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('quota.contract', function ($query) use ($user) {
                    return $query->where('seller_id', $user->id);
                });
            })
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->whereHas('quota.contract.seller', function ($query) use ($user) {
                    return $query->where('credit_manager_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $creditManagerId) {
                return $query->whereHas('quota.contract.seller', function ($query) use ($creditManagerId) {
                    return $query->where('credit_manager_id', $creditManagerId);
                });
            })
            ->when($request->name, function ($query, $name) {
                return $query->whereHas('quota.contract', function ($query) use ($name) {
                    return $query->where(function ($query) use ($name) {
                        return $query->where('name', 'like', '%' . $name . '%')->orWhere('group_name', 'like', '%' . $name . '%');
                    });
                });
            })->when($request->payment_method_id, function ($query, $payment_method_id) {
                return $query->where('payment_method_id', $payment_method_id);
            })->when($request->seller_id, function ($query, $seller_id) {
                return $query->whereHas('quota.contract', function ($query) use ($seller_id) {
                    return $query->where('seller_id', $seller_id);
                });
            })->when($startDate && $rentabilidadCard !== 'projected', function ($query) use ($startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })->when($endDate && $rentabilidadCard !== 'projected', function ($query) use ($endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->when($rentabilidadCard === 'advance', function ($query) {
                return $query->whereRaw('DATE(payments.date) < (SELECT DATE(quotas.date) FROM quotas WHERE quotas.id = payments.quota_id)');
            })
            ->when($rentabilidadCard === 'timely', function ($query) {
                return $query->whereRaw('DATE(payments.date) = (SELECT DATE(quotas.date) FROM quotas WHERE quotas.id = payments.quota_id)');
            })
            ->when($rentabilidadCard === 'projected', function ($query) use ($startDate, $endDate) {
                return $query->whereHas('quota', function ($q) use ($startDate, $endDate) {
                    if ($startDate) {
                        $q->whereDate('date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $q->whereDate('date', '<=', $endDate);
                    }
                });
            })
            ->latest('date')->latest('id');

        $total = $paymentsQuery->sum('amount');

        // Obtener todos los pagos con relaciones
        $allPayments = $paymentsQuery->with(['quota.contract', 'payment_method'])->get();
        
        // Agrupar pagos por contrato + número de cuota + fecha
        $groupedByContractQuotaDate = $allPayments->groupBy(function($payment) {
            $contractId = optional(optional($payment->quota)->contract)->id ?? 'none';
            $quotaNumber = optional($payment->quota)->number ?? 'none';
            $dateKey = $payment->date->format('Y-m-d');
            
            return $contractId . '_' . $quotaNumber . '_' . $dateKey;
        });
        
        // Procesar grupos
        $grouped = [];
        foreach ($groupedByContractQuotaDate as $key => $paymentsGroup) {
            // Verificar si el contrato es de tipo Grupo
            $firstPayment = $paymentsGroup->first();
            $contract = optional(optional($firstPayment->quota)->contract);
            $isGroupContract = $contract && $contract->client_type == 'Grupo';
            
            // Solo agrupar si es contrato grupal Y hay múltiples pagos
            if ($isGroupContract && $paymentsGroup->count() > 1) {
                // Separar pagos según su formato
                $completeFormatGroups = collect();
                $paymentsToGroup = collect();
                
                foreach ($paymentsGroup as $payment) {
                    $hasCompleteFormat = false;
                    
                    if (!empty($payment->people)) {
                        $peopleData = json_decode($payment->people, true);
                        if (is_array($peopleData) && count($peopleData) > 0) {
                            // Verificar si tiene el formato completo (document, name, address)
                            if (isset($peopleData[0]['document']) && 
                                isset($peopleData[0]['name']) && 
                                isset($peopleData[0]['address'])) {
                                $hasCompleteFormat = true;
                            }
                        }
                    }
                    
                    if ($hasCompleteFormat) {
                        // Agrupar por hash de people + imagen para formato completo
                        $peopleData = json_decode($payment->people, true);
                        usort($peopleData, function($a, $b) {
                            return strcmp($a['document'] ?? '', $b['document'] ?? '');
                        });
                        // Incluir la imagen en el hash para separar pagos con diferentes imágenes
                        $groupKey = md5(serialize($peopleData) . '|' . ($payment->image ?? 'no-image'));
                        
                        if (!isset($completeFormatGroups[$groupKey])) {
                            $completeFormatGroups[$groupKey] = collect();
                        }
                        $completeFormatGroups[$groupKey]->push($payment);
                    } else {
                        // Pagos sin formato completo se agrupan todos juntos
                        $paymentsToGroup->push($payment);
                    }
                }
                
                // Procesar grupos con formato completo
                foreach ($completeFormatGroups as $peopleHash => $groupPayments) {
                    if ($groupPayments->count() > 1) {
                        // Agrupar pagos con mismo people
                        $firstPayment = $groupPayments->first();
                        $totalAmount = $groupPayments->sum('amount');
                        
                        $images = $groupPayments->filter(function($p) {
                            return !empty($p->image);
                        })->pluck('image')->toArray();
                        
                        // Verificar si todos tienen el mismo método de pago
                        $uniqueMethods = $groupPayments->pluck('payment_method.name')->unique();
                        $paymentMethodName = $uniqueMethods->count() === 1 ? $uniqueMethods->first() : 'MIXTO';
                        // Cambiar Efectivo por Retanqueo
                        if ($paymentMethodName === 'Efectivo') {
                            $paymentMethodName = 'Retanqueo';
                        }
                        
                        $groupedPayment = clone $firstPayment;
                        $groupedPayment->amount = $totalAmount;
                        $groupedPayment->payment_method = (object)['name' => $paymentMethodName];
                        $groupedPayment->grouped_details = $groupPayments->map(function($p) {
                            // Obtener person_name de la cuota
                            $personName = optional($p->quota)->person_name ?? 'N/A';
                            
                            // Cambiar Efectivo por Retanqueo
                            $methodName = 'N/A';
                            if ($p->payment_method) {
                                $methodName = $p->payment_method->id == 1 ? 'Retanqueo' : $p->payment_method->name;
                            }
                            
                            return [
                                'person_name' => $personName,
                                'method' => $methodName,
                                'amount' => $p->amount,
                                'image' => $p->image
                            ];
                        })->values()->toArray();
                        $groupedPayment->images = $images;
                        
                        $grouped[] = $groupedPayment;
                    } else {
                        // Solo un pago con este people específico
                        $grouped[] = $groupPayments->first();
                    }
                }

                // Agrupar los pagos sin formato completo (antiguos)
                if ($paymentsToGroup->count() > 1) {
                    $firstPayment = $paymentsToGroup->first();
                    $totalAmount = $paymentsToGroup->sum('amount');
                    
                    $images = $paymentsToGroup->filter(function($p) {
                        return !empty($p->image);
                    })->pluck('image')->toArray();
                    
                    // Verificar si todos tienen el mismo método de pago
                    $uniqueMethods = $paymentsToGroup->pluck('payment_method.name')->unique();
                    $paymentMethodName = $uniqueMethods->count() === 1 ? $uniqueMethods->first() : 'MIXTO';
                    // Cambiar Efectivo por Retanqueo
                    if ($paymentMethodName === 'Efectivo') {
                        $paymentMethodName = 'Retanqueo';
                    }
                    
                    $groupedPayment = clone $firstPayment;
                    $groupedPayment->amount = $totalAmount;
                    $groupedPayment->payment_method = (object)['name' => $paymentMethodName];
                    $groupedPayment->grouped_details = $paymentsToGroup->map(function($p) {
                        // Obtener person_name de la cuota también para pagos antiguos
                        $personName = optional($p->quota)->person_name ?? 'N/A';
                        
                        // Cambiar Efectivo por Retanqueo
                        $methodName = 'N/A';
                        if ($p->payment_method) {
                            $methodName = $p->payment_method->id == 1 ? 'Retanqueo' : $p->payment_method->name;
                        }
                        
                        return [
                            'person_name' => $personName,
                            'method' => $methodName,
                            'amount' => $p->amount,
                            'image' => $p->image
                        ];
                    })->values()->toArray();
                    $groupedPayment->images = $images;
                    
                    $grouped[] = $groupedPayment;
                } elseif ($paymentsToGroup->count() == 1) {
                    // Solo un pago sin formato completo
                    $grouped[] = $paymentsToGroup->first();
                }
            } else {
                // Contratos individuales o un solo pago: agregar sin agrupar
                foreach ($paymentsGroup as $payment) {
                    $grouped[] = $payment;
                }
            }
        }
        
        // Paginar manualmente
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $groupedCollection = collect($grouped);
        $pagedData = $groupedCollection->forPage($currentPage, $perPage);
        
        $payments = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $groupedCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if (auth()->user()->hasRole('operations')) {
            $payment_methods = PaymentMethod::active()->where('name', 'Efectivo')->get();
        } elseif (auth()->user()->hasRole('seller')) {
            $payment_methods = PaymentMethod::active()->where('name', '!=', 'Efectivo')->get();
        }
        else  {
            $payment_methods = PaymentMethod::active()->get();
        }

        $credit_managers = User::where('role', 'credit_manager')->active()->get();

        $sellers = User::seller()->where('state', 0)->active()
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->where('credit_manager_id', $user->id);
            })
            ->get();

        $day = now()->format('N'); // 1-5
        $hour = now()->format('G'); // 7 - 20

        return view('payments.index', compact('payments', 'payment_methods', 'sellers', 'credit_managers', 'day', 'hour', 'total'));
    }

    public function charges(Request $request)
    {
        $user = auth()->user();
        $sellers = User::seller()->active()
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->where('credit_manager_id', $user->id);
            })
            ->get();

        $quotas = Quota::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($query) use ($user) {
                    return $query->where('seller_id', $user->id);
                });
            })
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->whereHas('contract.seller', function ($query) use ($user) {
                    return $query->where('credit_manager_id', $user->id);
                });
            })
            ->when($request->name, function ($query, $name) {
                return $query->whereHas('contract', function ($query) use ($name) {
                    return $query->where(function ($query) use ($name) {
                        return $query->where('name', 'like', '%' . $name . '%')->orWhere('group_name', 'like', '%' . $name . '%');
                    });
                });
            })->when($request->seller_id, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($query) use ($seller_id) {
                    return $query->where('seller_id', $seller_id);
                });
            })->when($request->start_date, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })->where('paid', 0)->orderBy('date')->paginate(20);

        return view('payments.charges', compact('quotas', 'sellers'));
    }

    public function dues(Request $request)
    {
        $user = auth()->user();
        $sellers = User::seller()->active()
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->where('credit_manager_id', $user->id);
            })
            ->get();

        $date = $request->date ? Carbon::parse($request->date)->toDateString() : now()->toDateString();
        $referenceDate = Carbon::parse($date);

        $quotas = Quota::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($query) use ($user) {
                    return $query->where('seller_id', $user->id);
                });
            })
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->whereHas('contract.seller', function ($query) use ($user) {
                    return $query->where('credit_manager_id', $user->id);
                });
            })
            ->when($request->name, function ($query, $name) {
                return $query->whereHas('contract', function ($query) use ($name) {
                    return $query->where('name', 'like', '%' . $name . '%');
                });
            })->when($request->seller_id, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($query) use ($seller_id) {
                    return $query->where('seller_id', $seller_id);
                });
            })->when($request->from_days, function ($query, $from_days) use ($date) {
                return $query->whereRaw('DATEDIFF(?, date) >= ?', [$date, $from_days]);
            })->when($request->to_days, function ($query, $to_days) use ($date) {
                return $query->whereRaw('DATEDIFF(?, date) <= ?', [$date, $to_days]);
            })->whereDate('date', '<', $date)->where('paid', 0)->paginate(20);

        return view('payments.dues', compact('quotas', 'sellers', 'referenceDate'));
    }

    public function store(Request $request)
    {

        $day = now()->format('N'); // 1-5
        $hour = now()->format('G'); // 7 - 20

        if (!auth()->user()->hasRole('admin') && ($day > 5 || $hour < 8 || $hour > 19)) {
            return response()->json([
                'status' => false,
                'error' => 'El registro de pagos se encuentra fuera de horario.'
            ]);
        }

        // Determinar el tipo de pago
        $paymentType = $this->determinePaymentType($request);
        
        if ($paymentType === 'separated_group') {
            return $this->processSeparatedGroupPayment($request);
        } elseif ($paymentType === 'unified_group') {
            return $this->processUnifiedGroupPayment($request);
        } else {
            return $this->processIndividualPayment($request);
        }
    }

    private function determinePaymentType(Request $request)
    {
        // Pago de grupo con cuotas separadas por persona (GRUPO 1)
        if ($request->has('payments_data')) {
            return 'separated_group';
        }
        
        // Pago de grupo unificado (GRUPO 2)
        if ($request->has('quota_id') && $request->has('people')) {
            $quota = Quota::find($request->quota_id);
            if ($quota && $quota->contract->client_type == 'Grupo') {
                return 'unified_group';
            }
        }
        
        // Pago individual
        return 'individual';
    }

    private function processSeparatedGroupPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payments_data' => 'required',
            'payment_method_id' => 'required',
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $paymentsData = json_decode($request->payments_data, true);
        
        if (!$paymentsData || count($paymentsData) == 0) {
            return response()->json([
                'status' => false,
                'error' => 'Debe seleccionar al menos una persona para realizar el pago'
            ]);
        }

        // Validar cada pago individual
        $totalAmount = 0;
        foreach ($paymentsData as $paymentData) {
            if (!isset($paymentData['quota_id']) || !isset($paymentData['amount'])) {
                return response()->json([
                    'status' => false,
                    'error' => 'Datos de pago incompletos'
                ]);
            }
            
            $quota = Quota::find($paymentData['quota_id']);
            if (!$quota) {
                return response()->json([
                    'status' => false,
                    'error' => 'Cuota no encontrada'
                ]);
            }
            
            if ($paymentData['amount'] > $quota->debt) {
                return response()->json([
                    'status' => false,
                    'error' => "El monto para {$quota->person_name} no puede ser mayor a su deuda"
                ]);
            }
            
            $totalAmount += $paymentData['amount'];
        }

        DB::beginTransaction();

        try {
            $payment_date = Carbon::parse($request->date);
            $image = null;

            if ($request->hasFile('image')) {
                $image = $request->image->store('payments', 'public');
            }

            $contract = null;

            // Recopilar todas las personas seleccionadas en esta transacción
            $allSelectedPeople = [];
            foreach ($paymentsData as $paymentData) {
                if (isset($paymentData['person_data'])) {
                    $allSelectedPeople[] = $paymentData['person_data'];
                }
            }

            foreach ($paymentsData as $paymentData) {
                $quota = Quota::find($paymentData['quota_id']);
                $contract = $quota->contract;
                
                $diff = $payment_date->diffInDays($quota->date, false);
                $due_days = $diff < 0 ? abs($diff) : 0;

                // Guardar TODAS las personas que se pagaron en este momento juntas
                Payment::create([
                    'quota_id' => $quota->id,
                    'amount' => $paymentData['amount'],
                    'payment_method_id' => $request->payment_method_id,
                    'date' => $request->date,
                    'due_days' => $due_days,
                    'image' => $image,
                    'people' => json_encode($allSelectedPeople)
                ]);

                $newDebt = $quota->debt - $paymentData['amount'];
                $isPaid = $newDebt <= 0.01 ? 1 : 0;

                $quota->update([
                    'debt' => max(0, $newDebt),
                    'paid' => $isPaid
                ]);
            }

            // Verificar si todas las cuotas del contrato están pagadas
            if ($contract) {
                $unpaidQuotas = Quota::where('contract_id', $contract->id)->where('paid', 0)->count();
                if ($unpaidQuotas == 0) {
                    $contract->update(['paid' => 1]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => 'Ocurrió un error al procesar el pago'
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    private function processUnifiedGroupPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quota_id' => 'required',
            'amount' => 'required|numeric|min:0.1',
            'payment_method_id' => 'required',
            'date' => 'required|date',
            'people' => 'required|array'
        ]);

        $quota = Quota::find($request->quota_id);

        $validator->after(function ($validator) use ($request, $quota) {
            if ($quota) {
                if ($request->amount > $quota->debt) {
                    $validator->errors()->add('amount', 'El pago debe ser menor o igual al saldo pendiente');
                }
            } else {
                $validator->errors()->add('quota_id', 'La cuota no se encuentra');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {
            $payment_date = Carbon::parse($request->date);
            $image = null;

            if ($request->hasFile('image')) {
                $image = $request->image->store('payments', 'public');
            }

            $contract = $quota->contract;
            $diff = $payment_date->diffInDays($quota->date, false);
            $due_days = $diff < 0 ? abs($diff) : 0;

            // Construir el array de personas seleccionadas
            $payment_people = $request->people ? $request->people : [];
            $people = [];

            foreach ($payment_people as $document) {
                foreach (json_decode($contract->people) as $client) {
                    if ($client->document == $document) {
                        $people[] = $client;
                    }
                }
            }

            Payment::create([
                'quota_id' => $request->quota_id,
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'date' => $request->date,
                'due_days' => $due_days,
                'image' => $image,
                'people' => json_encode($people)
            ]);

            $paid = $request->amount == $quota->debt ? 1 : 0;

            $quota->update([
                'debt' => $quota->debt - $request->amount,
                'paid' => $paid
            ]);

            $quotas = Quota::where('contract_id', $quota->contract_id)->where('paid', 0)->count();

            if ($quotas == 0) {
                $quota->contract()->update(['paid' => 1]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => 'Ocurrió un error al procesar el pago'
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    private function processIndividualPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quota_id' => 'required',
            'amount' => 'required|numeric|min:0.1',
            'payment_method_id' => 'required',
            'date' => 'required|date'
        ]);

        $quota = Quota::find($request->quota_id);

        if ($quota) {
            $contract = $quota->contract;
        }

        $validator->after(function ($validator) use ($request, $quota) {
            if ($quota) {
                if ($request->amount > $quota->debt) {
                    $validator->errors()->add('amount', 'El pago debe ser menor o igual al saldo pendiente');
                }
            } else {
                $validator->errors()->add('quota_id', 'La cuota no se encuentra');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {
            $payment_date = Carbon::parse($request->date);
            $image = null;

            if ($request->hasFile('image')) {
                $image = $request->image->store('payments', 'public');
            }

            $diff = $payment_date->diffInDays($quota->date, false);
            $due_days = $diff < 0 ? abs($diff) : 0;

            Payment::create([
                'quota_id' => $request->quota_id,
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'date' => $request->date,
                'due_days' => $due_days,
                'image' => $image,
                'people' => null
            ]);

            $paid = $request->amount == $quota->debt ? 1 : 0;

            $quota->update([
                'debt' => $quota->debt - $request->amount,
                'paid' => $paid
            ]);

            $quotas = Quota::where('contract_id', $quota->contract_id)->where('paid', 0)->count();

            if ($quotas == 0) {
                $quota->contract()->update(['paid' => 1]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => 'Ocurrió un error al procesar el pago'
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Payment $payment)
    {
        return response()->json([
            'id' => $payment->id,
            'client' => optional(optional($payment->quota)->contract)->client(),
            'quota' => $payment->quota,
            'amount' => $payment->amount,
            'payment_method_id' => $payment->payment_method_id,
            'date' => $payment->date->format('d/m/Y'),
            'date_iso' => $payment->date->format('Y-m-d')
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        // Verificar si es un pago agrupado
        if ($request->has('grouped_payments')) {

            // Decodificar el JSON
            $groupedPaymentsData = json_decode($request->grouped_payments, true);

            if (!is_array($groupedPaymentsData)) {
                return response()->json([
                    'status' => false,
                    'error'  => 'Formato de datos inválido'
                ]);
            }

            $validator = Validator::make(['grouped_payments' => $groupedPaymentsData], [
                'grouped_payments' => 'required|array',
                'grouped_payments.*.payment_id' => 'required|integer',
                'grouped_payments.*.payment_method_id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error'  => $validator->errors()->first()
                ]);
            }

            DB::beginTransaction();

            try {
                // nueva fecha (si viene)
                $newDate = ($request->has('date') && $request->date) ? $request->date : null;

                foreach ($groupedPaymentsData as $groupedPayment) {

                    // Cargar pago + cuota para calcular mora
                    $paymentToUpdate = Payment::with('quota')->find($groupedPayment['payment_id']);

                    if (!$paymentToUpdate) {
                        throw new \Exception('Pago no encontrado: ' . $groupedPayment['payment_id']);
                    }

                    $updateData = [
                        'payment_method_id' => $groupedPayment['payment_method_id']
                    ];

                    if ($newDate) {
                        if (!$paymentToUpdate->quota) {
                            throw new \Exception('La cuota no existe para el pago: ' . $paymentToUpdate->id);
                        }

                        $paymentDate = Carbon::parse($newDate)->startOfDay();
                        $quotaDate   = Carbon::parse($paymentToUpdate->quota->date)->startOfDay();

                        // mora = días si pago_date > cuota_date, sino 0
                        $daysLate = $quotaDate->diffInDays($paymentDate, false);
                        $updateData['due_days'] = max(0, (int)$daysLate);

                        $updateData['date'] = $newDate;
                    }

                    $paymentToUpdate->update($updateData);
                }

                DB::commit();

                return response()->json(['status' => true]);

            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'error'  => 'Error al actualizar pagos: ' . $e->getMessage()
                ]);
            }

        } else {
            // Pago individual
            $validator = Validator::make($request->all(), [
                'payment_method_id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error'  => $validator->errors()->first()
                ]);
            }

            $updateData = [
                'payment_method_id' => $request->payment_method_id
            ];

            // si mandan date, recalcular due_days usando quota.date
            if ($request->has('date') && $request->date) {
                $payment->load('quota');

                if (!$payment->quota) {
                    return response()->json([
                        'status' => false,
                        'error'  => 'La cuota no existe para este pago'
                    ]);
                }

                $paymentDate = Carbon::parse($request->date)->startOfDay();
                $quotaDate   = Carbon::parse($payment->quota->date)->startOfDay();

                $daysLate = $quotaDate->diffInDays($paymentDate, false);

                $updateData['date'] = $request->date;
                $updateData['due_days'] = max(0, (int)$daysLate);
            }

            $payment->update($updateData);

            return response()->json(['status' => true]);
        }
    }

    public function getGroupPayments(Request $request, Payment $payment)
    {
        try {
            $quota = $payment->quota;
            $contract = optional($quota)->contract;
            $quotaNumber = $quota->number;
            $paymentDate = $payment->date->format('Y-m-d');

            if ($contract->client_type != 'Grupo') {
                return response()->json([
                    'status' => false,
                    'error' => 'Este no es un contrato de grupo'
                ]);
            }

            // Obtener pagos que son exactamente iguales al seleccionado
            // (mismo contrato, número de cuota, fecha, people e imagen)
            $groupedPayments = Payment::where('deleted', 0)
                ->whereHas('quota', function($query) use ($contract, $quotaNumber) {
                    $query->where('contract_id', $contract->id)
                        ->where('number', $quotaNumber);
                })
                ->whereDate('date', $paymentDate)
                ->where('people', $payment->people)
                ->where('image', $payment->image)
                ->with(['quota', 'payment_method'])
                ->orderBy('id', 'ASC')
                ->get();

            $paymentsData = $groupedPayments->map(function($p) {
                $personName = optional($p->quota)->person_name ?? 'N/A';
                $quotaNumber = optional($p->quota)->number ?? 'N/A';
                
                return [
                    'id' => $p->id,
                    'person_name' => $personName,
                    'quota_number' => $quotaNumber,
                    'amount' => number_format($p->amount, 2),
                    'amount_raw' => $p->amount,
                    'payment_method' => optional($p->payment_method)->name ?? 'N/A',
                    'payment_method_id' => $p->payment_method_id,
                    'image' => $p->image ? asset('storage/' . $p->image) : null,
                    'date' => $p->date->format('d/m/Y'),
                    'people' => $p->people,
                    'quota_id' => $p->quota_id
                ];
            });

            return response()->json([
                'status' => true,
                'payments' => $paymentsData,
                'total_payments' => $paymentsData->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al obtener pagos: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy(Request $request, Payment $payment)
    {
        DB::beginTransaction();

        try {
            $quota = $payment->quota;
            $contract = optional($quota)->contract;
            $paymentIds = $request->input('payment_ids', []);

            // Si es un contrato de grupo y se enviaron IDs específicos
            if ($contract->client_type == 'Grupo' && !empty($paymentIds)) {
                $paymentsToDelete = Payment::whereIn('id', $paymentIds)
                    ->where('deleted', 0)
                    ->get();

                if ($paymentsToDelete->count() == 0) {
                    throw new \Exception('No se encontraron pagos para eliminar');
                }

                $affectedQuotas = [];
                
                foreach ($paymentsToDelete as $groupPayment) {
                    $groupPayment->update(['deleted' => 1]);
                    
                    $quotaInfo = $groupPayment->quota;
                    if (!isset($affectedQuotas[$quotaInfo->id])) {
                        $affectedQuotas[$quotaInfo->id] = [
                            'quota' => $quotaInfo,
                            'total_amount' => 0
                        ];
                    }
                    $affectedQuotas[$quotaInfo->id]['total_amount'] += $groupPayment->amount;
                }
                
                // Actualizar cada cuota afectada
                foreach ($affectedQuotas as $quotaData) {
                    $quotaData['quota']->update([
                        'debt' => $quotaData['quota']->debt + $quotaData['total_amount'],
                        'paid' => 0
                    ]);
                }

            } else {
                // Contrato individual o eliminación directa
                $payment->update(['deleted' => 1]);
                
                $quota->update([
                    'debt' => $quota->debt + $payment->amount,
                    'paid' => 0
                ]);
            }

            // Verificar si todas las cuotas del contrato fueron pagadas
            $hasUnpaidQuotas = $contract->quotas()->where('paid', 0)->exists();
            
            $contract->update([
                'paid' => $hasUnpaidQuotas ? 0 : 1
            ]);

            DB::commit();
            
            return response()->json([
                'status' => true,
                'message' => 'Pago(s) eliminado(s) correctamente'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'error' => 'Error al eliminar: ' . $e->getMessage()
            ]);
        }
    }

    public function chargesExcel(Request $request)
    {
        $name = "GestionDeCobranza_" . now()->format('d_m_Y') . ".xlsx";
        return Excel::download(new ChargesExport, $name);
    }

    public function duesExcel(Request $request)
    {
        $name = "GestionDeMora_" . now()->format('d_m_Y') . ".xlsx";
        return Excel::download(new DuesExport, $name);
    }

    public function deleteGroup(Request $request)
    {
        try {
            $contractId = $request->contract_id;
            $quotaId = $request->quota_id;
            
            Payment::whereHas('quota', function($query) use ($contractId, $quotaId) {
                $query->where('contract_id', $contractId)
                    ->where('id', $quotaId);
            })->update(['deleted' => 1]);
            
            Quota::where('id', $quotaId)
                ->where('contract_id', $contractId)
                ->update(['paid' => 0]);
            
            return response()->json([
                'status' => true,
                'message' => 'Pagos eliminados y cuota marcada como no pagada'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al eliminar los pagos: ' . $e->getMessage()
            ], 500);
        }
    }
}
