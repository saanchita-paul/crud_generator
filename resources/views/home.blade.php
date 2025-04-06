@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                        <p class="float-end">{{ __('You are logged in!') }}</p>

                        @if (!empty($models))
                            <h5 class="mt-5">Generated Models</h5>
                            <ul class="list-group">
                                @foreach ($models as $model)
                                    @php
                                        $route = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($model));
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Model: {{ $model }}
                                        <div>
                                            <a href="{{ route($route . '.index') }}" class="btn btn-sm btn-outline-primary">List</a>
                                            <a href="{{ route($route . '.create') }}" class="btn btn-sm btn-outline-success">Create</a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
