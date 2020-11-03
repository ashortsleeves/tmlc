<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class TemplateServices extends Controller
{
  public function tmpService()
  {
    return get_field('services_repeater');
  }
}
