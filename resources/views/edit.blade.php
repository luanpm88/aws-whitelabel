@extends('layouts.backend')

@section('title', trans('awswhitelabel::messages.aws_whitelabel_plugin'))

@section('page_script')
    <script type="text/javascript" src="{{ URL::asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/validate.js') }}"></script>
@endsection

@section('page_header')

    <div class="page-title">				
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li><a href="{{ action("Admin\PluginController@index") }}">{{ trans('messages.plugins') }}</a></li>
        </ul>
        <div class="d-flex align-items-center">
            <div class="mr-4">
                <img width="80px" height="80px" src="{{ url('/images/plugin.svg') }}" />
            </div>
            <div>
                <h1 class="mt-0 mb-2">
                    {{ $plugin->title }}
                </h1>
                <p class="mb-1">
                    {{ $plugin->description }}
                </p>
                <div class="text-muted">
                    {{ trans('awswhitelabel::messages.version') }}: {{ $plugin->version }}
                </div>
            </div>		
        </div>		
    </div>

@endsection

@section('content')
    
    <div class="row">
        <div class="col-md-6">
            <form method="POST" action="{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@saveKey') }}">
                {{ csrf_field() }}
                <p>
                    {{ trans('awswhitelabel::messages.whitelabel.choose_brand.wording') }}
                </p>
                <div class="row mb-4">
                    <div class="col-md-12 pr-0 form-groups-bottom-0">
                        @include('helpers.form_control', [
                            'type' => 'text',
                            'class' => '',
                            'label' => 'AWS key',
                            'name' => 'aws_key',
                            'value' => isset($data['aws_key']) ? $data['aws_key'] : null,
                            'help_class' => 'aws_key',
                            'rules' => ['aws_key' => 'required']
                        ])
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-12 pr-0 form-groups-bottom-0">
                        @include('helpers.form_control', [
                            'type' => 'text',
                            'class' => '',
                            'label' => 'AWS secret',
                            'name' => 'aws_secret',
                            'value' => isset($data['aws_secret']) ? $data['aws_secret'] : null,
                            'help_class' => 'aws_secret',
                            'rules' => ['aws_secret' => 'required']
                        ])
                    </div>
                </div>

                <div class=" mt-4">
                    <input type="submit" value="Save & Connect">
                    <a href="{{ action('Admin\PluginController@index') }}" class="btn btn-link" style="color: #333">Back to Plugins</a>
                </div>
            </form>
        </div>
    </div>
@endsection
