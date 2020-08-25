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
}
