<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Workbench\App\Enums\BrandGroup;
use Workbench\App\Models\Brand;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;
use Workbench\App\Models\Language;
use Workbench\App\Models\River;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Deterministic dataset for quick testing and demos.
        $this->seedGeography();
    }

    protected function seedGeography(): void
    {
        // Countries
        $de = Country::factory()->create(['name' => 'Germany', 'code' => 'DE']);
        $fr = Country::factory()->create(['name' => 'France', 'code' => 'FR']);
        $uk = Country::factory()->create(['name' => 'United Kingdom', 'code' => 'UK']);
        $nl = Country::factory()->create(['name' => 'Netherlands', 'code' => 'NL']);
        $ch = Country::factory()->create(['name' => 'Switzerland', 'code' => 'CH']);
        $be = Country::factory()->create(['name' => 'Belgium', 'code' => 'BE']);

        // Capitals
        $berlin = $this->createCity($de, 'Berlin', 3_669_000);
        $paris = $this->createCity($fr, 'Paris', 2_148_000);
        $london = $this->createCity($uk, 'London', 8_982_000);
        $bern = $this->createCity($ch, 'Bern', 133_000);
        $amsterdam = $this->createCity($nl, 'Amsterdam', 821_752);
        $amsterdam->update(['is_capital' => true]);
        $berlin->update(['is_capital' => true]);
        $paris->update(['is_capital' => true]);
        $london->update(['is_capital' => true]);
        $bern->update(['is_capital' => true]);

        // Belgium
        $brussels = $this->createCity($be, 'Brussels', 185_000);
        $brussels->update(['is_capital' => true]);
        $antwerp = $this->createCity($be, 'Antwerp', 530_000);
        $ghent = $this->createCity($be, 'Ghent', 263_000);
        $liege = $this->createCity($be, 'Liège', 196_000);

        // Other major cities (existing + newly added where needed)
        $munich = $this->createCity($de, 'Munich', 1_472_000);
        $hamburg = $this->createCity($de, 'Hamburg', 1_841_000);
        $lyon = $this->createCity($fr, 'Lyon', 522_000);
        $marseille = $this->createCity($fr, 'Marseille', 861_000);
        $manchester = $this->createCity($uk, 'Manchester', 553_000);
        $birmingham = $this->createCity($uk, 'Birmingham', 1_148_000);
        $rotterdam = $this->createCity($nl, 'Rotterdam', 651_446);
        $basel = $this->createCity($ch, 'Basel', 173_000);
        $cologne = $this->createCity($de, 'Cologne', 1_086_000);
        $koblenz = $this->createCity($de, 'Koblenz', 114_000);
        $leverkusen = $this->createCity($de, 'Leverkusen', 163_000);
        $duesseldorf = $this->createCity($de, 'Düsseldorf', 619_000);
        $bonn = $this->createCity($de, 'Bonn', 329_000);
        $mainz = $this->createCity($de, 'Mainz', 218_000);
        $mannheim = $this->createCity($de, 'Mannheim', 309_000);
        $frankfurt = $this->createCity($de, 'Frankfurt am Main', 764_000);
        $bremen = $this->createCity($de, 'Bremen', 567_000);
        $regensburg = $this->createCity($de, 'Regensburg', 153_000);
        // smaller demo city (may remain unattached to rivers as needed)
        $deggendorf = $this->createCity($de, 'Deggendorf', 37_000);

        // Newly added cities to satisfy river constraints
        $dresden = $this->createCity($de, 'Dresden', 556_000);
        $magdeburg = $this->createCity($de, 'Magdeburg', 239_000);
        $potsdam = $this->createCity($de, 'Potsdam', 183_000);
        $wuppertal = $this->createCity($de, 'Wuppertal', 355_000);
        $heidelberg = $this->createCity($de, 'Heidelberg', 160_000);
        $stuttgart = $this->createCity($de, 'Stuttgart', 635_000);
        $trier = $this->createCity($de, 'Trier', 110_000);
        $bremerhaven = $this->createCity($de, 'Bremerhaven', 114_000);
        $ulm = $this->createCity($de, 'Ulm', 126_000);
        $ingolstadt = $this->createCity($de, 'Ingolstadt', 138_000);
        $wuerzburg = $this->createCity($de, 'Würzburg', 128_000);
        $rouen = $this->createCity($fr, 'Rouen', 112_000);
        $nantes = $this->createCity($fr, 'Nantes', 314_000);
        $orleans = $this->createCity($fr, 'Orléans', 116_000);
        $oxford = $this->createCity($uk, 'Oxford', 151_000);
        $reading = $this->createCity($uk, 'Reading', 160_000);
        $nottingham = $this->createCity($uk, 'Nottingham', 332_000);
        $stoke = $this->createCity($uk, 'Stoke-on-Trent', 258_000);

        // Rivers: Create required rivers and attach to cities via pivot (passthrough via cities only)
        // Rhine: many large cities (keep)
        $rhine = $this->createRiver('Rhine', 1230);
        $this->addRiverCities($rhine, [$rotterdam, $cologne, $mannheim, $mainz, $bonn, $koblenz, $basel, $duesseldorf]);

        // Elbe: Hamburg, Dresden, Magdeburg
        $elbe = $this->createRiver('Elbe', 1094);
        $this->addRiverCities($elbe, [$hamburg, $dresden, $magdeburg]);

        // Seine: Paris, Rouen
        $seine = $this->createRiver('Seine', 777);
        $this->addRiverCities($seine, [$paris, $rouen]);

        // Loire: Nantes, Orléans
        $loire = $this->createRiver('Loire', 1012);
        $this->addRiverCities($loire, [$nantes, $orleans]);

        // Thames: London, Oxford, Reading
        $thames = $this->createRiver('Thames', 346);
        $this->addRiverCities($thames, [$london, $oxford, $reading]);

        // Trent: Nottingham, Stoke-on-Trent
        $trent = $this->createRiver('Trent', 298);
        $this->addRiverCities($trent, [$nottingham, $stoke]);

        // Main: Frankfurt, Würzburg (and Mainz is on the Rhine at confluence, keep Main-specific major cities)
        $main = $this->createRiver('Main', 524);
        $this->addRiverCities($main, [$frankfurt, $wuerzburg]);

        // Neckar: Mannheim, Heidelberg, Stuttgart
        $neckar = $this->createRiver('Neckar', 362);
        $this->addRiverCities($neckar, [$mannheim, $heidelberg, $stuttgart]);

        // Danube: Regensburg, Ulm, Ingolstadt
        $danube = $this->createRiver('Danube', 2850);
        $this->addRiverCities($danube, [$regensburg, $ulm, $ingolstadt]);

        // Havel: Berlin, Potsdam
        $havel = $this->createRiver('Havel', 325);
        $this->addRiverCities($havel, [$berlin, $potsdam]);

        // Spree: Explicitly add and attach to Berlin as requested
        $spree = $this->createRiver('Spree', 400);
        $this->addRiverCities($spree, [$berlin]);

        // Mosel: Koblenz, Trier
        $mosel = $this->createRiver('Mosel', 544);
        $this->addRiverCities($mosel, [$koblenz, $trier]);

        // Weser: Bremen, Bremerhaven
        $weser = $this->createRiver('Weser', 452);
        $this->addRiverCities($weser, [$bremen, $bremerhaven]);

        // Wupper: Wuppertal, Leverkusen
        $wupper = $this->createRiver('Wupper', 116);
        $this->addRiverCities($wupper, [$wuppertal, $leverkusen]);

        // Düssel: Explicitly add and attach to Düsseldorf as requested
        $duessel = $this->createRiver('Düssel', 36);
        $this->addRiverCities($duessel, [$duesseldorf]);

        // Note: We purposely DO NOT create rivers < 100 km (e.g., Düssel, Alster, Bille, Ilz, Lesum, Nidda) or
        // rivers that would only have one or zero large cities (e.g., Spree, Isar, Inn, Sieg in this constrained dataset).

        // Brands (headquarters) — ensure each city has 2–7 brands it is famous for
        // Germany
        $this->addCityBrands($berlin, [
            ['Zalando', BrandGroup::Digital],
            ['HelloFresh', BrandGroup::Food],
            ['N26', BrandGroup::Digital],
            ['SoundCloud', BrandGroup::Digital],
        ]);
        $this->addCityBrands($munich, [
            ['BMW', BrandGroup::Cars],
            ['Siemens', BrandGroup::Electronics],
            ['FC Bayern München', BrandGroup::Sports],
            ['MAN', BrandGroup::Cars],
            ['Linde', BrandGroup::Chemicals],
        ]);
        $this->addCityBrands($hamburg, [
            ['Beiersdorf', BrandGroup::Pharma],
            ['Montblanc', BrandGroup::Fashion],
            ['Otto Group', BrandGroup::Digital],
            ['Airbus', BrandGroup::Electronics],
        ]);
        $this->addCityBrands($cologne, [
            ['Ford Europe', BrandGroup::Cars],
            ['REWE', BrandGroup::Food],
            ['RTL Deutschland', BrandGroup::Digital],
            ['1. FC Köln', BrandGroup::Sports],
        ]);
        $this->addCityBrands($koblenz, [
            ['Canyon Bicycles', BrandGroup::Sports],
            ['Debeka', BrandGroup::Digital],
        ]);
        $this->addCityBrands($leverkusen, [
            ['Bayer', BrandGroup::Pharma],
            ['TSV Bayer Leverkusen', BrandGroup::Sports],
            ['Currenta', BrandGroup::Chemicals],
        ]);
        $this->addCityBrands($duesseldorf, [
            ['Henkel', BrandGroup::Chemicals],
            ['trivago', BrandGroup::Digital],
            ['METRO', BrandGroup::Food],
            ['Fortuna Düsseldorf', BrandGroup::Sports],
        ]);
        $this->addCityBrands($bonn, [
            ['Deutsche Post DHL', BrandGroup::Digital],
            ['Haribo', BrandGroup::Food],
            ['Deutsche Telekom', BrandGroup::Digital],
            ['Telekom Baskets Bonn', BrandGroup::Sports],
        ]);
        $this->addCityBrands($mainz, [
            ['SCHOTT', BrandGroup::Electronics],
            ['ZDF', BrandGroup::Digital],
            ['1. FSV Mainz 05', BrandGroup::Sports],
        ]);
        $this->addCityBrands($mannheim, [
            ['John Deere', BrandGroup::Electronics],
            ['Pepperl+Fuchs', BrandGroup::Electronics],
            ['Rhein-Neckar Löwen', BrandGroup::Sports],
        ]);
        $this->addCityBrands($frankfurt, [
            ['Lufthansa', BrandGroup::Digital],
            ['Deutsche Börse', BrandGroup::Digital],
            ['Eintracht Frankfurt', BrandGroup::Sports],
        ]);
        $this->addCityBrands($bremen, [
            ["Beck's", BrandGroup::Food],
            ['Mercedes-Benz Bremen', BrandGroup::Cars],
            ['Werder Bremen', BrandGroup::Sports],
        ]);
        $this->addCityBrands($bremerhaven, [
            ['FRoSTA', BrandGroup::Food],
            ['Eisbären Bremerhaven', BrandGroup::Sports],
        ]);
        $this->addCityBrands($regensburg, [
            ['BMW Werk Regensburg', BrandGroup::Cars],
            ['OSRAM', BrandGroup::Electronics],
            ['SSV Jahn Regensburg', BrandGroup::Sports],
        ]);
        $this->addCityBrands($ulm, [
            ['Magirus', BrandGroup::Cars],
            ['ratiopharm', BrandGroup::Pharma],
            ['SSV Ulm 1846', BrandGroup::Sports],
        ]);
        $this->addCityBrands($ingolstadt, [
            ['Audi', BrandGroup::Cars],
            ['MediaMarktSaturn', BrandGroup::Electronics],
            ['ERC Ingolstadt', BrandGroup::Sports],
        ]);
        $this->addCityBrands($wuerzburg, [
            ['s.Oliver', BrandGroup::Fashion],
            ['Würzburger Kickers', BrandGroup::Sports],
            ['Vogel Communications', BrandGroup::Digital],
        ]);
        $this->addCityBrands($potsdam, [
            ['Studio Babelsberg', BrandGroup::Digital],
            ['UFA', BrandGroup::Digital],
            ['Turbine Potsdam', BrandGroup::Sports],
        ]);
        $this->addCityBrands($dresden, [
            ['GlobalFoundries Dresden', BrandGroup::Electronics],
            ['Sachsenmilch', BrandGroup::Food],
            ['Dynamo Dresden', BrandGroup::Sports],
        ]);
        $this->addCityBrands($magdeburg, [
            ['GETEC', BrandGroup::Chemicals],
            ['MDCC', BrandGroup::Digital],
            ['1. FC Magdeburg', BrandGroup::Sports],
        ]);
        $this->addCityBrands($wuppertal, [
            ['Vorwerk', BrandGroup::Electronics],
            ['Wuppertaler SV', BrandGroup::Sports],
        ]);
        $this->addCityBrands($heidelberg, [
            ['Heidelberger Druckmaschinen', BrandGroup::Electronics],
            ['MLP', BrandGroup::Digital],
            ['MLP Academics Heidelberg', BrandGroup::Sports],
        ]);
        $this->addCityBrands($stuttgart, [
            ['Porsche', BrandGroup::Cars],
            ['Mercedes-Benz', BrandGroup::Cars],
            ['Bosch', BrandGroup::Electronics],
            ['VfB Stuttgart', BrandGroup::Sports],
        ]);
        $this->addCityBrands($trier, [
            ['Bitburger', BrandGroup::Food],
            ['SV Eintracht Trier', BrandGroup::Sports],
        ]);
        $this->addCityBrands($deggendorf, [
            ['ZF Deggendorf', BrandGroup::Electronics],
            ['Deggendorfer SC', BrandGroup::Sports],
        ]);

        // France
        $this->addCityBrands($paris, [
            ['Danone', BrandGroup::Food],
            ['L\'Oréal', BrandGroup::Fashion],
            ['Louis Vuitton', BrandGroup::Fashion],
            ['Paris Saint-Germain', BrandGroup::Sports],
            ['Ubisoft', BrandGroup::Digital],
            ['Renault', BrandGroup::Cars],
            ['Carrefour', BrandGroup::Food],
        ]);
        $this->addCityBrands($lyon, [
            ['Groupe SEB', BrandGroup::Electronics],
            ['Renault Trucks', BrandGroup::Cars],
            ['Boehringer Ingelheim Animal Health', BrandGroup::Pharma],
            ['Olympique Lyonnais', BrandGroup::Sports],
        ]);
        $this->addCityBrands($marseille, [
            ['CMA CGM', BrandGroup::Digital],
            ['Olympique de Marseille', BrandGroup::Sports],
            ['Pernod Ricard', BrandGroup::Food],
        ]);
        $this->addCityBrands($rouen, [
            ['Matmut', BrandGroup::Digital],
            ['FC Rouen', BrandGroup::Sports],
            ['Lubrizol France', BrandGroup::Chemicals],
        ]);
        $this->addCityBrands($nantes, [
            ['LU Biscuits', BrandGroup::Food],
            ['Armor', BrandGroup::Electronics],
            ['FC Nantes', BrandGroup::Sports],
        ]);
        $this->addCityBrands($orleans, [
            ['John Deere Saran', BrandGroup::Electronics],
            ['Orléans Loiret Basket', BrandGroup::Sports],
        ]);

        // United Kingdom
        $this->addCityBrands($london, [
            ['Unilever', BrandGroup::Food],
            ['GSK', BrandGroup::Pharma],
            ['BBC', BrandGroup::Digital],
            ['Burberry', BrandGroup::Fashion],
            ['Arsenal FC', BrandGroup::Sports],
            ['Chelsea FC', BrandGroup::Sports],
            ['Diageo', BrandGroup::Food],
        ]);
        $this->addCityBrands($manchester, [
            ['Manchester United', BrandGroup::Sports],
            ['Manchester City', BrandGroup::Sports],
            ['Co-op', BrandGroup::Food],
            ['JD Sports', BrandGroup::Fashion],
            ['AO.com', BrandGroup::Digital],
        ]);
        $this->addCityBrands($birmingham, [
            ['Jaguar Land Rover', BrandGroup::Cars],
            ['Cadbury', BrandGroup::Food],
            ['Gymshark', BrandGroup::Fashion],
            ['Aston Villa', BrandGroup::Sports],
        ]);
        $this->addCityBrands($oxford, [
            ['MINI Plant Oxford', BrandGroup::Cars],
            ['Oxford University Press', BrandGroup::Digital],
            ['Oxford United', BrandGroup::Sports],
        ]);
        $this->addCityBrands($reading, [
            ['Microsoft UK', BrandGroup::Digital],
            ['Oracle UK', BrandGroup::Digital],
            ['Reading FC', BrandGroup::Sports],
        ]);
        $this->addCityBrands($nottingham, [
            ['Boots', BrandGroup::Pharma],
            ['Experian', BrandGroup::Digital],
            ['Nottingham Forest', BrandGroup::Sports],
        ]);
        $this->addCityBrands($stoke, [
            ['Wedgwood', BrandGroup::Fashion],
            ['Portmeirion', BrandGroup::Fashion],
            ['bet365', BrandGroup::Digital],
            ['Stoke City', BrandGroup::Sports],
        ]);

        // Netherlands
        $this->addCityBrands($amsterdam, [
            ['Heineken', BrandGroup::Food],
            ['Booking.com', BrandGroup::Digital],
            ['Adyen', BrandGroup::Digital],
            ['TomTom', BrandGroup::Digital],
            ['AFC Ajax', BrandGroup::Sports],
        ]);
        $this->addCityBrands($rotterdam, [
            ['Port of Rotterdam', BrandGroup::Digital],
            ['Coolblue', BrandGroup::Digital],
            ['Feyenoord', BrandGroup::Sports],
        ]);

        // Switzerland
        $this->addCityBrands($basel, [
            ['Novartis', BrandGroup::Pharma],
            ['Roche', BrandGroup::Pharma],
            ['Syngenta', BrandGroup::Chemicals],
            ['FC Basel', BrandGroup::Sports],
        ]);
        $this->addCityBrands($bern, [
            ['Swiss Post', BrandGroup::Digital],
            ['SBB', BrandGroup::Digital],
            ['Toblerone', BrandGroup::Food],
            ['BSC Young Boys', BrandGroup::Sports],
        ]);

        // Belgium
        $this->addCityBrands($brussels, [
            ['European Commission', BrandGroup::Digital],
            ['Solvay', BrandGroup::Chemicals],
            ['AB InBev (HQ region)', BrandGroup::Food],
            ['RSC Anderlecht', BrandGroup::Sports],
        ]);
        $this->addCityBrands($antwerp, [
            ['Port of Antwerp-Bruges', BrandGroup::Digital],
            ['KBC Group', BrandGroup::Digital],
            ['Royal Antwerp FC', BrandGroup::Sports],
        ]);
        $this->addCityBrands($ghent, [
            ['UGent', BrandGroup::Digital],
            ['ArcelorMittal Ghent', BrandGroup::Chemicals],
            ['KAA Gent', BrandGroup::Sports],
        ]);
        $this->addCityBrands($liege, [
            ['Liège Airport', BrandGroup::Digital],
            ['Mithra Pharmaceuticals', BrandGroup::Pharma],
            ['Standard Liège', BrandGroup::Sports],
        ]);

        // Languages
        $deLang = Language::firstOrCreate(['code' => 'de'], ['name' => 'German']);
        $frLang = Language::firstOrCreate(['code' => 'fr'], ['name' => 'French']);
        $enLang = Language::firstOrCreate(['code' => 'en'], ['name' => 'English']);
        $nlLang = Language::firstOrCreate(['code' => 'nl'], ['name' => 'Dutch']);

        $de->languages()->syncWithoutDetaching([$deLang->id]);
        $fr->languages()->syncWithoutDetaching([$frLang->id]);
        $uk->languages()->syncWithoutDetaching([$enLang->id]);
        $nl->languages()->syncWithoutDetaching([$nlLang->id]);
        $ch->languages()->syncWithoutDetaching([$deLang->id, $frLang->id]);
        $be->languages()->syncWithoutDetaching([$nlLang->id, $frLang->id, $deLang->id]);

        // Country borders (store symmetric pairs)
        $this->addBorderPair($de->id, $fr->id, 451);
        $this->addBorderPair($de->id, $ch->id, 347);
        $this->addBorderPair($de->id, $nl->id, 575);
        $this->addBorderPair($fr->id, $ch->id, 572);
        $this->addBorderPair($fr->id, $be->id, 620);
        $this->addBorderPair($be->id, $nl->id, 478);
        $this->addBorderPair($be->id, $de->id, 204);
    }

    // Helpers for readability
    private function createCity(Country $country, string $name, int $population): City
    {
        return City::factory()->create([
            'name' => $name,
            'background' => $this->backgroundFor($country, $name),
            'population' => $population,
            'country_id' => $country->id,
        ]);
    }

    private function createRiver(string $name, int $lengthKm): ?River
    {

        return River::factory()->create([
            'name' => $name,
            'length_km' => $lengthKm,
        ]);
    }

    /**
     * Attach multiple cities to a river.
     *
     * @param  City[]  $cities
     */
    private function addRiverCities(River $river, array $cities): void
    {
        foreach ($cities as $city) {
            if ($city instanceof City) {
                $this->setCityOnRiver($city, $river);
            }
        }
    }

    private function setCityOnRiver(City $city, ?River $river, int $bridgeCount = 0): void
    {
        if (! $river) {
            return;
        }

        DB::table('city_river')->updateOrInsert(
            [
                'city_id' => $city->id,
                'river_id' => $river->id,
            ],
            [
                'bridge_count' => $bridgeCount,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function addBorderPair(int $countryId, int $neighborId, int $lengthKm): void
    {
        // ensure both directions exist
        DB::table('country_borders')->updateOrInsert(
            ['country_id' => $countryId, 'neighbor_id' => $neighborId],
            ['border_length_km' => $lengthKm, 'updated_at' => now(), 'created_at' => now()]
        );
        DB::table('country_borders')->updateOrInsert(
            ['country_id' => $neighborId, 'neighbor_id' => $countryId],
            ['border_length_km' => $lengthKm, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Build a short, friendly background text for a given city.
     */
    private function backgroundFor(Country $country, string $cityName): string
    {
        // Curated, distinct backgrounds focused on iconic sights tourists love (no river mentions)
        $map = [
            'Berlin' => 'A city of reinvention: Brandenburg Gate at sunrise, the glass dome of the Reichstag, Museum Island’s world-class galleries, and the electric buzz of the East Side Gallery.',
            'Munich' => 'Alpine elegance and tradition: Marienplatz’s Glockenspiel, the grand Residenz, English Garden beer gardens, and halls lined with Oktoberfest folklore.',
            'Hamburg' => 'Harbor vibes with culture: Elbphilharmonie’s shimmering sails, Speicherstadt’s red-brick canyons, and the lively Reeperbahn after dark.',
            'Cologne' => 'Cathedral skyline: the soaring Kölner Dom, Roman roots beneath the streets, and cheerful squares filled with carnival energy.',
            'Düsseldorf' => 'Design-forward and chic: Königsallee boutiques, the shimmering MedienHafen architecture, and the cozy Altstadt packed with art and tastes.',
            'Frankfurt am Main' => 'Skylines and old-town charm: Römerberg timbered houses, observation decks, and a powerhouse of finance and contemporary art.',
            'Stuttgart' => 'Engineering meets culture: the sweeping Porsche and Mercedes-Benz museums, palace gardens, and hillside vineyards.',
            'Heidelberg' => 'Romantic classic: castle ruins above baroque streets, a historic university, and atmospheric squares lit by golden evenings.',
            'Bonn' => 'Sweet notes of history: Beethoven’s birthplace, stately avenues, and museums that trace a nation’s modern story.',
            'Mainz' => 'Gutenberg’s legacy lives: cathedral courtyards, cheerful markets, and a love for printcraft and celebration.',
            'Würzburg' => 'Baroque masterpiece: the Residenz with its famed staircase and frescoes, serene courtyards, and festive terraces.',
            'Regensburg' => 'UNESCO-stamped lanes: medieval towers, cheerful plazas, and stone-bound bridges to wander.',
            'Ulm' => 'A spire to the clouds: the mighty minster, peaceful quarters, and inventive spirit tucked into every corner.',
            'Ingolstadt' => 'Precision and pace: design pavilions, lively shopping streets, and proud craftsmanship on display.',
            'Leverkusen' => 'Green parks and innovation: cutting-edge labs, spirited sports, and friendly neighborhoods.',
            'Koblenz' => 'Fortresses and plazas: cable-car views, historic nooks, and relaxed cafés by bright squares.',
            'Wuppertal' => 'Suspended wonder: a city remembered for daring transit, playful sculptures, and surprising hillside vistas.',
            'Potsdam' => 'Palatial gardens and studios: Sanssouci’s elegance, tree-lined alleys, and storied film stages.',
            'Dresden' => 'Resplendent revival: the Frauenkirche dome, Zwinger’s galleries, and an old town warmed by light and music.',
            'Magdeburg' => 'Cathedral calm and modern art: plazas where history meets playful architecture and open-air culture.',
            'Bremen' => 'Fairy-tale touches: the Town Musicians statues, ornate facades, and festive squares that welcome wanderers.',
            'Bremerhaven' => 'Maritime museums and fresh air: wide promenades, lookout decks, and stories of travel and trade.',
            'Deggendorf' => 'Gateway feel: friendly streets, cozy bakeries, and seasonal fairs that gather neighbors together.',
            'Paris' => 'Timeless icons: the Louvre and its glass pyramid, café terraces, and grand boulevards stitched with fashion and art.',
            'Lyon' => 'Twin hills, twin rivers of flavor—yet we focus on bouchons, traboules, and a culinary capital’s warm heart.',
            'Marseille' => 'Sun-baked charm: hilltop basilicas, bustling markets, and vibrant street art near ancient stones.',
            'Rouen' => 'Gothic silhouettes: cathedral light, half-timbered streets, and squares set for leisurely strolls.',
            'Nantes' => 'Imaginative cityscapes: mechanical creatures, grand passages, and buoyant creative districts.',
            'Orléans' => 'Heroic echoes: Jeanne d’Arc monuments, calm promenades, and bright facades under generous skies.',
            'London' => 'Global stage: royal pageantry, West End lights, and museums that anchor centuries of stories.',
            'Oxford' => 'Scholarly beauty: cloisters and quads, ancient libraries, and lanes where ideas have walked for ages.',
            'Reading' => 'Festival spirit and innovation: buzzing venues, leafy parks, and a talent for reinvention.',
            'Manchester' => 'Music and matchdays: legendary venues, street-side murals, and a makers’ city with grit and warmth.',
            'Birmingham' => 'Canals and craftsmanship—told through markets, jewelry quarter brilliance, and surprising green pockets.',
            'Nottingham' => 'Legends and lace: a castle on a crag, secret caves, and independent quarters full of character.',
            'Stoke-on-Trent' => 'Ceramic heritage: kilns reborn as galleries, creative studios, and friendly local haunts.',
            'Amsterdam' => 'Golden Age charm: gabled rows, lively squares, and galleries that define a painter’s light.',
            'Rotterdam' => 'Bold lines and skyline: cube houses, sweeping bridges, and a love of architecture that looks ahead.',
            'Basel' => 'Art Basel’s pulse: museums aplenty, elegant streets, and courtyard cafés that invite conversation.',
            'Bern' => 'Arcaded calm: clocktowers, bears in lore, and tranquil corners perfect for lingering.',
            'Brussels' => 'Grand Place glow: ornate guildhalls, chocolatiers, and a crossroads of ideas and tastes.',
            'Antwerp' => 'Diamonds and design: cathedral splendor, fashion ateliers, and lively docks reborn.',
            'Ghent' => 'Spired silhouettes and student buzz: murals, music, and waterways lined with festive terraces.',
            'Liège' => 'Stairways and squares: dynamic cultural halls, markets, and warm cafés for long talks.',
        ];

        $text = $map[$cityName] ?? sprintf(
            '%s offers beloved landmarks, lively neighborhoods, and a distinctive cultural heartbeat in %s.',
            $cityName,
            $country->name
        );

        // Ensure max length (schema allows up to 200)
        if (mb_strlen($text) > 200) {
            $text = mb_substr($text, 0, 197).'...';
        }

        return $text;
    }

    /**
     * Attach multiple brands to a city for readability.
     *
     * @param  array<int, array{0:string,1:BrandGroup}>  $brands  [name, BrandGroup]
     */
    private function addCityBrands(City $city, array $brands): void
    {
        foreach ($brands as $tuple) {
            [$name, $group] = $tuple;
            $this->createBrand($city, $name, $group);
        }
    }

    /**
     * Create a brand headquartered in a given city.
     */
    private function createBrand(City $city, string $name, BrandGroup $group): Brand
    {
        return Brand::factory()->create([
            'name' => $name,
            'group' => $group,
            'city_id' => $city->id,
        ]);
    }
}
