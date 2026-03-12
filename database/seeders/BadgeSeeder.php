<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $badges = [
            [
                'name' => 'identity_verified',
                'description' => 'User identity has been officially verified with documents.',
                'icon_path' => 'badges/verified.png'
            ],
            [
                'name' => 'expert',
                'description' => 'Completed more than 20 successful transports.',
                'icon_path' => 'badges/expert.png'
            ],
            [
                'name' => 'super_transporter',
                'description' => 'Elite transporter with high rating and volume.',
                'icon_path' => 'badges/super.png'
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['name' => $badge['name']], $badge);
        }
    }
}
