{{--
  Template Name: Events Template
--}}

@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  @if (!have_posts())
    <div class="alert alert-warning">
      {{ __('Sorry, no results were found.', 'sage') }}
    </div>
    {!! get_search_form(false) !!}
  @endif
  @while (have_posts()) @php the_post() @endphp
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

  @endwhile

  {!! get_the_posts_navigation() !!}
@endsection
