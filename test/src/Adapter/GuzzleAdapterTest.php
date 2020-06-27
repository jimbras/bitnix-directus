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

namespace Bitnix\Directus\Adapter;

use Bitnix\Directus\ClientException,
    Bitnix\Directus\ResponseException,
    Bitnix\Directus\Token,
    GuzzleHttp\Client,
    GuzzleHttp\Promise\FulfilledPromise,
    GuzzleHttp\Psr7\Response,
    PHPUnit\Framework\TestCase;

/**
 * @version 0.1.0
 */
class GuzzleAdapterTest extends TestCase {

    public function testGetSuccess() {

        $client = new Client(['handler' => function($req, $opts) {
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'uri' => \parse_url((string) $req->getUri()),
                'method' => $req->getMethod(),
                'headers' => $req->getHeaders(),
                'options' => $opts
            ])));
        }]);

        $adapter = new GuzzleAdapter($client);
        $adapter
            ->get(new Token('t0k3n'), 'http://localhost/', ['zig' => 'zag'])
            ->then(function($res) {
                $this->assertMethod('GET', $res);
                $this->assertToken('t0k3n', $res);
                $this->assertUri([
                    'scheme' => 'http',
                    'host' => 'localhost',
                    'path' => '/',
                    'query' => 'zig=zag'
                ], $res);
                $this->assertOptions($res);
            })
            ->wait();
    }

    public function testClientException() {
        $client = new Client(['handler' => function($req, $opts) {
            throw new \Exception('kaput', 123);
        }]);
        $adapter = new GuzzleAdapter($client);
        foreach (['get', 'post', 'patch', 'delete'] as $method) {
            $adapter->$method(new Token('t0k3n'), 'http://localhost/')
                ->then(function($res) {
                    $this->fail('Expected ResponseException...');
                })
                ->otherwise(function($x) {
                    $this->assertInstanceOf(ClientException::CLASS, $x);
                    $this->assertEquals(123, $x->getCode());
                    $this->assertEquals('kaput', $x->getMessage());
                })
                ->wait();
        }
    }

    public function testResponseException() {
        $client = new Client(['handler' => function($req, $opts) {
            return new FulfilledPromise(new Response(404, [], \json_encode([
                'error' => [
                    'code' => 123,
                    'message' => 'kaput'
                ]
            ])));
        }]);
        $adapter = new GuzzleAdapter($client);
        foreach (['get', 'post', 'patch', 'delete'] as $method) {
            $adapter->$method(new Token('t0k3n'), 'http://localhost/')
                ->then(function($res) {
                    $this->fail('Expected ResponseException...');
                })
                ->otherwise(function($x) {
                    $this->assertInstanceOf(ResponseException::CLASS, $x);
                    $this->assertEquals(404, $x->getStatus());
                    $this->assertEquals(123, $x->getCode());
                    $this->assertEquals('kaput', $x->getMessage());
                })
                ->wait();
        }
    }

    public function testPostSuccess() {

        $client = new Client(['handler' => function($req, $opts) {
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'uri' => \parse_url((string) $req->getUri()),
                'method' => $req->getMethod(),
                'headers' => $req->getHeaders(),
                'options' => $opts,
                'body' => \json_decode($req->getBody()->getContents(), true)
            ])));
        }]);

        $adapter = new GuzzleAdapter($client);
        $adapter
            ->post(new Token('t0k3n'), 'http://localhost/', ['foo' => 'bar'], ['zig' => 'zag'])
            ->then(function($res) {
                $this->assertMethod('POST', $res);
                $this->assertToken('t0k3n', $res);
                $this->assertUri([
                    'scheme' => 'http',
                    'host' => 'localhost',
                    'path' => '/',
                    'query' => 'zig=zag'
                ], $res);
                $this->assertBody(['foo' => 'bar'], $res);
                $this->assertOptions($res);
            })
            ->wait();
    }

    public function testPatchSuccess() {

        $client = new Client(['handler' => function($req, $opts) {
            return new FulfilledPromise(new Response(200, [], \json_encode([
                'uri' => \parse_url((string) $req->getUri()),
                'method' => $req->getMethod(),
                'headers' => $req->getHeaders(),
                'options' => $opts,
                'body' => \json_decode($req->getBody()->getContents(), true)
            ])));
        }]);

        $adapter = new GuzzleAdapter($client);
        $adapter
            ->patch(new Token('t0k3n'), 'http://localhost/', ['foo' => 'bar'], ['zig' => 'zag'])
            ->then(function($res) {
                $this->assertMethod('PATCH', $res);
                $this->assertToken('t0k3n', $res);
                $this->assertUri([
                    'scheme' => 'http',
                    'host' => 'localhost',
                    'path' => '/',
                    'query' => 'zig=zag'
                ], $res);
                $this->assertBody(['foo' => 'bar'], $res);
                $this->assertOptions($res);
            })
            ->wait();
    }

    public function testDeleteSuccess() {

        $client = new Client(['handler' => function($req, $opts) {
            $this->assertMethod('DELETE', ['method' => $req->getMethod()]);
            $this->assertToken('t0k3n', ['headers' => $req->getHeaders()]);
            $this->assertUri([
                'scheme' => 'http',
                'host' => 'localhost',
                'path' => '/'
            ], ['uri' => \parse_url((string) $req->getUri())]);
            $this->assertOptions(['options' => $opts]);
            return new FulfilledPromise(new Response(204));
        }]);

        $adapter = new GuzzleAdapter($client);
        $adapter
            ->delete(new Token('t0k3n'), 'http://localhost/')
            ->then(function($res) {
                $this->assertEquals([], $res);
            })
            ->wait();
    }

    public function testToString() {
        $this->assertIsString((string) new GuzzleAdapter(new Client()));
    }

    private function assertBody(array $body, array $res) : void {
        $this->assertEquals($body, $res['body'] ?? null);
    }

    private function assertOptions(array $res) : void {
        foreach ([
            'allow_redirects' => true,
            'decode_content'  => true,
            'http_errors'     => false
        ] as $key => $value) {
            $this->assertTrue(isset($res['options'][$key]));
            $this->assertEquals($value, $res['options'][$key]);
        }
    }

    private function assertUri(array $uri, array $res) : void {
        $this->assertEquals($uri, $res['uri'] ?? null);
    }

    private function assertMethod(string $method, array $res) : void {
        $this->assertEquals($method, $res['method'] ?? null);
    }

    private function assertToken(string $token, array $res) : void {
        $this->assertTrue(isset($res['headers']['Authorization'][0]));
        $this->assertEquals('Bearer ' . $token, $res['headers']['Authorization'][0]);
    }

}
