<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Gera database/data/service_zones.json (distrito, concelho, freguesia) a partir da GEO API PT,
 * que usa a Carta Administrativa Oficial de Portugal (CAOP).
 * Só é preciso correr quando a CAOP muda; o seeder lê o ficheiro e não precisa de internet.
 */
class ImportServiceZones extends Command
{
    protected $signature = 'zones:import'; // php artisan zones:import

    protected $description = 'Gera o ficheiro das zonas (distritos, concelhos e freguesias) a partir da GEO API PT';

    /** Todos os concelhos, cada um com o código INE e as freguesias, num só pedido. */
    private const SOURCE = 'https://json.geoapi.pt/municipios/freguesias';

    /** Códigos INE dos distritos (os 2 primeiros dígitos do código do concelho). */
    private const DISTRICTS = [
        '01' => 'Aveiro', '02' => 'Beja', '03' => 'Braga', '04' => 'Bragança', '05' => 'Castelo Branco',
        '06' => 'Coimbra', '07' => 'Évora', '08' => 'Faro', '09' => 'Guarda', '10' => 'Leiria',
        '11' => 'Lisboa', '12' => 'Portalegre', '13' => 'Porto', '14' => 'Santarém', '15' => 'Setúbal',
        '16' => 'Viana do Castelo', '17' => 'Vila Real', '18' => 'Viseu',
    ];

    public function handle(): int
    {
        $response = Http::timeout(60)->acceptJson()->get(self::SOURCE);

        if ($response->failed()) {
            $this->error('Não foi possível obter os dados da GEO API PT (HTTP ' . $response->status() . ').');

            return self::FAILURE;
        }

        $zones = [];

        foreach ($response->json() as $municipality) {
            $district = $this->district($municipality['codigoine']);

            foreach ($municipality['freguesias'] as $parish) {
                $zones[] = [
                    'district' => $district,
                    'county'   => $municipality['nome'],
                    'location' => $this->parishName($parish),
                ];
            }
        }

        // ordenado por distrito, concelho e freguesia: o ficheiro fica legível e as diferenças no git são claras
        usort($zones, fn ($a, $b) => [$a['district'], $a['county'], $a['location']]
            <=> [$b['district'], $b['county'], $b['location']]);

        File::ensureDirectoryExists(database_path('data'));
        File::put(
            database_path('data/service_zones.json'),
            json_encode($zones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $this->info(count($zones) . ' zonas gravadas em database/data/service_zones.json');

        return self::SUCCESS;
    }

    /**
     * Distrito a partir do código INE do concelho. Açores e Madeira não têm distritos (têm ilhas):
     * ficam como "Açores" e "Madeira", como no catálogo anterior.
     */
    private function district(string $ineCode): string
    {
        $code = substr($ineCode, 0, 2);

        return match (true) {
            in_array($code, ['31', '32'], true) => 'Madeira',
            str_starts_with($code, '4')         => 'Açores',
            default                              => self::DISTRICTS[$code]
                ?? throw new \RuntimeException("Distrito desconhecido para o código INE {$ineCode}."),
        };
    }

    /**
     * Tira o prefixo das uniões de freguesias, em todas as variantes da CAOP
     * ("União das/de freguesias de/do/da/dos/das …"): "União das freguesias de Alvega e Concavada" → "Alvega e Concavada".
     */
    private function parishName(string $name): string
    {
        return preg_replace('/^União d[ae]s? freguesias d[aeo]s? /u', '', $name);
    }
}