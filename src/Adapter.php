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

namespace Bitnix\Directus;

use GuzzleHttp\Promise\PromiseInterface as Promise;

/**
 * @version 0.1.0
 */
interface Adapter {

    /**
     * @param Token $token
     * @param string $uri
     * @param array $params
     * @return Promise
     */
    public function get(Token $token, string $uri, array $params = []) : Promise;

    /**
     * @param null|Token $token
     * @param string $uri
     * @param array $payload
     * @param array $params
     * @return Promise
     */
    public function post(?Token $token, string $uri, array $payload = [], array $params = []) : Promise;

    /**
     * @param Token $token
     * @param string $uri
     * @param array $payload
     * @param array $params
     * @return Promise
     */
    public function patch(Token $token, string $uri, array $payload = [], array $params = []) : Promise;

    /**
     * @param Token $token
     * @param string $uri
     * @return Promise
     */
    public function delete(Token $token, string $uri) : Promise;

}