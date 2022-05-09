@include('layout.head')
@include('layout.header')

<section class="container">
    <form style="margin: 3rem 0 0 0" method="post" action='<?php echo htmlspecialchars('#');?>' enctype="multipart/form-data">
        <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon1">게시글 제목</span>
            <input type="text" class="form-control" name="boardName" required>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon1">상품 이름</span>
            <input type="text" class="form-control" name="productName" required>
        </div>
        <div class="input-group mb-3">
            <label class="input-group-text" for="inputGroupFile01">이미지</label>
            <input type="file" class="form-control" id="inputGroupFile01" name="imageInfo">
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon1">가격</span>
            <input type="text" class="form-control" id="priceValue" name="productPrice" placeholder="1000원 이상" onKeyup="this.value=this.value.replace(/[^-0-9]/g,'');" onchange="priceCommission(this)" required>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon1">실가격</span>
            <input type="text" class="form-control" id="realPrice" readonly required>
            <span class="input-group-text" id="basic-addon1">수수료</span>
            <input type="text" class="form-control" id="commission" readonly required>
        </div>
        <div class="input-group">
            <span class="input-group-text">상세 설명</span>
            <textarea class="form-control" aria-label="With textarea" name="productInformation"></textarea>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin: 1rem 0;">
            <div>
                <button type="submit" class="btn btn-primary" name="btn" value="insert">등록</button>
                <button type="button" class="btn btn-secondary">임시</button>
            </div>
            <button type="button" class="btn btn-secondary">이전 페이지</button>
        </div>
    </form>
</section>

<script>
    function priceCommission(event) {
        const price = event.value;
        const commission = 0.05;
        let commissionPrice = 0;
        if(price < 1000) {
            alert('1000원 이상에 금액만 가능 합니다.');
            return;
        }
        commissionPrice = price * commission;
        document.querySelector('#commission').value = Math.floor(commissionPrice);
        document.querySelector('#realPrice').value = Math.ceil(price - commissionPrice);
    }
</script>

@include('layout.footer')
