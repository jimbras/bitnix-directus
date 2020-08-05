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

use GuzzleHttp\Promise\PromiseInterface as Promise,
    GuzzleHttp\Promise\RejectedPromise;

/**
 * @version 0.1.0
 */
final class Client {

    /**
     * @var Adapter
     */
    private Adapter $client;

    /**
     * @var Settings
     */
    private Settings $config;

    /**
     * @var TokenStorage
     */
    private TokenStorage $tokens;

    /**
     * @param Adapter $client
     * @param Settings $config
     * @param TokenStorage $tokens
     */
    public function __construct(Adapter $client, Settings $config, TokenStorage $tokens) {
        $this->client = $client;
        $this->config = $config;
        $this->tokens = $tokens;
    }

    /**
     * @param string $endpoint
     * @return string
     */
    private function uri(string $endpoint) : string {
        return $this->config->uri()
            . '/'
            . $this->config->project()
            . $endpoint;
    }

    /**
     * @return Token
     * @throws ClientException
     */
    public function login() : Token {
        $token = $this->tokens->getToken($this->config->project());

        if (null === $token) {
            $promise = $this->client->post(
                null,
                $this->uri('/auth/authenticate'),
                $this->config->credentials()
            );
        } else if ($token->expiring()) {
            $promise = $this->client->post(
                null,
                $this->uri('/auth/refresh'),
                ['token' => $token->value()]
            );
        } else {
            return $token;
        }

        return $promise->then(function($response) {
            $token = new Token($response['data']['token']);
            $this->tokens->putToken($this->config->project(), $token);
            return $token;
        })->wait();
    }

    /**
     * @throws ClientException
     */
    public function logout() : void {
        $this->client->post(
            $this->tokens->getToken($this->config->project()),
            $this->uri('/auth/logout')
        )->then(function($res) {
            $this->tokens->removeToken($this->config->project());
        })->wait();
    }

    /**
     * @param string $collection
     * @param array $params
     * @return Promise
     */
    public function getItems(string $collection, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/items/' . $collection),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param string $collection
     * @param array $params
     * @return Promise
     */
    public function getItem(int $id, string $collection, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/items/' . $collection . '/' . $id),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param string $collection
     * @param array $payload
     * @param array $params
     * @return Promise
     */
    public function createItem(string $collection, array $payload, array $params = []) : Promise {
        try {
            return $this->client->post(
                $this->login(),
                $this->uri('/items/' . $collection),
                $payload,
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param string $collection
     * @param array $payload
     * @param array $params
     * @return Promise
     */
    public function updateItem(int $id, string $collection, array $payload, array $params = []) : Promise {
        try {
            return $this->client->patch(
                $this->login(),
                $this->uri('/items/' . $collection . '/' . $id),
                $payload,
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param string $collection
     * @return Promise
     */
    public function deleteItem(int $id, string $collection) : Promise {
        try {
            return $this->client->delete(
                $this->login(),
                $this->uri('/items/' . $collection . '/' . $id)
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param string $collection
     * @param array $params
     * @return Promise
     */
    public function getItemRevisions(int $id, string $collection, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/items/' . $collection . '/' . $id . '/revisions'),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param int $offset
     * @param string $collection
     * @param array $params
     * @return Promise
     */
    public function getItemRevision(int $id, int $offset, string $collection, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/items/' . $collection . '/' . $id . '/revisions/' . $offset),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param int $revision
     * @param string $collection
     * @param array $params
     * @return Promise
     */
    public function revertItem(int $id, int $revision, string $collection, array $params = []) : Promise {
        try {
            return $this->client->patch(
                $this->login(),
                $this->uri('/items/' . $collection . '/' . $id . '/revert/' . $revision),
                [],
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param array $params
     * @return Promise
     */
    public function getFiles(array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/files'),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param array $params
     * @return Promise
     */
    public function getFile(int $id, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/files/' . $id),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param string $file
     * @param array $params
     * @return Promise
     */
    public function createFile(string $file, array $params = []) : Promise {
        try {
            return $this->client->post(
                $this->login(),
                $this->uri('/files'),
                $this->filePayload($file, $params, true)
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param null|string $file
     * @param array $params
     * @return Promise
     */
    public function updateFile(int $id, string $file = null, array $params = []) : Promise {

        if (null !== $file) {
            $params = $this->filePayload($file, $params, false);
        }

        try {
            return $this->client->patch(
                $this->login(),
                $this->uri('/files/' . $id),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param string $file
     * @param array $params
     * @param bool $new
     * @return array
     * @throws ClientException
     */
    private function filePayload(string $file, array $params, bool $new) : array {
        $name = null;

        if (\is_file($file)) {
            $name = $file;
            $params['data'] = \base64_encode(\file_get_contents($file));
        } else if (0 === \stripos($file, 'http')
            && false !== ($parts = \parse_url($file))
            && isset($parts['path'])) {

            $name = $parts['path'];
            $params['data'] = $file;

        } else {
            throw new ClientException(\sprintf(
                'Unable to resolve file: %s', $file
            ));
        }

        if ($new) {
            if (!isset($params['filename_disk'])) {
                $params['filename_disk'] = \basename($name);
            }

            if (!isset($params['filename_download'])) {
                $params['filename_download'] = \basename($name);
            }
        }

        return $params;
    }

    /**
     * @param int $id
     * @return Promise
     */
    public function deleteFile(int $id) : Promise {
        try {
            return $this->client->delete(
                $this->login(),
                $this->uri('/files/' . $id)
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param array $params
     * @return Promise
     */
    public function getFileRevisions(int $id, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/files/' . $id . '/revisions'),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param int $offset
     * @param array $params
     * @return Promise
     */
    public function getFileRevision(int $id, int $offset, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/files/' . $id . '/revisions/' . $offset),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param array $params
     * @return Promise
     */
    public function getActivities(array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/activity'),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param array $params
     * @return Promise
     */
    public function getActivity(int $id, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/activity/' . $id),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param array $params
     * @return Promise
     */
    public function getCollections(array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/collections'),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param string $collection
     * @param array $params
     * @return Promise
     */
    public function getCollection(string $collection, array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/collections/' . $collection),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @return Promise
     */
    public function getProjects() : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->config->uri() . '/server/projects'
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param array $params
     * @return Promise
     */
    public function getUsers(array $params = []) : Promise {
        try {
            return $this->client->get(
                $this->login(),
                $this->uri('/users'),
                $params
            );
        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @param int $id
     * @param array $params
     * @return Promise
     */
    public function getUser(int $id = 0, array $params = []) : Promise {

        if (0 === $id) {
            $id = 'me';
        }

        try {

            return $this->client->get(
                $this->login(),
                $this->uri('/users/' . $id),
                $params
            );

        } catch (ClientException $x) {
            return new RejectedPromise($x);
        }
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
