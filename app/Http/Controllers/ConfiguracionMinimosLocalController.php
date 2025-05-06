<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionMinimosLocal;
use App\Models\Local;
use App\Models\ProductoAlmacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ConfiguracionMinimosLocalController extends Controller
{
    public function index(Request $request)
    {
        $configuraciones = [];
        $locales = [];
        $localSeleccionado = null;
        {

            $locales = Local::all();
            
            // Obtener el local seleccionado
            $localSeleccionado = $request->input('local_id') ?? ($locales->first() ? $locales->first()->id : null);
    
            if ($localSeleccionado) {
                $configuraciones = ConfiguracionMinimosLocal::with('local', 'productoAlmacen')
                    ->where('local_id', $localSeleccionado)
                    ->get();
            }
        } 

        
    
        return view('configuracion_minimos_local.index', compact('configuraciones', 'locales', 'localSeleccionado'));
    }
    



    public function create()
    {
        // Obtener todos los locales y productos para el formulario
        $locales = Local::all();
        $productos = ProductoAlmacen::all();

        return view('configuracion_minimos_local.create', compact('locales', 'productos'));
    }

    public function store(Request $request)
    {
        // Validar la entrada
        $request->validate([
            'local_id' => 'required|exists:locales,id',
            'producto_almacen_id' => 'required|exists:productos_almacen,id',
            'cantidad_minima' => 'required|numeric|min:0',
        ]);

        // Crear la configuración de mínimo
        ConfiguracionMinimosLocal::create([
            'local_id' => $request->input('local_id'),
            'producto_almacen_id' => $request->input('producto_almacen_id'),
            'cantidad_minima' => $request->input('cantidad_minima'),
        ]);

        return redirect()->route('configuracion_minimos_local.index')->with('success', 'Configuración de mínimo guardada con éxito.');
    }

    public function edit($id)
    {
        // Obtener la configuración actual
        $configuracion = ConfiguracionMinimosLocal::findOrFail($id);

        // Obtener todos los locales y productos para el formulario de edición
        $locales = Local::all();
        $productos = ProductoAlmacen::all();

        return view('configuracion_minimos_local.edit', compact('configuracion', 'locales', 'productos'));
    }

    public function update(Request $request, $id)
    {
        // Validar la entrada
        $request->validate([
            'local_id' => 'required|exists:locales,id',
            'producto_almacen_id' => 'required|exists:productos_almacen,id',
            'cantidad_minima' => 'required|numeric|min:0',
        ]);

        // Actualizar la configuración
        $configuracion = ConfiguracionMinimosLocal::findOrFail($id);
        $configuracion->update([
            'local_id' => $request->input('local_id'),
            'producto_almacen_id' => $request->input('producto_almacen_id'),
            'cantidad_minima' => $request->input('cantidad_minima'),
        ]);

        return redirect()->route('configuracion_minimos_local.index')->with('success', 'Configuración de mínimo actualizada con éxito.');
    }

    public function destroy($id)
    {
        // Eliminar la configuración de mínimo
        $configuracion = ConfiguracionMinimosLocal::findOrFail($id);
        $configuracion->delete();

        return redirect()->route('configuracion_minimos_local.index')->with('success', 'Configuración de mínimo eliminada con éxito.');
    }
}
