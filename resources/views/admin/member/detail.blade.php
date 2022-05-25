@include('layout.admin.head')
@include('layout.admin.header')

<section class="container">
    <form action='<?php echo htmlspecialchars('/');?>' method="post" id="methodForm">
        @csrf
        <div class="mb-3">
            <label for="userId" class="form-label">ID</label>
            <input type="text" class="form-control" value="{{$data->id}}" name="userId" id="userId" readonly required>
        </div>
        <div class="mb-3">
            <label for="userName" class="form-label">Name</label>
            <input type="text" class="form-control" value="{{$data->name}}" name="userName" id="userName" required>
        </div>
        <div class="mb-3">
            <label for="userEmail" class="form-label">Email</label>
            <input type="email" class="form-control" value="{{$data->email}}" name="userEmail" id="userEmail" readonly required>
        </div>
        <div class="mb-3">
            <label for="userEmailStatus" class="form-label">Email Authentication</label>
            <input type="text" class="form-control" value="{{$data->email_status}}" name="userEmailStatus" id="userEmailStatus" readonly required>
        </div>
        <div class="mb-3">
            <label for="usingMileage" class="form-label">Using Mileage</label>
            <input type="text" class="form-control" value="{{$data->using_mileage}}"id="usingMileage" readonly required>
        </div>
        <div class="mb-3">
            <label for="realMileage" class="form-label">Real Mileage</label>
            <input type="text" class="form-control" value="{{$data->real_mileage}}" id="realMileage" readonly required>
        </div>
        <div class="mb-3">
            <label for="eventMileage" class="form-label">Event Mileage</label>
            <input type="text" class="form-control" value="{{$data->event_mileage}}" id="eventMileage" readonly required>
        </div>
        <div class="mb-3">
            <label for="userStatus" class="form-label">Status</label>
            <input type="text" class="form-control" value="{{$data->status_kr}}" name="userStatus" id="userStatus" readonly required>
        </div>
        <div class="mb-3">
            <label for="userRegistered" class="form-label">Registered</label>
            <input type="text" class="form-control" value="{{$data->registration_date}}" name="userRegistered" id="userRegistered" readonly required>
        </div>

        <button type="submit" name="action" value="update" class="btn btn-primary">회원수정</button>
        @if($data->status === 'a')
        <button type="submit" name="action" value="delete" class="btn btn-danger">회원탈퇴</button>
        @endif
    </form>
</section>

@include('layout.admin.footer')
