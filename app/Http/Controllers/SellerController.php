<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Contract;
use App\Models\User;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::active();

        // Si el usuario autenticado es jefe de crédito, solo ver sus subordinados
        if (auth()->user()->hasRole('credit_manager')) {
            $query->where('credit_manager_id', auth()->id());
        }
        // Si es admin, ve todos (no hace falta filtro adicional)
    
        $sellers = $query->when($request->search, function ($query, $search) {
            return $query->where('name', 'like', '%' . $search . '%');
        })->paginate(20);
        $creditManagers = User::where('role', 'credit_manager')->where('deleted', 0)->get();
        return view('sellers.index', compact('sellers', 'creditManagers'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|digits:8|unique:users,document',
            'name' => 'required',
            'address' => 'nullable',
            'phone' => 'nullable|digits:9',
            'email' => 'nullable|email',
            'user' => 'required|unique:users,user',
            'password' => 'required',
            'credit_manager_id' => 'nullable|exists:users,id' // ← Agregar validación
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }
    
        User::create([
            'document' => $request->document,
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'user' => $request->user,
            'password' => Hash::make($request->password),
            'role' => 'seller',
            'credit_manager_id' => $request->credit_manager_id // ← Agregar esto
        ]);
    
        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, User $seller)
    {
        return response()->json($seller);
    }

    public function update(Request $request, User $seller)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|digits:8|unique:users,document,' . $seller->id,
            'name' => 'required',
            'address' => 'nullable',
            'phone' => 'nullable|digits:9',
            'email' => 'nullable|email',
            'user' => 'required|unique:users,user,' . $seller->id,
            'password' => 'nullable|string',
            'credit_manager_id' => 'nullable|exists:users,id' // ← Agregar validación
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }
    
        $data = $request->all();
    
        if (isset($data['password']) && $data['password'] !== null && $data['password'] !== '') {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
    
        $seller->update($data);
    
        return response()->json([
            'status' => true
        ]);
    }

    //Cambiar estado del Seller
    public function drop(string $id)
    {
        //
        $seller = User::find($id);
        $seller->update(['state' => 1]);
        return response()->json([
            'status' => true
        ]);
    }

    public function up(string $id)
    {
        //
        $seller = User::find($id);
        $seller->update(['state' => 0]);
        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, User $seller)
    {
        $seller->update(['deleted' => 1]);

        return response()->json([
            'status' => true
        ]);
    }
}
