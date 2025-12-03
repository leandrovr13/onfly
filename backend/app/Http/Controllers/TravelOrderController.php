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

        $query = TravelOrder::query()
            ->with('user');

        // Usuário comum vê apenas seus próprios pedidos
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Filtro por status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filtro por destino
        if ($destination = $request->query('destination')) {
            $query->where('destination', 'like', "%{$destination}%");
        }

        // Filtro por período de data de viagem
        if ($from = $request->query('date_from')) {
            $query->whereDate('departure_date', '>=', $from);
        }

        if ($to = $request->query('date_to')) {
            $query->whereDate('return_date', '<=', $to);
        }

        return $query->orderByDesc('created_at')->paginate(10);
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
