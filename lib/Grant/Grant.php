<?php
namespace Ridibooks\OAuth2Resource\Grant;

use InvalidArgumentException;
use Ridibooks\OAuth2Resource\Constant\GrantTypeConstant;
use Ridibooks\OAuth2Resource\Grant\DataTransferObject\AuthorizationServerInfo;
use Ridibooks\OAuth2Resource\Grant\DataTransferObject\ClientInfo;
use Ridibooks\OAuth2Resource\Grant\DataTransferObject\TokenData;
use Ridibooks\OAuth2Resource\Grant\Exception\InvalidResponseException;
use Ridibooks\OAuth2Resource\Grant\Exception\OAuthFailureException;

class Grant
{
    /**
     * @var ClientInfo
     */
    private $client_info;

    /**
     * @var AuthorizationServerInfo
     */
    private $auth_server_info;

    /**
     * BaseGrant constructor.
     * @param ClientInfo $client_info
     * @param AuthorizationServerInfo $auth_server_info
     */
    public function __construct(ClientInfo $client_info, AuthorizationServerInfo $auth_server_info)
    {
        $this->client_info = $client_info;
        $this->auth_server_info = $auth_server_info;
    }

    /**
     * @param string $state
     * @return string
     */
    public function authorize(string $state): string
    {
        $query = http_build_query([
            'client_id' => $this->client_info->getClientId(),
            'redirect_uri' => $this->client_info->getRedirectUri(),
            'scope' => $this->client_info->getScope(),
            'state' => $state,
            'response_type' => 'code',
        ]);

        $authorize_url = $this->auth_server_info->getAuthorizationUrl() . '?' . $query;
        return $authorize_url;
    }


    /**
     * @param string $code
     * @return TokenData
     * @throws InvalidResponseException
     * @throws OAuthFailureException
     */
    public function code(string $code): TokenData
    {
        $data = $this->getDefaultTokenData(GrantTypeConstant::AUTHORIZATION_CODE);
        $data['code'] = $code;
        $data['redirect_uri'] = $this->client_info->getRedirectUri();
        return $this->requestToken($data);
    }

    /**
     * @param $refresh_token
     * @return TokenData
     * @throws InvalidResponseException
     * @throws OAuthFailureException
     */
    public function refresh($refresh_token): TokenData
    {
        $data = $this->getDefaultTokenData(GrantTypeConstant::REFRESH_TOKEN);
        $data['refresh_token'] = $refresh_token;
        return $this->requestToken($data);
    }

    /**
     * @param string $grant_type
     * @return array
     */
    private function getDefaultTokenData(string $grant_type): array
    {
        return [
            'client_id' => $this->client_info->getClientId(),
            'client_secret' => $this->client_info->getClientSecret(),
            'grant_type' => $grant_type,
        ];
    }

    /**
     * @param array $data
     * @return TokenData
     * @throws InvalidResponseException
     * @throws OAuthFailureException
     */
    private function requestToken(array $data): TokenData
    {
        try {
            return TokenData::fromDict($this->request($this->auth_server_info->getTokenUrl(), $data));
        } catch (InvalidArgumentException $e) {
            throw new InvalidResponseException();
        }
    }

    /**
     * @param string $url
     * @param array $data
     * @return array
     * @throws OAuthFailureException
     * @throws InvalidResponseException
     */
    private function request(string $url, array $data): array
    {
        $headers = ['Accept: application/json'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Conn timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Read timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        // TODO: 개발이 완료 되면 해당 옵션 제거
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $body = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new InvalidResponseException('EN: ' . curl_errno($ch) . 'EM: ' . curl_error($ch));
        }

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->processException($http_status, $body);

        $result = json_decode($body, true);
        if ($result === null) {
            throw new InvalidResponseException();
        }

        return $result;
    }

    /**
     * @param int $http_status
     * @param string $body
     * @throws InvalidResponseException
     * @throws OAuthFailureException
     */
    private function processException(int $http_status, string $body)
    {
        if ($http_status === 200) {
            return;
        }

        $error_res = json_decode($body, true);
        if ($error_res === null) {
            throw new InvalidResponseException($body);
        }

        $error = $error_res['error'] !== null ? $error_res['error'] : 'status code: ' . $http_status;
        $error_description = $error_res['error_description'] !== null ? $error_res['error_description'] : '';
        $error_uri = $error_res['error_uri'] !== null ? $error_res['error_uri'] : '';

        throw new OAuthFailureException($error, $error_description, $error_uri);
    }
}