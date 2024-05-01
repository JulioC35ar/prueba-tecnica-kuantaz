<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;
/**
 * @OA\Info(
 *     title="Endpoint get Beneficios",
 *     version="1.0.0",
 *     description="Obtener beneficios filtrados y ordenados a partir de informacion obtenida de otros endpoints"
 * )
 */
class BenefitController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/get_data",
     *     summary="Obtener datos de beneficios",
     *     tags={"Beneficios"},
     *     @OA\Response(response=200, description="Datos de beneficios obtenidos correctamente"),
     *     @OA\Response(response=404, description="Error al procesar la solicitud")
     * )
     * @OA\Schema(
     *     schema="Beneficio",
     *     @OA\Property(property="id_programa", type="integer"),
     *     @OA\Property(property="monto", type="number", format="float"),
     *     @OA\Property(property="fecha_recepcion", type="string", format="date"),
     *     @OA\Property(property="fecha", type="string", format="date"),
     *     @OA\Property(property="anio", type="integer"),
     *     @OA\Property(property="view", type="boolean"),
     *     @OA\Property(property="ficha", ref="#/components/schemas/Ficha"),
     * ),
     * @OA\Schema(
     *     schema="Ficha",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="nombre", type="string"),
     *     @OA\Property(property="id_programa", type="integer"),
     *     @OA\Property(property="url", type="string"),
     *     @OA\Property(property="categoria", type="string"),
     *     @OA\Property(property="descripcion", type="string"),
     * )
    */

    public function getData(){
        try {
            $beneficios = collect($this->getDataFromURL("https://run.mocky.io/v3/399b4ce1-5f6e-4983-a9e8-e3fa39e1ea71")['data']);
            $filtros = collect($this->getDataFromURL("https://run.mocky.io/v3/06b8dd68-7d6d-4857-85ff-b58e204acbf4")['data']);
            $fichas = collect($this->getDataFromURL("https://run.mocky.io/v3/c7a4777f-e383-4122-8a89-70f29a6830c0")['data']);

            // agrupar beneficios por año (1)
            $data = $beneficios->groupBy(function ($beneficio) {
                return Carbon::parse($beneficio['fecha'])->year;
            })->map(function ($beneficios, $anio) use ($filtros, $fichas) {
                $filtroPorId = function ($idPrograma) use ($filtros) {
                    return $filtros->firstWhere('id_programa', $idPrograma);
                };

                // filtrar beneficios que cumplen con los montos maximos y minimos (4)
                $beneficiosFiltrados = $beneficios->filter(function ($beneficio) use ($filtroPorId) {
                    $filtro = $filtroPorId($beneficio['id_programa']);
                    return $beneficio['monto'] >= $filtro['min'] && $beneficio['monto'] <= $filtro['max'];
                });

                return [
                    'year' => $anio,
                    'monto_total' => $beneficiosFiltrados->sum('monto'), // calcular monto total por año (2)
                    'num' => $beneficiosFiltrados->count(), //el numero de beneficios por año (3)
                    'beneficios' => $beneficiosFiltrados->map(function ($beneficio) use ($fichas, $filtroPorId, $anio) {
                        $filtro = $filtroPorId($beneficio['id_programa']);
                        $ficha = $fichas->firstWhere('id', $filtro['ficha_id']);// cada beneficio con su ficha (5)

                        return [
                            'id_programa' => $beneficio['id_programa'],
                            'monto' => $beneficio['monto'],
                            'fecha_recepcion' => $beneficio['fecha_recepcion'],
                            'fecha' => $beneficio['fecha'],
                            'anio' => $anio,
                            'view' => true,
                            'ficha' => $ficha,
                        ];
                    }),
                ];
            })->sortByDesc('year')->values();

            $response = [
                'code' => 200,
                'success' => true,
                'data' => $data,
            ];
            return $response;
        } catch (\Throwable $th) {
            Log::error('Error al procesar la solicitud: '.$th->getMessage());
            $response = [
                'code' => 404,
                'success' => false,
                'message' => 'Error al procesar la solicitud ',
            ];
            return $response;
        }
    }
    
    public function getDataFromURL($url){
        $response = Http::get($url);
        return $response->json();
    }
}
