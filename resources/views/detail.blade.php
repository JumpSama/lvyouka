<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" id="viewport" content="width=device-width, initial-scale=1.0,minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name') }}-商品详情</title>
    <link href="{{asset('css/common.css')}}" rel="stylesheet" type="text/css" media="screen,projection" />
    <script type="text/javascript" src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
</head>

<body>
<div class="content">
    <div class="banner">
        <div class="swiper-container">
            <div class="swiper-wrapper">
                @foreach ($banner as $one)
                    <div class="swiper-slide"><img src="{{ $one }}"  alt=""/></div>
                @endforeach
            </div>
            <!-- Add Pagination -->
            <div class="swiper-pagination"></div>
        </div>
    </div>

    <div class="info_box">
        <p>{{ $detail->name }}</p>
        <div class="l"><span>{{ (integer)$detail->price }}</span>积分</div>
        <div class="r">库存剩余：{{ $detail->stock }}</div>
    </div>

    <div class="info_box2 mt10">
        <h1>商品详情</h1>
        <div class="text">
            {!! $detail->content !!}
        </div>
    </div>

    <div class="bth2"></div>

    <div class="btn_bt">
        @if ($detail->stock > 0)
            <input class="b1" type="button" value="立即兑换" id="buy">
        @else
            <input class="b2" type="button" value="已售罄">
        @endif
    </div>
</div>

<script src="{{asset('js/swiper.min.js')}}"></script>
<link rel="stylesheet" href="{{asset('js/swiper.min.css')}}">
<script>
    var id = '{{ $detail->id }}';

    new Swiper('.swiper-container', {
        pagination: {
            el: '.swiper-pagination',
        }
    });

    // 兑换
    $('#buy').on('click', function () {
        if (!confirm('确认兑换吗？')) return false;
        $.ajax({
            type: 'post',
            url: 'commodity_buy',
            data: {
                id: id
            },
            dataType: 'json',
            success: function (res) {
                if (res.code === 200) {
                    alert('兑换成功');
                    location.reload();
                } else {
                    alert(res.msg);
                }
            }
        })
    });
</script>

</body>
</html>