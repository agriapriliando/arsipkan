@extends('layouts.platform')

@section('content')
    <livewire:tenant.user-file-browser :mode="$mode" :heading="$heading" :description="$description" />
@endsection
