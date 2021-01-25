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
            <form method="POST" action="">
                {{ csrf_field() }}
                <p>
                    {{ trans('awswhitelabel::messages.whitelabel.choose_brand.wording') }}
                </p>
                <div class="row mb-4">
                    <div class="col-md-12 pr-0 form-groups-bottom-0">
                        @include('helpers.form_control', [
                            'type' => 'text',
                            'class' => '',
                            'label' => '',
                            'name' => 'brand',
                            'value' => 'Amazon Route S3',
                            'disabled' => true,
                            'help_class' => 'whitelabel',
                            'rules' => ['brand' => 'required']
                        ])
                    </div>
                </div>
                <div class=" mt-4">
                    <button class="btn btn-mc_primary mr-3 whitelabel-save">{{ trans('messages.save') }}</button>
                    <a href="{{ action('Admin\PluginController@index') }}" class="btn btn-link" style="color: #333">{{ trans('messages.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
