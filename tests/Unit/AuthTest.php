<?php

namespace Tests\Unit;


use App\Service\User\UserService;
use Tests\TestCase;

class AuthTest extends TestCase
{
   public function testCheckMobileSendCaptchaCount()
   {
       $mobile = '13111111111';
       foreach (range(0, 9) as $i) {
           $isPass = (new UserService())->checkMobileSendCaptchaCount($mobile);
           $this->assertTrue($isPass);
       }
       $isPass = (new UserService())->checkMobileSendCaptchaCount($mobile);
       $this->assertTrue($isPass);
       $countkey = 'register_captcha_count'.$mobile;
       Cache::forget($countkey);
       $isPass = (new UserService())->checkMobileSendCaptchaCount($mobile);
       $this->assertTrue($isPass);
   }

   public function testCheckCaptcha()
   {
       $mobile = '13111111112';
       $code = (new UserService())->setCaptcha($mobile);
       $isPass =(new UserService())->checkCaptcha($mobile);
       $this->assertTrue($isPass);
       $isPass =(new UserService())->checkCaptcha($mobile);
       $this->assertFalse($isPass);
   }
}
