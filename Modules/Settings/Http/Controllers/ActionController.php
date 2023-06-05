<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

class ActionController extends Controller
{
    public function save(Request $request)
    {
        $this->setEnv($request->all());

        (new Process(['sudo systemctl stop supervisord.service']))->start();
        (new Process(['sudo systemctl start supervisord.service']))->start();

        return redirect()->back();
    }

    private function setEnv(array $envs)
    {
        $content = file_get_contents(base_path('.env'));
        $envFields = array_merge(array_keys($envs),['IS_TRADING_ENABLED']);

        if ($content !== false) {
            $strings = [];
            // Разбиваем строку на массив строк по символу переноса строки
            $lines = explode("\n", $content);
            // Выводим каждую строку на экран
            foreach ($lines as $line) {
                $line = explode('=',$line);

                if (in_array($line[0],$envFields)){
                    $strings[] = $line[0].'="'.$envs[$line[0]].'"';
                }else{
                    $strings[] = implode('=',$line);
                }
            }
            file_put_contents(base_path('.env'),implode(PHP_EOL,$strings));
        }
    }
}
