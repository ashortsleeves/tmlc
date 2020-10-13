{{--
  Template Name: Events Template
--}}

@extends('layouts.app')

@section('content')
  @if (!have_posts())
    <div class="alert alert-warning">
      {{ __('Sorry, no results were found.', 'sage') }}
    </div>
    {!! get_search_form(false) !!}
  @endif
  @while (have_posts()) @php the_post() @endphp

    <section class="content-section content-section-events">
      <img class="content-section-background" src="{{$footer['background']['url']}}" />
      <div class="container container-sm content-container">
        @php the_content() @endphp
      </div>
    </section>

  @endwhile
  @php

  $args = array(
    'post_type' => 'tribe_events',
    'post_status' => 'publish',
  );

  $loop = new WP_Query( $args );

  while ( $loop->have_posts() ) : $loop->the_post();
    print the_title();
    the_excerpt();
  endwhile;

  wp_reset_postdata();
  @endphp
@endsection
