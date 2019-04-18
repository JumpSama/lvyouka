<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" id="viewport" content="width=device-width, initial-scale=1.0,minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name') }}</title>
    <link href="{{asset('css/common.css')}}" rel="stylesheet" type="text/css" media="screen,projection" />
    <script type="text/javascript" src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
</head>

<body>
<div class="content">
    <div class="card_box">
        <div class="wx_btn"></div>
        @if ($member['status'] == 1)
            <div class="time">过期时间：{{ $member['overdue'] }}</div>
        @else
            <div class="time">已过期</div>
        @endif
    </div>

    <div class="card_info">
        <div class="l">
            <div class="t1"><a href="{{ url('/wechat/point') }}">我的积分</a><em></em></div>
            <div class="t2">{{ (integer)$member['point'] }}</div>
        </div>
        <div class="r">
            <div class="t1"><a href="{{ url('/wechat/sign') }}">每日签到</a><em></em></div>
            <div class="t2">已连续签到<em>{{ $signDay }}</em>天</div>
        </div>
    </div>

    <ul class="list_1">
        <li><a class="i1" href="{{ url('/wechat/order') }}">我的订单</a></li>
    </ul>

    <div class="bth"></div>

    <div class="menu_bt">
        <a class="i1" href="{{ url('/wechat/mall') }}">全部商品</a>
        <a class="i2 slt" href="#">我的</a>
    </div>

    <!--  弹出二维码 -->
    <div class="wm_open"><img src=""></div>

    <!--  遮罩层 -->
    <div class="cover"></div>

    <!-- 加载遮罩 -->
    <div id="cover2" class="cover2">
        <div class="loading"></div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        $(".wx_btn").click(function(){
            $('#cover2').show();

            $.ajax({
                type: 'post',
                url: 'qr_code',
                dataType: 'json',
                success: function (res) {
                    $('#cover2').hide();

                    if (res.code === 200) {
                        $('.wm_open img').attr('src', res.data.url);
                        $(".cover,.wm_open").show();
                    } else {
                        alert('获取二维码失败');
                    }
                }
            });
        });

        $(".cover,.wm_open").click(function(){
            $(".cover,.wm_open").hide();
        });
    });
</script>

</body>
</html>