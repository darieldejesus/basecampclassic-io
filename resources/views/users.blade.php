@extends('layouts.app')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>
    Users
  </h1>
</section>
<!-- Main content -->
<section class="content">
<!-- /.content -->
  <!-- Default box -->
  <div class="box">
    <div class="box-body">
      <!-- /.box-header -->
      <div class="box-body no-padding">
        <table class="table table-striped">
          <tr>
            <th style="width: 50px"># ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Options</th>
          </tr>
          @foreach ($users as $user)
          <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>
              <div class="btn-group">
                <a class="btn btn-default" href="/users/edit/{{ $user->id }}">Edit</a>
              </div>
            </td>
          </tr>
          @endforeach
        </table>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.box-body -->
    <div class="box-footer">
      <!-- Footer -->
    </div>
    <!-- /.box-footer-->
  </div>
  <!-- /.box -->
</section>
@endsection