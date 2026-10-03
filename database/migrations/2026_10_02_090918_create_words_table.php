<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('words', function (Blueprint $table) {
            $table->id();

            // Outer JSON fields
            $table->boolean('found')->default(false);
            $table->unsignedBigInteger('_version');
            $table->string('_type');
            $table->string('_id');

            // _source fields
            $table->string('id_text');
            $table->string('pos_text')->nullable();
            $table->text('base_text')->nullable();
            $table->string('Created By')->nullable();
            $table->string('refid_text');
            $table->string('ukpron_text')->nullable();

            // Unix timestamp in milliseconds - preserved exactly
            $table->unsignedBigInteger('Created Date')->nullable();
            $table->unsignedBigInteger('Modified Date')->nullable();

            $table->string('cefr_text_text')->nullable();
            $table->integer('culture_number')->nullable();
            $table->string('guideword_text')->nullable();

            $table->text('definition_text')->nullable();
            $table->text('searchterms_text')->nullable();
            $table->string('audiofilename_text')->nullable();

            $table->string('cefr_custom_evp_cefr')->nullable();

            $table->longText('learnerexamples_text')->nullable();

            $table->string('pos_custom_evp_l_pos')->nullable();
            $table->string('l_category_custom_evp_l_category')->nullable();

            $table->json('l_grammars_list_custom_evp_l_grammar')->nullable();

            $table->string('l_suffix_custom_evp_l_suffix')->nullable();

            $table->string('l_topic_text_text')->nullable();

            $table->json('l_topics_list_custom_evp_l_topic')->nullable();

            $table->text('hw_text')->nullable();

            // Useful indexes for future vocabulary/review queries
            $table->index('id_text');
            $table->index('refid_text');
            $table->index('base_text');
            $table->index('cefr_text_text');
            $table->index('pos_text');
            $table->index('_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('words');
    }
};