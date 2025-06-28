<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;


it('verifyAccountConcurrency', function (int $concurrencyLevel) {


    $responses = Http::pool(function (Pool $pool) use ($concurrencyLevel) {

        $array = [];
        for ($i = 0; $i < $concurrencyLevel; $i++) {
            $array[] = $pool->post('http://127.0.0.1/index/verifyAccount', [
                'accountName' => fake()->email(),
                'password'    => fake()->password(), // 使用您提供的密码
            ]);
        }

        return $array;
    });

    $errorCount = 0;
    foreach ($responses as $response) {

        if(!$response instanceof \Illuminate\Http\Client\Response){
            $errorCount++;
            echo get_class($response).PHP_EOL;
            continue;
        }

        if($response->json('code') != 500){
            $errorCount++;
            echo $response->json('code').PHP_EOL;
            continue;
        }

        if($response->json('message') != "{\n  \"serviceErrors\" : [ {\n    \"code\" : \"-20101\",\n    \"message\" : \"Check the account information you entered and try again.\",\n    \"suppressDismissal\" : false\n  } ]\n}"){
            $errorCount++;

            echo $response->json('message').PHP_EOL;
            continue;
        }
    }

    expect($errorCount)->toBe(0);

})->with([
    '50个并发请求' => [50],
])->group('concurrency-guzzle');
