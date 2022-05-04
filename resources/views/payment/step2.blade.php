@include('layout.head')
@include('layout.header')

<section class="container">
    @if($radioValue == 'credit')
        <h1>카드 결제</h1>
        <form action="<?php echo htmlspecialchars('/payment/credit') ?>" method="post" id="methodForm" name="methodForm">
            @csrf
            <input type="hidden" value="{{$radioValue}}" name="radioValue">
            <input type="hidden" value="{{$price}}" name="price">

            <div class="mb-3">
                <label for="creditNumber1" class="form-label">Card Number</label>
                <div style="display: flex;">
                    <input type="text" class="form-control" value="" name="cardNumber[]" id="creditNumber1" maxlength="4" required>
                    <input type="password" class="form-control" value="" name="cardNumber[]" id="creditNumber2" maxlength="4" required>
                    <input type="password" class="form-control" value="" name="cardNumber[]" id="creditNumber3" maxlength="4" required>
                    <input type="text" class="form-control" value="" name="cardNumber[]" id="creditNumber4" maxlength="4" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="cardMonth" class="form-label">Valid Month/Year</label>
                <div style="display: flex;">
                    <input type="text" class="form-control" name="cardMonth" id="cardMonth" maxlength="2" required>
                    <input type="text" class="form-control" name="cardYear" id="cardYear" maxlength="2" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="cardCVC" class="form-label">CVC</label>
                <input type="password" class="form-control" name="cardCVC" id="cardCVC" maxlength="3" required>
            </div>
            <div class="mb-3">
                <label for="cardPassword" class="form-label">Password</label>
                <input type="password" class="form-control" name="cardPassword" id="cardPassword" maxlength="4" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @elseif($radioValue == 'phone')
        <h1>휴대폰 결제</h1>
        <form action="<?php echo htmlspecialchars('/payment/phone') ?>" method="post" id="methodForm">
            @csrf
            <input type="hidden" value="{{$radioValue}}" name="radioValue">
            <input type="hidden" value="{{$price}}" name="price">

            <div class="mb-3">
                <label for="phoneNumber" class="form-label">Phone Number</label>
                <div style="display: flex;">
                    <input type="text" class="form-control" value="" name="phoneNumber" id="phoneNumber" required>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <label for="flexRadioDefault1" class="form-label">SKT</label>
                    <input class="form-check-input" type="radio" value="skt" name="agencyValue" id="flexRadioDefault1" required>
                </div>

                <div class="form-check">
                    <label for="flexRadioDefault2" class="form-label">KT</label>
                    <input class="form-check-input" type="radio" value="kt" name="agencyValue" id="flexRadioDefault2" required>
                </div>

                <div class="form-check">
                    <label for="flexRadioDefault3" class="form-label">LG</label>
                    <input class="form-check-input" type="radio" value="lg" name="agencyValue" id="flexRadioDefault3" required>
                </div>

                <div class="form-check">
                    <label for="flexRadioDefault4" class="form-label">알뜰 요금제</label>
                    <input class="form-check-input" type="radio" value="thrifty" name="agencyValue" id="flexRadioDefault4" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @elseif($radioValue == 'voucher')
        @csrf
        <h1>상품권 결제</h1>
        <form action="<?php echo htmlspecialchars('/payment/voucher') ?>" method="post" id="methodForm">
            <input type="hidden" value="{{$radioValue}}" name="radioValue">
            <input type="hidden" value="{{$price}}" name="price">

            <div class="mb-3">
                <label for="voucherNumber" class="form-label">Voucher Number</label>
                <div style="display: flex;">
                    <input type="text" class="form-control" value="" name="voucherNumber" id="voucherNumber" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @endif
</section>

@include('layout.footer')
