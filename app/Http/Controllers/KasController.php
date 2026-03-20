namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kas;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class KasController extends Controller
{
public function index()
{
$leaderboard = User::withSum('kas', 'jumlah')
->orderByDesc('kas_sum_jumlah')
->get();

return view('kas.index', compact('leaderboard'));
}

public function store(Request $request)
{
$request->validate([
'jumlah' => 'required|integer|min:1000'
]);

Kas::create([
'user_id' => Auth::id(), // 🔥 AUTO USER LOGIN
'jumlah' => $request->jumlah
]);

return redirect()->back()->with('success', 'Iuran berhasil ditambahkan!');
}
}