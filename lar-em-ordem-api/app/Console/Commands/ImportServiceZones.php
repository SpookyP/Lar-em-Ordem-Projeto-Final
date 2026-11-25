<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Builds database/data/service_zones.json (district, county, parish) from the GEO API PT,
 * which uses the official administrative map of Portugal (CAOP).
 * Only needs to run when the CAOP changes; the seeder reads the file and needs no internet.
 */
class ImportServiceZones extends Command
{
    protected $signature = 'zones:import'; // php artisan zones:import

    protected $description = 'Gera o ficheiro das zonas (distritos, concelhos e freguesias) a partir da GEO API PT';

    /** Every county, each with its INE code and parishes, in a single request. */
    private const SOURCE = 'https://json.geoapi.pt/municipios/freguesias';

    /** INE codes of the districts (the first 2 digits of the county code). */
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

        // sorted by district, county and parish so the file stays readable and git diffs stay clear
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
     * District from the county's INE code. The Azores and Madeira have islands instead of districts,
     * so they are stored as "Açores" and "Madeira", as in the previous catalog.
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
     * Removes the prefix of merged parishes, in every CAOP variant
     * ("União das/de freguesias de/do/da/dos/das …"): "União das freguesias de Alvega e Concavada" → "Alvega e Concavada".
     */
    private function parishName(string $name): string
    {
        return preg_replace('/^União d[ae]s? freguesias d[aeo]s? /u', '', $name);
    }
}