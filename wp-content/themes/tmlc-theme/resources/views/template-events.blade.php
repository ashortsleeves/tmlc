{{--
  Template Name: Events Template
--}}

@extends('layouts.app')
@php
  $hideContact = true;
@endphp
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
      <div class="container container-sm">
        @php the_content() @endphp
      </div>
    </section>
    {{-- @php

    $args = array(
      'post_type' => 'tribe_events',
      'post_status' => 'publish',
    );

    $loop = new WP_Query( $args );
    @endphp
    <div class="event-slick">
      @php
        while ( $loop->have_posts() ) : $loop->the_post();
      @endphp
        <div class="jumbo-bg" style="background-image: url({{get_the_post_thumbnail_url()}})">
          {{the_title()}}
          {{the_excerpt()}}
        </div>
      @endwhile
    </div>
    @php wp_reset_postdata(); @endphp --}}
  @endwhile

@endsection
