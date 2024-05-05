<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ufee\Amo\Oauthapi;

class amoCRM extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amo:issue-token {--client_secret=} {--code=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get oauth token from AmoCRM Cabinet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $arg_client_secret = $this->option('client_secret');
        $arg_code = $this->option('code');
        $data = config('amocrm.account');

        if (!isset($data) || !isset($data['client_id'])) {
            $this->output->error(['Set up your configuration file', '[config/amocrm.php]']);
            return 0;
        }

        $account = [
            'domain' => '',
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => '',
        ];

        foreach ($account as $key=>$val) {
            $account[$key] = isset($data[$key]) ? $data[$key] : $val;
        }

        $account['client_secret'] = $arg_client_secret ?: $account['client_secret'];
        $amo = Oauthapi::setInstance($account);
        $oauth = $amo->getOauth();

        if ($oauth['access_token'] == '') {
            if (!isset($arg_code)) {
                $this->output->error(['You should provide --code for this command']);
                return 0;
            }

            try {
                $amo->fetchAccessToken($arg_code);
                $this->getOutput()->writeln('Token has been issued.');
                return 1;
            } catch (\Exception $e) {
                $this->getOutput()->error($e->getMessage());
                return 0;
            }
        }

        $this->getOutput()->writeln('Token has been fetch from Cache.');
        return 1;
    }
}
