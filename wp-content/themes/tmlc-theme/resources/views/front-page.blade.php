@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <section class="fp-hero">
      <div class="container hero-container">
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
        <div class="container strapline-container">
            @foreach($fp_strapline as $affiliate)
              <div class="affiliate-wrap">
                <div class="jumbo-bg affiliate @if(!$affiliate['logo']) affiliate-title @endif" style="background-image: url({{$affiliate['image']['url']}})">
                  @if(!$affiliate['logo'])
                    <span>{!!$affiliate['title']!!}</span>
                  @endif
                </div>
              </div>
            @endforeach
        </div>
      </div>
    </section>
    <section class="fp-services jumbo-bg">
      <div class="background-wrap">
        <img class="section-background" src="{{$fp_services['background']['url']}}" />
      </div>
      <div class="container title-container">
        <h1>{{$fp_services['title']}}</h1>
        @if($fp_services['subtitle'])
          <span class="subtitle">{!!$fp_services['subtitle']!!}</span>
        @endif
      </div>
      <div class="container container-services">
        <div class="row">
          @foreach($fp_services['services'] as $service)
            <div class="col-md-4 col-12">
              <div class="service jumbo-bg">
                <div class="jumbo-bg" style="background-image: url({!!$service['image']['url']!!})"></div>
                <h2>{!!$service['title']!!}</h2>
                @if($service['subtitle'])
                  <span class="subtitle">{!! $service['subtitle'] !!}</span>
                @endif
                @if($service['button'])
                  <a class="btn" href="{{$service['button']['url']}}">{{$service['button']['title']}}</a>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
    <section class="fp-works">
      <div class="container">
        @foreach($fp_works as $work)
          <div class="row work-row">
            <div class="col-md-6">
              <img src="{{$work['image']['url']}}" />
            </div>
            <div class="col-md-6">
              <span class="subtitle">{{$work['subtitle']}}</span>
              <h1 class="h2">{!!$work['title']!!}</h1>
              {!!$work['content']!!}
              <a class="btn" href="{{$work['button']['url']}}">{{$work['button']['title']}}</a>
            </div>
          </div>
        @endforeach
      </div>
    </section>

    @if($fp_events)
      <section class="fp-events">
        <div class="background-wrap">
          <img class="section-background" src="{{$fp_eventstitle['background']['url']}}" />
        </div>
        <div class="container">
          <div class="title">
            <h1>{!! $fp_eventstitle['title'] !!}</h1>
            <span class="subtitle">{!! $fp_eventstitle['subtitle'] !!}</span>
          </div>
          <div class="event-wrapper">
            <div class="events-grid">
              @php $i = 0; @endphp
              @foreach( $fp_events as $featured_post )
                @php
                  $permalink = get_permalink( $featured_post->ID );
                  $title = get_the_title( $featured_post->ID );
                  $i++;
                @endphp
                @if($i < 6)
                  <div class="event-single">
                    <span class="event-bg jumbo-bg" style="background-image: url({!! get_the_post_thumbnail_url($featured_post->ID) !!});"></span>
                    <a class="event-link" href="{!!esc_url( $permalink )!!}">
                      <span class="event-title">
                        <h2>{!! esc_html( $title ) !!}</h2>
                        @if(tribe_get_venue($featured_post->ID))
                          <span>{{tribe_get_venue($featured_post->ID)}}</span>
                        @elseif(!empty(get_field('location',$featured_post->ID)))
                          <span>{!!get_field('location',$featured_post->ID)!!}</span>
                        @endif
                      </span>
                    </a>
                  </div>
                @endif
              @endforeach
              <div class="event-single jumbo-bg">
                <span class="event-bg jumbo-bg"></span>
                <a class="view-more" href="/portfolio">
                  View More
                  <span>Events</span>
                  <i class="fas fa-arrow-alt-right"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </section>
    @endif

    @if($fp_team)
      <section class="fp-team">
        <img class="section-background" src="{!!$fp_team['image']['url']!!}"/>
        <div class="container team-container">
          <h1>{{$fp_team['title']}}</h1>
          <span class="subtitle">{{$fp_team['subtitle']}}</span>
          @foreach($fp_team['team_members'] as $team)
            <div class="team-member">
              <div class="image-wrap">
                <img src="{!!$team['image']['url']!!}" />
              </div>
              <div class="content">
                <h2>{{$team['name']}}</h2>
                <span class="jobtitle">{{$team['job_title']}}</span>
                {{$team['bio']}}
                <a class="email" href="mailto:{!!$team['email']!!}">{{$team['email']}}</a>
                <a target="_blank" class="linkedin" href="{!!$team['linkedin']!!}">LinkedIn</a>
              </div>
            </div>
          @endforeach
          @if($fp_team['button'])
            <a href="{{$fp_team['button']['url']}}" class="btn btn-blue">{{$fp_team['button']['title']}}</a>
          @endif
        </div>
      </section>
    @endif
    @if($fp_testimonial['testimonials'])
      <section class="fp-testimonials">
        <i class="fas fa-quote-left bg-quote"></i>
        <div class="container">
          <h1>{{$fp_testimonial['title']}}</h1>
          <div class="slick-quotes">
            @foreach($fp_testimonial['testimonials'] as $testimonial)
              <div class="slick-wrap">
                <div class="testimonial-single">
                  "{!!$testimonial['quote']!!}"
                </div>
              </div>
            @endforeach
          </div>
          <div class="slick-names">
            @foreach($fp_testimonial['testimonials'] as $testimonial)
              <div class="testimonial-wrap">
                <div class="slick-wrap">
                  <div class="testimonial-single">
                    <span class="name">{{$testimonial['name']}} -</span>
                    <span class="org">{{$testimonial['organization']}}</span>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>

      </section>

    @endif
  @endwhile
@endsection
