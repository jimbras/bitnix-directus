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
    RuntimeException,
    Bitnix\Directus\Token,
    PHPUnit\Framework\TestCase;

/**
 * ...
 *
 * @version 0.1.0
 */
class FilesystemTokensTest extends TestCase {

    private ?string $file = null;

    public function setUp() : void {
        $this->file = __DIR__ . '/_tokens/file';
    }

    public function tearDown() : void {
        if (\is_file($this->file)) {
            \unlink($this->file);
        }
    }

    public function testStorage() {
        $tokens = new FilesystemTokens($this->file);
        $this->assertFalse(\is_file($this->file));

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

        $last = $this->token();
        $tokens->putToken('proj3', $last);
        unset($tokens);
        $this->assertTrue(\is_file($this->file));
        $stored = \unserialize(\file_get_contents($this->file));
        $this->assertIsArray($stored);
        $this->assertInstanceOf(Token::CLASS, $stored['proj3']);
        $this->assertEquals($last->value(), $stored['proj3']->value());
    }

    public function testStorageLoadFile() {
        $tokens = new FilesystemTokens($this->file);
        $token = $this->token();
        $tokens->putToken('proj1', $token);
        unset($tokens);
        $this->assertTrue(\is_file($this->file));

        $tokens = new FilesystemTokens($this->file);
        $this->assertEquals($token, $tokens->getToken('proj1'));
    }

    public function testStorageReadError() {
        $this->expectException(RuntimeException::CLASS);
        $tokens = new FilesystemTokens($this->file);
        $token = $this->token();
        $tokens->putToken('proj1', $token);
        unset($tokens);
        $this->assertTrue(\is_file($this->file));

        \chmod($this->file, 0000 & ~\umask());

        try {
            $tokens = new FilesystemTokens($this->file);
        } finally {
            \chmod($this->file, 0644 & ~\umask());
        }
    }

    public function testStorageCorruptFileReadError() {
        $this->expectException(RuntimeException::CLASS);
        \touch($this->file);
        new FilesystemTokens($this->file);
    }

    public function testMkdirError() {
        $this->expectException(RuntimeException::CLASS);
        $dir = \dirname($this->file);
        \chmod($dir, 0000 & ~\umask());
        try {
            $tokens = new FilesystemTokens($this->file . '/other');
        } finally {
            \chmod($dir, 0755 & ~\umask());
        }
    }

    public function testToString() {
        $this->assertIsString((string) new FilesystemTokens($this->file));
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
