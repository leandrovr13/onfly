<?php

namespace App\Http\Controllers;

use App\Models\TravelOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Notifications\TravelOrderStatusChanged;

class TravelOrderController extends Controller
{
    /**
     * Lista os pedidos de viagem do usuário autenticado.
     * Admins visualizam todos os pedidos, com possibilidade de filtros.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Validação leve dos filtros
        $validated = $request->validate([
            'status'      => ['nullable', 'in:solicitado,aprovado,cancelado'],
            'destination' => ['nullable', 'string'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $query = TravelOrder::with('user')
            ->where('user_id', $user->id);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['destination'])) {
            $query->where('destination', 'like', '%' . $validated['destination'] . '%');
        }

        // Filtro de intervalo de datas com overlap
        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $start = $validated['start_date'];
            $end   = $validated['end_date'];

            $query->where(function ($q) use ($start, $end) {
                $q->whereDate('departure_date', '<=', $end)
                ->whereDate('return_date', '>=', $start);
            });
        }

        $orders = $query->orderByDesc('created_at')->paginate(10);

        return response()->json($orders);
    }


    /**
     * Cria um novo pedido de viagem vinculado ao usuário autenticado.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'destination'    => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date'],
            'return_date'    => ['required', 'date', 'after_or_equal:departure_date'],
        ]);

        $order = TravelOrder::create([
            'user_id'        => $request->user()->id,
            'destination'    => $data['destination'],
            'departure_date' => $data['departure_date'],
            'return_date'    => $data['return_date'],
            'status'         => 'solicitado',
        ]);

        return response()->json($order->load('user'), 201);
    }


    /**
     * Atualiza o status do pedido (somente admin).
     */
    public function updateStatus(Request $request, TravelOrder $order)
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:aprovado,cancelado'],
        ]);

        // Regra extra: não pode cancelar pedido já aprovado
        if ($data['status'] === 'cancelado' && $order->status === 'aprovado') {
            return response()->json(['error' => 'Pedidos aprovados não podem ser cancelados.'], 422);
        }

        $order->status = $data['status'];
        $order->save();

        $order->user->notify(new TravelOrderStatusChanged($order));

        return response()->json($order->load('user'));
    }

}
