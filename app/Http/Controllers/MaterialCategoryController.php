<?php

namespace App\Http\Controllers;

use App\Models\MaterialCategory;
use Illuminate\Http\Request;

class MaterialCategoryController extends Controller
{
    public function destroy($id)
    {
        // Obtener la categoría de material
        $category = MaterialCategory::findOrFail($id);

        // Eliminar las solicitudes asociadas
        $category->requests()->delete(); // Asegúrate de que la relación esté definida en el modelo

        // Ahora eliminar la categoría
        $category->delete();

        return redirect()->route('material-categories.index')->with('success', 'Categoría eliminada con éxito.');
    }
}
