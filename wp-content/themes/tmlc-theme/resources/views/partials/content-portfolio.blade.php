<article @php post_class() @endphp>
  @if(has_post_thumbnail())

  <div class="img-wrap jumbo-bg" style="background-image: url({{ the_post_thumbnail_url() }});">
    <a href="{{ get_permalink() }}">
      <h2 class="entry-title">{!! get_the_title() !!}</h2>
      @if(tribe_get_venue($featured_post->ID))
        <span>{{tribe_get_venue($featured_post->ID)}}</span>
      @elseif(!empty(get_field('location',$featured_post->ID)))
        <span>{!!get_field('location',$featured_post->ID)!!}</span>
      @endif
    </a>
  </div>
  @endif
</article>
