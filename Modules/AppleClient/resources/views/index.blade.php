@extends('appleclient::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('appleclient.name') !!}</p>
@endsection
