<section class="content-section">
  <img class="content-section-background" src="{{$footer['background']['url']}}" />
  <div class="container container-sm content-container">
    <div class="breadcrumbs cardstyle">
      {{bcn_display($return = false, $linked = true, $reverse = false, $force = false)}}
    </div>
    <div class="cardstyle">
      @php the_content() @endphp
    </div>
  </div>
</section>
