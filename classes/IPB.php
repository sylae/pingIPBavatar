<?php

/*
 * @author Keira Sylae Aro <sylae@calref.net>
 * @copyright Copyright (C) 2017 Keira Sylae Aro <sylae@calref.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sylae;

/**
 * Class for managing IPB stuff.
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class IPB {

  /**
   * Hold the $config object passed in the constructor
   * @var array
   */
  private $config = [];

  /**
   * Hold a curl object to do all the things.
   * @var \Curl\Curl
   */
  private $curl;

  /**
   * Init a new IPB instance and log in
   * @param array $config Config object (@see config.sample.php)
   */
  public function __construct(array $config) {
    $this->config = $config;
    try {
      ini_set('user_agent', "sylae/pingIPBavatar (https://github.com/sylae/pingIPBavatar)");
      $this->curl = new \Curl\Curl();
      $this->curl->setCookieJar("cookies.txt");
      $this->curl->setUserAgent("sylae/pingIPBavatar (https://github.com/sylae/pingIPBavatar)");
      $this->curl->setOpt(CURLOPT_FOLLOWLOCATION, true);

      // todo: if already logged in, skip this
      $this->login();
    } catch (Throwable $e) {
      var_dump($e);
    }
  }

  /**
   * Log in to the forum
   * @return bool true if login successful
   */
  private function login(): bool {
    $this->curl->get($this->config['ipbURL'] . '/login/');
    $html = $this->curl->response;

    $auth = \htmlqp($html, 'input[name=\'csrfKey\']')->attr("value");

    $this->curl->post($this->config['ipbURL'] . '/login/', [
      'auth' => $this->config['ipbUser'],
      'password' => $this->config['ipbPass'],
      'login__standard_submitted' => 1,
      'csrfKey' => $auth,
      ], true);
    return true;
  }

  /**
   * Set avatar URL
   * @param string $url URL to set avatar to
   * @return bool True if success
   */
  public function setAvatarURL(string $url): bool {
    $form = $this->curl->get($this->config['ipbURL'] . "/profile/" . $this->config['ipbProfileLink'] . "/photo/");

    $data = [
      'form_submitted' => 1,
      'csrfKey' => \htmlqp($form, 'input[name=\'csrfKey\']')->attr("value"),
      'MAX_FILE_SIZE' => \htmlqp($form, 'input[name=\'MAX_FILE_SIZE\']')->attr("value"),
      'plupload' => \htmlqp($form, 'input[name=\'plupload\']')->attr("value"),
      'radio_pp_photo_type__empty' => 1,
      'pp_photo_type' => 'url',
      'member_photo_upload' => \htmlqp($form, 'input[name=\'member_photo_upload\']')->attr("value"),
      'member_photo_url' => $url,
    ];

    $r = $this->curl->post($this->config['ipbURL'] . "/profile/" . $this->config['ipbProfileLink'] . "/photo/", $data, true);
    return (bool) ($r->error ?? false);
  }

}
