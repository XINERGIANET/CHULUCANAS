<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Exports\ExpensesExport;
use App\Exports\ExpensesCashExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Expense;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\ExpensePayment;
use App\Models\ExpensePhoto;

class ExpenseController extends Controller
{
    public function index(Request $request){
        $user = auth()->user();
        $expensesQuery = Expense::with('expensePayments.paymentMethod')->active()
            ->when($user->hasRole('seller'), function($query) use($user){
                return $query->where('seller_id', $user->id);
            })->when($request->description, function($query, $description){
                return $query->where('description', 'like', '%'.$description.'%');
            })->when($request->seller_id, function($query, $seller_id){
                return $query->where('seller_id', $seller_id);
            })->when($request->payment_method_id, function($query, $payment_method_id){
                return $query->where(function($q) use ($payment_method_id){
                    $q->where('payment_method_id', $payment_method_id)
                      ->orWhereHas('expensePayments', function($q2) use ($payment_method_id){
                          $q2->where('payment_method_id', $payment_method_id);
                      });
                });
            })->when($request->start_date, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->latest('date')->latest('id')
            ->whereNotNull('contract_id');

        // calcular total desde expense_payments para los gastos filtrados
        $expenseIds = $expensesQuery->pluck('id')->toArray();
        $totalExpenses = 0;
        if (!empty($expenseIds)) {
            $totalExpenses = ExpensePayment::whereIn('expenses_id', $expenseIds)
            ->when($request->payment_method_id, function($query, $payment_method_id){
                return $query->where('payment_method_id', $payment_method_id);
            })->sum('amount');
        }

        $totalAmounts = $expensesQuery->sum('amounts');

        $total = $totalExpenses + $totalAmounts;

        $expenses = $expensesQuery->paginate(20);

        $sellers = User::seller()->where('state', 0)->active()->get();
        $payment_methods = PaymentMethod::all();
        
        return view('expenses.index', compact('expenses', 'sellers', 'payment_methods', 'total'));
    }

    public function index_cash(Request $request){
        $user = auth()->user();
        $expensesQuery = Expense::with('expensePayments.paymentMethod')->active()
            ->when($user->hasRole('seller'), function($query) use($user){
                return $query->where('seller_id', $user->id);
            })->when($request->description, function($query, $description){
                return $query->where('description', 'like', '%'.$description.'%');
            })->when($request->seller_id, function($query, $seller_id){
                return $query->where('seller_id', $seller_id);
            })->when($request->payment_method_id, function($query, $payment_method_id){
                return $query->where(function($q) use ($payment_method_id){
                    $q->where('payment_method_id', $payment_method_id)
                      ->orWhereHas('expensePayments', function($q2) use ($payment_method_id){
                          $q2->where('payment_method_id', $payment_method_id);
                      });
                });
            })->when($request->start_date, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->latest('date')->latest('id')
            ->whereNull('contract_id');
        

        // calcular total desde expense_payments para los gastos filtrados
        $expenseIds = $expensesQuery->pluck('id')->toArray();
        $totalExpenses = 0;
        if (!empty($expenseIds)) {
            $totalExpenses = ExpensePayment::whereIn('expenses_id', $expenseIds)
            ->when($request->payment_method_id, function($query, $payment_method_id){
                return $query->where('payment_method_id', $payment_method_id);
            })->sum('amount');
        }

        $totalAmounts = $expensesQuery->sum('amounts');

        $total = $totalExpenses + $totalAmounts;

        $expenses = $expensesQuery->paginate(20);

        $sellers = User::seller()->where('state', 0)->active()->get();
        $payment_methods = PaymentMethod::all();
        
        return view('expenses.index_cash', compact('expenses', 'sellers', 'payment_methods', 'total'));
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'payment_method_id' => 'required',
            'payment_amount' => 'required|numeric',
            'date' => 'required|date',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $expense = Expense::create([
            'description' => $request->description,
            'seller_id' => $request->seller_id,
            'contract_id' => $request->contract_id,
            'date' => $request->date,
        ]);

        // Guardar métodos de pago asociados (tabla expenses_payments) con montos
        if ($expense) {
            ExpensePayment::where('expenses_id', $expense->id)->delete();

            if ($request->payment_method_id) {
                ExpensePayment::create([
                    'expenses_id' => $expense->id,
                    'payment_method_id' => $request->payment_method_id,
                    'amount' => $request->payment_amount
                ]);
            }

            if ($request->payment_method_id_2 && $request->payment_amount_2) {
                ExpensePayment::create([
                    'expenses_id' => $expense->id,
                    'payment_method_id' => $request->payment_method_id_2,
                    'amount' => $request->payment_amount_2
                ]);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $img) {
                    if (!$img->isValid()) continue;
                    // almacena en storage/app/public/expenses_photos
                    $path = $img->store('expenses_photos', 'public');
                    // crea registro en expenses_photos (expense_id, url)
                    ExpensePhoto::create([
                        'expense_id' => $expense->id,
                        'url' => $path
                    ]);
                }
            }
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Expense $expense){
        // obtener pagos asociados y mapear al primer y segundo (si existen)
        $payments = $expense->expensePayments()->get();
        $first = $payments->get(0);
        $second = $payments->get(1);

        // obtener fotos asociadas y mapear a {id, url} (url pública usando storage)
        $photoModels = ExpensePhoto::where('expense_id', $expense->id)->get();
        $photos = $photoModels->map(function($p){
            return [
                'id' => $p->id,
                'url' => $p->url,
            ];
        })->values()->all();

        return response()->json([
            'id' => $expense->id,
            'description' => $expense->description,
            'seller_id' => $expense->seller_id,
            'amount' => $payments->sum('amount'),
            'payment_method_id' => $first ? $first->payment_method_id : null,
            'payment_amount' => $first ? $first->amount : null,
            'payment_method_id_2' => $second ? $second->payment_method_id : null,
            'payment_amount_2' => $second ? $second->amount : null,
            'date' => $expense->date->format('Y-m-d'),
            'photos' => $photos
        ]);
    }

    public function update(Request $request, Expense $expense){

        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'payment_method_id' => 'required',
            'payment_amount' => 'required|numeric',
            'date' => 'required|date',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'removed_photo_ids' => 'nullable|array',
            'removed_photo_ids.*' => 'integer'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        // legacy single image field (si lo utilizas)
        $image = $expense->image;
        if($request->hasFile('image')){
            $image = $request->image->store('expenses', 'public');
        }

        $expense->update([
            'description' => $request->description,
            'seller_id' => $request->seller_id,
            'date' => $request->date,
            'image' => $image
        ]);

        // sincronizar expenses_payments: eliminar y volver a crear según lo enviado (con montos)
        ExpensePayment::where('expenses_id', $expense->id)->delete();

        if ($request->payment_method_id) {
            ExpensePayment::create([
                'expenses_id' => $expense->id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $request->payment_amount
            ]);
        }

        if ($request->payment_method_id_2 && $request->payment_amount_2) {
            ExpensePayment::create([
                'expenses_id' => $expense->id,
                'payment_method_id' => $request->payment_method_id_2,
                'amount' => $request->payment_amount_2
            ]);
        }

        // borrar fotos marcadas para eliminar (y eliminar fichero en storage/public)
        if ($request->filled('removed_photo_ids')) {
            $ids = (array) $request->removed_photo_ids;
            $photosToDelete = ExpensePhoto::whereIn('id', $ids)
                ->where('expense_id', $expense->id)
                ->get();
            foreach ($photosToDelete as $p) {
                if ($p->url) {
                    Storage::disk('public')->delete($p->url);
                }
                $p->delete();
            }
        }

        // guardar nuevas imágenes enviadas desde el formulario (images[])
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                if (!$img->isValid()) continue;
                $path = $img->store('expenses_photos', 'public');
                ExpensePhoto::create([
                    'expense_id' => $expense->id,
                    'url' => $path
                ]);
            }
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Expense $expense){
        $expense->update(['deleted' => 1]);

        return response()->json([
            'status' => true
        ]);
    }

    public function excel(Request $request){
        $name = "Egresos_".now()->format('d_m_Y').".xlsx";
        return Excel::download(new ExpensesExport, $name);
    }
    public function excel_cash(Request $request){
        $name = "Egresos_caja_".now()->format('d_m_Y').".xlsx";
        return Excel::download(new ExpensesCashExport, $name);
    }

}
