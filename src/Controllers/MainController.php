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

        if (!array_key_exists('aws_key', $data) || !array_key_exists('aws_secret', $data)) {
            return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@editKey');
        }

        if (!array_key_exists('zone', $data) || !array_key_exists('domain', $data)) {
            return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@selectDomain');
        }

        return view('awswhitelabel::index', [
            'plugin' => $record,
            'data' => $data,
        ]);
    }

    public function editKey(Request $request)
    {
        $main = new Main();
        $record = $main->getDbRecord();
        $data = $record->getData();

        return view('awswhitelabel::edit', [
            'plugin' => $record,
            'data' => $data,
        ]);
    }

    public function reset(Request $request)
    {
        $main = new Main();
        $record = $main->getDbRecord();
        $record->reset();

        return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@editKey');
    }

    public function selectDomain(Request $request)
    {
        $main = new Main();
        $record = $main->getDbRecord();
        $data = $record->getData();
        $domains = $main->getRoute53Domains();
        return view('awswhitelabel::select', [
            'plugin' => $record,
            'data' => $data,
            'domains' => $domains,
        ]);
    }

    public function saveKey(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'aws_key' => 'required',
            'aws_secret' => 'required',
        ]);
            
        // redirect if fails
        if ($validator->fails()) {
            $error = print_r(array_values($validator->errors()->toArray()), true);
            $request->session()->flash('alert-error', $error);
            return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@editKey');
        }

        $main = new Main();
        try {
            $main->connectAndSave($request->input('aws_key'), $request->input('aws_secret'));    
        } catch (\Exception $ex) {
            $request->session()->flash('alert-error', 'Cannot connect to AWS Route53. Error: '.$ex->getMessage());
            return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@editKey');
        }

        $request->session()->flash('alert-success', "Connected to AWS Route53");
        return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@selectDomain');
    }

    public function saveDomain(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'domain' => 'required',
        ]);
            
        // redirect if fails
        if ($validator->fails()) {
            $error = print_r(array_values($validator->errors()->toArray()), true);
            $request->session()->flash('alert-error', $error);
            return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@selectDomain');
        }

        $main = new Main();
        $main->updateDomain($request->input('domain'));

        $request->session()->flash('alert-success', "Domain selected");
        return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@index');
    }

    public function activate(Request $request)
    {
        $main = new Main();
        $main->activate();

        $request->session()->flash('alert-success', "Plugin activated!");
        return redirect()->action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@index');
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
        $main = new Main();
        $record = $main->getDbRecord();
        return view('awswhitelabel::connection', [
            'plugin' => $record,
            'domain' => $record->getData()['domain'],
            'zone' => $record->getData()['zone'],
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
