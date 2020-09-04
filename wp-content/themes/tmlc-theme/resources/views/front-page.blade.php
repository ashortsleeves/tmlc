@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <section class="fp-hero">
      <div class="container">
        <div class="fp-hero-image jumbo-bg" style="background-image: url({{$fp_hero['hero_image']['url']}})">

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
        <div class="cta">
          <i class="fas fa-lock"></i>
          <span class="title">{{$fp_hero['title']}}</span>
          <span class="subtitle">{{$fp_hero['subtitle']}}</span>
        </div>

        <div class="btn-container">
          @if($fp_hero['button_1'])
            <a class="btn" href="{{$fp_hero['button_1']['url']}}">{{$fp_hero['button_1']['title']}}</a>
          @endif
          @if($fp_hero['button_2'])
            <a class="btn btn-blue" href="{{$fp_hero['button_2']['url']}}">{{$fp_hero['button_2']['title']}}</a>
          @endif
        </div>

      </div>
      <div class="fp-strapline">

      </div>
    </section>
  @endwhile
@endsection
