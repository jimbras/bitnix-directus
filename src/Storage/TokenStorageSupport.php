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

use LogicException,
    Bitnix\Directus\Token;

/**
 * @version 0.1.0
 */
trait TokenStorageSupport {

    /**
     * @var array
     */
    private array $tokens;

    /**
     * @param string $project
     * @return null|Token
     */
    public function getToken(string $project) : ?Token {

        if (!isset($this->tokens[$project])) {
            return null;
        }

        $token = $this->tokens[$project];

        if ($token->expired()) {
            unset($this->tokens[$project]);
            return null;
        }

        return $token;
    }

    /**
     * @param string $project
     * @param Token $token
     * @throws LogicException
     */
    public function putToken(string $project, Token $token) : void {
        if ($token->expired()) {
            throw new LogicException('Expired tokens cannot be stored');
        }

        $this->tokens[$project] = $token;
    }

    /**
     * @param string $project
     */
    public function removeToken(string $project) : void {
        if (isset($this->tokens[$project])) {
            $this->tokens[$project]->expire();
            unset($this->tokens[$project]);
        }
    }


}
