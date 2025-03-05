<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        DB::table("order_statuses")->insert([
            "status" => "order",
            "types" => json_encode(["chef","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "pending",
            "types" => json_encode(["driver","chef","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "approve",
            "types" => json_encode(["driver","chef","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "ready to move	",
            "types" => json_encode(["chef","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "preparing",
            "types" => json_encode(["chef","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "cancel",
            "types" => json_encode(["chef","customer","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "picked up",
            "types" => json_encode(["driver","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "on the way",
            "types" => json_encode(["driver","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "delivered",
            "types" => json_encode(["driver","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
        DB::table("order_statuses")->insert([
            "status" => "failed",
            "types" => json_encode(["chef","admin"]),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ]);
    }
}
