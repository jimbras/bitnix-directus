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

use InvalidArgumentException;

/**
 * @version 0.1.0
 */
final class Settings {

    private const SUPPORTED = ['http' => true, 'https' => true];

    /**
     * @var string
     */
    private string $uri;

    /**
     * @var string
     */
    private string $project;

    /**
     * @var array
     */
    private array $credentials;

    /**
     * @param string $dsn
     * @throws InvalidArgumentException
     */
    public function __construct(string $dsn) {
        $parts = \parse_url($dsn);

        if (!$parts) {
            throw new InvalidArgumentException(\sprintf(
                'Unparseable directus dsn: %s', $dsn
            ));
        }

        $this->uri = $this->filterUri($parts);
        $this->project = $this->filterProject($parts);
        $this->credentials = $this->filterCredentials($parts);
    }

    /**
     * @param array $uri
     * @return string
     */
    private function filterUri(array $uri) : string {
        $scheme = \strtolower($this->filterRequired('Directus dsn scheme', $uri['scheme'] ?? ''));
        if (!isset(self::SUPPORTED[$scheme])) {
            throw new InvalidArgumentException(\sprintf(
                'Unsupported directus dsn scheme: %s', $scheme
            ));
        }
        $host = $this->filterRequired('Directus dsn host', $uri['host'] ?? '');
        return \sprintf('%s://%s', $scheme, $host);
    }

    /**
     * @param array $uri
     * @return string
     */
    private function filterProject(array $uri) : string {
        return $this->filterRequired('Directus dsn project', \ltrim($uri['path'] ?? '', '/'));
    }

    /**
     * @param array $uri
     * @return array
     */
    private function filterCredentials(array $uri) : array {
        return [
            'email' => $this->filterEmail($this->filterRequired('Directus dsn email address', $uri['user'] ?? '')),
            'password' => $this->filterRequired('Directus dsn user password', $uri['pass'] ?? '')
        ];
    }

    /**
     * @param string $email
     * @return string
     * @throws InvalidArgumentException
     */
    private function filterEmail(string $email) : string {

        if (false === \filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid or unsupported directus dsn email address: %s', $email
            ));
        }
        return $email;
    }

    /**
     * @param string $key
     * @param string $value
     * @return string
     * @throws InvalidArgumentException
     */
    private function filterRequired(string $key, string $value) : string {
        $value = \trim($value);
        if ('' === $value) {
            throw new InvalidArgumentException(\sprintf(
                '%s is required', $key
            ));
        }
        return $value;
    }

    /**
     * @return string
     */
    public function uri() : string {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function project() : string {
        return $this->project;
    }

    /**
     * @return array
     */
    public function credentials() : array {
        return $this->credentials;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return \sprintf(
            '%s (uri=%s,project=%s)',
                self::CLASS,
                $this->uri,
                $this->project
        );
    }
}
