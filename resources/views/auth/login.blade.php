@include('layout.head')
@include('layout.header')

<section class="container">

    <form action='/login' method="post" id="methodForm">
        @csrf
        <div class="mb-3">
            <label for="userId" class="form-label">ID</label>
            <input type="text" class="form-control" value="{{old('userId')}}" name="userId" id="userId" required>
            @if ($errors->has('userId'))
                <span class="text-danger">{{ $errors->first('userId') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <label for="userPassword" class="form-label">Password</label>
            <input type="password" class="form-control" name="userPw" id="userPassword" required>
            @if ($errors->has('userPw'))
                <span class="text-danger">{{ $errors->first('userPw') }}</span>
            @endif
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

</section>

@include('layout.footer')
