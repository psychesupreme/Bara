<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KenyaCountiesSeeder extends Seeder
{
    /**
     * Seed all 47 Kenya Counties into the system as lookup data / baseline locations.
     */
    public function run(): void
    {
        $counties = [
            ['code' => '001', 'name' => 'Mombasa', 'capital' => 'Mombasa City'],
            ['code' => '002', 'name' => 'Kwale', 'capital' => 'Kwale'],
            ['code' => '003', 'name' => 'Kilifi', 'capital' => 'Kilifi'],
            ['code' => '004', 'name' => 'Tana River', 'capital' => 'Hola'],
            ['code' => '005', 'name' => 'Lamu', 'capital' => 'Lamu'],
            ['code' => '006', 'name' => 'Taita-Taveta', 'capital' => 'Voi'],
            ['code' => '007', 'name' => 'Garissa', 'capital' => 'Garissa'],
            ['code' => '008', 'name' => 'Wajir', 'capital' => 'Wajir'],
            ['code' => '009', 'name' => 'Mandera', 'capital' => 'Mandera'],
            ['code' => '010', 'name' => 'Marsabit', 'capital' => 'Marsabit'],
            ['code' => '011', 'name' => 'Isiolo', 'capital' => 'Isiolo'],
            ['code' => '012', 'name' => 'Meru', 'capital' => 'Meru'],
            ['code' => '013', 'name' => 'Tharaka-Nithi', 'capital' => 'Kathwana'],
            ['code' => '014', 'name' => 'Embu', 'capital' => 'Embu'],
            ['code' => '015', 'name' => 'Kitui', 'capital' => 'Kitui'],
            ['code' => '016', 'name' => 'Machakos', 'capital' => 'Machakos'],
            ['code' => '017', 'name' => 'Makueni', 'capital' => 'Wote'],
            ['code' => '018', 'name' => 'Nyandarua', 'capital' => 'Ol Kalou'],
            ['code' => '019', 'name' => 'Nyeri', 'capital' => 'Nyeri'],
            ['code' => '020', 'name' => 'Kirinyaga', 'capital' => 'Kerugoya'],
            ['code' => '021', 'name' => 'Murang\'a', 'capital' => 'Murang\'a'],
            ['code' => '022', 'name' => 'Kiambu', 'capital' => 'Kiambu'],
            ['code' => '023', 'name' => 'Turkana', 'capital' => 'Lodwar'],
            ['code' => '024', 'name' => 'West Pokot', 'capital' => 'Kapenguria'],
            ['code' => '025', 'name' => 'Samburu', 'capital' => 'Maralal'],
            ['code' => '026', 'name' => 'Trans-Nzoia', 'capital' => 'Kitale'],
            ['code' => '027', 'name' => 'Uasin Gishu', 'capital' => 'Eldoret'],
            ['code' => '028', 'name' => 'Elgeyo-Marakwet', 'capital' => 'Iten'],
            ['code' => '029', 'name' => 'Nandi', 'capital' => 'Kapsabet'],
            ['code' => '030', 'name' => 'Baringo', 'capital' => 'Kabarnet'],
            ['code' => '031', 'name' => 'Laikipia', 'capital' => 'Nanyuki'],
            ['code' => '032', 'name' => 'Nakuru', 'capital' => 'Nakuru City'],
            ['code' => '033', 'name' => 'Narok', 'capital' => 'Narok'],
            ['code' => '034', 'name' => 'Kajiado', 'capital' => 'Kajiado'],
            ['code' => '035', 'name' => 'Kericho', 'capital' => 'Kericho'],
            ['code' => '036', 'name' => 'Bomet', 'capital' => 'Bomet'],
            ['code' => '037', 'name' => 'Kakamega', 'capital' => 'Kakamega'],
            ['code' => '038', 'name' => 'Vihiga', 'capital' => 'Vihiga'],
            ['code' => '039', 'name' => 'Bungoma', 'capital' => 'Bungoma'],
            ['code' => '040', 'name' => 'Busia', 'capital' => 'Busia'],
            ['code' => '041', 'name' => 'Siaya', 'capital' => 'Siaya'],
            ['code' => '042', 'name' => 'Kisumu', 'capital' => 'Kisumu City'],
            ['code' => '043', 'name' => 'Homa Bay', 'capital' => 'Homa Bay'],
            ['code' => '044', 'name' => 'Migori', 'capital' => 'Migori'],
            ['code' => '045', 'name' => 'Kisii', 'capital' => 'Kisii'],
            ['code' => '046', 'name' => 'Nyamira', 'capital' => 'Nyamira'],
            ['code' => '047', 'name' => 'Nairobi City', 'capital' => 'Nairobi'],
        ];

        // Store counties array in config or dedicated lookup dataset
        config(['bara.counties' => $counties]);
    }
}
