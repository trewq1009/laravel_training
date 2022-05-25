@include('layout.head')
@include('layout.header')

<section class="container">
    <div class="alert alert-danger">
        <ul>
            <li>{{ $exception->getMessage() }}</li>
        </ul>
    </div>
</section>

@include('layout.footer')
