@extends('phone::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('phone.name') !!}</p>
@endsection
