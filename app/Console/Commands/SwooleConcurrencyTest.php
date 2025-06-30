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
    protected $signature = 'test:swoole-concurrency {concurrency=10 : The number of concurrent requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run concurrency tests for verifyAccount using Swoole coroutines';

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

        $concurrency = (int) $this->argument('concurrency');
        if ($concurrency <= 0) {
            $this->error('Concurrency must be a positive integer.');
            return self::FAILURE;
        }

        run(function () use ($concurrency) {
            $this->info("Starting concurrency test with {$concurrency} requests...");

            $channel = new Channel($concurrency);
            $startTime = microtime(true);

            // This is the expected error message for a failed login with fake credentials.
            // It's fetched once to avoid calling the helper inside the loop.
            $expectedMessage = __('apple.signin.incorrect');

            for ($i = 0; $i < $concurrency; $i++) {
                Coroutine::create(function () use ($channel, $expectedMessage) {
                    try {
                        $client = new Client('www.whtskk.cn', 443, true);
                        $client->setHeaders([
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'Host' => 'www.whtskk.cn'
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
}
