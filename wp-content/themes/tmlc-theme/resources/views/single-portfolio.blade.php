@extends('layouts.app')

@section('content')
  @if(has_post_thumbnail())
    <section class="page-header">
      <div class="background responsive-hero jumbo-bg">

      </div>
      <div class="container">
        <h1>{!! get_the_title() !!}</h1>
        @if(!empty(get_field('location')))
          <span>{!!get_field('location')!!}</span>
        @endif
      </div>
    </section>
  @endif
  <section class="blog-wrap content-section" style="background-image: url({{$footer['background']['url']}}})">
      <img class="content-section-background" src="{{$footer['background']['url']}}" />
    @if (!have_posts())
      <div class="alert alert-warning">
        {{ __('Sorry, no results were found.', 'sage') }}
      </div>
      {!! get_search_form(false) !!}
    @endif
    <div class="container container-sm content-container posts-container">
      <div class="breadcrumbs cardstyle">
        {{bcn_display($return = false, $linked = true, $reverse = false, $force = false)}}
      </div>

      @while(have_posts()) @php the_post() @endphp
        <article @php post_class() @endphp>
          <div class="entry-content">
            @php the_content() @endphp
          </div>
        </article>
      @endwhile
    </div>
  </section>
@endsection
