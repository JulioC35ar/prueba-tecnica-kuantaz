<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BenefitController extends Controller
{
    public function getData(){
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
    }
    
    public function getDataFromURL($url){
        $response = Http::get($url);
        return $response->json();
    }
}
