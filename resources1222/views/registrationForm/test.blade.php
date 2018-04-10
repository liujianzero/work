<h3>报名者信息</h3>
<h4>姓名：</h4>{{ $name }}；
<h4>电话：</h4>{{ $mobile }}；
<h4>学习课程：</h4>@if($course == '0') 3DMAX @else 3DMAXWEBVR @endif；
<h4>学习地址：</h4>
          @if($address == '0')
            劳动局(厦门市长青路191号劳动力大厦3楼312、313室)
          @elseif($address == '1')
             软件园二期(软件园二期望海路47号302)
          @else{{--($address == '2')--}}
             集美(杏林湾商业运营中心9号楼裙楼创星谷2楼)
          @endif；
<h4>备注：</h4>
        @if(!empty($remark))
            {{ $remark }}
        @else
            无备注
        @endif。