@extends('layouts.platform')

@section('content')
    @livewire('tenant.guest-upload-form', ['code' => $code])
@endsection
