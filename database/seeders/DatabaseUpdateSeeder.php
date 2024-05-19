<?php

use Illuminate\Database\Seeder;
use App\Traits\Seedable;

class DatabaseUpdateSeeder extends Seeder
{
    use Seedable;

    protected $seedersPath = __DIR__ . '/';

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /* ===== ORDER IS IMPORTANT ===== */
        /* ===== KEEP THE ORDER SAME ===== */
        // update voyager tables
        $this->seed('DataTypesTableSeeder');
        $this->seed('DataRowsTableSeeder');
        $this->seed('MenusTableSeeder');
        $this->seed('MenuItemsTableSeeder');
        $this->seed('PermissionsTableSeeder');
        $this->seed('PermissionRoleTableSeeder');
        $this->seed('SettingsTableSeeder');
    }
}
