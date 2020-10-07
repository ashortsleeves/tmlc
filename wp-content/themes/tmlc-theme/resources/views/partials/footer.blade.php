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
        @php gravity_form($footer['form'], true); @endphp
      </div>
    </div>
  </div>
</section>

<footer class="content-info">
  <div class="container">
    <img class="footer-logo" src="{{$footer['logo']['url']}}" alt="{{$footer['logo']['alt']}}"/>
    <nav class="nav-footer">
      @if (has_nav_menu('primary_navigation'))
        {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav']) !!}
      @endif
    </nav>
    <ul class="socials">
      @if($footer['site_info']['facebook'])
        <li>
          <a href="{{$footer['site_info']['facebook']}}"><i class="fab fa-facebook-f"></i></a>
        </li>
      @endif
      @if($footer['site_info']['instagram'])
        <li>
          <a href="{{$footer['site_info']['instagram']}}"><i class="fab fa-instagram"></i></a>
        </li>
      @endif
      @if($footer['site_info']['linkedin'])
        <li>
          <a href="{{$footer['site_info']['linkedin']}}"><i class="fab fa-linkedin-in"></i></a>
        </li>
      @endif
    </ul>

  </div>
</footer>

<div class="copyright">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-wd-10 col-12 col-copyright">
        <div class="ci-copy">
         Website Design & Development © <?php echo date("Y"); ?>
          <a href="http://www.crafticonic.com/" target="blank" title="Craft Iconic">
            Craft Iconic
          </a>
        </div>
        <div class="client-copy">
         Website Content Copyright © <?php echo date("Y"); ?> <?php bloginfo( 'name' ); ?>
         |  <a href="/sitemap_index.xml" target="_blank">Sitemap</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="application/ld+json">
{
  "@context": "http://schema.org/",
  "@type": "LocalBusiness",
  "name": "{{ get_bloginfo('name', 'display') }}",
  "telephone": "{{$footer['site_info']['phone']}}",
  "address":
  [
      {
          "@type": "PostalAddress",
          "streetAddress": "{!! $footer['site_info']['address'] !!}",
          "addressLocality": "{!! $footer['site_info']['town'] !!}",
          "addressRegion": "{!! $footer['site_info']['state'] !!}",
          "postalCode": "{!! $footer['site_info']['zip'] !!}"
      },
  ]
}
</script>
