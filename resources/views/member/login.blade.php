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

    <form action='/login' method="post" id="methodForm">
        @csrf
        <div class="mb-3">
            <label for="userId" class="form-label">ID</label>
            <input type="text" class="form-control" value="{{old('userId')}}" name="userId" id="userId" required>
        </div>
        <div class="mb-3">
            <label for="userPassword" class="form-label">Password</label>
            <input type="password" class="form-control" name="userPw" id="userPassword" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

</section>

@include('layout.footer')
