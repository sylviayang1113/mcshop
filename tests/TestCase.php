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

}
