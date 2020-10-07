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
    Bitnix\Directus\Adapter\GuzzleAdapter,
    Bitnix\Directus\Storage\RuntimeTokens,
    GuzzleHttp\Client as Guzzle,
    GuzzleHttp\Promise\PromiseInterface,
    GuzzleHttp\Promise\FulfilledPromise,
    GuzzleHttp\Psr7\Request,
    GuzzleHttp\Psr7\Response,
    GuzzleHttp\Psr7\Uri,
    PHPUnit\Framework\TestCase;

/**
 * @version 0.1.0
 */
class ClientTest extends TestCase {

    private $handler = null;
    private ?TokenStorage $tokens = null;
    private ?Client $client = null;

    public function setUp() : void {
        $this->client = new Client(
            new GuzzleAdapter(new Guzzle(['handler' => [$this, 'reply']])),
            new Settings('https://user@email.net:s3cr3t@localhost/project'),
            $this->tokens = new RuntimeTokens()
        );
        $tokens = new RuntimeTokens();
    }

    public function testLoginNewToken() {

        $this->handler = function(Request $request, array $options) {

            $this->assertEquals('POST', $request->getMethod());
            $this->assertJsonBody($request, [
                'email'    => 'user@email.net',
                'password' => 's3cr3t'
            ]);
            $this->assertUri($request->getUri(), '/project/auth/authenticate');

            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['token' => 't0k3n']
            ])));
        };

        $this->assertNull($this->tokens->getToken('project'));

        $token = $this->client->login();
        $this->assertInstanceOf(Token::CLASS, $token);
        $this->assertSame($token, $this->tokens->getToken('project'));
    }

    public function testLoginExpiringToken() {
        $token1 = new Token('t0k3n');
        $this->tokens->putToken('project', $token1);

        $ref = new ReflectionObject($token1);
        $expire = $ref->getProperty('expire');
        $expire->setAccessible(true);
        $expire->setValue($token1, $expire->getValue($token1) - 1140);
        $this->assertTrue($token1->expiring());

        $this->handler = function(Request $request, array $options) {

            $this->assertEquals('POST', $request->getMethod());
            $this->assertJsonBody($request, [
                'token'    => 't0k3n'
            ]);
            $this->assertUri($request->getUri(), '/project/auth/refresh');

            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['token' => 't0k3n#2']
            ])));
        };

        $token2 = $this->client->login();
        $this->assertInstanceOf(Token::CLASS, $token2);
        $this->assertSame($token2, $this->tokens->getToken('project'));
        $this->assertNotSame($token1, $token2);
    }

    public function testLoginValidToken() {
        $token1 = new Token('t0k3n');
        $this->tokens->putToken('project', $token1);
        $this->handler = function(Request $request, array $options) {
            $this->fail('Unexpected call');
        };

        $token2 = $this->client->login();
        $this->assertSame($token1, $token2);
    }

    public function testLogout() {
        $token = new Token('t0k3n');
        $this->tokens->putToken('project', $token);
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertJsonBody($request, []);
            $this->assertUri($request->getUri(), '/project/auth/logout');
            return new FulfilledPromise(new Response(204));
        };
        $this->client->logout();
        $this->assertNull($this->tokens->getToken('project'));
    }

    public function testGetItems() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/items/foo', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getItems('foo', ['zig' => 'zag'])
            ->then(function(array $res) {
                $this->assertEquals(['data' => []], $res);
            })
            ->wait();
    }

    public function testGetItemsError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->getItems('foo')->wait();
    }

    public function testGetItem() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/items/foo/1', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getItem(1, 'foo', ['zig' => 'zag'])
            ->then(function(array $res) {
                $this->assertEquals(['data' => []], $res);
            })
            ->wait();
    }

    public function testGetItemError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->getItem(1, 'foo')->wait();
    }

    public function testCreateItem() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertJsonBody($request, ['foo' => 'bar']);
            $this->assertUri($request->getUri(), '/project/items/foo', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['id' => 1]
            ])));
        };
        $this->client->createItem('foo', ['foo' => 'bar'], ['zig' => 'zag'])->wait();
    }

    public function testCreateItemError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->createItem('foo', ['foo' => 'bar'], ['zig' => 'zag'])->wait();
    }

    public function testUpdateItem() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('PATCH', $request->getMethod());
            $this->assertJsonBody($request, ['foo' => 'bar']);
            $this->assertUri($request->getUri(), '/project/items/foo/1', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['id' => 1]
            ])));
        };
        $this->client->updateItem(1, 'foo', ['foo' => 'bar'], ['zig' => 'zag'])->wait();
    }

    public function testUpdateItemError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->updateItem(1, 'foo', ['foo' => 'bar'], ['zig' => 'zag'])->wait();
    }

    public function testDeleteItem() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('DELETE', $request->getMethod());
            return new FulfilledPromise(new Response(204));
        };
        $this->client->deleteItem(1, 'foo')->wait();
    }

    public function testDeleteItemError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->deleteItem(1, 'foo')->wait();
    }

    public function testGetItemRevisions() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/items/foo/1/revisions', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['id' => 1]
            ])));
        };
        $this->client->getItemRevisions(1, 'foo', ['zig' => 'zag'])->wait();
    }

    public function testGetItemRevisionsError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->getItemRevisions(1, 'foo', ['zig' => 'zag'])->wait();
    }

    public function testGetItemRevision() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/items/foo/1/revisions/1', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['id' => 1]
            ])));
        };
        $this->client->getItemRevision(1, 1, 'foo', ['zig' => 'zag'])->wait();
    }

    public function testGetItemRevisionError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->getItemRevision(1, 1, 'foo', ['zig' => 'zag'])->wait();
    }

    public function testRevertItem() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('PATCH', $request->getMethod());
            $this->assertJsonBody($request);
            $this->assertUri($request->getUri(), '/project/items/foo/1/revert/1', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['id' => 1]
            ])));
        };
        $this->client->revertItem(1, 1, 'foo', ['zig' => 'zag'])->wait();
    }

    public function testRevertItemError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->revertItem(1, 1, 'foo', ['zig' => 'zag'])->wait();
    }

    public function testGetFiles() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/files', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getFiles(['zig' => 'zag'])
            ->then(function(array $res) {
                $this->assertEquals(['data' => []], $res);
            })
            ->wait();
    }

    public function testGetFilesError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->getFiles()->wait();
    }

    public function testGetFile() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/files/1', ['zig' => 'zag']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getFile(1, ['zig' => 'zag'])
            ->then(function(array $res) {
                $this->assertEquals(['data' => []], $res);
            })
            ->wait();
    }

    public function testGetFileError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new ClientException('kaput');
        };
        $this->client->getFile(1)->wait();
    }

    public function testCreateFile() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/files', []);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['id' => 1]
            ])));
        };
        $this->client->createFile(__FILE__)->wait();
    }

    public function testCreateFileFromUrl() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/files', []);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['id' => 1]
            ])));
        };
        $this->client->createFile('https://localhost/some_file.txt')->wait();
    }

    public function testCreateFileInvalidParams() {
        $this->expectException(ClientException::CLASS);
        $this->login();
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('unexpected call');
        };
        $this->client->createFile(__DIR__)->wait();
    }

    public function testUpdateFile() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('PATCH', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/files/1', []);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => ['id' => 1]
            ])));
        };
        $this->client->updateFile(1, __FILE__)->wait();
    }

    public function testUpdateFileError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->updateFile(1, __FILE__)->wait();
    }

    public function testDeleteFile() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('DELETE', $request->getMethod());
            return new FulfilledPromise(new Response(204));
        };
        $this->client->deleteFile(1)->wait();
    }

    public function testDeleteFileError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->deleteFile(1)->wait();
    }

    public function testGetFileRevisions() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/files/1/revisions', ['foo' => 'bar']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getFileRevisions(1, ['foo' => 'bar'])->wait();
    }

    public function testGetFileRevisionsError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getFileRevisions(1, ['foo' => 'bar'])->wait();
    }

    public function testGetFileRevision() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/files/1/revisions/2', ['foo' => 'bar']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getFileRevision(1, 2, ['foo' => 'bar'])->wait();
    }

    public function testGetFileRevisionError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getFileRevision(1, 2, ['foo' => 'bar'])->wait();
    }

    public function testGetActivities() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/activity', ['foo' => 'bar']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getActivities(['foo' => 'bar'])->wait();
    }

    public function testGetActivitiesError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getActivities(['foo' => 'bar'])->wait();
    }

    public function testGetActivity() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/activity/1', ['foo' => 'bar']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getActivity(1, ['foo' => 'bar'])->wait();
    }

    public function testGetActivityError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getActivity(1, ['foo' => 'bar'])->wait();
    }

    public function testGetCollections() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/collections', ['foo' => 'bar']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getCollections(['foo' => 'bar'])->wait();
    }

    public function testGetCollectionsError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getCollections(['foo' => 'bar'])->wait();
    }

    public function testGetCollection() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/collections/collection', ['foo' => 'bar']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getCollection('collection', ['foo' => 'bar'])->wait();
    }

    public function testGetCollectionError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getCollection('collection', ['foo' => 'bar'])->wait();
    }

    public function testGetProjects() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/server/projects', []);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getProjects()->wait();
    }

    public function testGetProjectsError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getProjects()->wait();
    }

    public function testGetUsers() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/users', ['foo' => 'bar']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getUsers(['foo' => 'bar'])->wait();
    }

    public function testGetUsersError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getUsers(['foo' => 'bar'])->wait();
    }

    public function testGetCurrentUser() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/users/me');
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getUser()->wait();
    }

    public function testGetUser() {
        $this->login();
        $this->handler = function(Request $request, array $options) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertUri($request->getUri(), '/project/users/666', ['foo' => 'bar']);
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'data' => []
            ])));
        };
        $this->client->getUser(666, ['foo' => 'bar'])->wait();
    }

    public function testGetUserError() {
        $this->expectException(ClientException::CLASS);
        $this->handler = function(Request $request, array $options) {
            throw new \Exception('kaput');
        };
        $this->client->getUser()->wait();
    }

    public function testToString() {
        $this->assertIsString((string) $this->client);
    }

    private function login() : void {
        $this->tokens->putToken('project', new Token('t0k3n'));
    }

    private function assertJsonBody(Request $request, array $expected = []) : void {
        $this->assertEquals('application/json', $request->getHeaderLine('content-type'));
        $this->assertEquals($expected, \json_decode($request->getBody()->getContents(), true));
    }

    private function assertUri(Uri $uri, string $path, array $query = []) : void {
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('localhost', $uri->getHost());
        $this->assertEquals($path, $uri->getPath());
        \parse_str($uri->getQuery(), $q);
        $this->assertEquals($query, $q, 'got ' . $uri->getQuery());
    }

    public function reply(Request $request, array $options) : PromiseInterface {
        return ($this->handler)($request, $options);
    }

}
