<?php
namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with('product')->latest();

        // ১. ডেট ফিল্টার
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // ২. টাইপ ফিল্টার (in / out)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->paginate(15)->withQueryString();

        // সামারি কার্ডের জন্য হিসাব (গ্রাম ও কেজিতে)
        $stats = [
            'total_in'  => (clone $query)->where('type', 'in')->sum('quantity_in_grams'),
            'total_out' => (clone $query)->where('type', 'out')->sum('quantity_in_grams'),
        ];

        return view('admin.stock_movements.index', compact('movements', 'stats'));
    }
}