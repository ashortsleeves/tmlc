@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <section class="fp-hero">
      <div class="container">
        <svg class="mobile-svg" viewBox="0 0 448 512">
            <defs>
                <!-- pattern3 - aspect ratio control on both image and pattern -->
                <pattern id="pattern3" height="100%" width="100%"
                patternContentUnits="objectBoundingBox"
                viewBox="0 0 1 1" preserveAspectRatio="xMidYMid slice" >
                    <image height="1" width="1"  preserveAspectRatio="xMidYMid slice" href="{!!$fp_hero['hero_image_mobile']['url']!!}"/>
                </pattern>
            </defs>

          <path d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z" fill="#00a7cf">

        </svg>
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
        {{-- <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="lock" class="svg-inline--fa fa-lock fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
          <path fill="currentColor" d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z">
          </path>
        </svg> --}}



        {{-- <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="lock" class="svg-inline--fa fa-lock fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
          <defs>
              <pattern id="img1" width="100%" height="100%" >
                  <image href="{{$fp_hero['hero_image']['url']}}" x="-200" y="0"/>
              </pattern>
          </defs>
          <path d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z" fill="url(#img1)" fill="#00a7cf">
          </path>
        </svg> --}}

      </div>






{{--
      <div class="svg-container jumbo-bg" style="background-image: url({{$fp_hero['hero_image']['url']}})">
        <svg
         xmlns="http://www.w3.org/2000/svg"
         xmlns:xlink="http://www.w3.org/1999/xlink"
         width="1361px" height="2708px">
        <path fill-rule="evenodd"  fill="rgb(255, 255, 255)"
         d="M1361.000,2708.000 C1185.333,2708.000 1009.667,2708.000 834.000,2708.000 C556.000,2708.000 278.000,2708.000 0.000,2708.000 C0.000,1805.333 0.000,902.667 0.000,0.000 C453.333,0.000 906.667,0.000 1360.000,0.000 C938.000,422.000 516.000,844.000 94.000,1266.000 C72.264,1289.767 60.124,1320.789 60.000,1353.000 C59.875,1385.555 72.032,1416.978 94.000,1441.000 C516.333,1863.333 938.667,2285.667 1361.000,2708.000 Z"/>
        </svg>

      </div> --}}
      <div class="fp-strapline">

      </div>
    </section>
  @endwhile
@endsection
