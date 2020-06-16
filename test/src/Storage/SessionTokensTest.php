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
    Bitnix\Directus\Token,
    PHPUnit\Framework\TestCase;

/**
 * ...
 *
 * @version 0.1.0
 */
class SessionTokensTest extends TestCase {

    private ?array $session = null;

    public function setUp() : void {
        $this->session = isset($_SESSION) ? $_SESSION : null;
    }

    public function tearDown() : void {
        $_SESSION = $this->session;
    }

    public function testStorage() {
        $tokens = new SessionTokens();

        $this->assertNull($tokens->getToken('proj1'));
        $this->assertNull($tokens->getToken('proj2'));

        $token1 = $this->token();
        $token2 = $this->token();
        $tokens->putToken('proj1', $token1);
        $tokens->putToken('proj2', $token2);
        $this->assertSame($token1, $tokens->getToken('proj1'));
        $this->assertSame($token2, $tokens->getToken('proj2'));

        $token1->expire();
        $this->assertNull($tokens->getToken('proj1'));

        $tokens->removeToken('proj2');
        $this->assertNull($tokens->getToken('proj2'));

        try {
            $tokens->putToken('proj1', $token1);
            $this->fail('Expired not should not be stored');
        } catch (LogicException $x) {}


    }

    public function testToString() {
        $this->assertIsString((string) new SessionTokens());
    }

    private function token() : Token {
        return new Token(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZXhwIjoxNTkwMT'
            . 'M2MTA2LCJ0eXBlIjoiYXV0aCIsImtleSI6ImU5NGIzMDAwLTYwYTktNGQyNy1hM'
            . 'jQwLTY0N2VmNjE2Mjg4MyIsInByb2plY3QiOiJpbmR5bWVkaWEifQ.JTBO-SDv0'
            . 'xI5B60vfhINo_jJklqsvuDTCfsxhcqKhZ8'
        );
    }
}
