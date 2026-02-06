<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoadDistanceKmToDistancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distances', function (Blueprint $table) {
            $table->decimal('road_distance_km', 10, 3)->nullable()->after('distance_km');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('distances', function (Blueprint $table) {
            //
        });
    }
}
