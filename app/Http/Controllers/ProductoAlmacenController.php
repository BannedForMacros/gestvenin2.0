<?php

namespace App\Http\Controllers;

use App\Models\ProductoAlmacen;
use App\Models\Categoria;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;

class ProductoAlmacenController extends Controller
{
    public function index()
    {
        $productosAlmacen = ProductoAlmacen::with(['categoria', 'unidadMedida'])->get();
        return view('productos_almacen.index', compact('productosAlmacen'));
    }

    public function create()
    {
        $categorias = Categoria::where('activo', true)->get();
        $unidadesMedida = UnidadMedida::where('activo', true)->get();
        return view('productos_almacen.create', compact('categorias', 'unidadesMedida'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'codigo' => 'required|unique:productos_almacen',
                'nombre' => 'required',
                'categoria_id' => 'required|exists:categorias,id',
                'unidad_medida_id' => 'required|exists:unidades_medida,id',
            ]);
    
            ProductoAlmacen::create($request->all());
    
            return response()->json(['success' => true, 'message' => 'Producto almacenado con éxito.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el producto: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function show($id)
    {
        $producto = ProductoAlmacen::with(['categoria', 'unidadMedida'])->findOrFail($id);
        return response()->json($producto);
    }


    public function edit($id)
    {
        $productoAlmacen = ProductoAlmacen::findOrFail($id);
        $categorias = Categoria::where('activo', true)->get();
        $unidadesMedida = UnidadMedida::where('activo', true)->get();
        return view('productos_almacen.edit', compact('productoAlmacen', 'categorias', 'unidadesMedida'));
    }
    
    public function update(Request $request, $id)
    {
        $productoAlmacen = ProductoAlmacen::findOrFail($id);
    
        $request->validate([
            'codigo' => 'required|unique:productos_almacen,codigo,' . $id,
            'nombre' => 'required',
            'categoria_id' => 'required|exists:categorias,id',
            'unidad_medida_id' => 'required|exists:unidades_medida,id',
        ]);
    
        $productoAlmacen->update($request->all());
        return redirect()->route('productos_almacen.index')->with('success', 'Producto actualizado con éxito.');
    }
    

    public function destroy($id)
    {
        // Buscar el producto por ID
        $productoAlmacen = ProductoAlmacen::findOrFail($id);
    
        // Cambiar el estado de "activo" (1 -> 0, 0 -> 1)
        $productoAlmacen->activo = !$productoAlmacen->activo;
    
        // Guardar el cambio
        $productoAlmacen->save();
    
        // Retornar respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'El estado del producto se ha cambiado con éxito.',
        ]);
    }
    
    
    
    
    
    
    
    

    public function buscar(Request $request)
    {
        $categoriaId = $request->input('categoria_id'); // Filtrar por categoría si está presente
    
        $productos = ProductoAlmacen::where('categoria_id', $categoriaId)
            ->where('activo', true)
            ->get();
    
        // Devolver los productos en formato JSON para ser usados en el select
        return response()->json($productos);
    }
    
    

}
