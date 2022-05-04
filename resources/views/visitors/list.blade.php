@include('layout.head')
@include('layout.header')

<section class="container">
    <form method="POST" action='<?php echo htmlspecialchars('/visitors');?>' id="methodForm">
        @csrf
        <div>
            <label for="content" class="form-label">방명록</label>
            <div class="input-group">
                <textarea class="form-control mainArea" aria-label="With textarea" id='content' name="content"placeholder="글을 입력해 주세요."></textarea>
                <span class="input-group-text">
                    <button type="button" class="btn btn-secondary" onclick='btnEvent()' name="btn" value="insert">등록</button>
                </span>
            </div>

            @if(!\Illuminate\Support\Facades\Auth::check())
            <div class="input-group mb-3">
                <span class="input-group-text" id="basic-addon1">게스트 패스워드</span>
                <input type="password" class="form-control" id="visitorsPassword" name="visitorsPassword" required>
            </div>
            @endif

        </div>
    </form>

    <div style="margin: 1rem 0 0 0;">
        <ul class="list-group">
            @foreach($data as $item)
            <li class="list-group-item" data-board="{{$item->no}}">
                <div class="firstBox">
                    <div data-board="{{$item->no}}" data-user="{{$item->user_no}}" data-type="{{$item->user_type}}">
                        <span>{{$item->user_name}}</span>
                        <div>
                            @if($item->user_type == 'g')
                            <input type="password" id="boardPassword{{$item->no}}" name="boardPassword" style="height: 1.25rem; width: 10rem;" placeholder="password">
                            @endif
                            <small onclick="updateHtml(this)">수정</small>
                            <small onclick="deleteAction(this)">삭제</small>
                        </div>
                    </div>
                    <small>{{$item->registration_date}}</small>
                </div>

                <div id="contentBox{{$item->no}}">
                    <p>{{$item->content}}</p>
                </div>

                <div>
                    <a href="javascript:void(0);" onclick="commentList(this)" style="color: red;">댓글 {{$item->comment_count}}</a>
                </div>

                <div id="commentBox{{$item->no}}" class="commentBox">
                    <div id="commentBlock">
                        <ul class="list-group" data-board="{{$item->no}}"></ul>
                    </div>
                    <div class="input-group">
                        <textarea class="form-control" aria-label="With textarea" id="comment{{$item->no}}" placeholder="글을 입력해 주세요."></textarea>
                        <span class="input-group-text">
                            @if(!\Illuminate\Support\Facades\Auth::check())
                            <input type="password" name="boardPassword" style="height: 1.25rem; width: 10rem;" placeholder="password">
                            @endif
                            <button type="button" class="btn btn-secondary" onclick='commentEvent(this)' data-board="{{$item->no}}">등록</button>
                        </span>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>

</section>
<script>
    // 방명록 등록
    function btnEvent() {
        if(!{{\Illuminate\Support\Facades\Auth::check()}}) {
            const result = window.confirm('비회원으로 글을 등록 하시겠습니까?');
            if(!result) {
                return;
            }
            // 비회원 글 등록
            document.querySelector('#methodForm').submit();
            return;
        }
        // 회원 글 등록
        document.querySelector('#methodForm').submit();
    };
</script>

@include('layout.footer')
