<?php

namespace Acelle\Plugin\AwsWhitelabel\Controllers;

use Acelle\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Acelle\Plugin\AwsWhitelabel\Main;
use Acelle\Model\SendingServer;

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

    /**
     * Whitelabel.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function popup(Request $request, $uid)
    {
        $server = SendingServer::findByUid($uid);
        $plugin = \Acelle\Model\Plugin::getByName('acelle/aws-whitelabel');

        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        return view('awswhitelabel::popup', [
            'server' => $server,
            'plugin' => $plugin,
        ]);
    }

    /**
     * Whitelabel settings.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function connection(Request $request, $uid)
    {
        $server = SendingServer::findByUid($uid);

        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            // make validator
            $validator = \Validator::make($request->all(), [
                'brand' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('awswhitelabel::popup', [
                    'server' => $server,
                    'errors' => $validator->errors(),
                ], 400);
            }
            
            // update plugin data
            $plugin = \Acelle\Model\Plugin::getByName('acelle/aws-whitelabel');
            $plugin->updateData([
                $server->uid => $request->brand,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.whitelable.updated'),
            ]);
        }

        $main = new Main();
        $record = $main->getDbRecord();
        return view('awswhitelabel::connection', [
            'server' => $server,
            'plugin' => $record,
        ]);
    }

    /**
     * Turn off Whitelabelling.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function turnOff(Request $request, $uid)
    {
        $server = SendingServer::findByUid($uid);

        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        // update plugin data
        $plugin = \Acelle\Model\Plugin::getByName('acelle/aws-whitelabel');
        $data = $plugin->getData();
        unset($data[$server->uid]);
        $plugin['data'] = json_encode($data);
        $plugin->save();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.whitelable.turned_of'),
        ]);
    }
}
