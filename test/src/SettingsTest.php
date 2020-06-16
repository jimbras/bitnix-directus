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

use InvalidArgumentException,
    PHPUnit\Framework\TestCase;

/**
 * @version 0.1.0
 */
class SettingsTest extends TestCase {

    public function testConstructor() {
        $settings = new Settings('https://user@email.tld:password@host.tld/project');

        $this->assertEquals('https://host.tld', $settings->uri());
        $this->assertEquals('project', $settings->project());
        $this->assertEquals(['email' => 'user@email.tld', 'password' => 'password'], $settings->credentials());
    }

    public function testInvalidDsnFormat() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Settings('malformed:0');
    }

    public function testRequiredScheme() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Settings('user@email.tld:password@host.tld/project');
    }

    public function testInvalidScheme() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Settings('fake://user@email.tld:password@host.tld/project');
    }

    public function testRequiredProject() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Settings('https://user@email.tld:password@host.tld');
    }

    public function testEmptyProject() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Settings('https://user@email.tld:password@host.tld/');
    }

    public function testRequiredEmail() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Settings('https://host.tld/project');
    }

    public function testInvalidEmail() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Settings('https://bad@email:password@host.tld/project');
    }

    public function testRequiredPassword() {
        $this->expectException(InvalidArgumentException::CLASS);
        new Settings('https://user@email.tld@host.tld/project');
    }

    public function testToString() {
        $this->assertIsString((string) new Settings(
            'https://user@email.tld:password@host.tld/project'
        ));
    }
}
