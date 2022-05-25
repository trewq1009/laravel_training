@include('layout.head')
@include('layout.header')

<section class="container">
    <table class="table">
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Method</th>
            <th scope="col">Before Mileage</th>
            <th scope="col">Use Mileage</th>
            <th scope="col">After Mileage</th>
            <th scope="col">Registration Date</th>
            <th scope="col">#</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($list as $item)
            <tr>
                <th scope="row"></th>
                <td>{{ $item->method }}</td>
                <td>{{ $item->before_mileage }}</td>
                <td>{{ $item->use_mileage }}</td>
                <td>{{ $item->after_mileage }}</td>
                <td>{{ $item->registration_date }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{$list->links()}}
</section>

@include('layout.footer')
