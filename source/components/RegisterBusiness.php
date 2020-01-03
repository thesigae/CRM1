<?php namespace BranMuffin\Crm1\Components;

use Session;
use Cache;
use Input;
use Crypt;
use Redirect;
use Illuminate\Contracts\Encryption\DecryptException;
use October\Rain\Support\Collection;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use Validator;
use Str;

use BranMuffin\Crm1\Models\Apps;
use BranMuffin\Crm1\Models\Businesses;
use BranMuffin\Crm1\Models\Revisions;

class RegisterBusiness extends \Cms\Classes\ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Register / Edit Business',
            'description' => 'Register and edit business.'
        ];
    }
    
    public function getMessages() {
        $messages = Session::get('messages');
        return $messages;
    }
    
    public function getUser() {
        $user = Auth::getUser();
        return $user;
    }
    
    public function getBusiness($user) {
        return $user->business;
    }
    
    public function getApps($business){
        return $business->app;
    }
    
    public function getApp($apps) {
        $app = $apps->where('slug', $this->param('app'))->first();
        return $app;
    }
    
    Public function onUserLogin() {
        $user = Auth::authenticate([
            'login' => post('login'),
            'password' => post('password')
        ]);
        return Redirect::refresh();
    }
    
    public function onUserLogout() {
        Auth::logout();
        return Redirect::to('business');
    }
    
    public function onEditApp() {
        Session::put('newApp', $this->param('app'));
        return Redirect::to('business/create');
    }
    
}// End of PHP class
