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

class CreateApp extends \Cms\Classes\ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Create An App',
            'description' => 'Create an app for your Crm1 business.'
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
    
    public function getNewApp() {
        $app = Apps::where('slug', Session::get('newApp'))->first();
        return $app;
    }
    
    public function getBusiness() {
        $user = $this->getUser();
        $business = $user->business;
        return $business;
    }
    
    public function getApps(){
        $business = $this->getBusiness();
        return $business->app;
    }
    
    public function onNewApp() {
        $user = $this->getUser();
        $appName = Input::get('appName');
        $appDescription = Input::get('appDescription');
        $validator = Validator::make(
            [
                'appName' => $appName,
                'appDescription' => $appDescription
            ],
            [
                'appName' => 'required|regex:/^[a-zA-Z0-9\s]+$/',
                'appDescription' => 'required|min:20'
            ]
        );
        if ($validator->fails()) {
            return Redirect::refresh()->with('messages', $validator->messages());
        } else if ( $this->getApps()->where('name', $appName)->count() > 0 ) {
            return Redirect::refresh()->with('messages',  [ 'messages' => ['appName' => ['You are already using an app by that name.']]]);
        } else {
            $app = new Apps;
            $app->name = $appName;
            $app->slug = Str::slug($app->name);
            $app->businesses_id = $user->business->id;
            $app->template = [
                'Creator' => $user->name,
                'Description' => $appDescription,
                'Version' => 1,
                'Fields' => []
                ];
            $app->save();
            $revision = new Revisions;
            $revision->revision = $app->template;
            $revision->app()->add($app);
            $revision->save();
            Session::put('newApp', $app->slug);
            return Redirect::refresh()->with('messages', [ 'messages' => $app->name.' was created successfully!']);
        }
        
    }
    
    public function onCancelField() {
        return [
            '#fieldsBar' => $this->renderPartial('@chooseFields.htm')
        ];
    }
    
    public function onChooseField() {
        $fieldType = Input::get('field');
        Session::put('fieldType', $fieldType);
        return [
            '#fieldsBar' => $this->renderPartial('@fields/'.$fieldType)
        ];
    }
    
    public function onAddField() {
        $newApp = $this->getNewApp();
        $fieldType = Session::get('fieldType');
        $currentFields = $newApp->template;
        $inputs = Input::all();
        unset($inputs['_handler']);
        unset($inputs['_session_key']);
        unset($inputs['_token']);
        $counter = count($currentFields['Fields']) + 1;
        $key = Str::slug($inputs['fieldLabel']).'-'.$counter;
        $fields = $currentFields['Fields'] + [ 
            $key => $inputs + ['fieldType' => $fieldType] + ['order' => $counter]
        ];
        $newApp->template = ['Fields' => $fields] + $currentFields;
        //dd($newApp->template);
        $newApp->save();
        return [
            '#fieldsBar' => $this->renderPartial('@chooseFields.htm'),
            '#appTemplate' => $this->renderPartial('@newApp.htm')
        ];
    }
    
    public function onEditField() {
        $field = Input::get('field');
        $fieldType = Input::get('fieldType');
        $this->page['field'] = $field;
        session::put('fieldKey', $field);
        Session::put('fieldType', $fieldType);
        return [
            '#fieldsBar' => $this->renderPartial('@fields/'.$fieldType)
        ];
    }
    
    public function getField($field) {
        $newApp = $this->getNewApp();
        $currentFields = $newApp->template;
        $field = $currentFields['Fields'][$field];
        return $field;
    }
    
    public function onUpdateField() {
        $inputs = Input::all();
        unset($inputs['_handler']);
        unset($inputs['_session_key']);
        unset($inputs['_token']);
        $newApp = $this->getNewApp();
        $key = Session::get('fieldKey');
        $fieldType = Session::get('fieldType');
        $currentFields = $newApp->template;
        unset($currentFields['Fields'][$key]);
        $counter = count($currentFields['Fields']) + 1;
        $key = Str::slug($inputs['fieldLabel']).'-'.$counter;
        $fields = $currentFields['Fields'] + [ 
            $key => $inputs + ['fieldType' => $fieldType]
        ];
        $newApp->template = ['Fields' => $fields] + $currentFields;
        //dd($newApp);
        $newApp->save();
        return [
            '#fieldsBar' => $this->renderPartial('@chooseFields.htm'),
            '#appTemplate' => $this->renderPartial('@newApp.htm')
        ];
    }
    
    public function onDeleteField() {
        $newApp = $this->getNewApp();
        $key = Session::get('fieldKey');
        $fieldType = Session::get('fieldType');
        $currentFields = $newApp->template;
        unset($currentFields['Fields'][$key]);
        $newApp->template = $currentFields;
        $newApp->save();
        return [
            '#fieldsBar' => $this->renderPartial('@chooseFields.htm'),
            '#appTemplate' => $this->renderPartial('@newApp.htm')
        ];
    }
    
}// End of PHP class
