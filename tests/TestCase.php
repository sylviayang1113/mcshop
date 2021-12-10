<?php

namespace Tests;

use App\Models\User\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected  $token;

    /**
     * @var User $user
     */
    protected $user;

    public function getAuthHeader($username = 'user123', $password = 'user123')
    {
        $response = $this->post('wx/auth/login', ['user' => 'user123', 'password' => 'user123']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $this->token = $token;
        return ['Authorization' => "Bearer{$token}"];
    }

    public function assertLitemallApiGet($uri, $ignore = [])
    {
        return $this->assertLitemallApi($uri, 'get', [], $ignore);
    }

    public function assertLitemallApiPost($uri, $data = [], $ignore = [])
    {
        return $this->assertLitemallApi($uri, 'post', $data, $ignore);
    }

    /**
     * @param $uri
     * @param  string  $method
     * @param  array  $data
     * @throws GuzzleException
     */
    public function assertLitemallApi($uri, $method = 'get', $data = [], $ignore = [])
    {
        $this->user = UserServices::getInstance()->getUserById(1);
        $this->auth($this->user);
        $client = new Client();
        if ($method == 'get') {
            if (!empty($data)) {
                $uri .= '?'.Arr::query($data);
            }
            $response1 = $this->get($uri);
            $response2 = $client->get('http://122.112.215.32:8080/'.$uri,
                ['headers' => ['X-Litemall-Token' => $this->token]]);
        } else {
            $response1 = $this->post($uri, $data);
            $response2 = $client->post('http://122.112.215.32:8080/'.$uri,
                [
                    'headers' => ['X-Litemall-Token' => $this->token],
                    'json' => $data
                ]);
        }

        $content1 = $response1->getContent();
        echo "mcshop    =>".json_encode(json_decode($content1), JSON_UNESCAPED_UNICODE).PHP_EOL;
        $content1 = json_decode($content1, true);
        $content2 = $response2->getBody()->getContents();
        echo "litemall  =>$content2".PHP_EOL;
        $content2 = json_decode($content2, true);

        foreach ($ignore as $key) {
            Arr::forget($content1, [$key]);
            Arr::forget($content2, [$key]);
        }

        $this->assertEquals($content2, $content1);
    }

}
