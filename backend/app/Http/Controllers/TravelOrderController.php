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

        // base da query, já trazendo o usuário
        $query = TravelOrder::with('user');

        // se NÃO for admin, restringe aos pedidos do próprio usuário
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // filtros opcionais
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($id = $request->input('id')) {
            $query->where('id', $id);
        }

        if ($destination = $request->input('destination')) {
            $query->where('destination', 'like', "%{$destination}%");
        }

        // filtro por intervalo de datas (sua regra de interseção)
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('departure_date', [$startDate, $endDate])
                ->orWhereBetween('return_date', [$startDate, $endDate])
                ->orWhere(function ($inner) use ($startDate, $endDate) {
                        $inner->where('departure_date', '<', $startDate)
                            ->where('return_date', '>', $endDate);
                });
            });
        }

        // ordenação básica e paginação
        $orders = $query->orderByDesc('id')->paginate(10);

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
