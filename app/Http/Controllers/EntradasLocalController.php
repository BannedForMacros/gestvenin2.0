<?php 

namespace App\Http\Controllers;

use App\Models\EntradasLocal;
use App\Models\InventarioLocal;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EntradasLocalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user(); // Obtener el usuario autenticado
        $locales = []; // Inicializar los locales
        $entradas = [];
    
        // Verificar el rol del usuario usando DB
        $rolUsuario = DB::table('roles')
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->value('roles.name');
    
        // Si el usuario es 'dueño' o 'admin', mostrar todos los locales y entradas
        if ($rolUsuario === 'dueño' || $rolUsuario === 'admin') {
            $locales = Local::all(); // Obtener todos los locales
            $entradas = EntradasLocal::with('local')
                ->when($request->local_id, function ($query) use ($request) {
                    return $query->where('local_id', $request->local_id); // Filtrar por local si se selecciona
                })
                ->orderBy('fecha_entrada', 'desc') // Ordenar por fecha_entrada en forma descendente
                ->get();
        }
        // Si el usuario es 'cajera', mostrar solo las entradas de su local asignado
        elseif ($rolUsuario === 'cajera'||$rolUsuario === 'cremas') {
            $locales = []; // No mostrar el selector de locales para cajeras
            $entradas = EntradasLocal::with('local')
                ->where('local_id', $user->local_id) // Filtrar por el local del usuario
                ->orderBy('fecha_entrada', 'desc') // Ordenar por fecha_entrada en forma descendente
                ->get();
        }
    
        return view('entradas_local.index', compact('entradas', 'locales'));
    }
    
    public function show($id)
    {
        $entrada = EntradasLocal::with('detalles.productoAlmacen')->findOrFail($id);
        return view('entradas_local.show', compact('entrada'));
    }
    
    public function confirmarEntrada($id)
    {
        // Obtener la entrada por su ID con sus detalles
        $entrada = EntradasLocal::with('detalles')->findOrFail($id);
        
        // Cambiar el estado de la entrada a 'confirmado'
        $entrada->estado = 'confirmado';
        $entrada->save();
    
        // Actualizar el inventario local en función de los detalles de la entrada
        foreach ($entrada->detalles as $detalle) {
            $productoLocal = InventarioLocal::where('producto_almacen_id', $detalle->producto_almacen_id)
                ->where('local_id', $entrada->local_id)
                ->first();
    
            // Asegúrate de que `precio_unitario` y `precio_total` estén presentes en `$detalle`
            if (!isset($detalle->precio_unitario) || !isset($detalle->precio_total)) {
                return redirect()->route('inventario_local.index')->withErrors('Faltan precios en los detalles de la entrada.');
            }
    
            if ($productoLocal) {
                // Aumentar el stock del producto en el inventario del local
                $productoLocal->cantidad += $detalle->cantidad_entrada;
                $productoLocal->precio_unitario = $detalle->precio_unitario; // Asegurar que el precio unitario se actualiza
                $productoLocal->precio_total = $productoLocal->cantidad * $productoLocal->precio_unitario;
                $productoLocal->save();
            } else {
                // Crear el producto en el inventario local si no existe
                InventarioLocal::create([
                    'producto_almacen_id' => $detalle->producto_almacen_id,
                    'local_id' => $entrada->local_id,
                    'cantidad' => $detalle->cantidad_entrada,
                    'precio_unitario' => $detalle->precio_unitario, // Asegurar que el precio unitario se inserta
                    'precio_total' => $detalle->precio_total,
                ]);
            }
        }
    
        // Redireccionar al index de entradas locales con un mensaje de éxito
        return redirect()->route('inventario_local.index')->with('success', 'Entrada confirmada y stock actualizado.');
    }
    
    
    
    
    public function store(Request $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'local_id' => 'required|exists:locales,id',
            'detalles' => 'required|array',
            'detalles.*.producto_almacen_id' => 'required|exists:productos_almacen,id',
            'detalles.*.cantidad_entrada' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric',
            'detalles.*.precio_total' => 'required|numeric',
        ]);
    
        // Crear una nueva entrada de productos en el local
        $entrada = new EntradasLocal();
        $entrada->local_id = $request->input('local_id');
        $entrada->usuario_id = Auth::id(); // Si tienes autenticación
        $entrada->estado = 'pendiente';
        $entrada->fecha_entrada = now(); // Asigna la fecha actual
        $entrada->save(); // Guarda la entrada principal
    
        // Detalles de la entrada
        foreach ($request->input('detalles') as $detalle) {
            $entrada->detalles()->create([
                'producto_almacen_id' => $detalle['producto_almacen_id'],
                'cantidad_entrada' => $detalle['cantidad_entrada'],
                'precio_unitario' => $detalle['precio_unitario'],
                'precio_total' => $detalle['precio_total'],
            ]);
        }
    
        return redirect()->route('entradas_local.index')->with('success', 'Entrada registrada con éxito.');
    }

    /**
 * Devuelve JSON con las entradas de un local en una fecha dada.
 */
public function porFecha(Request $request)
{
    $data = $request->validate([
        'local_id' => 'required|integer|exists:locales,id',
        'fecha'    => 'required|date',
    ]);

    $entradas = EntradasLocal::with('detalles.productoAlmacen.unidadMedida')
        ->where('local_id', $data['local_id'])
        ->whereDate('fecha_entrada', $data['fecha'])
        ->orderBy('fecha_entrada', 'asc')
        ->get();

    return response()->json($entradas);
}
    
    
}

