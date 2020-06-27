<?php declare(strict_types=1);

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.txt>.
 */

namespace Bitnix\Directus\Adapter;

use Bitnix\Directus\Adapter,
    Bitnix\Directus\ClientException,
    Bitnix\Directus\ResponseException,
    Bitnix\Directus\Token,
    GuzzleHttp\Client,
    GuzzleHttp\Promise\PromiseInterface as Promise;

/**
 * ...
 *
 * @version 0.1.0
 */
final class GuzzleAdapter implements Adapter {

    private const OPTIONS = [
        'allow_redirects' => true,
        'decode_content'  => true,
        'http_errors'     => false
    ];

    /**
     * @var Client
     */
    private Client $guzzle;

    /**
     * @var callable
     */
    private static $ok = null;

    /**
     * @var callable
     */
    private static $error = null;

    /**
     * @param Client $guzzle
     */
    public function __construct(Client $guzzle) {
        $this->guzzle = $guzzle;

        if (null === self::$ok) {

            self::$ok = function($response) {
                $status = $response->getStatusCode();

                if (204 === $status) {
                    return [];
                }

                $body = \json_decode(
                    $response->getBody()->getContents(),
                    true,
                    512,
                    \JSON_THROW_ON_ERROR
                );

                if ($status >= 400) {
                    $error = $body['error'];
                    throw new ResponseException($status, $error['code'], $error['message']);
                }

                return $body;
            };

            self::$error = function($x) {
                if (!($x instanceof ClientException)) {
                    $x = new ClientException($x->getMessage(), $x->getCode());
                }
                throw $x;
            };
        }
    }

    /**
     * @param Token $token
     * @param string $uri
     * @param array $params
     * @return Promise
     */
    public function get(Token $token, string $uri, array $params = []) : Promise {
        return $this->guzzle->requestAsync('GET', $uri, self::OPTIONS + [
            'headers' => ['Authorization' => 'Bearer ' . $token->value()],
            'query'   => $params
        ])->then(self::$ok, self::$error);
    }

    /**
     * @param null|Token $token
     * @param string $uri
     * @param array $payload
     * @param array $params
     * @return Promise
     */
    public function post(?Token $token, string $uri, array $payload = [], array $params = []) : Promise {
        $headers = $token ? ['Authorization' => 'Bearer ' . $token->value()] : [];
        return $this->guzzle->requestAsync('POST', $uri, self::OPTIONS + [
            'headers' => $headers,
            'query'   => $params,
            'json'    => $payload
        ])->then(self::$ok, self::$error);
    }

    /**
     * @param Token $token
     * @param string $uri
     * @param array $payload
     * @param array $params
     * @return Promise
     */
    public function patch(Token $token, string $uri, array $payload = [], array $params = []) : Promise {
        return $this->guzzle->requestAsync('PATCH', $uri, self::OPTIONS + [
            'headers' => ['Authorization' => 'Bearer ' . $token->value()],
            'query'   => $params,
            'json'    => $payload
        ])->then(self::$ok, self::$error);
    }

    /**
     * @param Token $token
     * @param string $uri
     * @return Promise
     */
    public function delete(Token $token, string $uri) : Promise {
        return $this->guzzle->requestAsync('DELETE', $uri, self::OPTIONS + [
            'headers' => ['Authorization' => 'Bearer ' . $token->value()]
        ])->then(self::$ok, self::$error);
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
