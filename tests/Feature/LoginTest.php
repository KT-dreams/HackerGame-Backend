<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\User;
use App\Http\Controllers\UsersController;

class LoginTest extends TestCase
{
    const COMMAND_LOGIN = 'commandLogin';
    const UUID = 'abcd-dddd-eeee-aaaa-xyzz';
    private $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = new User();
        $this->user->all()->each(function($user)
        {
            $user->delete();
        });
    }
    
    /** @test */
    public function user_can_run_login_command()
    {
        $this->mockUuid();
        $this->json('POST', route(self::COMMAND_LOGIN), [
            'data' => [
                'username' => 'TestUser',
            ]
        ])
             ->assertJson([
                 'data' => '',
                 'messageOptions' => [
                 'request_uuid' => self::UUID,
                'info' => 'Password for TestUser:',
                'type' => 'password'
            ]
        ])
             ->assertStatus(200);
        $this->assertArrayHasKey(self::UUID, Cache::get('requests'));
    }

    /** @test */
    public function username_is_required()
    {
        $this->json('POST', route(self::COMMAND_LOGIN))
             ->assertJson([
                 'data' => 'Command requires <username>'
                 
             ])->assertStatus(200);
        $this->assertEmpty(Cache::get(self::UUID));
    }

    /** @test */
    public function user_can_enter_password()
    {
        factory(User::class)->make([
            'username' => 'TestUser',
            'password' => Hash::make('qwerty')
        ])->save();
        
        $this->mockRequestHasValidUuid();
        Cache::put('requests', self::UUID); 
        $this->json('POST', route(self::COMMAND_LOGIN), [
            'messageOptions' => [
                 'request_uuid' => self::UUID
            ],
            'data' => [
                'password' => 'qwerty'
            ]
        ])
            ->assertJson([
            'data' => 'You are logged in!'
        ])
            ->assertStatus(200);
    }

    /** @test */
    public function password_is_required()
    {
        $this->mockRequestHasValidUuid();
        $this->json('POST', route(self::COMMAND_LOGIN), [
            'messageOptions' => [
                 'request_uuid' => self::UUID
            ]
        ])
             ->assertJson([
                 'data' => 'Command requires <password>'
                 
             ])
             ->assertStatus(200);
    }

    public function mockUuid()
    {
        return $this->partialMock(UsersController::class, function ($mock) {
            $mock->shouldReceive('generate_uuid')
                 ->andReturn(self::UUID);
        });
    }
    
    public function mockRequestHasValidUuid()
    {
        return $this->partialMock(UsersController::class, function($mock) {
            $mock->shouldReceive('requestHasValidUuid')
                 ->andReturn([
                     'request_uuid' => self::UUID,
                     'command_step'=>'password',
                     'username' => 'TestUser'
                     
                 ]);
        });
    }
}
