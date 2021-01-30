<?php

namespace Acelle\Plugin\AwsWhitelabel\Controllers;

use Acelle\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MainController extends Controller
{
    /**
     * Whitelabel setting page.
     *
     * @return string
     **/
    public function index(Request $request) {
        if ($request->isMethod('post')) {
            // Redirect to my lists page
            $request->session()->flash('alert-success', trans('awswhitelabel::messages.plugin.setting.updated'));
        }

        return view('awswhitelabel::index', [
            'plugin' => \Acelle\Model\Plugin::getByName('acelle/aws-whitelabel'),
        ]);
    }
}
