{{--
  Template Name: Services Template
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @include('partials.page-header')
    <section class="content-section service-content-section">
      <img class="content-section-background" src="{{$footer['background']['url']}}" />
      {{-- <div class="container container-sm content-container">
        <div class="cardstyle">
          @php the_content() @endphp
        </div>
      </div> --}}
        @foreach($tmp_service as $service)
          <div class="service-section">
            <div class="container">
              <div class="service-image jumbo-bg" style="background-image: url({{$service['image']['url']}})">

                <svg xmlns="http://www.w3.org/2000/svg" height="100%" viewBox="0 0 306 694">
                  <defs>
                    <style>
                      .cls-1 {
                        fill: #fff;
                        fill-rule: evenodd;
                      }
                    </style>
                  </defs>
                  <path class="cls-1" d="M5,694H311L41,423c-17.921-17.987-29.492-42.581-31-72-1.522-29.686,10.1-58.722,31-85L306,0H5V694Z" transform="translate(-5)"/>
                </svg>

              </div>
              <div class="content cardstyle">
                <h2>{{$service['title']}}</h2>
                {!!$service['content']!!}
                @if($service['button'])
                  <a class="btn" href="{{$service['button']['url']}}">{{$service['button']['title']}}</a>
                @endif
              </div>
            </div>
          </div>
        @endforeach

    </section>


    </section>
  @endwhile
@endsection
