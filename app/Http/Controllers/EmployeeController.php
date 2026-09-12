<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\EmployeeVacation;
use App\Models\User;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Employee::active()->with(['vacations', 'user'])
            ->when($user->hasRole('seller'), function ($q) use ($user) {
                return $q->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('document', $user->document);
                });
            })
            ->when($user->hasRole('credit_manager'), function ($q) use ($user) {
                return $q->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('document', $user->document)
                          ->orWhereHas('user', function ($uq) use ($user) {
                              $uq->where('credit_manager_id', $user->id);
                          });
                });
            })
            ->when($request->name, function ($q, $name) {
                return $q->where(function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%')
                        ->orWhere('document', 'like', '%' . $name . '%')
                        ->orWhere('position', 'like', '%' . $name . '%');
                });
            })
            ->when($request->status !== null && $request->status !== '', function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->position, function ($q, $position) {
                return $q->where('position', $position);
            })
            ->orderBy('status', 'desc')
            ->orderBy('name', 'asc');

        $employees = $query->paginate(20);

        // Resumen / KPIs filtrados según el rol y alcance
        $scopedActiveEmployeesQuery = Employee::active()->where('status', 1)->with('vacations')
            ->when($user->hasRole('seller'), function ($q) use ($user) {
                return $q->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('document', $user->document);
                });
            })
            ->when($user->hasRole('credit_manager'), function ($q) use ($user) {
                return $q->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('document', $user->document)
                          ->orWhereHas('user', function ($uq) use ($user) {
                              $uq->where('credit_manager_id', $user->id);
                          });
                });
            });

        $allActiveEmployees = $scopedActiveEmployeesQuery->get();
        $totalActive = $allActiveEmployees->count();

        $today = now()->format('Y-m-d');
        $activeEmpIds = $allActiveEmployees->pluck('id')->toArray();
        $onVacationCount = EmployeeVacation::active()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->whereIn('employee_id', $activeEmpIds)
            ->distinct('employee_id')
            ->count('employee_id');

        $totalGeneratedDays = $allActiveEmployees->sum('vacation_days_generated');
        $totalTakenDays = $allActiveEmployees->sum('vacation_days_taken');
        $totalBalanceDays = $allActiveEmployees->sum('vacation_balance');

        // Lista de usuarios del sistema disponibles para vincular (solo admin y operaciones)
        $systemUsers = User::active()->orderBy('name', 'asc')->get();

        // Cargos únicos para filtros
        $positions = Employee::active()->whereNotNull('position')->distinct()->pluck('position');

        return view('employees.index', compact(
            'employees',
            'totalActive',
            'onVacationCount',
            'totalGeneratedDays',
            'totalTakenDays',
            'totalBalanceDays',
            'systemUsers',
            'positions'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->hasRole('operations')) {
            return response()->json([
                'status' => false,
                'error' => 'No tienes permisos para registrar colaboradores. Solo Operaciones y Administrador pueden crear.'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'document' => 'nullable|string|max:20',
            'entry_date' => 'required|date',
            'position' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer',
            'annual_vacation_days' => 'nullable|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        try {
            $employee = new Employee();
            $employee->name = $request->name;
            $employee->document = $request->document;
            $employee->entry_date = $request->entry_date;
            $employee->position = $request->position;
            $employee->phone = $request->phone;
            $employee->email = $request->email;
            $employee->address = $request->address;
            $employee->user_id = $request->user_id ?: null;
            $employee->annual_vacation_days = $request->annual_vacation_days ?: 15;
            $employee->regime = $request->regime ?: 'REMYPE';
            $employee->status = 1;
            $employee->deleted = 0;
            $employee->save();

            return response()->json([
                'status' => true,
                'message' => 'Colaborador registrado exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Ocurrió un error al guardar: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            return response()->json([
                'status' => false,
                'error' => 'No tienes permisos para editar colaboradores.'
            ]);
        }

        $employee = Employee::active()->find($id);
        if (!$employee) {
            return response()->json([
                'status' => false,
                'error' => 'Colaborador no encontrado.'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'document' => 'nullable|string|max:20',
            'entry_date' => 'required|date',
            'position' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer',
            'annual_vacation_days' => 'nullable|numeric|min:1',
            'status' => 'required|in:0,1',
            'termination_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        try {
            $employee->name = $request->name;
            $employee->document = $request->document;
            $employee->entry_date = $request->entry_date;
            $employee->position = $request->position;
            $employee->phone = $request->phone;
            $employee->email = $request->email;
            $employee->address = $request->address;
            $employee->user_id = $request->user_id ?: null;
            $employee->annual_vacation_days = $request->annual_vacation_days ?: 15;
            $employee->regime = $request->regime ?: 'REMYPE';
            $employee->status = $request->status;
            $employee->termination_date = $request->status == 0 ? $request->termination_date : null;
            $employee->save();

            return response()->json([
                'status' => true,
                'message' => 'Colaborador actualizado exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Ocurrió un error al actualizar: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            return response()->json([
                'status' => false,
                'error' => 'Solo el administrador puede eliminar colaboradores.'
            ]);
        }

        $employee = Employee::active()->find($id);
        if (!$employee) {
            return response()->json([
                'status' => false,
                'error' => 'Colaborador no encontrado.'
            ]);
        }

        $employee->deleted = 1;
        $employee->save();

        return response()->json([
            'status' => true,
            'message' => 'Colaborador eliminado correctamente.'
        ]);
    }

    public function details($id)
    {
        $employee = Employee::active()->with(['vacations.creator', 'user'])->find($id);
        if (!$employee) {
            return response()->json([
                'status' => false,
                'error' => 'Colaborador no encontrado.'
            ]);
        }

        // Formatear histórico de vacaciones
        $vacations = $employee->vacations->map(function ($v) {
            return [
                'id' => $v->id,
                'start_date' => Carbon::parse($v->start_date)->format('d/m/Y'),
                'end_date' => Carbon::parse($v->end_date)->format('d/m/Y'),
                'raw_start' => Carbon::parse($v->start_date)->format('Y-m-d'),
                'raw_end' => Carbon::parse($v->end_date)->format('Y-m-d'),
                'days' => (float) $v->days,
                'comments' => $v->comments ?: 'Sin observaciones',
                'created_by' => $v->creator ? $v->creator->name : 'Sistema',
                'created_at' => $v->created_at ? $v->created_at->format('d/m/Y H:i') : '-',
            ];
        });

        // Desglose de periodos anuales cumplidos para sustento / liquidación
        $entryDate = Carbon::parse($employee->entry_date);
        $endDate = ($employee->status == 0 && $employee->termination_date)
            ? Carbon::parse($employee->termination_date)
            : now();

        $periods = [];
        $currentStart = $entryDate->copy();
        $periodIndex = 1;

        while ($currentStart->lt($endDate)) {
            $currentEnd = $currentStart->copy()->addYear()->subDay();
            $isComplete = $currentEnd->lte($endDate);
            $effectiveEnd = $isComplete ? $currentEnd : $endDate;

            $daysInPeriod = $currentStart->diffInDays($effectiveEnd) + 1;
            $daysEarned = round(($daysInPeriod * ($employee->annual_vacation_days ?: 15)) / 365, 2);

            $periods[] = [
                'index' => $periodIndex,
                'period_text' => $currentStart->format('d/m/Y') . ' - ' . $effectiveEnd->format('d/m/Y'),
                'status_text' => $isComplete ? 'Año cumplido' : 'Periodo trunco / en curso',
                'days_earned' => $daysEarned,
                'is_complete' => $isComplete
            ];

            $currentStart = $currentStart->copy()->addYear();
            $periodIndex++;
        }

        return response()->json([
            'status' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'document' => $employee->document ?: '-',
                'position' => $employee->position ?: '-',
                'phone' => $employee->phone ?: '-',
                'email' => $employee->email ?: '-',
                'address' => $employee->address ?: '-',
                'entry_date' => Carbon::parse($employee->entry_date)->format('d/m/Y'),
                'raw_entry_date' => Carbon::parse($employee->entry_date)->format('Y-m-d'),
                'tenure_text' => $employee->tenure_text,
                'total_days_worked' => $employee->total_days_worked,
                'vacation_days_generated' => $employee->vacation_days_generated,
                'vacation_days_taken' => $employee->vacation_days_taken,
                'vacation_balance' => $employee->vacation_balance,
                'status' => $employee->status,
                'termination_date' => $employee->termination_date ? Carbon::parse($employee->termination_date)->format('d/m/Y') : null,
                'regime' => $employee->regime,
                'annual_vacation_days' => $employee->annual_vacation_days ?: 15,
            ],
            'vacations' => $vacations,
            'periods' => $periods,
            'is_admin' => auth()->user()->hasRole('admin')
        ]);
    }
}
