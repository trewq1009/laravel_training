@include('layout.head')
@include('layout.header')

<section class="container">
    <table class="table">
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Method</th>
            <th scope="col">Using Mileage +</th>
            <th scope="col">Using Mileage -</th>
            <th scope="col">Event Mileage +</th>
            <th scope="col">Event Mileage -</th>
            <th scope="col">Real Mileage +</th>
            <th scope="col">Real Mileage -</th>
            <th scope="col">Registration Date</th>
            <th scope="col">#</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($list as $item)
            <tr>
                <th scope="row"></th>
                <td>{{ $item->method }}</td>
                <td>{{ $item->using_plus }}</td>
                <td>{{ $item->using_minus }}</td>
                <td>{{ $item->event_plus }}</td>
                <td>{{ $item->event_minus }}</td>
                <td>{{ $item->real_plus }}</td>
                <td>{{ $item->real_minus }}</td>
                <td>{{ $item->registration_date }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{$list->links()}}
</section>

@include('layout.footer')
