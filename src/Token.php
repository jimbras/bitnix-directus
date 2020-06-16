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

/**
 * @version 0.1.0
 */
final class Token {

    private const TTL       = 1200; // 20 minutes
    private const THRESHOLD = 60;   // 1 minute

    /**
     * @var string
     */
    private string $value;

    /**
     * @var int
     */
    private int $expire;

    /**
     * @param string $value
     */
    public function __construct(string $value) {
        $this->value = $value;
        $this->expire = \time() + self::TTL;
    }

    /**
     * @return string
     */
    public function value() : string {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function valid() : bool {
        return \time() < $this->expire;
    }

    /**
     * @return bool
     */
    public function expired() : bool {
        return !$this->valid();
    }

    /**
     * ...
     */
    public function expire() : void {
        $this->expire = \time();
    }

    /**
     * @return bool
     */
    public function expiring() : bool {
        return $this->valid()
            && \time() + self::THRESHOLD >= $this->expire;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return \sprintf(
            '%s (value=%s,expired=%s)',
                self::CLASS,
                $this->value,
                $this->valid() ? 'false' : 'true'
        );
    }
}
