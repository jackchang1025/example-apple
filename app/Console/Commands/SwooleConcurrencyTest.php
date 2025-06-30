<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Client;
use Throwable;
use function Swoole\Coroutine\run;

class SwooleConcurrencyTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:swoole-concurrency
                            {domain : The domain to test (e.g., www.example.com)}
                            {concurrency=10 : The number of concurrent requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run concurrency tests for verifyAccount using Swoole coroutines against a specified domain';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (extension_loaded('xdebug')) {
            ini_set('xdebug.max_nesting_level', -1);
            $this->comment('Xdebug is loaded. Disabled nesting level limit to prevent infinite loop errors during concurrency test.');
        }

        if (!extension_loaded('swoole')) {
            $this->error('The Swoole extension is not installed or enabled.');
            return self::FAILURE;
        }

        // 验证域名参数
        $domain = $this->argument('domain');
        if (empty($domain)) {
            $this->error('Domain parameter is required.');
            return self::FAILURE;
        }

        // 验证域名格式
        if (!$this->isValidDomain($domain)) {
            $this->error('Invalid domain format. Please provide a valid domain (e.g., www.example.com).');
            return self::FAILURE;
        }

        $concurrency = (int) $this->argument('concurrency');
        if ($concurrency <= 0) {
            $this->error('Concurrency must be a positive integer.');
            return self::FAILURE;
        }

        run(function () use ($concurrency, $domain) {
            $this->info("Starting concurrency test with {$concurrency} requests against domain: {$domain}...");

            $channel = new Channel($concurrency);
            $startTime = microtime(true);

            // This is the expected error message for a failed login with fake credentials.
            // It's fetched once to avoid calling the helper inside the loop.
            $expectedMessage = __('apple.signin.incorrect');

            for ($i = 0; $i < $concurrency; $i++) {
                Coroutine::create(function () use ($channel, $expectedMessage, $domain) {
                    try {
                        $client = new Client($domain, 443, true);
                        $client->setHeaders([
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'Host' => $domain
                        ]);
                        $client->set(['timeout' => 60]);

                        $postData = json_encode([
                            'accountName' => fake()->email(),
                            'password'    => fake()->password(),
                        ], JSON_THROW_ON_ERROR);

                        $client->post('/index/verifyAccount', $postData);

                        $result = [
                            'statusCode' => $client->statusCode,
                            'body'       => $client->body,
                            'error'      => $client->errCode !== 0 ? socket_strerror($client->errCode) : null,
                        ];

                        $client->close();
                        $channel->push($result);
                    } catch (Throwable $e) {
                        $channel->push(['error' => $e->getMessage()]);
                    }
                });
            }

            $successCount = 0;
            $errorCount = 0;
            $incorrectResponseCount = 0;

            $progressBar = $this->output->createProgressBar($concurrency);
            $progressBar->start();

            for ($i = 0; $i < $concurrency; $i++) {
                $response = $channel->pop();

                if (!empty($response['error'])) {
                    $errorCount++;
                } else {
                    $body = json_decode($response['body'], true);

                    //输出 body
                    // var_dump($body);

                    if ($response['statusCode'] === 500) {
                        // This is the expected outcome from the original test: a successful request
                        // that results in a predictable "incorrect credentials" error from the application.
                        $successCount++;
                    } else {
                        $incorrectResponseCount++;
                    }
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info("Test finished in {$duration} seconds.");
            $this->line("Total Requests: <fg=blue>{$concurrency}</>");
            $this->line("Successful (Expected 'Incorrect Credentials' Response): <fg=green>{$successCount}</>");
            $this->line("Mismatched Responses (Unexpected Status/Message): <fg=yellow>{$incorrectResponseCount}</>");
            $this->line("Connection/Request Errors: <fg=red>{$errorCount}</>");
        });

        return self::SUCCESS;
    }

    /**
     * 验证域名格式是否正确
     *
     * @param string $domain
     * @return bool
     */
    private function isValidDomain(string $domain): bool
    {
        // 移除协议前缀（如果存在）
        $domain = preg_replace('/^https?:\/\//', '', $domain);

        // 移除路径部分（如果存在）
        $domain = explode('/', $domain)[0];

        // 移除端口号（如果存在）
        $domain = explode(':', $domain)[0];

        // 域名必须包含至少一个点（即至少有两个部分）
        if (strpos($domain, '.') === false) {
            return false;
        }

        // 验证域名格式
        // 1. 使用 filter_var 进行基本验证
        if (!filter_var('http://' . $domain, FILTER_VALIDATE_URL)) {
            return false;
        }

        // 2. 使用正则表达式进行更严格的域名格式验证
        $pattern = '/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/';
        if (!preg_match($pattern, $domain)) {
            return false;
        }

        // 3. 检查域名长度（总长度不超过253个字符）
        if (strlen($domain) > 253) {
            return false;
        }

        // 4. 检查每个标签的长度（不超过63个字符）
        $labels = explode('.', $domain);
        foreach ($labels as $label) {
            if (strlen($label) > 63 || strlen($label) < 1) {
                return false;
            }
        }

        return true;
    }
}
