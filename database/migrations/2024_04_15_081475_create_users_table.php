<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('role_id')->unsigned()->nullable()->index('users_role_id_foreign');
			$table->string('name', 256)->nullable();
			$table->string('email', 256)->unique();
			$table->string('avatar', 256)->nullable();
			$table->dateTime('email_verified_at')->nullable();
			$table->string('password', 256)->nullable();
			$table->string('remember_token', 256)->nullable();
			$table->text('settings')->nullable();
			$table->timestamps();
		});

		Schema::table('users', function (Blueprint $table) {
			$table->foreign('role_id')->references('id')->on('roles')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}
