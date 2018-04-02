<?php

namespace Ridibooks\OAuth2Resource\Symfony\Exception;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidRequestException extends HttpException
{
    public function __construct($message)
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }
}
