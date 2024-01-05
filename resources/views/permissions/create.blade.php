@extends('layouts.app')

@section('content')
<!-- <div class="row">
    <div class="col-lg-12 margin-tb">
        <div class="pull-left">
            <h2>Create New Role</h2>
        </div>
        <div class="pull-right">
            <a class="btn btn-primary" href="{{ route('permissions.index') }}"> Back</a>
        </div>
    </div>
</div> -->

@if (count($errors) > 0)
<div class="alert alert-danger">
    <strong>Whoops!</strong> There were some problems with your input.<br><br>
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row">
    <div class="col-md-6">
        <h3>Existing Permissions</h3>
        <ul>
            @foreach ($permissions as $permission)
            <li>{{ $permission->name }}</li>
            @endforeach
        </ul>
    </div>
    <div class="col-md-6">
        <h3>Create New Permission</h3>
        {!! Form::open(['route' => 'permissions.store', 'method' => 'POST']) !!}
        <div class="form-group">
            <strong>Name:</strong>
            {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => 'Enter Permission Name']) !!}
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Create Permission</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

<p class="text-center text-primary"><small>Tutorial by ItSolutionStuff.com</small></p>
@endsection