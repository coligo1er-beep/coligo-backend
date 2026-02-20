<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Shipment;
use App\Models\Route;
use App\Models\RouteWaypoint;
use App\Models\MatchModel;
use App\Models\MatchMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // --- 1. USERS ---
        $password = Hash::make('password123');
        
        // Specific users
        $users = [
            ['first_name' => 'Jean', 'last_name' => 'Expéditeur', 'email' => 'jean@coligo.com', 'user_type' => 'sender'],
            ['first_name' => 'Marc', 'last_name' => 'Transport', 'email' => 'marc@coligo.com', 'user_type' => 'transporter'],
            ['first_name' => 'Sophie', 'last_name' => 'Polyvalente', 'email' => 'sophie@coligo.com', 'user_type' => 'both'],
            ['first_name' => 'Rochnel', 'last_name' => 'Tegomo', 'email' => 'rochneltegomo@gmail.com', 'user_type' => 'both'],
            ['first_name' => 'Alice', 'last_name' => 'Cameroon', 'email' => 'alice@coligo.cm', 'user_type' => 'sender'],
            ['first_name' => 'Bob', 'last_name' => 'Driver', 'email' => 'bob@coligo.cm', 'user_type' => 'transporter'],
        ];

        $createdUsers = [];
        foreach ($users as $u) {
            $createdUsers[] = User::updateOrCreate(
                ['email' => $u['email']],
                array_merge($u, [
                    'phone' => '+336' . rand(10000000, 99999999),
                    'password' => $password,
                    'is_verified' => true,
                    'verification_score' => rand(75, 100),
                ])
            );
        }

        // Add 10 more random users
        for ($i = 0; $i < 10; $i++) {
            $createdUsers[] = User::create([
                'first_name' => 'User' . $i,
                'last_name' => 'Test',
                'email' => "user$i@example.com",
                'phone' => '+2376' . rand(10000000, 99999999),
                'password' => $password,
                'user_type' => ['sender', 'transporter', 'both'][rand(0, 2)],
                'is_verified' => rand(0, 1),
            ]);
        }

        // --- 2. SHIPMENTS (20 items) ---
        $cities = [
            ['city' => 'Paris', 'country' => 'France', 'lat' => 48.8566, 'lng' => 2.3522],
            ['city' => 'Lyon', 'country' => 'France', 'lat' => 45.7640, 'lng' => 4.8357],
            ['city' => 'Marseille', 'country' => 'France', 'lat' => 43.2965, 'lng' => 5.3698],
            ['city' => 'Douala', 'country' => 'Cameroun', 'lat' => 4.0511, 'lng' => 9.7679],
            ['city' => 'Yaoundé', 'country' => 'Cameroun', 'lat' => 3.8480, 'lng' => 11.5021],
            ['city' => 'Bafoussam', 'country' => 'Cameroun', 'lat' => 5.4777, 'lng' => 10.4176],
        ];

        $shipments = [];
        for ($i = 0; $i < 20; $i++) {
            $from = $cities[array_rand($cities)];
            $to = $cities[array_rand($cities)];
            while($to['city'] == $from['city']) $to = $cities[array_rand($cities)];

            $user = collect($createdUsers)->whereIn('user_type', ['sender', 'both'])->random();

            $shipments[] = Shipment::create([
                'user_id' => $user->id,
                'title' => 'Envoi ' . Str::random(5) . ' de ' . $from['city'] . ' vers ' . $to['city'],
                'description' => 'Description de test pour un envoi de colis urgent.',
                'weight' => rand(1, 50),
                'pickup_address' => 'Quartier central, ' . $from['city'],
                'pickup_city' => $from['city'],
                'pickup_country' => $from['country'],
                'pickup_latitude' => $from['lat'] + (rand(-100, 100) / 1000),
                'pickup_longitude' => $from['lng'] + (rand(-100, 100) / 1000),
                'pickup_date_from' => now()->addDays(rand(1, 10)),
                'pickup_date_to' => now()->addDays(rand(11, 15)),
                'delivery_address' => 'Avenue de la République, ' . $to['city'],
                'delivery_city' => $to['city'],
                'delivery_country' => $to['country'],
                'delivery_latitude' => $to['lat'] + (rand(-100, 100) / 1000),
                'delivery_longitude' => $to['lng'] + (rand(-100, 100) / 1000),
                'delivery_date_limit' => now()->addDays(rand(16, 20)),
                'budget_min' => rand(10, 50),
                'budget_max' => rand(60, 200),
                'status' => 'published',
                'published_at' => now(),
            ]);
        }

        // --- 3. ROUTES (15 items) ---
        $routes = [];
        for ($i = 0; $i < 15; $i++) {
            $from = $cities[array_rand($cities)];
            $to = $cities[array_rand($cities)];
            while($to['city'] == $from['city']) $to = $cities[array_rand($cities)];

            $user = collect($createdUsers)->whereIn('user_type', ['transporter', 'both'])->random();

            $routes[] = Route::create([
                'user_id' => $user->id,
                'title' => 'Trajet ' . $from['city'] . ' - ' . $to['city'] . ' (' . ($i+1) . ')',
                'departure_address' => 'Gare de ' . $from['city'],
                'departure_city' => $from['city'],
                'departure_country' => $from['country'],
                'departure_latitude' => $from['lat'],
                'departure_longitude' => $from['lng'],
                'departure_date_from' => now()->addDays(rand(1, 5)),
                'departure_date_to' => now()->addDays(rand(6, 7)),
                'arrival_address' => 'Centre ville, ' . $to['city'],
                'arrival_city' => $to['city'],
                'arrival_country' => $to['country'],
                'arrival_latitude' => $to['lat'],
                'arrival_longitude' => $to['lng'],
                'arrival_date_from' => now()->addDays(rand(8, 9)),
                'arrival_date_to' => now()->addDays(rand(10, 11)),
                'total_capacity_kg' => rand(100, 1000),
                'available_capacity_kg' => rand(50, 1000),
                'vehicle_type' => ['car', 'van', 'truck', 'airplane'][rand(0, 3)],
                'price_per_kg' => rand(1, 10),
                'status' => 'published',
                'published_at' => now(),
            ]);
        }

        // --- 4. MATCHES & MESSAGES ---
        // Create 15 matches
        for ($i = 0; $i < 15; $i++) {
            $shipment = collect($shipments)->random();
            $route = collect($routes)->random();
            
            $match = MatchModel::create([
                'shipment_id' => $shipment->id,
                'route_id' => $route->id,
                'transporter_id' => $route->user_id,
                'sender_id' => $shipment->user_id,
                'status' => ['pending', 'accepted', 'completed'][rand(0, 2)],
                'proposed_price' => rand(30, 150),
                'pickup_datetime' => $shipment->pickup_date_from,
                'delivery_datetime' => $shipment->delivery_date_limit->subDays(1),
                'matching_score' => rand(40, 95),
                'transporter_message' => 'Je suis disponible pour ce trajet !',
            ]);

            // Add some messages
            for ($j = 0; $j < rand(2, 5); $j++) {
                MatchMessage::create([
                    'match_id' => $match->id,
                    'sender_id' => ($j % 2 == 0) ? $match->transporter_id : $match->sender_id,
                    'message' => 'Message de test numéro ' . ($j + 1) . ' pour la coordination.',
                    'message_type' => 'text',
                ]);
            }
        }
    }
}
