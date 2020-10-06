<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class FrontPage extends Controller
{
  public function fpHero()
  {
    return get_field('fp_hero');
  }

  public function fpStrapline()
  {
    return get_field('fp_affiliate_strapline');
  }

  public function fpServices()
  {
    return get_field('fp_services');
  }

  public function fpWorks()
  {
    return get_field('fp_how_it_works');
  }

  public function fpEventstitle()
  {
    return get_field("featured_event_title");
  }

  public function fpEvents()
  {
    return get_field('fp_featured_events');
  }

  public function fpTeam()
  {
    return get_field('team_section');
  }

  public function fpTestimonial()
  {
    return get_field('testimonial_section');
  }
}
