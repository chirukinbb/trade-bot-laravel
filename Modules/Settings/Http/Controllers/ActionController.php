<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

class ActionController extends Controller
{
    public function save(Request $request)
    {
        $this->setEnv('SECONDS_DELAY',$request->delay);
        $this->setEnv('TARGET_SPREAD',$request->spread);

        exec('sudo systemctl stop supervisord.service');
        exec('sudo systemctl start supervisord.service');

        return redirect()->back();
    }

    private function setEnv(string $name, int $variable)
    {
        // Читаем файл в строку
        $content = file_get_contents(base_path('.env'));
// Проверяем, удалось ли прочитать файл
        if ($content !== false) {
            $strings = [];
            // Разбиваем строку на массив строк по символу переноса строки
            $lines = explode("\n", $content);
            // Выводим каждую строку на экран
            foreach ($lines as $line) {
                if (str_contains($line,$name)){
                    $strings[] = $name.'='.$variable;
                }else{
                    $strings[] = $line;
                }
            }
            file_put_contents(base_path('.env'),implode(PHP_EOL,$strings));
        }
    }
}
