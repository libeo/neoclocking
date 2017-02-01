<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use NeoClocking\Models\UserRole;

class AddUsersProjectsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('priority');
            $table->timestamps();
        });

        Eloquent::unguard();

        UserRole::create([
            'code'     => UserRole::CODE_MANAGER,
            'name'     => 'Gestionnaire',
            'priority' => 100,
        ]);
        UserRole::create([
            'code'     => UserRole::CODE_ASSISTANT,
            'name'     => 'Assistant',
            'priority' => 50,
        ]);
        UserRole::create([
            'code'     => UserRole::CODE_MEMBER,
            'name'     => 'Membre',
            'priority' => 1,
        ]);

        Schema::create('user_project_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');

            $table->integer('project_id')->unsigned();
            $table->foreign('project_id')->references('id')->on('projects');

            $table->integer('user_role_id')->unsigned();
            $table->foreign('user_role_id')->references('id')->on('user_roles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_project_roles');
        Schema::drop('user_roles');
    }

}
