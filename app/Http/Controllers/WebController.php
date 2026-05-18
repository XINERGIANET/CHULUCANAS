<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Quota;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Contract;
use App\Models\User;
use App\Models\Transfer;

class WebController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        /* Administrador */

        /* Inicio */

        $home_sales_1 = Payment::active()->where('payment_method_id', 1)->when($request->start_date_4, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->when($request->seller_id_1, function ($query, $seller_id) {
            return $query->whereHas('quota.contract', function ($query) use ($seller_id) {
                return $query->where('seller_id', $seller_id);
            });
        })->sum('amount');

        // Sum only expensePayments with payment_method_id = 1
        $home_expenses_1 = Expense::active()->whereHas('expensePayments', function ($q) {
            return $q->where('payment_method_id', 1);
        })
            ->when($request->start_date_4, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date_4, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })->when($request->seller_id_1, function ($query, $seller_id) {
                return $query->where('seller_id', $seller_id);
            })->with(['expensePayments' => function ($q) {
                $q->where('payment_method_id', 1);
            }])->get()
            ->sum(function ($expense) {
                return $expense->expensePayments->sum('amount');
            });

        $home_transfers_1_from = Transfer::active()->when($request->seller_id_1, function ($query, $seller_id) {
            return $query->where('from_seller_id', $seller_id);
        })->when($request->start_date_4, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->where('type', 'seller')->sum('amount');

        $home_transfers_1_to = Transfer::active()->when($request->seller_id_1, function ($query, $seller_id) {
            return $query->where('to_seller_id', $seller_id);
        })->when($request->start_date_4, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->where('type', 'seller')->sum('amount');

        $home_sales_1 = $home_sales_1 - $home_expenses_1 - $home_transfers_1_from + $home_transfers_1_to;

        /* Cuadre general */

        // $sales_1 = Payment::active()->where('payment_method_id', 1)->when($request->start_date_3, function($query, $start_date){
        //     return $query->whereDate('date', '>=', $start_date);
        // })->when($request->end_date_3, function($query, $end_date){
        //     return $query->whereDate('date', '<=', $end_date);
        // })->sum('amount');

        // $expenses_1 = Expense::active()->whereHas('expensePayments', function($q){
        //         return $q->where('payment_method_id', 1);
        //     })->when($request->start_date_3, function($query, $start_date){
        //         return $query->whereDate('date', '>=', $start_date);
        //     })->when($request->end_date_3, function($query, $end_date){
        //         return $query->whereDate('date', '<=', $end_date);
        //     })->with('expensePayments')->get()
        //     ->sum(function($expense){
        //         // suma solo payments con payment_method_id = 1
        //         return $expense->expensePayments->where('payment_method_id', 1)->sum('amount');
        //     });

        // $transfers_1_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 1)->sum('amount');

        // $transfers_1_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 1)->sum('amount');

        // $sales_2 = Payment::active()->where('payment_method_id', 2)->when($request->start_date_3, function($query, $start_date){
        //     return $query->whereDate('date', '>=', $start_date);
        // })->when($request->end_date_3, function($query, $end_date){
        //     return $query->whereDate('date', '<=', $end_date);
        // })->sum('amount');

        // $expenses_2 = Expense::active()->whereHas('expensePayments', function($q){
        //         return $q->where('payment_method_id', 2);
        //     })->when($request->start_date_3, function($query, $start_date){
        //         return $query->whereDate('date', '>=', $start_date);
        //     })->when($request->end_date_3, function($query, $end_date){
        //         return $query->whereDate('date', '<=', $end_date);
        //     })->with('expensePayments')->get()
        //     ->sum(function($expense){
        //         // suma solo payments con payment_method_id = 2
        //         return $expense->expensePayments->where('payment_method_id', 2)->sum('amount');
        //     });

        // $transfers_2_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 2)->sum('amount');

        // $transfers_2_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 2)->sum('amount');

        // $sales_3 = Payment::active()->where('payment_method_id', 3)->when($request->start_date_3, function($query, $start_date){
        //     return $query->whereDate('date', '>=', $start_date);
        // })->when($request->end_date_3, function($query, $end_date){
        //     return $query->whereDate('date', '<=', $end_date);
        // })->sum('amount');

        // $expenses_3 = Expense::active()->whereHas('expensePayments', function($q){
        //         return $q->where('payment_method_id', 3);
        //     })->when($request->start_date_3, function($query, $start_date){
        //         return $query->whereDate('date', '>=', $start_date);
        //     })->when($request->end_date_3, function($query, $end_date){
        //         return $query->whereDate('date', '<=', $end_date);
        //     })->with('expensePayments')->get()
        //     ->sum(function($expense){
        //         // suma solo payments con payment_method_id = 3
        //         return $expense->expensePayments->where('payment_method_id', 3)->sum('amount');
        //     });

        // $transfers_3_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 3)->sum('amount');

        // $transfers_3_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 3)->sum('amount');

        // $sales_4 = Payment::active()->where('payment_method_id', 4)->when($request->start_date_3, function($query, $start_date){
        //     return $query->whereDate('date', '>=', $start_date);
        // })->when($request->end_date_3, function($query, $end_date){
        //     return $query->whereDate('date', '<=', $end_date);
        // })->sum('amount');

        // $expenses_4 = Expense::active()->whereHas('expensePayments', function($q){
        //         return $q->where('payment_method_id', 4);
        //     })->when($request->start_date_3, function($query, $start_date){
        //         return $query->whereDate('date', '>=', $start_date);
        //     })->when($request->end_date_3, function($query, $end_date){
        //         return $query->whereDate('date', '<=', $end_date);
        //     })->with('expensePayments')->get()
        //     ->sum(function($expense){
        //         // suma solo payments con payment_method_id = 4
        //         return $expense->expensePayments->where('payment_method_id', 4)->sum('amount');
        //     });

        // $transfers_4_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 4)->sum('amount');

        // $transfers_4_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 4)->sum('amount');

        // $sales_5 = Payment::active()->where('payment_method_id', 5)->when($request->start_date_3, function($query, $start_date){
        //     return $query->whereDate('date', '>=', $start_date);
        // })->when($request->end_date_3, function($query, $end_date){
        //     return $query->whereDate('date', '<=', $end_date);
        // })->sum('amount');

        // $expenses_5 = Expense::active()->whereHas('expensePayments', function($q){
        //         return $q->where('payment_method_id', 5);
        //     })->when($request->start_date_3, function($query, $start_date){
        //         return $query->whereDate('date', '>=', $start_date);
        //     })->when($request->end_date_3, function($query, $end_date){
        //         return $query->whereDate('date', '<=', $end_date);
        //     })->with('expensePayments')->get()->sum(function($expense){
        //         // suma solo payments con payment_method_id = 4
        //         return $expense->expensePayments->where('payment_method_id', 4)->sum('amount');
        //     });

        // $transfers_5_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 5)->sum('amount');

        // $transfers_5_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 5)->sum('amount');

        // // $sales_6 = 0; // Pagos Caja chica

        // // $expenses_6 = Expense::active()->whereHas('expensePayments', function($q){
        // //         return $q->where('payment_method_id', 6);
        // //     })->when($request->start_date_3, function($query, $start_date){
        // //         return $query->whereDate('date', '>=', $start_date);
        // //     })->when($request->end_date_3, function($query, $end_date){
        // //         return $query->whereDate('date', '<=', $end_date);
        // //     })->with('expensePayments')->get()->sum('amount');

        // // $transfers_6_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 6)->sum('amount');

        // $sales_1 = $sales_1 - $expenses_1 - $transfers_1_from + $transfers_1_to;
        // $sales_2 = $sales_2 - $expenses_2 - $transfers_2_from + $transfers_2_to;
        // $sales_3 = $sales_3 - $expenses_3 - $transfers_3_from + $transfers_3_to;
        // $sales_4 = $sales_4 - $expenses_4 - $transfers_4_from + $transfers_4_to;
        // $sales_5 = $sales_5 - $expenses_5 - $transfers_5_from + $transfers_5_to;
        // // $sales_6 = $sales_6 - $expenses_6 + $transfers_6_to;
        // $total = $sales_1 + $sales_2 + $sales_3 + $sales_4 + $sales_5; // + $sales_6;


        // CARTERA TOTAL : suma de deuda entre las fechas establecidas
        // Cartera total: suma de deuda entre las fechas establecidas.
        // Usamos el scope active() y aplicamos todos los filtros sobre la relación `contract`
        // para evitar filtrar por columnas que no pertenecen a la tabla `quotas`.

        $wallet_total = Quota::active()
            ->when($request->start_date_1, function ($query, $start_date) {
                return $query->whereHas('contract', function ($q) use ($start_date) {
                    return $q->whereDate('date', '>=', $start_date);
                });
            })
            ->when($request->end_date_1, function ($query, $end_date) {
                return $query->whereHas('contract', function ($q) use ($end_date) {
                    return $q->whereDate('date', '<=', $end_date);
                });
            })
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->where('paid', 0)
            ->sum('debt');

        // DEUDA TOTAL : CUOTAS QUE FALTAN PAGAR POR CLIENTES MOROSOS
        $due_total = Quota::when($request->start_date_1, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_1, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->where('paid', 0)
            ->whereHas('contract', function ($q) { // suma de due_days de todos los payments de las cuotas del contrato > 0
                return $q->whereRaw("(select coalesce(sum(p.due_days),0) from payments p inner join quotas qt on p.quota_id = qt.id where qt.contract_id = contracts.id) > 0");
            })->sum('debt');

        $payments = Payment::active()->when($request->start_date_1, function ($query, $start_date) {
            return $query->whereHas('quota.contract', function ($query) use ($start_date) {
                return $query->whereDate('date', '>=', $start_date);
            });
        })->when($request->end_date_1, function ($query, $end_date) {
            return $query->whereHas('quota.contract', function ($query) use ($end_date) {
                return $query->whereDate('date', '<=', $end_date);
            });
        })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('quota.contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('quota.contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->sum('amount');

        // Load expenses and sum using accessor `amount` (which sums expensePayments)
        $expenses = Expense::when($request->start_date_1, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_1, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->where('seller_id', $seller_id);
            })
            ->active()->with('expensePayments')->get()->sum('amount');

        $today_real = $payments - $expenses;

        // PAGOS DE HOY : todos los pagos de now()
        //Cuotas (mismo criterio que el modal)
        $today_payments_people = Payment::active()
            ->when($request->start_date_1, function ($query, $start_date) {
                return $query->whereDate('quotas.date', '>=', $start_date);
            })
            ->when($request->end_date_1, function ($query, $end_date) {
                return $query->whereDate('quotas.date', '<=', $end_date);
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('quota.contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('quota.contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->with('quota.contract')
            ->get()
            ->groupBy(function ($payment) {
                $quota = $payment->quota;
                $contractId = $quota && $quota->contract ? $quota->contract->id : 'none';
                $quotaNumber = $quota ? $quota->number : 'none';
                return $contractId . '_' . $quotaNumber;
            })
            ->count();

        $section = $request->query('section');

        $paymentsByMethod = Payment::active()
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('date', '<=', $d);
            })
            ->selectRaw('payment_method_id, COALESCE(SUM(amount),0) as total')
            ->groupBy('payment_method_id')
            ->pluck('total', 'payment_method_id');

        // Sum from expense_payments (detailed payments)
        $expensesByMethodPayments = DB::table('expenses_payments as ep')
            ->join('expenses', 'ep.expenses_id', '=', 'expenses.id')
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('expenses.date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('expenses.date', '<=', $d);
            })
            ->where('expenses.deleted', 0)
            ->selectRaw('ep.payment_method_id, COALESCE(SUM(ep.amount),0) as total')
            ->groupBy('ep.payment_method_id')
            ->pluck('total', 'payment_method_id');

        // Sum from expenses.amounts (the main amount stored on the expense record)
        $expensesByMethodAmounts = DB::table('expenses')
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('date', '<=', $d);
            })
            ->where('deleted', 0)
            ->selectRaw('payment_method_id, COALESCE(SUM(amounts),0) as total')
            ->groupBy('payment_method_id')
            ->pluck('total', 'payment_method_id');

        // Merge both sources: for each payment method, sum amounts + payments
        $expensesByMethodArr = [];
        foreach ($expensesByMethodPayments as $pm => $total) {
            $expensesByMethodArr[$pm] = floatval($total);
        }
        foreach ($expensesByMethodAmounts as $pm => $total) {
            $expensesByMethodArr[$pm] = floatval(($expensesByMethodArr[$pm] ?? 0) + $total);
        }

        $expensesByMethod = collect($expensesByMethodArr);

        $transfersFrom = Transfer::active()->where('type', 'payment_method')
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('date', '<=', $d);
            })
            ->selectRaw('from_payment_method_id as pm, COALESCE(SUM(amount),0) as total')
            ->groupBy('from_payment_method_id')
            ->pluck('total', 'pm');

        $transfersTo = Transfer::active()->where('type', 'payment_method')
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('date', '<=', $d);
            })
            ->selectRaw('to_payment_method_id as pm, COALESCE(SUM(amount),0) as total')
            ->groupBy('to_payment_method_id')
            ->pluck('total', 'pm');

        // obtener todos los métodos y calcular acumulado dinámicamente
        $payment_methods = PaymentMethod::active()->get()->map(function ($pm) use ($paymentsByMethod, $expensesByMethod, $transfersFrom, $transfersTo) {
            $id = $pm->id;
            $payments = floatval($paymentsByMethod[$id] ?? 0);
            $expenses = floatval($expensesByMethod[$id] ?? 0);
            $from = floatval($transfersFrom[$id] ?? 0);
            $to = floatval($transfersTo[$id] ?? 0);

            // Acumulado original
            $pm->acumulado = $payments - $expenses - $from + $to;

            return $pm;
        })->values();

        return view('index', compact(
            'today_payments',
            'today_projected',
            'today_real',
            'active_clients',
            'due_clients',
            'home_sales_1',
            'payment_methods',
            'due_total',
            'wallet_total',
            'requested_amount',
            'expenses',
            'sales_totals_1',
            'expenses_totals_1',
            'sales_totals_2',
            'expenses_totals_2',
            'sellers',
            'seller_wallet',
            'today_timely_payments',
            'due_quotas',
            'section',
            'admincredits'
        ));
    }

    public function apiReniec(Request $request)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('apireniec.token')
        ])->get(config('apireniec.url'), [
            'numero' => $request->dni
        ]);

        $data = $response->json();

        if ($response->successful()) {

            return response()->json([
                'status' => true,
                'name' => $data['nombres'] . ' ' . $data['apellidoPaterno'] . ' ' . $data['apellidoMaterno']
            ]);
        } else {

            return response()->json([
                'status' => false
            ]);
        }
    }

    public function indicadores(Request $request)
    {
        $user = auth()->user();


        $home_sales_1 = Payment::active()->where('payment_method_id', 1)->when($request->start_date_4, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->when($request->seller_id_1, function ($query, $seller_id) {
            return $query->whereHas('quota.contract', function ($query) use ($seller_id) {
                return $query->where('seller_id', $seller_id);
            });
        })->sum('amount');

        // Sum only expensePayments with payment_method_id = 1
        $home_expenses_1 = Expense::active()->whereHas('expensePayments', function ($q) {
            return $q->where('payment_method_id', 1);
        })->when($request->start_date_4, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->when($request->seller_id_1, function ($query, $seller_id) {
            return $query->where('seller_id', $seller_id);
        })->with(['expensePayments' => function ($q) {
            $q->where('payment_method_id', 1);
        }])->get()
            ->sum(function ($expense) {
                return $expense->expensePayments->sum('amount');
            });

        $home_transfers_1_from = Transfer::active()->when($request->seller_id_1, function ($query, $seller_id) {
            return $query->where('from_seller_id', $seller_id);
        })->when($request->start_date_4, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->where('type', 'seller')->sum('amount');

        $home_transfers_1_to = Transfer::active()->when($request->seller_id_1, function ($query, $seller_id) {
            return $query->where('to_seller_id', $seller_id);
        })->when($request->start_date_4, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->where('type', 'seller')->sum('amount');

        $home_sales_1 = $home_sales_1 - $home_expenses_1 - $home_transfers_1_from + $home_transfers_1_to;

        if ($user->hasRole('credit_manager')) {
            $sellers = User::seller()->active()
                ->where('credit_manager_id', $user->id)
                ->when($request->seller_id_2, function ($q, $seller_id) {
                    return $q->where('id', $seller_id);
                })->get();
        } elseif ($user->hasRole('admin_credit')) {
            $sellers = User::seller()->active()
                ->when($request->credit_manager_id, function ($q, $cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                })->when($request->seller_id_2, function ($q, $seller_id) {
                    return $q->where('id', $seller_id);
                })->get();
        } else {
            $sellers = User::seller()->active()->get();
        }

        // Sum from expenses.amounts (the main amount stored on the expense record)
        $expensesByMethodAmounts = DB::table('expenses')
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('date', '<=', $d);
            })
            ->where('deleted', 0)
            ->selectRaw('payment_method_id, COALESCE(SUM(amounts),0) as total')
            ->groupBy('payment_method_id')
            ->pluck('total', 'payment_method_id');

        // Sum from expense_payments (detailed payments)
        $expensesByMethodPayments = DB::table('expenses_payments as ep')
            ->join('expenses', 'ep.expenses_id', '=', 'expenses.id')
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('expenses.date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('expenses.date', '<=', $d);
            })
            ->where('expenses.deleted', 0)
            ->selectRaw('ep.payment_method_id, COALESCE(SUM(ep.amount),0) as total')
            ->groupBy('ep.payment_method_id')
            ->pluck('total', 'payment_method_id');

        // Merge both sources: for each payment method, sum amounts + payments
        $expensesByMethodArr = [];
        foreach ($expensesByMethodPayments as $pm => $total) {
            $expensesByMethodArr[$pm] = floatval($total);
        }
        foreach ($expensesByMethodAmounts as $pm => $total) {
            $expensesByMethodArr[$pm] = floatval(($expensesByMethodArr[$pm] ?? 0) + $total);
        }

        $expensesByMethod = collect($expensesByMethodArr);

        $paymentsByMethod = Payment::active()
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('date', '<=', $d);
            })
            ->selectRaw('payment_method_id, COALESCE(SUM(amount),0) as total')
            ->groupBy('payment_method_id')
            ->pluck('total', 'payment_method_id');

        $transfersFrom = Transfer::active()->where('type', 'payment_method')
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('date', '<=', $d);
            })
            ->selectRaw('from_payment_method_id as pm, COALESCE(SUM(amount),0) as total')
            ->groupBy('from_payment_method_id')
            ->pluck('total', 'pm');


        $transfersTo = Transfer::active()->where('type', 'payment_method')
            ->when($request->start_date_3, function ($q, $d) {
                return $q->whereDate('date', '>=', $d);
            })
            ->when($request->end_date_3, function ($q, $d) {
                return $q->whereDate('date', '<=', $d);
            })
            ->selectRaw('to_payment_method_id as pm, COALESCE(SUM(amount),0) as total')
            ->groupBy('to_payment_method_id')
            ->pluck('total', 'pm');

        // obtener todos los métodos y calcular acumulado dinámicamente
        $payment_methods = PaymentMethod::active()->get()->map(function ($pm) use ($paymentsByMethod, $expensesByMethod, $transfersFrom, $transfersTo) {
            $id = $pm->id;
            $payments = floatval($paymentsByMethod[$id] ?? 0);
            $expenses = floatval($expensesByMethod[$id] ?? 0);
            $from = floatval($transfersFrom[$id] ?? 0);
            $to = floatval($transfersTo[$id] ?? 0);

            // Acumulado original
            $pm->acumulado = $payments - $expenses - $from + $to;

            return $pm;
        })->values();
        return view('dashboard.indicadores', compact('sellers', 'payment_methods', 'home_sales_1'));
    }

    public function rentabilidad(Request $request)
    {
        $user = auth()->user();
        $admincredits = User::where('role', 'credit_manager')->active()->get();
        // Obtener TODOS los sellers para el select, el filtrado se hace en el frontend
        $sellers = User::seller()->active()->get();

        $wallet_total = Quota::active()
            ->when($request->start_date_1, function ($query, $start_date) {
                return $query->whereHas('contract', function ($q) use ($start_date) {
                    return $q->whereDate('date', '>=', $start_date);
                });
            })
            ->when($request->end_date_1, function ($query, $end_date) {
                return $query->whereHas('contract', function ($q) use ($end_date) {
                    return $q->whereDate('date', '<=', $end_date);
                });
            })
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })->when($request->credit_manager_id, function ($query, $cm_id) {})

            ->where('paid', 0)
            ->sum('debt');

        $due_total = Quota::when($request->start_date_1, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_1, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->where('paid', 0)
            ->whereHas('contract', function ($q) { // suma de due_days de todos los payments de las cuotas del contrato > 0
                return $q->whereRaw("(select coalesce(sum(p.due_days),0) from payments p inner join quotas qt on p.quota_id = qt.id where qt.contract_id = contracts.id) > 0");
            })->sum('debt');

        // Precargar cache de cuotas grupales para evitar N+1 queries
        // Obtener todos los contratos grupales que podrían estar involucrados
        $groupContractsQuery = Contract::where('client_type', 'Grupo')
            ->where('deleted', 0)
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->where('seller_id', $seller_id);
            });

        $groupContractIds = $groupContractsQuery->pluck('id')->toArray();

        // Precargar todas las cuotas de contratos grupales y crear cache
        $groupQuotasCache = collect();
        $groupQuotaCountCache = collect(); // total de miembros por grupo+cuota
        if (!empty($groupContractIds)) {
            $groupQuotasRaw = Quota::whereIn('contract_id', $groupContractIds)
                ->with('contract')
                ->get()
                ->groupBy(function ($quota) {
                    $groupName = $quota->contract ? $quota->contract->group_name : $quota->contract_id;
                    return ($groupName ?: $quota->contract_id) . '_' . ($quota->number ?? 'none');
                });
            $groupQuotasCache = $groupQuotasRaw->map(function ($quotas) {
                return $quotas->every(function ($q) {
                    return $q->paid == 1;
                });
            });
            $groupQuotaCountCache = $groupQuotasRaw->map(fn($quotas) => $quotas->count());
        }

        // Clave para agrupar por documento (Personal) o group_name (Grupo) + cuota - evita duplicados
        $peopleGroupKey = function ($payment) {
            $quota = $payment->quota;
            $contract = $quota ? $quota->contract : null;
            $clientKey = $contract && $contract->client_type === 'Personal'
                ? ($contract->document ?: $contract->name ?? '')
                : ($contract->group_name ?: $contract->name ?? '');
            return ($clientKey ?: 'none') . '|' . ($quota->number ?? 'none');
        };

        // PAGOS : todos los pagos filtrados por fecha
        //Personas por documento (sin duplicar documentos ni grupos)
        $today_payments_people = Payment::active()
            ->when($request->start_date_1, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date_1, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })
            ->whereHas('quota', function ($q) {
                return $q->where('paid', 1);
            })->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('quota.contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('quota.contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('quota.contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->with('quota.contract')
            ->get()
            ->groupBy($peopleGroupKey)
            ->count();
        //Monto en soles
        $today_payments = Payment::active()
            ->when($request->start_date_1, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date_1, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })
            ->whereHas('quota', function ($q) {
                return $q->where('paid', 1);
            })->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('quota.contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('quota.contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('quota.contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->sum('amount');

        // Pagos adelantados / puntuales (criterio por fecha de cuota)
        $advanceTimelyPayments = Payment::active()
            ->when($request->start_date_1, function ($query, $start_date) {
                return $query->whereHas('quota', function ($q) use ($start_date) {
                    return $q->whereDate('date', '>=', $start_date);
                });
            })
            ->when($request->end_date_1, function ($query, $end_date) {
                return $query->whereHas('quota', function ($q) use ($end_date) {
                    return $q->whereDate('date', '<=', $end_date);
                });
            })
            ->whereHas('quota', function ($q) {
                return $q->where('paid', 1);
            })->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('quota.contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('quota.contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('quota.contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->with('quota.contract')
            ->get();

        $personalQuotaCompletionDates = $advanceTimelyPayments
            ->filter(function ($payment) use ($groupContractIds) {
                $quota = $payment->quota;
                return $quota && !in_array($quota->contract_id ?? null, $groupContractIds, true);
            })
            ->groupBy('quota_id')
            ->map(function ($payments) {
                $completionDate = $payments->max('date');
                return $completionDate ? \Carbon\Carbon::parse($completionDate) : null;
            });

        // Para grupos grupales: 
        // 1. Si todos pagan el día exacto -> Puntual
        // 2. Si mínimo una persona paga después (tarde) -> No aparece
        // 3. Si mínimo una persona paga antes (y nadie tarde) -> Aparece como pago puntual realizado
        // Los grupos NUNCA van a Adelantado, solo a Puntual o no aparecen.

        // Claves de grupo+cuota donde AL MENOS UN miembro pagó TARDE (solo cuotas dentro del rango filtrado)
        $lateGroupKeys = collect();
        if (!empty($groupContractIds)) {
            $lateGroupKeys = Payment::active()
                ->join('quotas', 'payments.quota_id', '=', 'quotas.id')
                ->whereIn('quotas.contract_id', $groupContractIds)
                ->whereRaw('DATE(payments.date) > DATE(quotas.date)')
                ->when($request->start_date_1, fn($q, $d) => $q->whereDate('quotas.date', '>=', $d))
                ->when($request->end_date_1, fn($q, $d) => $q->whereDate('quotas.date', '<=', $d))
                ->select('quotas.contract_id', 'quotas.number')
                ->get()
                ->map(fn($row) => ($row->contract_id ?? 'none') . '_' . ($row->number ?? 'none'))
                ->unique();
        }

        // Pagos ADELANTADOS para contratos personales
        $advancePayments = $advanceTimelyPayments->filter(function ($payment) use ($groupContractIds, $personalQuotaCompletionDates) {
            $quota = $payment->quota;
            if (!$quota || !$payment->date || !$quota->date) {
                return false;
            }
            $contractId = $quota->contract_id ?? null;

            // Los pagos de grupos nunca van a adelantado (van a puntual)
            if (in_array($contractId, $groupContractIds, true)) {
                return false;
            }

            $completionDate = $personalQuotaCompletionDates->get($payment->quota_id);

            return $completionDate && !$completionDate->isSameDay($quota->date) && $completionDate->lt($quota->date);
        });

        // Pagos PUNTUALES
        $timelyPayments = $advanceTimelyPayments->filter(function ($payment) use ($request, $groupContractIds, $lateGroupKeys, $personalQuotaCompletionDates) {
            $quota = $payment->quota;
            if (!$quota || !$payment->date || !$quota->date) {
                return false;
            }
            $contractId = $quota->contract_id ?? null;
            $key = ($contractId ?? 'none') . '_' . ($quota->number ?? 'none');

            if (in_array($contractId, $groupContractIds, true)) {
                // Para grupos: no aparece si alguien pagó tarde
                if ($lateGroupKeys->contains($key)) {
                    return false;
                }
                // Filtro para grupos en puntual (siempre aplica a la fecha de la cuota)
                if ($request->start_date_1 && $quota->date->lt(\Carbon\Carbon::parse($request->start_date_1)->startOfDay())) {
                    return false;
                }
                if ($request->end_date_1 && $quota->date->gt(\Carbon\Carbon::parse($request->end_date_1)->endOfDay())) {
                    return false;
                }
            } else {
                // Contratos personales: clasificar por la fecha en que la cuota quedo completa.
                // Si hubo un abono adelantado y el saldo se completo el dia de la cuota,
                // todos los pagos de esa cuota cuentan como puntual.
                $completionDate = $personalQuotaCompletionDates->get($payment->quota_id);
                if (!$completionDate || !$completionDate->isSameDay($quota->date)) {
                    return false;
                }
            }
            return true;
        });

        // Personal: solo contar cuando la cuota está pagada (paid=1). Grupo: cuando TODOS pagaron esa cuota (del 100%)
        $onlyCompleteGroupPayments = function ($payment) use ($groupQuotasCache, $groupContractIds) {
            $quota = $payment->quota;
            $contractId = $quota->contract_id ?? null;
            if ($contractId === null) {
                return false;
            }
            if (!in_array($contractId, $groupContractIds, true)) {
                return $quota->paid == 1;
            }
            $contract = $quota->contract;
            $groupName = $contract ? $contract->group_name : null;
            $key = ($groupName ?: $contractId) . '_' . ($quota->number ?? 'none');
            return $groupQuotasCache->get($key, false);
        };

        // Personal: solo contar cuando la cuota está pagada (paid=1). Grupo: cuando TODOS pagaron esa cuota (del 100%)
        $advanceTimelyContractIds = $advanceTimelyPayments
            ->map(fn($payment) => optional($payment->quota)->contract_id)
            ->filter()
            ->unique()
            ->values();

        $advanceTimelyQuotaAmounts = Quota::whereIn('contract_id', $advanceTimelyContractIds)
            ->select('contract_id', 'number', DB::raw('SUM(amount) as total'))
            ->groupBy('contract_id', 'number')
            ->get()
            ->keyBy(fn($quota) => $quota->contract_id . '_' . $quota->number);

        $paymentGroupCoversQuota = function ($paymentsGroup) use ($advanceTimelyQuotaAmounts) {
            $first = $paymentsGroup->first();
            $quota = $first ? $first->quota : null;
            if (!$quota) {
                return false;
            }

            $quotaTotal = (float) optional($advanceTimelyQuotaAmounts->get($quota->contract_id . '_' . $quota->number))->total;
            if ($quotaTotal <= 0) {
                return false;
            }

            return (float) $paymentsGroup->sum('amount') + 0.01 >= $quotaTotal;
        };

        $completedAdvanceTimelyGroups = $advanceTimelyPayments
            ->groupBy($peopleGroupKey)
            ->filter(function ($paymentsGroup) use ($onlyCompleteGroupPayments, $paymentGroupCoversQuota) {
                $first = $paymentsGroup->first();
                return $first
                    && $paymentsGroup->every($onlyCompleteGroupPayments)
                    && $paymentGroupCoversQuota($paymentsGroup);
            });

        $isAdvancePaymentGroup = function ($paymentsGroup) {
            $first = $paymentsGroup->first();
            $quota = $first ? $first->quota : null;
            $completionDate = $paymentsGroup->max('date');
            if (!$quota || !$quota->date || !$completionDate) {
                return false;
            }

            return \Carbon\Carbon::parse($completionDate)->lt($quota->date);
        };

        $isTimelyPaymentGroup = function ($paymentsGroup) {
            $first = $paymentsGroup->first();
            $quota = $first ? $first->quota : null;
            $completionDate = $paymentsGroup->max('date');
            if (!$quota || !$quota->date || !$completionDate) {
                return false;
            }

            return \Carbon\Carbon::parse($completionDate)->format('Y-m-d') === $quota->date->format('Y-m-d');
        };

        $today_advance_payments_people = $completedAdvanceTimelyGroups
            ->filter($isAdvancePaymentGroup)
            ->count();
        $today_advance_payments = $completedAdvanceTimelyGroups
            ->filter($isAdvancePaymentGroup)
            ->sum(fn($paymentsGroup) => $paymentsGroup->sum('amount'));

        $today_timely_payments_people = $timelyPayments
            ->groupBy($peopleGroupKey)
            ->filter(function ($paymentsGroup) use ($onlyCompleteGroupPayments, $groupContractIds, $personalQuotaCompletionDates) {
                $first = $paymentsGroup->sortBy('id')->first();
                if (!$first || !$first->date || !$first->quota || !$first->quota->date) {
                    return false;
                }
                if (!$onlyCompleteGroupPayments($first)) {
                    return false;
                }
                $contractId = $first->quota->contract_id ?? null;
                // Para grupos: ya validamos que todo el grupo pagó en o antes de la cuota;
                // basta con que la cuota esté pagada (paid=1 via onlyCompleteGroupPayments).
                if (in_array($contractId, $groupContractIds, true)) {
                    return true;
                }
                $completionDate = $personalQuotaCompletionDates->get($first->quota_id);
                return $completionDate && $completionDate->isSameDay($first->quota->date);
            })
            ->count();
        $today_timely_payments = $timelyPayments->filter(function ($payment) use ($onlyCompleteGroupPayments, $groupContractIds, $personalQuotaCompletionDates) {
            if (!$onlyCompleteGroupPayments($payment)) {
                return false;
            }
            if (!$payment->date || !$payment->quota || !$payment->quota->date) {
                return false;
            }
            $contractId = $payment->quota->contract_id ?? null;
            // Para grupos: ya validados como puntuales; incluir todos los pagos del grupo (incluso los adelantados)
            if (in_array($contractId, $groupContractIds, true)) {
                return true;
            }
            $completionDate = $personalQuotaCompletionDates->get($payment->quota_id);
            return $completionDate && $completionDate->isSameDay($payment->quota->date);
        })->sum('amount');

        //PROYECTADO PARA HOY : todo lo que está en el rango de fechas (pagado y no pagado)

        //Personas por documento (agrupado por cliente + Número de cuota)
        $today_projected_people = Quota::query()
            ->when($request->start_date_1, function ($query, $start_date) {
                return $query->whereDate('quotas.date', '>=', $start_date);
            })
            ->when($request->end_date_1, function ($query, $end_date) {
                return $query->whereDate('quotas.date', '<=', $end_date);
            })
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->join('contracts', 'contracts.id', '=', 'quotas.contract_id')
            ->where('contracts.deleted', 0)
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(CASE WHEN contracts.client_type = 'Personal' THEN COALESCE(contracts.document, contracts.name) ELSE COALESCE(contracts.group_name, contracts.name) END,''),'|',COALESCE(quotas.number,''))) as total")
            ->value('total');
        //Monto en soles
        $today_projected = Quota::active()
            ->when($request->start_date_1, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date_1, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $cm_id) {
                return $query->whereHas('contract.seller', function ($q) use ($cm_id) {
                    return $q->where('credit_manager_id', $cm_id);
                });
            })
            ->when($request->seller_id_2, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->sum('amount');

        $advance_people = (int) ($today_advance_payments_people ?? 0);
        $timely_people = (int) ($today_timely_payments_people ?? 0);
        $projected_people = (int) ($today_projected_people ?? 0);
        $today_punctual_percent = $projected_people > 0
            ? min(100, round((($advance_people + $timely_people) / $projected_people) * 100, 2))
            : 0;

        return view('dashboard.rentabilidad', compact(
            'admincredits',
            'wallet_total',
            'sellers',
            'due_total',
            'today_payments_people',
            'today_payments',
            'today_timely_payments_people',
            'today_timely_payments',
            'today_projected_people',
            'today_projected',
            'today_advance_payments',
            'today_advance_payments_people',
            'today_punctual_percent'
        ));
    }


    public function productividad(Request $request)
    {

        $user = auth()->user();
        $admincredits = User::where('role', 'credit_manager')->active()->get();
        $sellers = User::seller()->active()->get();
        // TOTAL DE CLIENTES (únicos por document|group_name) respetando mismos filtros
        $total_clients_count = DB::table('contracts')
            ->leftJoin('users', 'contracts.seller_id', '=', 'users.id')
            ->when($user->hasRole('seller'), function ($q) {
                return $q->where('contracts.seller_id', auth()->user()->id);
            })
            ->when($request->credit_manager_id, function ($q, $cm_id) {
                return $q->where('users.credit_manager_id', $cm_id);
            })
            ->when($request->seller_id_2, function ($q, $seller_id) {
                return $q->where('contracts.seller_id', $seller_id);
            })
            ->when($request->start_date_2, function ($q, $start_date) {
                return $q->whereDate('contracts.date', '>=', $start_date);
            })
            ->when($request->end_date_2, function ($q, $end_date) {
                return $q->whereDate('contracts.date', '<=', $end_date);
            })
            ->where('contracts.deleted', 0)
            ->where('contracts.paid', 0)
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(contracts.document,''),'|',COALESCE(contracts.group_name,''))) as total")
            ->value('total');

        $cutoff = now()->subDays(120)->toDateString();


        $due_clients = DB::table('contracts')
            ->join('quotas', 'quotas.contract_id', 'contracts.id')
            ->leftJoin('payments', 'payments.quota_id', 'quotas.id')
            ->leftJoin('users', 'contracts.seller_id', '=', 'users.id')
            ->when($user->hasRole('seller'), function ($q) {
                return $q->where('contracts.seller_id', auth()->user()->id);
            })
            ->when($request->credit_manager_id, function ($q, $cm_id) {
                return $q->where('users.credit_manager_id', $cm_id);
            })
            ->when($request->seller_id_2, function ($q, $seller_id) {
                return $q->where('contracts.seller_id', $seller_id);
            })
            ->when($request->start_date_2, function ($q, $start_date) {
                return $q->whereDate('contracts.date', '>=', $start_date);
            })
            ->when($request->end_date_2, function ($q, $end_date) {
                return $q->whereDate('contracts.date', '<=', $end_date);
            })
            ->where(function ($q) use ($cutoff) {
                $q->where('payments.due_days', '>=', 120)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->where('quotas.paid', 0)
                            ->whereDate('quotas.date', '<=', $cutoff);
                    });
            })
            ->where('contracts.deleted', 0)
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(contracts.document,''),'|',COALESCE(contracts.group_name,''))) as total")
            ->value('total');

        $active_clients = max(0, intval($total_clients_count) - intval($due_clients));


        $seller_wallet = Quota::when($user->hasRole('seller'), function ($query) {
            return $query->whereHas('contract', function ($query) {
                return $query->where('seller_id', auth()->user()->id);
            });
        })->when($request->credit_manager_id, function ($query, $cm_id) {
            return $query->whereHas('contract.seller', function ($q) use ($cm_id) {
                return $q->where('credit_manager_id', $cm_id);
            });
        })->when($request->seller_id_2, function ($query, $seller_id) {
            return $query->whereHas('contract', function ($query) use ($seller_id) {
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->start_date_2, function ($query, $start_date) {
            return $query->whereHas('contract', function ($query) use ($start_date) {
                return $query->whereDate('date', '>=', $start_date);
            });
        })->when($request->end_date_2, function ($query, $end_date) {
            return $query->whereHas('contract', function ($query) use ($end_date) {
                return $query->whereDate('date', '<=', $end_date);
            });
        })->whereHas('contract', function ($query) {
            return $query->where('deleted', 0);
        })->where('paid', 0)->sum('debt');


        $requested_amount = Contract::active()->when($user->hasRole('seller'), function ($query) {
            return $query->where('seller_id', auth()->user()->id);
        })->when($request->credit_manager_id, function ($query, $cm_id) {
            return $query->whereHas('seller', function ($q) use ($cm_id) {
                return $q->where('credit_manager_id', $cm_id);
            });
        })->when($request->seller_id_2, function ($query, $seller_id) {
            return $query->where('seller_id', $seller_id);
        })->when($request->start_date_2, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_2, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->sum('requested_amount');



        $due_quotas = Quota::when($user->hasRole('seller'), function ($query) {
            return $query->whereHas('contract', function ($query) {
                return $query->where('seller_id', auth()->user()->id);
            });
        })->when($request->credit_manager_id, function ($query, $cm_id) {
            return $query->whereHas('contract.seller', function ($q) use ($cm_id) {
                return $q->where('credit_manager_id', $cm_id);
            });
        })->when($request->seller_id_2, function ($query, $seller_id) {
            return $query->whereHas('contract', function ($query) use ($seller_id) {
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->start_date_2, function ($query, $start_date) {
            return $query->whereHas('contract', function ($query) use ($start_date) {
                return $query->whereDate('date', '>=', $start_date);
            });
        })->when($request->end_date_2, function ($query, $end_date) {
            return $query->whereHas('contract', function ($query) use ($end_date) {
                return $query->whereDate('date', '<=', $end_date);
            });
        })->whereHas('contract', function ($query) {
            return $query->where('deleted', 0);
        })->where('paid', 0)
            ->count();

        return view('dashboard.productividad', compact('admincredits', 'sellers', 'active_clients', 'due_clients', 'total_clients_count', 'seller_wallet', 'requested_amount', 'due_quotas'));
    }

    public function rentabilidadCardDetails(Request $request)
    {
        $user = auth()->user();
        $card = $request->card;
        $allowedCards = ['advance', 'today', 'timely', 'projected'];

        if (!in_array($card, $allowedCards, true)) {
            return response()->json([
                'status' => false,
                'error' => 'Tipo de tarjeta inválido'
            ], 422);
        }

        $startDate = $request->start_date_1;
        $endDate = $request->end_date_1;
        $creditManagerId = $request->credit_manager_id;
        $sellerId = $request->seller_id_2;

        if ($card === 'projected') {
            $quotas = Quota::active()
                ->when($startDate, function ($query) use ($startDate) {
                    return $query->whereDate('date', '>=', $startDate);
                })
                ->when($endDate, function ($query) use ($endDate) {
                    return $query->whereDate('date', '<=', $endDate);
                })
                ->when($user->hasRole('seller'), function ($query) use ($user) {
                    return $query->whereHas('contract', function ($q) use ($user) {
                        return $q->where('seller_id', $user->id);
                    });
                })
                ->when($creditManagerId, function ($query) use ($creditManagerId) {
                    return $query->whereHas('contract.seller', function ($q) use ($creditManagerId) {
                        return $q->where('credit_manager_id', $creditManagerId);
                    });
                })
                ->when($sellerId, function ($query) use ($sellerId) {
                    return $query->whereHas('contract', function ($q) use ($sellerId) {
                        return $q->where('seller_id', $sellerId);
                    });
                })
                ->with(['contract', 'payments' => function ($query) {
                    $query->select('id', 'quota_id', 'date');
                }])
                ->orderBy('date', 'DESC')
                ->orderBy('id', 'DESC')
                ->get();

            // Mismo cálculo que las tarjetas: peopleGroupKey = 99 en card = 99 filas
            $items = $quotas
                ->groupBy(function ($quota) {
                    $contract = $quota->contract;
                    $clientKey = $contract && $contract->client_type === 'Personal'
                        ? ($contract->document ?: $contract->name ?? '')
                        : ($contract->group_name ?: $contract->name ?? '');
                    return ($clientKey ?: 'none') . '|' . ($quota->number ?? 'none');
                })
                ->map(function ($group) {
                    $first = $group->first();
                    $contract = $first->contract;
                    $allPaid = $group->every(function ($q) {
                        return $q->paid == 1;
                    });
                    $paymentDate = $group
                        ->flatMap(function ($q) {
                            return $q->payments;
                        })
                        ->max('date');

                    $clientLabel = $contract ? $contract->client() : 'N/A';
                    if ($contract && $contract->client_type === 'Grupo') {
                        $personNames = $group->map(fn($q) => $q->person_name)->unique()->filter()->values();
                        if ($personNames->isNotEmpty()) {
                            $clientLabel = $clientLabel . ' (' . $personNames->implode(', ') . ')';
                        }
                    }

                    return [
                        'client' => $clientLabel,
                        'contract_date' => $contract && $contract->date ? $contract->date->format('d/m/Y') : null,
                        'quota_number' => $first->number,
                        'person_name' => $first->person_name,
                        'amount' => $group->sum('amount'),
                        'debt' => $group->sum('debt'),
                        'due_date' => $first->date ? $first->date->format('d/m/Y') : null,
                        'payment_date' => $paymentDate ? \Carbon\Carbon::parse($paymentDate)->format('d/m/Y') : null,
                        'paid' => $allPaid,
                    ];
                })
                ->values();

            return response()->json([
                'status' => true,
                'type' => 'quotas',
                'total' => $items->count(),
                'items' => $items,
            ]);
        }

        if (in_array($card, ['advance', 'timely'], true)) {
            $paymentsBase = Payment::active()
                ->when($startDate, function ($query) use ($startDate) {
                    return $query->whereHas('quota', function ($q) use ($startDate) {
                        return $q->whereDate('date', '>=', $startDate);
                    });
                })
                ->when($endDate, function ($query) use ($endDate) {
                    return $query->whereHas('quota', function ($q) use ($endDate) {
                        return $q->whereDate('date', '<=', $endDate);
                    });
                })
                ->whereHas('quota', function ($q) {
                    return $q->where('paid', 1);
                })->when($user->hasRole('seller'), function ($query) use ($user) {
                    return $query->whereHas('quota.contract', function ($q) use ($user) {
                        return $q->where('seller_id', $user->id);
                    });
                })
                ->when($creditManagerId, function ($query) use ($creditManagerId) {
                    return $query->whereHas('quota.contract.seller', function ($q) use ($creditManagerId) {
                        return $q->where('credit_manager_id', $creditManagerId);
                    });
                })
                ->when($sellerId, function ($query) use ($sellerId) {
                    return $query->whereHas('quota.contract', function ($q) use ($sellerId) {
                        return $q->where('seller_id', $sellerId);
                    });
                })
                ->with(['quota.contract', 'payment_method'])
                ->orderBy('date', 'DESC')
                ->orderBy('id', 'DESC')
                ->get();

            // Contratos grupales (mismo filtro que las tarjetas)
            $groupContractIdsCard = Contract::where('client_type', 'Grupo')
                ->where('deleted', 0)
                ->when($user->hasRole('seller'), function ($query) use ($user) {
                    return $query->where('seller_id', $user->id);
                })
                ->when($creditManagerId, function ($query) use ($creditManagerId) {
                    return $query->whereHas('seller', function ($q) use ($creditManagerId) {
                        return $q->where('credit_manager_id', $creditManagerId);
                    });
                })
                ->when($sellerId, function ($query) use ($sellerId) {
                    return $query->where('seller_id', $sellerId);
                })
                ->pluck('id')
                ->toArray();

            $personalQuotaCompletionDatesCard = $paymentsBase
                ->filter(function ($payment) use ($groupContractIdsCard) {
                    $quota = $payment->quota;
                    return $quota && !in_array($quota->contract_id ?? null, $groupContractIdsCard, true);
                })
                ->groupBy('quota_id')
                ->map(function ($payments) {
                    $completionDate = $payments->max('date');
                    return $completionDate ? \Carbon\Carbon::parse($completionDate) : null;
                });

            // Cuotas grupales: valida que el 100% de miembros pagó (every paid=1)
            $groupQuotasCacheCard = collect();
            if (!empty($groupContractIdsCard)) {
                $groupQuotasRawCard = Quota::whereIn('contract_id', $groupContractIdsCard)
                    ->with('contract')
                    ->get()
                    ->groupBy(function ($quota) {
                        $groupName = $quota->contract ? $quota->contract->group_name : $quota->contract_id;
                        return ($groupName ?: $quota->contract_id) . '_' . ($quota->number ?? 'none');
                    });
                $groupQuotasCacheCard = $groupQuotasRawCard->map(function ($quotas) {
                    return $quotas->every(function ($q) {
                        return $q->paid == 1;
                    });
                });
            }

            // CASO 2: grupos donde al menos un miembro pagó TARDE (dentro del rango filtrado)
            $lateGroupKeysCard = collect();
            if (!empty($groupContractIdsCard)) {
                $lateGroupKeysCard = Payment::active()
                    ->join('quotas', 'payments.quota_id', '=', 'quotas.id')
                    ->whereIn('quotas.contract_id', $groupContractIdsCard)
                    ->whereRaw('DATE(payments.date) > DATE(quotas.date)')
                    ->when($startDate, fn($q, $d) => $q->whereDate('quotas.date', '>=', $d))
                    ->when($endDate, fn($q, $d) => $q->whereDate('quotas.date', '<=', $d))
                    ->select('quotas.contract_id', 'quotas.number')
                    ->get()
                    ->map(fn($row) => ($row->contract_id ?? 'none') . '_' . ($row->number ?? 'none'))
                    ->unique();
            }

            // Personal: cuota paid=1. Grupo: TODOS pagaron (every paid=1)
            $onlyCompleteGroupPaymentsCard = function ($payment) use ($groupQuotasCacheCard, $groupContractIdsCard) {
                $quota = $payment->quota;
                $contractId = $quota->contract_id ?? null;
                if ($contractId === null) {
                    return false;
                }
                if (!in_array($contractId, $groupContractIdsCard, true)) {
                    return $quota->paid == 1;
                }
                $contract = $quota->contract;
                $groupName = $contract ? $contract->group_name : null;
                $key = ($groupName ?: $contractId) . '_' . ($quota->number ?? 'none');
                return $groupQuotasCacheCard->get($key, false);
            };

            if ($card === 'advance') {
                // ADELANTADO: solo personales que pagaron ANTES. Grupos NUNCA van a adelantado.
                $payments = $paymentsBase->filter(function ($payment) use ($groupContractIdsCard, $personalQuotaCompletionDatesCard) {
                    $quota = $payment->quota;
                    if (!$quota || !$payment->date || !$quota->date) {
                        return false;
                    }
                    if (in_array($quota->contract_id ?? null, $groupContractIdsCard, true)) {
                        return false;
                    }
                    $completionDate = $personalQuotaCompletionDatesCard->get($payment->quota_id);
                    return $completionDate && !$completionDate->isSameDay($quota->date) && $completionDate->lt($quota->date);
                })->filter($onlyCompleteGroupPaymentsCard)->values();
            } else {
                // PUNTUAL (timely) - los 3 casos:
                // 1. Todos pagan el dia exacto -> Puntual
                // 2. Minimo uno paga tarde -> No aparece
                // 3. Minimo uno paga antes (y nadie tarde) -> Puntual
                $payments = $paymentsBase->filter(function ($payment) use ($startDate, $endDate, $groupContractIdsCard, $lateGroupKeysCard, $personalQuotaCompletionDatesCard) {
                    $quota = $payment->quota;
                    if (!$quota || !$payment->date || !$quota->date) {
                        return false;
                    }
                    $contractId = $quota->contract_id ?? null;
                    $key = ($contractId ?? 'none') . '_' . ($quota->number ?? 'none');

                    if (in_array($contractId, $groupContractIdsCard, true)) {
                        // Caso 2: si alguien pago tarde -> excluir
                        if ($lateGroupKeysCard->contains($key)) {
                            return false;
                        }
                        // Casos 1 y 3: nadie pago tarde -> puntual (rango por fecha de CUOTA)
                        if ($startDate && $quota->date->lt(\Carbon\Carbon::parse($startDate)->startOfDay())) {
                            return false;
                        }
                        if ($endDate && $quota->date->gt(\Carbon\Carbon::parse($endDate)->endOfDay())) {
                            return false;
                        }
                    } else {
                        // Personal: clasificar por la fecha en que la cuota quedo completa.
                        // Los abonos previos se muestran junto al pago puntual si el saldo se completo en fecha.
                        $completionDate = $personalQuotaCompletionDatesCard->get($payment->quota_id);
                        if (!$completionDate || !$completionDate->isSameDay($quota->date)) {
                            return false;
                        }
                    }
                    return true;
                })->filter($onlyCompleteGroupPaymentsCard)->values();
            }
        } else {
            $payments = Payment::active()
                ->when($startDate, function ($query) use ($startDate) {
                    return $query->whereDate('date', '>=', $startDate);
                })
                ->when($endDate, function ($query) use ($endDate) {
                    return $query->whereDate('date', '<=', $endDate);
                })
                ->whereHas('quota', function ($q) {
                    return $q->where('paid', 1);
                })->when($user->hasRole('seller'), function ($query) use ($user) {
                    return $query->whereHas('quota.contract', function ($q) use ($user) {
                        return $q->where('seller_id', $user->id);
                    });
                })
                ->when($creditManagerId, function ($query) use ($creditManagerId) {
                    return $query->whereHas('quota.contract.seller', function ($q) use ($creditManagerId) {
                        return $q->where('credit_manager_id', $creditManagerId);
                    });
                })
                ->when($sellerId, function ($query) use ($sellerId) {
                    return $query->whereHas('quota.contract', function ($q) use ($sellerId) {
                        return $q->where('seller_id', $sellerId);
                    });
                })
                ->with(['quota.contract', 'payment_method'])
                ->orderBy('date', 'DESC')
                ->orderBy('id', 'DESC')
                ->get();
        }

        // Mismo cálculo que las tarjetas: peopleGroupKey (documento/group_name + cuota) = total card = total filas
        $peopleGroupKeyCard = function ($payment) {
            $quota = $payment->quota;
            $contract = $quota ? $quota->contract : null;
            $clientKey = $contract && $contract->client_type === 'Personal'
                ? ($contract->document ?: $contract->name ?? '')
                : ($contract->group_name ?: $contract->name ?? '');
            return ($clientKey ?: 'none') . '|' . ($quota->number ?? 'none');
        };


        $grouped = $payments->groupBy($peopleGroupKeyCard);

        $items = $grouped
            ->map(function ($group) use ($quotaAmounts) {
                $first = $group->first();
                $quota = $first->quota;
                $contract = $quota ? $quota->contract : null;

                $methods = $group->map(function ($p) {
                    $name = optional($p->payment_method)->name ?? 'N/A';
                    return $name === 'Efectivo' ? 'Retanqueo' : $name;
                })->unique()->values()->toArray();

                $paymentDate = $group->max('date');
                $dueDays = $group->sortByDesc('date')->first()->due_days ?? null;

                $clientLabel = $contract ? $contract->client() : 'N/A';
                if ($contract && $contract->client_type === 'Grupo') {
                    $personNames = $group->map(fn($p) => $p->quota ? $p->quota->person_name : null)->unique()->filter()->values();
                    if ($personNames->isNotEmpty()) {
                        $clientLabel = $clientLabel . ' (' . $personNames->implode(', ') . ')';
                    }
                }

                return [
                    'client' => $clientLabel,
                    'contract_date' => $contract && $contract->date ? $contract->date->format('d/m/Y') : null,
                    'quota_number' => $quota ? $quota->number : null,
                    'person_name' => $quota ? $quota->person_name : null,
                    'amount' => $group->sum('amount'),
                    'quota_amount' => $quotaAmounts->get(($contract->id ?? 'none') . '_' . ($quota->number ?? 'none'))->total ?? 0,
                    'payment_method' => implode(' / ', $methods),
                    'quota_date' => $quota && $quota->date ? $quota->date->format('d/m/Y') : null,
                    'payment_date' => $paymentDate ? $paymentDate->format('d/m/Y') : null,
                    'due_days' => $dueDays,
                ];
            })
            ->filter(function ($item) use ($card) {
                if (!in_array($card, ['advance', 'timely'], true)) {
                    return true;
                }

                return (float) ($item['amount'] ?? 0) + 0.01 >= (float) ($item['quota_amount'] ?? 0);
            })
            ->values();

        return response()->json([
            'status' => true,
            'type' => 'payments',
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    // Calculos para el analisis de carteras por asesor
    public function carteraAsesor(Request $request)
    {
        $user = auth()->user();
        $admincredits = User::where('role', 'credit_manager')->active()->get();
        $sellers = User::seller()->active()->get();

        // Fecha de referencia: end_date_2 si se filtra, sino hoy
        $referenceDate = $request->end_date_2
            ? \Carbon\Carbon::parse($request->end_date_2)->startOfDay()
            : now()->startOfDay();

        $cutoff = $referenceDate->copy()->subDays(120)->toDateString();

        // Total de clientes activos (con contratos no pagados y no eliminados)
        $total_clients_count = DB::table('contracts')
            ->leftJoin('users', 'contracts.seller_id', '=', 'users.id')
            ->when($user->hasRole('seller'), function ($q) {
                return $q->where('contracts.seller_id', auth()->user()->id);
            })
            ->when($request->credit_manager_id, function ($q, $cm_id) {
                return $q->where('users.credit_manager_id', $cm_id);
            })
            ->when($request->seller_id_2, function ($q, $seller_id) {
                return $q->where('contracts.seller_id', $seller_id);
            })
            ->when($request->end_date_2, function ($q, $end_date) {
                return $q->whereDate('contracts.date', '<=', $end_date);
            })
            ->where('contracts.deleted', 0)
            ->where('contracts.paid', 0)
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(contracts.document,''),'|',COALESCE(contracts.group_name,''))) as total")
            ->value('total');

        // Clientes con mora >= 120 días
        $due_clients = DB::table('contracts')
            ->join('quotas', 'quotas.contract_id', 'contracts.id')
            ->leftJoin('payments', 'payments.quota_id', 'quotas.id')
            ->leftJoin('users', 'contracts.seller_id', '=', 'users.id')
            ->when($user->hasRole('seller'), function ($q) {
                return $q->where('contracts.seller_id', auth()->user()->id);
            })
            ->when($request->credit_manager_id, function ($q, $cm_id) {
                return $q->where('users.credit_manager_id', $cm_id);
            })
            ->when($request->seller_id_2, function ($q, $seller_id) {
                return $q->where('contracts.seller_id', $seller_id);
            })
            ->when($request->end_date_2, function ($q, $end_date) {
                return $q->whereDate('contracts.date', '<=', $end_date);
            })
            ->where(function ($q) use ($cutoff, $referenceDate) {
                $q->where(function ($q2) use ($cutoff, $referenceDate) {
                    // Cuotas no pagadas con más de 120 días respecto a la fecha de referencia
                    $q2->where('quotas.paid', 0)
                        ->whereDate('quotas.date', '<=', $cutoff);
                })->orWhere(function ($q2) use ($referenceDate) {
                    // Pagos registrados con due_days >= 120 y cuya cuota venció antes de la fecha referencia
                    $q2->where('payments.due_days', '>=', 120)
                        ->whereDate('quotas.date', '<=', $referenceDate->toDateString());
                });
            })
            ->where('contracts.deleted', 0)
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(contracts.document,''),'|',COALESCE(contracts.group_name,''))) as total")
            ->value('total');

        $active_clients = max(0, intval($total_clients_count) - intval($due_clients));

        // --- CÁLCULOS MONETARIOS DE CARTERA ---
        // Closure con los filtros comunes de asesor/jefe para reutilizar en queries de Quota
        $baseQuotaQuery = function () use ($user, $request) {
            return Quota::when($user->hasRole('seller'), function ($q) {
                return $q->whereHas('contract', fn($c) => $c->where('seller_id', auth()->user()->id));
            })->when($request->credit_manager_id, function ($q, $cm_id) {
                return $q->whereHas('contract.seller', fn($s) => $s->where('credit_manager_id', $cm_id));
            })->when($request->seller_id_2, function ($q, $seller_id) {
                return $q->whereHas('contract', fn($c) => $c->where('seller_id', $seller_id));
            })->when($request->end_date_2, function ($q, $d) {
                return $q->whereHas('contract', fn($c) => $c->whereDate('date', '<=', $d));
            })->whereHas('contract', fn($c) => $c->where('deleted', 0))
              ->where('paid', 0);
        };

        // Cartera bruta = toda la deuda pendiente sin filtro de tramo
        $cartera_bruta = $baseQuotaQuery()->sum('debt');

        // Mora > 121 días (cuotas vencidas hace más de 121 días respecto a la fecha de referencia)
        $cutoff_121 = $referenceDate->copy()->subDays(121)->toDateString();
        $seller_wallet = $baseQuotaQuery()
            ->whereDate('date', '<=', $cutoff_121)
            ->sum('debt');

        // Mora 1-120 días (cuotas vencidas entre 1 y 120 días respecto a la fecha de referencia)
        $due_clients_amount = $baseQuotaQuery()
            ->whereDate('date', '<', $referenceDate->toDateString())
            ->whereDate('date', '>', $cutoff_121)
            ->sum('debt');

        // Cartera actual = Cartera bruta - Mora 121+
        $seller_wallet_actual = max(0, $cartera_bruta - $seller_wallet);

        // Mora total = Mora 1-120 + Mora 121+
        $mora_total = $due_clients_amount + $seller_wallet;

        // % Mora = Mora 1-120 días / Cartera actual * 100
        $pct_mora = $seller_wallet_actual > 0 ? round($due_clients_amount / $seller_wallet_actual * 100, 2) : 0;

        // Reasignar variables para la vista
        $active_clients   = $seller_wallet_actual;  // Cartera actual
        $due_clients      = $due_clients_amount;     // Mora <120 días
        $requested_amount = $mora_total;             // Mora total
        $due_quotas       = $pct_mora;               // % Mora

        $section = $request->section ?? 'analisis';

        return view('dashboard.cartera_asesor', compact(
            'admincredits',
            'sellers',
            'cartera_bruta',
            'active_clients',
            'due_clients',
            'seller_wallet',
            'requested_amount',
            'due_quotas',
            'section'
        ));
    }
}
