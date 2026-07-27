<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\GiaoBan\GiaoBanMetricTemplate;

class CreateGiaobanMetricTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('giaoban_metric_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('block_type', 20);
            $table->text('metrics');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach (GiaoBanMetricTemplate::SEED as $mau) {
            GiaoBanMetricTemplate::create([
                'name' => $mau['name'],
                'block_type' => $mau['block_type'],
                'sort_order' => $mau['sort_order'],
                'metrics' => json_encode($mau['metrics'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('giaoban_metric_templates');
    }
}
