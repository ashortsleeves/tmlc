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
              <p>{{$work['content']}}</p>
              <a class="btn" href="{{$work['button']['url']}}">{{$work['button']['title']}}</a>
            </div>
          </div>
        @endforeach
      </div>
    </section>


    <section class="insert-post-test">
      @php
      if(isset($_POST['new_event']) == '1') {

        $new_event = array(
            'ID' => '',
            'post_type'   => 'tribe_events', // Custom Post Type Slug
            'post_status' => 'draft',
            'post_title'  => $_POST['post_title'],
            'meta_input' => array(
                '_EventURL' => 'www.google.com',
            ),
          );

          $new_ticket = array(
            'ID' => '',
            'post_type' => 'tribe_tpp_tickets',
            'post_title'  => $_POST['post_title'],
          );

        $event_id = wp_insert_post($new_event);
        $ticket_id = wp_insert_post($new_ticket);
        $post = get_posts($event_id, $ticket_id);
      }
      @endphp
      <form method="post" action="">
        <input name="post_title" type="text" />
        <input type="hidden" name="new_event" value="1" />
        <input type="submit" name="submit" value="Post" />
      </form>
    </section>
  @endwhile
@endsection
