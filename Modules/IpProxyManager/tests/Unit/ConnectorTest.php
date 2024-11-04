<?php

use Modules\IpProxyManager\Service\HuaSheng\Dto\ExtractDto;
use Modules\IpProxyManager\Service\HuaSheng\HuaShengConnector;
use Modules\IpProxyManager\Service\HuaSheng\Requests\ExtractRequest;
use Psr\Log\LoggerInterface;
use Saloon\Enums\Method;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

trait HasLogger
{
    protected ?LoggerInterface $logger = null;

    public function withLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    public function bootHasLogger(PendingRequest $pendingRequest): void
    {
        $pendingRequest->getConnector()->middleware()->onRequest($this->defaultRequestMiddle());
        $pendingRequest->getConnector()->middleware()->onResponse($this->defaultResponseMiddle());
    }

    protected function defaultRequestMiddle(): \Closure
    {
        return function (PendingRequest $request) {
            $this->getLogger()?->debug('request', [
                'method'  => $request->getMethod(),
                'uri'     => (string)$request->getUri(),
                'headers' => $request->headers(),
                'body'    => (string)$request->body(),
            ]);

            return $request;
        };
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    protected function defaultResponseMiddle(): \Closure
    {
        return function (Response $response) {
            $this->getLogger()?->debug('response', [
                'status'  => $response->status(),
                'headers' => $response->headers(),
                'body'    => $response->body(),
            ]);

            return $response;
        };
    }
}

class TestConnector extends Connector
{
    use HasLogger;

    public function resolveBaseUrl(): string
    {
        return 'https://www.example.com';
    }
}

class TestRequest extends \Saloon\Http\Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/test';
    }
}

beforeEach(function () {

    $this->connector = Mockery::mock(HuaShengConnector::class)->makePartial();

    $this->log = Mockery::mock(LoggerInterface::class);
});


test('it can be initialised8', function () {

    $this->log->shouldReceive('debug')->times(6);

    $this->connector->withLogger($this->log);

    MockClient::global([
        ExtractRequest::class => MockResponse::make(
            body: [
                'status' => '0',
                'list'   => [
                    [],
                    [],
                ],
            ],
            status: 200
        ),
    ]);

    $dto = Mockery::mock(ExtractDto::class);
    $dto->shouldReceive('get')
        ->with('session')
        ->times(1)
        ->andReturn('session');

    $dto->shouldReceive('toQueryParameters')
        ->times(1)
        ->andReturn([]);

    //toQueryParameters
    $request = new ExtractRequest($dto);

    $this->connector->send($request);
    $this->connector->send($request);
    $this->connector->send($request);

});


test('it can be initialised9', function () {

    $this->log->shouldReceive('debug')->times(12);

    $this->connector = new TestConnector();
    $this->connector->withLogger($this->log);


    $mockClient = new MockClient([
        TestRequest::class => MockResponse::make([], 200),
    ]);

    $this->connector->withMockClient($mockClient);

    //toQueryParameters
    $request = new TestRequest();

    $this->connector->send($request);//2
    $this->connector->send($request);//4
    $this->connector->send($request);//2 +

});
