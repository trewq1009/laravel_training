@include('layout.head')
@include('layout.header')

<section class="container">

    <form action='' method="post" id="methodForm">
        @csrf
        @if ($errors->has('field'))
            <span class="text-danger">{{ $errors->first('field') }}</span>
        @endif
        <div class="mb-3">
            <label for="userId" class="form-label">ID</label>
            <input type="text" class="form-control" value="{{$id}}" name="userId" id="userId" readonly required>
        </div>
        <div class="mb-3">
            <label for="userName" class="form-label">Name</label>
            <input type="text" class="form-control" value="{{$name}}" name="userName" id="userName" required>
            @if ($errors->has('userName'))
                <span class="text-danger">{{ $errors->first('userName') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <label for="userPassword" class="form-label">Password</label>
            <input type="password" class="form-control" name="userPw" id="userPassword">
            @if ($errors->has('userPw'))
                <span class="text-danger">{{ $errors->first('userPw') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <label for="userPasswordConfirm" class="form-label">Password Confirm</label>
            <input type="password" class="form-control" name="userPwC" id="userPasswordConfirm">
            @if ($errors->has('userPwC'))
                <span class="text-danger">{{ $errors->first('userPwC') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <label for="userEmail" class="form-label">Email</label>
            <input type="email" class="form-control" value="{{$email}}" name="userEmail" id="userEmail" readonly required>
        </div>
        <div class="mb-3">
            <label for="userMileage" class="form-label">Mileage</label>
            <input type="text" class="form-control" value="{{$mileage}}" name="userUsingMileage" id="userMileage" readonly required>
        </div>
        <div class="mb-3">
            <label for="userUseMileage" class="form-label">Using Mileage</label>
            <input type="text" class="form-control" value="{{$using_mileage}}" name="userUseMileage" id="userUseMileage" readonly required>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between">
            <div>
                <button type="button" name="action" onclick="btnAction(this)" value="update" class="btn btn-primary">정보수정</button>
                <button type="button" name="action" onclick="btnAction(this)" value="withdrawal" class="btn btn-primary">마일리지 출금</button>
                <button type="button" name="action" onclick="btnAction(this)" value="mileageReport" class="btn btn-info">마일리지 내역확인</button>
            </div>
            <button type="button" name="action" onclick="btnAction(this)" value="delete" class="btn btn-danger">탈퇴신청</button>
        </div>
    </form>
</section>

<script>
    function btnAction(event) {
        let form = document.querySelector('#methodForm');
        if(event.value === 'update') {
            form.action = '/profile';
            form.method = 'POST';
            form.submit();
        } else if(event.value === 'withdrawal') {
            window.location.href = '/withdrawal';
        } else if(event.value === 'mileageReport') {
            window.location.href = '/mileageReport';
        } else {
            if(!window.confirm('탈퇴 신청을 하시겠습니까?')) return;
            form.action = '/delete';
            form.method = 'POST';
            form.submit();
        }
    }
</script>

@include('layout.footer')
