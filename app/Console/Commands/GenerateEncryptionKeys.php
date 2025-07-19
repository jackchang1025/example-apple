<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use phpseclib3\Crypt\RSA;

class GenerateEncryptionKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-keys {--path= : The path where the keys should be stored.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate RSA public and private keys using phpseclib for fingerprint encryption';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating RSA 4096-bit key pair...');

        $privateKey = RSA::createKey(4096);
        $publicKey = $privateKey->getPublicKey();

        // 如果提供了 --path 选项，则使用该路径，否则使用默认的 config/encryption 路径
        $basePath = $this->option('path') ? base_path($this->option('path')) : config_path('encryption');

        if (!File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        File::put($basePath . '/private.key', $privateKey);
        File::put($basePath . '/public.key', $publicKey);

        $this->info('RSA public and private keys have been generated successfully.');
        $this->info("Keys stored in: " . realpath($basePath));

        return 0;
    }
}
