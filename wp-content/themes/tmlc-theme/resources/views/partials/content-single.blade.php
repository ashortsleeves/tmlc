<article @php post_class() @endphp>
  @if(has_post_thumbnail())
    <div class="img-wrap jumbo-bg" style="background-image: url({{ the_post_thumbnail_url() }});">
    </div>
  @endif
  <header>
    <h1 class="entry-title">{!! get_the_title() !!}</h1>
  </header>
  <div class="entry-content">
    @php the_content() @endphp
  </div>
  <footer>
    {!! wp_link_pages(['echo' => 0, 'before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']) !!}
  </footer>
</article>
