@extends('layouts.app')

@section('content')
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
      <div class="row">
        @while (have_posts()) @php the_post() @endphp
          <div class="col-lg-4 col-md-6">
            @include('partials.content-'.get_post_type())
          </div>
        @endwhile
      </div>
      <div class="primary_pagination">

        @php
        //Wordpress Pagination
        global $wp_query;
        $big = 999999999; // need an unlikely integer
        echo paginate_links( array(
          'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
          'format' => '?paged=%#%',
          'current' => max( 1, get_query_var('paged') ),
          'mid_size' => 2,
          'prev_text' => '« <span class="hide-text">Previous</span>',
          'next_text' => '<span class="hide-text">Next</span> »',
          'total' => $wp_query->max_num_pages
        ) );
        @endphp
      </div>
    </div>
  </section>
@endsection
