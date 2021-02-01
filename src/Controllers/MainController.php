<?php

namespace Acelle\Plugin\AwsWhitelabel\Controllers;

use Acelle\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Acelle\Plugin\AwsWhitelabel\Main;

class MainController extends Controller
{
    /**
     * Whitelabel setting page.
     *
     * @return string
     **/
    public function index(Request $request)
    {
        $main = new Main();
        $record = $main->getDbRecord();
        $data = $record->getData();
        return view('awswhitelabel::index', [
            'plugin' => $record,
            'data' => $data,
        ]);
    }

    public function save(Request $request)
    {

        $validator = \Validator::make($request->all(),[
            'aws_key' => 'required',
            'aws_secret' => 'required',
        ]);
            
        // redirect if fails
        if ($validator->fails()) {
            $error = print_r(array_values($validator->errors()->toArray()), true);
            $request->session()->flash('alert-error', $error);
            return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@index');
        }

        $main = new Main();
        $main->connectAndActivate($request->input('aws_key'), $request->input('aws_secret'));

        $request->session()->flash('alert-success', "Plugin Activated");
        return redirect()->action('Admin\PluginController@index');
    }
}
