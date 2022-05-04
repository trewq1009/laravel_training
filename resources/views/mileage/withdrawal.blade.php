@include('layout.head')
@include('layout.header')

<section class="container">
    <form action='<?php echo htmlspecialchars('/withdrawal');?>' method="post" id="methodForm">
        @csrf
        <div class="mb-3">
            <label for="usingMileage" class="form-label">사용중 마일리지</label>
            <input type="text" class="form-control" value="{{$using_mileage}}" name="usingMileage" id="usingMileage" readonly required>
        </div>
        <div class="mb-3">
            <label for="useMileage" class="form-label">사용 가능 마일리지</label>
            <input type="text" class="form-control" value="{{$use_mileage}}" name="useMileage" id="useMileage" readonly required>
        </div>
        <div class="mb-3">
            <label for="realMileage" class="form-label">출금 가능 마일리지</label>
            <input type="text" class="form-control" value="{{$real_mileage}}" name="realMileage" id="realMileage" readonly required>
        </div>
        <div class="mb-3">
            <label for="drawalMileage" class="form-label">출금할 마일리지</label>
            <input type="text" class="form-control" value="{{old('withdrawalMileage')}}" name="withdrawalMileage" id="drawalMileage" required>
            @if ($errors->has('withdrawalMileage'))
                <span class="text-danger">{{ $errors->first('withdrawalMileage') }}</span>
            @endif
        </div>
        <div class="mb-3">
            <div class="form-check">
                <label for="flexRadioDefault1" class="form-label">신한</label>
                <input class="form-check-input" type="radio" value="신한" name="bankValue" id="flexRadioDefault1" required>
            </div>
            <div class="form-check">
                <label for="flexRadioDefault2" class="form-label">기업</label>
                <input class="form-check-input" type="radio" value="기업" name="bankValue" id="flexRadioDefault2" required>
            </div>
            <div class="form-check">
                <label for="flexRadioDefault2" class="form-label">농협</label>
                <input class="form-check-input" type="radio" value="농협" name="bankValue" id="flexRadioDefault2" required>
            </div>
        </div>
        <div class="mb-3">
            <label for="bankNumber" class="form-label">계좌번호</label>
            <input type="text" class="form-control" value="{{old('bankNumber')}}" name="bankNumber" id="bankNumber" required>
            @if ($errors->has('bankNumber'))
                <span class="text-danger">{{ $errors->first('bankNumber') }}</span>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">출금신청</button>
    </form>
</section>

@include('layout.footer')
