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
      $this->curl = new \Curl\Curl();
      $this->curl->setCookieJar("cookies.txt");
      $this->curl->setUserAgent("sylae/pingIPBavatar (https://github.com/sylae/pingIPBavatar");
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
    $url = $this->config['ipbURL'] . "app=core&module=global&section=login";
    $html = file_get_contents($url);

    $auth = \htmlqp($html, 'input[name=\'auth_key\']')->attr("value");

    $this->curl->post($this->config['ipbURL'] . 'app=core&module=global&section=login&do=process', [
      'ips_username' => $this->config['ipbUser'],
      'ips_password' => $this->config['ipbPass'],
      'auth_key' => $auth,
    ]);
    return true;
  }

  /**
   * Set avatar URL
   * @param string $url URL to set avatar to
   * @return bool True if success
   */
  public function setAvatarURL(string $url): bool {
    $form = $this->curl->get($this->config['ipbURL'] . "app=members&module=profile&section=photo");

    $form_url = \htmlqp($form, "#photoEditorForm")->attr("action");
    $bits = [];
    parse_str($form_url, $bits);
    $session = $this->curl->getCookie("session_id");
    $secure_key = $bits['secure_key'];

    $postURL = sprintf($this->config['ipbURL'] . "s=%s&app=members&module=ajax&section=photo&do=importUrl&secure_key=%s", $session, $secure_key);

    $r = $this->curl->post($postURL, [
      'url' => $url,
    ]);
    return (bool) ($r->error ?? false);
  }

}
