<article @php post_class() @endphp>
  @if(has_post_thumbnail())
    <div class="img-wrap jumbo-bg" style="background-image: url({{ the_post_thumbnail_url() }});">
      <span class="post-type">
        @if (get_post_type() === 'post')
          Article
        @elseif (get_post_type() === 'tribe_events')
          Event
        @else
          {!! get_post_type() !!}
        @endif
      </span>
    </div>
  @endif
  <header>
    <h2 class="entry-title"><a href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h2>
  </header>
  @if (get_post_type() === 'post')
    @include('partials/entry-meta')
  @endif
  <div class="entry-summary">
    @php the_excerpt() @endphp
  </div>
  <a href="{{get_permalink() }}" class="btn">Read More</a>
</article>
