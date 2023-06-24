<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Entities\Setting;
use Modules\Settings\Http\Controllers\ActionController;

class SettingsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fields = (new ActionController())->fields;

        foreach ($fields as $key=>$value){
            if (isset($value['title'])) {
                foreach ($value['fields'] as $name=>$field) {
                    Setting::updateOrCreate(['value' => env($name)], ['name' => $name]);
                }
            }else{
                echo $key.PHP_EOL;
                Setting::updateOrCreate(['value' => env($key)], ['name' => $key]);
            }
        }
    }
}
