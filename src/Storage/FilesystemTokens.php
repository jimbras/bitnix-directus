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

namespace Bitnix\Directus\Storage;

use RuntimeException,
    Bitnix\Directus\TokenStorage;

/**
 * @version 0.1.0
 */
final class FilesystemTokens implements TokenStorage {

    use TokenStorageSupport;

    /**
     * @var string
     */
    private string $file;

    /**
     * @var bool
     */
    private static ?bool $opcache = null;

    /**
     * @param string $file
     * @throws RuntimeException
     */
    public function __construct(string $file) {

        if (null === self::$opcache) {
            self::$opcache = \function_exists('opcache_invalidate')
                && (bool) \ini_get('opcache.enable');
        }

        if (!\is_file($file)) {
            $this->file = $this->mkdir($file);
            $this->tokens = [];
        } else {
            $this->file = \realpath($file);
            $this->tokens = $this->read();
        }

    }

    /**
     *
     */
    public function __destruct() {
        $this->write();
    }

    /**
     * @return array
     * @throws RuntimeException
     */
    private function read() : array {

        if (!\is_readable($this->file)) {
            throw new RuntimeException(\sprintf(
                'Unreadable tokens file: %s', $this->file
            ));
        }

        $data = \unserialize(\file_get_contents($this->file));

        if (!is_array($data)) {
            throw new RuntimeException(\sprintf(
                'Malformed tokens file: %s', $this->file
            ));
        }
        return $data;
    }

    /**
     * ...
     */
    private function write() : void {
        $data = \serialize($this->tokens);
        $tmp = \tempnam(\dirname($this->file), \basename($this->file));
        if (false !== \file_put_contents($tmp, $data, \LOCK_EX) && \rename($tmp, $this->file)) {
            \chmod($this->file, 0644 & ~\umask());
            if (self::$opcache) {
                \opcache_invalidate($this->file, true);
            }
        }
    }

    /**
     * @param string $file
     * @return string
     * @throws RuntimeException
     */
    private function mkdir(string $file) : string {
        $dir = \dirname($file);

        if (!\is_dir($dir) && !@\mkdir($dir, 0755, true)) {
            throw new RuntimeException(\sprintf(
                'Failed to create tokens file directory "%s": %s',
                    $dir,
                    \error_get_last()['message'] ?? 'unknown error'
            ));
        }

        return $dir . \DIRECTORY_SEPARATOR . \basename($file);
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return self::CLASS;
    }
}
