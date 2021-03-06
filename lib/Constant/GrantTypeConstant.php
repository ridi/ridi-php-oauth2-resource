<?php declare(strict_types=1);
namespace Ridibooks\OAuth2\Constant;

class GrantTypeConstant
{
    const AUTHORIZATION_CODE = 'authorization_code';
    const IMPLICIT = 'implicit';
    const RESOURCE_OWNER_PASSWORD_CREDENTIALS = 'resource_owner_password_credentials';
    const CLIENT_CREDENTIALS = 'client_credentials';
    const REFRESH_TOKEN = 'refresh_token';
}
