@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <h1>TEST</h1>
    @include('partials.content-single-'.get_post_type())
    @php echo do_shortcode('[gravityform id="2" title="false" description="false" ajax="true" tabindex="49" field_values="check=First Choice,Second Choice"]'
) @endphp
  @endwhile
@endsection
