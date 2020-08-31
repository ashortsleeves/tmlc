@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <section class="fp-hero">
      <div class="container">
        <div class="cta">
          <i class="fas fa-lock"></i>
          <span class="title">{{$fp_hero['title']}}</span>
          <span class="subtitle">{{$fp_hero['subtitle']}}</span>
          <div class="btn-container">
            @if($fp_hero['button_1'])
              <a class="btn" href="{{$fp_hero['button_1']['url']}}">{{$fp_hero['button_1']['title']}}</a>
            @endif
            @if($fp_hero['button_2'])
              <a class="btn btn-blue" href="{{$fp_hero['button_2']['url']}}">{{$fp_hero['button_2']['title']}}</a>
            @endif
          </div>
        </div>
      </div>


      <div class="svg-container jumbo-bg" style="background-image: url({{$fp_hero['hero_image']['url']}})">
        <svg
         xmlns="http://www.w3.org/2000/svg"
         xmlns:xlink="http://www.w3.org/1999/xlink"
         width="1361px" height="2708px">
        <path fill-rule="evenodd"  fill="rgb(255, 255, 255)"
         d="M1361.000,2708.000 C1185.333,2708.000 1009.667,2708.000 834.000,2708.000 C556.000,2708.000 278.000,2708.000 0.000,2708.000 C0.000,1805.333 0.000,902.667 0.000,0.000 C453.333,0.000 906.667,0.000 1360.000,0.000 C938.000,422.000 516.000,844.000 94.000,1266.000 C72.264,1289.767 60.124,1320.789 60.000,1353.000 C59.875,1385.555 72.032,1416.978 94.000,1441.000 C516.333,1863.333 938.667,2285.667 1361.000,2708.000 Z"/>
        </svg>

      </div>
      <div class="fp-strapline">

      </div>
    </section>
  @endwhile
@endsection
