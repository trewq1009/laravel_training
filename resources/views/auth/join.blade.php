@include('layout.head')
@include('layout.header')

<section class="container">

    <form action='/register' method="post" id="methodForm">
        @csrf
        @if ($errors->has('field'))
            <span class="text-danger">{{ $errors->first('field') }}</span>
        @endif
        <div class="mb-3">
            <label for="userId" class="form-label">ID</label>
            <input type="text" class="form-control" value="{{ old('userId') }}" name="userId" id="userId" required>
            @if ($errors->has('userId'))
                <span class="text-danger">{{ $errors->first('userId') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <label for="userName" class="form-label">Name</label>
            <input type="text" class="form-control" value="{{ old('userName') }}" name="userName" id="userName" required>
            @if ($errors->has('userName'))
                <span class="text-danger">{{ $errors->first('userName') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <label for="userPassword" class="form-label">Password</label>
            <input type="password" class="form-control" name="userPw" id="userPassword" required>
            @if ($errors->has('userPw'))
                <span class="text-danger">{{ $errors->first('userPw') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <label for="passwordConfirm" class="form-label">Password Confirm</label>
            <input type="password" class="form-control" name="userPwC" id="passwordConfirm" required>
            @if ($errors->has('userPwC'))
                <span class="text-danger">{{ $errors->first('userPwC') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <label for="userEmail" class="form-label">Email</label>
            <input type="email" class="form-control" value="{{ old('userEmail') }}" name="userEmail" id="userEmail" required>
            @if ($errors->has('userEmail'))
                <span class="text-danger">{{ $errors->first('userEmail') }}</span>
            @endif
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

</section>



@include('layout.footer')
