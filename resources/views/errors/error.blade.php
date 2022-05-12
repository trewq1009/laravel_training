@include('layout.head')
@include('layout.header')
<section class="container">
    <div class="alert alert-danger">
        <ul>
            <li>{{ $message }}</li>
        </ul>
    </div>
</section>
@include('layout.footer')
