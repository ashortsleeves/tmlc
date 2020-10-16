@extends('layouts.app')

@section('content')
  <section class="content-section 404-section">
    <img class="content-section-background" src="{{$footer['background']['url']}}" />
    <div class="container container-sm container-404">
      <h1><i class="fas fa-times-hexagon"></i> Page Not Found</h1>
      <h3>{{ __('Sorry, but the page you were trying to view does not exist.', 'sage') }}</h3>
      <button class="btn btn-lg" onclick="goBack()"><i class="fas fa-arrow-left"></i> Go Back</button>

      <script>
      function goBack() {
        window.history.back();
      }
      </script>

    </div>
  </section>

@endsection
