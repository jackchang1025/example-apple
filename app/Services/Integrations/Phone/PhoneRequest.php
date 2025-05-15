<?php

namespace App\Services\Integrations\Phone;

use Saloon\Enums\Method;
use Saloon\Http\SoloRequest;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\ServerException;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request as SaloonRequest;
use App\Services\Integrations\Phone\Exception\AttemptGetPhoneCodeException;
use Weijiajia\SaloonphpLogsPlugin\HasLogger;
use Weijiajia\SaloonphpLogsPlugin\Contracts\HasLoggerInterface;

class PhoneRequest extends SoloRequest implements HasLoggerInterface
{
    use AlwaysThrowOnErrors;
    use HasLogger;

    protected Method $method = Method::GET;

    public function __construct(public string $uri){

        if (!preg_match('/^https?:\/\//i', $uri)) {
            $this->uri = 'http://' . $uri;
        }
    }

    public static array $phoneHistory = [];

    public ?int $tries = 5;

    public function handleRetry(FatalRequestException|RequestException $exception, SaloonRequest $request): bool
    {
        return $exception instanceof FatalRequestException || $exception instanceof ServerException || $exception instanceof ForbiddenException || $exception instanceof RequestException;
    }

    public function resolveEndpoint(): string
    {
        return $this->uri;
    }

    public function code(): ?string
    {
        $response = $this->send();

        return $this->parse($response->body());
    }

    public function attemptMobileVerificationCode(int $attempts = 5): string
    {
        for ($i = 1; $i <= $attempts; $i++) {

            sleep($i * 5);

            try {

                if (!$code = $this->code()) {
                    continue;
                }

                if ((self::$phoneHistory[md5(string: $this->uri)] ?? null) === $code) {
                    continue;
                }

                self::$phoneHistory[md5(string: $this->uri)] = $code;
                return $code;

            } catch (\Exception $e) {
                continue;
            }
        }
        throw new AttemptGetPhoneCodeException('获取验证码失败');
    }

    public function parse(string $str): ?string
    {
        if (preg_match('/\b\d{6}\b/', $str, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
