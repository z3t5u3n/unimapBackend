<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use App\Models\Documento;
use Illuminate\Http\JsonResponse;
// Controlador del Model Documento
class DocumentoContrller extends Controller
{
    //Extraer datos para enviar al formulario
    public function index(): JsonResponse
    {
        // Obtener los registro
        $documento = Documento::all();

        // Envialos al formulario
        return response()->json($documento);
    }
}
