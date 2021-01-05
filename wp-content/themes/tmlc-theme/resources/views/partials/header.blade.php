<header class="banner">
  <div class="container">
    <a class="brand @if(is_front_page() && !$ci_banner['text'])brand-lg @endif" href="{{ home_url('/') }}">
      <img src="{{$site_logo['url']}}" alt="{{ get_bloginfo('name', 'display') }}" />
    </a>
    <nav class="nav-primary">
      @if (has_nav_menu('primary_navigation'))
        {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'walker' => new wp_bootstrap_navwalker(), 'menu_class' => 'nav']) !!}
      @endif
    </nav>
    <button class="hamburger">
      <span>toggle menu</span>
    </button>
  </div>
</header>
@if($ci_banner['text'])
<div class="ci-banner">
  @if($ci_banner['link'])
    <a href="{!!$ci_banner['link']!!}">
      <div class="container">
        {!!$ci_banner['text']!!}
      </div>
    </a>
  @else
    <div class="container">
      {!!$ci_banner['text']!!}
    </div>
  @endif
</div>
@endif

<div class="sideNav" id="sideNav">
  <nav class="nav-primary">
    @if (has_nav_menu('primary_navigation'))
      {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'walker' => new wp_bootstrap_navwalker(), 'menu_class' => 'nav']) !!}
    @endif
  </nav>
</div>
