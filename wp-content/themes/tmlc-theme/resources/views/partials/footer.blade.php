<section class="contact-footer">
  <img class="section-background" src="{{$footer['background']['url']}}" />
  <div class="container">
    <div class="row">
      <div class="col-md-6">
        <div class="contact-content">
          {!! $footer['content'] !!}
        </div>
        <div class="contact-wrap">
          <img class="contact-logo" src="{{$site_logo['url']}}" alt="{{ get_bloginfo('name', 'display') }}" />
          @if($footer['site_info'])
            <ul class="footer-details">
              @if($footer['site_info']['email'])
                <li class="email">
                  <a href="mailto:{{$footer['site_info']['email']}}">
                    {{$footer['site_info']['email']}}
                  </a>
                </li>

              @endif
              @if($footer['site_info']['phone'])
                <li class="phone">
                  <a href="tel:{{$footer['site_info']['phone']}}">
                    {{$footer['site_info']['phone']}}
                  </a>
                </li>

              @endif
              @if($footer['site_info']['address'])
                <li class="address">
                  {{$footer['site_info']['address']}}<br />
                  {{$footer['site_info']['town']}},  {{$footer['site_info']['state']}} {{$footer['site_info']['zip']}}
                </li>
              @endif
            </ul>
          @endif      
        </div>
      </div>
      <div class="col-md-6">
        @php gravity_form($footer['form'], false); @endphp
      </div>
    </div>
  </div>
</section>

<footer class="content-info">
  <div class="container">

  </div>
</footer>
