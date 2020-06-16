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

use ReflectionObject,
    PHPUnit\Framework\TestCase;

/**
 * @version 0.1.0
 */
class TokenTest extends TestCase {

    /**
     * @var Token
     */
    private ?Token $token = null;

    public function setUp() : void {
        $this->token = new Token(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZXhwIjoxNTkwMT'
            . 'M2MTA2LCJ0eXBlIjoiYXV0aCIsImtleSI6ImU5NGIzMDAwLTYwYTktNGQyNy1hM'
            . 'jQwLTY0N2VmNjE2Mjg4MyIsInByb2plY3QiOiJpbmR5bWVkaWEifQ.JTBO-SDv0'
            . 'xI5B60vfhINo_jJklqsvuDTCfsxhcqKhZ8'
        );
    }

    public function testValue() {
        $this->assertEquals(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZXhwIjoxNTkwMT'
            . 'M2MTA2LCJ0eXBlIjoiYXV0aCIsImtleSI6ImU5NGIzMDAwLTYwYTktNGQyNy1hM'
            . 'jQwLTY0N2VmNjE2Mjg4MyIsInByb2plY3QiOiJpbmR5bWVkaWEifQ.JTBO-SDv0'
            . 'xI5B60vfhINo_jJklqsvuDTCfsxhcqKhZ8',
            $this->token->value()
        );
    }

    public function testValid() {
        $this->assertTrue($this->token->valid());

        $ref = new ReflectionObject($this->token);
        $expire = $ref->getProperty('expire');
        $expire->setAccessible(true);
        $expire->setValue($this->token, \time() - 2400);

        $this->assertFalse($this->token->valid());
    }

    public function testExpired() {
        $this->assertFalse($this->token->expired());

        $ref = new ReflectionObject($this->token);
        $expire = $ref->getProperty('expire');
        $expire->setAccessible(true);
        $expire->setValue($this->token, \time() - 2400);

        $this->assertTrue($this->token->expired());
    }

    public function testExpiring() {
        $this->assertFalse($this->token->expiring());

        $ref = new ReflectionObject($this->token);
        $expire = $ref->getProperty('expire');
        $expire->setAccessible(true);
        $expire->setValue($this->token, $expire->getValue($this->token) - 1140);

        $this->assertTrue($this->token->expiring());
    }

    public function testExpire() {
        $this->assertTrue($this->token->valid());
        $this->assertFalse($this->token->expired());
        $this->assertFalse($this->token->expiring());

        $this->token->expire();

        $this->assertFalse($this->token->valid());
        $this->assertTrue($this->token->expired());
        $this->assertFalse($this->token->expiring());
    }

    public function testToString() {
        $this->assertIsString((string) $this->token);
    }

}
