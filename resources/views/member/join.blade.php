@include('layout.head')
@include('layout.header')

<section class="container">

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                <li>{{ $errors->first() }}</li>
            </ul>
        </div>
    @endif

    <form action='/register' method="post" id="methodForm">
        @csrf
        <div class="mb-3">
            <label for="userId" class="form-label">ID</label>
            <input type="text" class="form-control" value="{{ old('userId') }}" name="userId" id="userId" required>
        </div>
        <div class="mb-3">
            <label for="userName" class="form-label">Name</label>
            <input type="text" class="form-control" value="{{ old('userName') }}" name="userName" id="userName" required>
        </div>
        <div class="mb-3">
            <label for="userPassword" class="form-label">Password</label>
            <input type="password" class="form-control" name="userPw" id="userPassword" required>
        </div>
        <div class="mb-3">
            <label for="passwordConfirm" class="form-label">Password Confirm</label>
            <input type="password" class="form-control" name="userPwC" id="passwordConfirm" required>
        </div>
        <div class="mb-3">
            <label for="userEmail" class="form-label">Email</label>
            <input type="email" class="form-control" value="{{ old('userEmail') }}" name="userEmail" id="userEmail" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

</section>



@include('layout.footer')
