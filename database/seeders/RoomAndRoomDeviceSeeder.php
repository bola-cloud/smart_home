<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\RoomDevice;

class RoomAndRoomDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Data from the provided document
        $rooms = [
            [
                'name' => 'غرف النوم',
                'devices' => [
                    ['name' => 'Mini R2', 'quantity' => 2, 'unit_price' => 120],
                    ['name' => 'Dual R3', 'quantity' => 1, 'unit_price' => 200],
                    ['name' => 'THR316D', 'quantity' => 1, 'unit_price' => 200],
                    ['name' => 'Touch 3 line', 'quantity' => 1, 'unit_price' => 210],
                    ['name' => 'Infrared device', 'quantity' => 2, 'unit_price' => 200],
                ]
            ],
            [
                'name' => 'الريسيبشن',
                'devices' => [
                    ['name' => 'SPM-MAIN', 'quantity' => 1, 'unit_price' => 450],
                    ['name' => 'SPM -4RELAY', 'quantity' => 3, 'unit_price' => 550],
                    ['name' => 'THR316D', 'quantity' => 2, 'unit_price' => 200],
                    ['name' => 'Touch 3 line', 'quantity' => 5, 'unit_price' => 210],
                    ['name' => 'Infrared device', 'quantity' => 5, 'unit_price' => 200],
                    ['name' => 'Dual R3', 'quantity' => 2, 'unit_price' => 200],
                ]
            ],
            [
                'name' => 'دورات المياه',
                'devices' => [
                    ['name' => 'Dual R3', 'quantity' => 1, 'unit_price' => 200],
                ]
            ],
            [
                'name' => 'المطبخ',
                'devices' => [
                    ['name' => 'Mini R2', 'quantity' => 2, 'unit_price' => 120],
                    ['name' => 'Dual R3', 'quantity' => 1, 'unit_price' => 200],
                    ['name' => 'Touch 3 line', 'quantity' => 1, 'unit_price' => 210],
                    ['name' => 'B02-F-A60', 'quantity' => 1, 'unit_price' => 150],
                ]
            ],
            [
                'name' => 'الحديقة',
                'devices' => [
                    ['name' => 'Dual R3', 'quantity' => 1, 'unit_price' => 200],
                    ['name' => 'Infrared device', 'quantity' => 2, 'unit_price' => 200],
                ]
            ],
            [
                'name' => 'الأبواب',
                'devices' => [
                    ['name' => 'Gateways', 'quantity' => 3, 'unit_price' => 1000],
                ]
            ],
            [
                'name' => 'Access Points',
                'devices' => [
                    ['name' => 'Access for transfer internet', 'quantity' => 15, 'unit_price' => 500],
                ]
            ],
        ];

        foreach ($rooms as $roomData) {
            // Create the room
            $room = Room::create(['name' => $roomData['name']]);

            // Create devices for the room
            foreach ($roomData['devices'] as $deviceData) {
                RoomDevice::create([
                    'room_id' => $room->id,
                    'name' => $deviceData['name'],
                    'quantity' => $deviceData['quantity'],
                    'unit_price' => $deviceData['unit_price'],
                    'total_price' => $deviceData['quantity'] * $deviceData['unit_price'],
                ]);
            }
        }
    }
}
