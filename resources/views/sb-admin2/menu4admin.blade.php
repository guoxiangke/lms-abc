<li class="nav-item {{url()->current()==route('home')?'active':''}}">
  <a class="nav-link" href="/home">
    <i class="fas fa-fw fa-tachometer-alt"></i>
    <span>{{__('Dashboard')}}</span>
  </a>
</li>

@env(['production','local'])
<li class="nav-item {{url()->current()==route('orders.index')?'active':''}}">
  <a class="nav-link" href="{{ route('orders.index') }}">
    <i class="fas fa-fw fa-table"></i>
    <span>订单列表</span>
  </a>
</li>
<hr class="sidebar-divider">
<!-- Heading -->
<div class="sidebar-heading">
  LMS-EN
</div>

<!-- Nav Item - Dashboard -->
<li class="nav-item {{url()->current()==route('orders.create')?'active':''}}">
  <a class="nav-link" href="{{ route('orders.create') }}">
    <i class="fas fa-fw fa-cart-plus"></i>
    <span>创建订单</span>
  </a>
</li>
<li class="nav-item {{url()->current()==route('students.index')?'active':''}}">
  <a class="nav-link" href="{{ route('students.index') }}">
    <i class="fas fa-fw fa-cart-plus"></i>
    <span>一键试听</span>
  </a>
</li>
<li class="nav-item {{url()->current()==route('classRecords.index')?'active':''}}">
  <a class="nav-link" href="{{ route('classRecords.index') }}">
    <i class="fas fa-fw fa-book-reader"></i>
    <span>课程记录</span>
  </a>
</li>

@endenv

@env(['staging','local'])
<!-- Divider -->
<hr class="sidebar-divider">

<!-- Heading -->
<div class="sidebar-heading">
  LMS-CN
</div>



<!-- Nav Item - Dashboard -->
<li class="nav-item {{url()->current()==route('profiles.index')?'active':''}}">
  <a class="nav-link" href="{{ route('profiles.index') }}">
    <i class="fa fa-fw fa-address-card"></i>
    <span>用户资料</span>
  </a>
</li>

<!-- Nav Item - Pages Collapse Menu -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
    <i class="fas fa-fw fa-table"></i>
    <span>订单列表</span>
  </a>
  <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">订单相关操作:</h6>
      @foreach(App\Models\Order::LIST_BY as $item)
        @php
          $route = "orders.{$item}";
          $isActive = url()->current() == route($route);
        @endphp
        <a class="collapse-item" href="{{ route($route) }}"><i class="fas fa-fw fa-table"></i> {{ $item }}</a>
      @endforeach
    </div>
  </div>
</li>
<!-- Nav Item - Dashboard -->
<li class="nav-item {{url()->current()==route('bills.index')?'active':''}}">
  <a class="nav-link" href="{{ route('bills.index') }}">
    <i class="fab fa-fw fa-cc-visa"></i>
    <span>{{__('Bills')}}</span>
  </a>
</li>


<li class="nav-item {{url()->current()==route('teachers.index')?'active':''}}">
  <a class="nav-link" href="{{ route('teachers.index') }}">
    <i class="fas fa-fw fa-book-reader"></i>
    <span>Teachers</span>
  </a>
</li>

<!-- Nav Item - Pages Collapse Menu -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="true" aria-controls="collapsePages">
    <i class="fas fa-fw fa-users"></i>
    <span>用户角色</span>
  </a>
  <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <a class="collapse-item" href="/agencies">{{__('Agency')}}</a>
      <a class="collapse-item" href="/schools">{{__('Schools')}}</a>
      <a class="collapse-item" href="/students">{{__('Students')}}</a>
    </div>
  </div>
</li>

<!-- Nav Item - Utilities Collapse Menu -->
<li class="nav-item">
  <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="true" aria-controls="collapseUtilities">
    <i class="fas fa-fw fa-wrench"></i>
    <span>系统设置</span>
  </a>
  <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <h6 class="collapse-header">系统配置:</h6>
      <a class="collapse-item" href="/books">{{__('Books')}}</a>
      <a class="collapse-item" href="/products">{{__('Products')}}</a>
      <a class="collapse-item" href="/rrules">{{__('Rrules')}}</a>
      <div class="collapse-divider"></div>
      <h6 class="collapse-header">用户权限:</h6>
      <a class="collapse-item" href="/users">{{__('Users')}}</a>
      <a class="collapse-item" href="/roles">{{__('Roles')}}</a>
      <a class="collapse-item" href="/permissions">{{__('Permissions')}}</a>
    </div>
  </div>
</li>
@endenv