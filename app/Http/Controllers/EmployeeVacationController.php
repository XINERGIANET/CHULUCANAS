<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\EmployeeVacation;
use Carbon\Carbon;

class EmployeeVacationController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->hasRole('operations')) {
            return response()->json([
                'status' => false,
                'error' => 'No tienes permisos para registrar salidas de vacaciones. Solo Operaciones y Administrador pueden registrar.'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'nullable|numeric|min:0.5',
            'comments' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $employee = Employee::active()->find($request->employee_id);
        if (!$employee) {
            return response()->json([
                'status' => false,
                'error' => 'Colaborador no encontrado.'
            ]);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Si no se especifica manualmente la cantidad de días, se calcula la diferencia en días calendario inclusiva
        $days = $request->days ? floatval($request->days) : ($startDate->diffInDays($endDate) + 1);

        try {
            $vacation = new EmployeeVacation();
            $vacation->employee_id = $employee->id;
            $vacation->start_date = $request->start_date;
            $vacation->end_date = $request->end_date;
            $vacation->days = $days;
            $vacation->period_year = $startDate->format('Y');
            $vacation->comments = $request->comments;
            $vacation->created_by = auth()->id();
            $vacation->deleted = 0;
            $vacation->save();

            return response()->json([
                'status' => true,
                'message' => 'Salida de vacaciones registrada exitosamente (' . $days . ' días).'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al registrar vacaciones: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            return response()->json([
                'status' => false,
                'error' => 'Solo el Administrador tiene permisos para editar registros de vacaciones.'
            ]);
        }

        $vacation = EmployeeVacation::active()->find($id);
        if (!$vacation) {
            return response()->json([
                'status' => false,
                'error' => 'Registro de vacaciones no encontrado.'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'required|numeric|min:0.5',
            'comments' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        try {
            $vacation->start_date = $request->start_date;
            $vacation->end_date = $request->end_date;
            $vacation->days = $request->days;
            $vacation->period_year = Carbon::parse($request->start_date)->format('Y');
            $vacation->comments = $request->comments;
            $vacation->save();

            return response()->json([
                'status' => true,
                'message' => 'Registro de vacaciones actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al actualizar vacaciones: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            return response()->json([
                'status' => false,
                'error' => 'Solo el Administrador tiene permisos para eliminar registros de vacaciones.'
            ]);
        }

        $vacation = EmployeeVacation::active()->find($id);
        if (!$vacation) {
            return response()->json([
                'status' => false,
                'error' => 'Registro de vacaciones no encontrado.'
            ]);
        }

        $vacation->deleted = 1;
        $vacation->save();

        return response()->json([
            'status' => true,
            'message' => 'Registro de vacaciones eliminado correctamente.'
        ]);
    }
}
