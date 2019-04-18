<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" id="viewport" content="width=device-width, initial-scale=1.0,minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name') }}-会员注册</title>
    <link href="{{asset('css/common.css')}}" rel="stylesheet" type="text/css" media="screen,projection" />
    <script type="text/javascript" src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
    <script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.4.0.js"></script>
</head>

<body>
@if ($hasSubmit && $memberInfo['status'] == 2)
<div class="warn">
    <p>审核未通过</p>
    <p><input type="button" value="点击修改" id="edit"></p>
</div>
@endif

@if ($hasSubmit && $memberInfo['status'] < 2)
<div class="success">
    @if ($memberInfo['status'] == 0)
        <p>提交成功，待支付</p>
        <p><input type="button" value="支付" id="pay"></p>
    @else
        <p>提交成功，审核中</p>
    @endif
</div>
@endif

<div class="content" @if ($hasSubmit) style="display: none;" @endif>
    <form id="memberForm">
        <div class="input_block">
            <div class="input_box">
                <span>姓名</span>
                <input class="i1" type="text" placeholder="请填写使用人姓名" name="name" value="{{$memberInfo['name']}}">
            </div>
            <div class="input_box">
                <span>性别</span>
                <div class="radio_box">
                    <label><input name="sex" value="1" type="radio" @if ($memberInfo['sex'] == 1) checked @endif> 男</label>
                    <label><input name="sex" value="2" type="radio" @if ($memberInfo['sex'] == 2) checked @endif> 女</label>
                </div>
            </div>
            <div class="input_box">
                <span>身份证号</span>
                <input type="text" class="i1" placeholder="请填写使用人身份证" name="identity" value="{{$memberInfo['identity']}}">
            </div>
            <div class="input_box">
                <span>手机号</span>
                <input type="tel" class="i1" placeholder="请填写使用人手机号" name="phone" value="{{$memberInfo['phone']}}">
            </div>
            <div class="input_box">
                <span>验证码</span>
                <input class="i2" type="text" placeholder="请填写手机验证码">
                <input class="b1" type="button" value="发送验证码" id="send">
            </div>
            <div class="input_box">
                <span>身份证正面</span>
                <div class="img_upload">
                    <input type="hidden" class="image_url" name="identity_front" value="{{$memberInfo['identity_front']}}">
                    <input class="upload_1" type="file" accept="image/*" name="image">
                    <div class="img_1">
                        @if ($hasSubmit)
                            <img src="{{$memberInfo['identity_front']}}">
                        @endif
                    </div>
                </div>
            </div>
            <div class="input_box">
                <span>身份证反面</span>
                <div class="img_upload2">
                    <input type="hidden" class="image_url" name="identity_reverse" value="{{$memberInfo['identity_reverse']}}">
                    <input class="upload_1" type="file" accept="image/*" name="image">
                    <div class="img_1">
                        @if ($hasSubmit)
                            <img src="{{$memberInfo['identity_reverse']}}">
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="bth2"></div>

        <div class="btn_bt">
            @if (!$hasSubmit || $memberInfo['status'] == 2)
            <input id="submit" class="b1" type="button" value="绑定或开通">
            @endif
        </div>
    </form>
</div>
<!-- 遮罩 -->
<div id="cover" class="cover2">
    <div class="loading"></div>
</div>

<script type="text/javascript">
    var id = '{{$memberInfo['id']}}';
    var jsConfig = JSON.parse(@json($jsConfig));
    wx.config(jsConfig);

    var fields = {
        'name' : '请填写姓名',
        'sex' : '请选择性别',
        'identity' : '请填写身份证',
        'phone' : '请填写手机号',
        'code' : '请填写手机验证码',
        'identity_front' : '请上传身份证正面',
        'identity_reverse' : '请上传身份证反面'
    };

    $('.upload_1').on('change', handleFile);

    // 图片上传
    function handleFile() {
        var formData = new FormData();
        var url = $(this).siblings('.image_url');
        var preview = $(this).siblings('.img_1');

        formData.append('image', this.files[0]);

        $('#cover').show();

        $.ajax({
            type: 'post',
            data: formData,
            url: 'upload',
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                $('#cover').hide();
                if (res.code === 200) {
                    url.val(res.data.url);
                    preview.empty().append('<img src="' + res.data.url + '">');
                }
            },
            fail: function () {
                $('#cover').hide();
            }
        })
    }

    // 提交
    $('#submit').on('click', function () {
        var flag = false;
        var formData = $('#memberForm').serializeArray();
        var data = {};

        $(formData).each(function () {
            if (!this.value || this.value === '') {
                flag = this.name;
                return false;
            }
            data[this.name] = this.value;
        });
        if (flag === false && !data.sex) flag = 'sex';

        if (flag !== false) {
            alert(fields[flag]);
            return false;
        }

        $('#cover').show();

        $.ajax({
            type: 'post',
            data: data,
            url: 'apply',
            dataType: 'json',
            success: function (res) {
                $('#cover').hide();

                if (res.code === 200) {
                    var data = res.data;

                    // 支付
                    if (data.params && data.params.package) {
                        var params = data.params;
                        wx.chooseWXPay({
                            timestamp: params.timestamp,
                            nonceStr: params.nonceStr,
                            package: params.package,
                            signType: params.signType,
                            paySign: params.paySign,
                            success: function (res) {
                                alert('支付成功！');

                                location.reload();
                            },
                            cancel: function () {
                                alert('已取消支付！');

                                location.reload();
                            }
                        });
                    } else {
                        location.reload();
                    }
                } else {
                    alert(res.msg);
                }
            }
        })
    });

    // 修改
    $('#edit').on('click', function () {
       $('.warn').hide();
       $('.content').show();
    });

    // 重新支付
    $('#pay').on('click', function () {
        $('#cover').show();

        $.ajax({
            type: 'post',
            data: {
                id: id
            },
            url: 'repay',
            dataType: 'json',
            success: function (res) {
                $('#cover').hide();

                if (res.code === 200) {
                    var data = res.data;

                    // 支付
                    var params = data.params;
                    wx.chooseWXPay({
                        timestamp: params.timestamp,
                        nonceStr: params.nonceStr,
                        package: params.package,
                        signType: params.signType,
                        paySign: params.paySign,
                        success: function (res) {
                            alert('支付成功！');

                            location.reload();
                        },
                        cancel: function () {
                            alert('已取消支付！');
                        }
                    });
                } else {
                    alert(res.msg);
                }
            }
        })
    });
</script>
</body>
</html>