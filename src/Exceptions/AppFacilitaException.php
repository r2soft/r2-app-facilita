<?php

declare(strict_types=1);

namespace R2Soft\AppFacilita\Exceptions;

class AppFacilitaException extends \RuntimeException
{
    protected ?int $statusCode;
    protected ?string $endpoint;
    protected ?string $method;
    protected ?string $requestId;
    protected $responseBody;

    public function __construct(
        string $message,
        ?int $statusCode = null,
        ?string $endpoint = null,
        ?string $method = null,
        ?string $requestId = null,
        $responseBody = null
    ) {
        parent::__construct($message, $statusCode ?? 0);
        $this->statusCode = $statusCode;
        $this->endpoint = $endpoint;
        $this->method = $method;
        $this->requestId = $requestId;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
