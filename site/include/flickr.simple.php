<?php

/*
	# sample usage
	
	$rsp = $flickr->call_method('flickr.photos.search', array(
                'auth_token' => $auth_token,
                'user_id' => 'me',
                'has_geo' => 1,
                'tags' => 'cameraphone',
                'min_taken_date' => $ago,
                'extras' => 'machine_tags,geo',
        ));
        
        foreach($rsp['photos']['photo'] as $photo) {
		.....
	}

*/

class Flickr {

	private $debug = 0;

	function Flickr($api_key, $api_secret='', $api_host='', $debug=0) {
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
		$this->debug = $debug;
		if (!$api_host) {
			$this->api_host = 'api.flickr.com';
		} else {
			$this->api_host = $api_host;
		}
		$this->auth_host = 'www.flickr.com';

		$this->_debug("initialized with api key '{$this->api_key}', secret '{$this->api_secret}', talking to '{$this->api_host}'");
	}
	
	function call_method($method, $args=array(), $sign_call=0) {
		$args['format'] = 'php_serial';
		$args['api_key'] = $this->api_key;
		$args['method'] = $method;
        
		$base_url = "http://" . $this->api_host . "/services/rest/?";
		
		$url = $this->_request_url($base_url, $args, $sign_call);

		$this->_debug("request url: {$url}");

		$rsp = file_get_contents($url);
		$rsp_obj = unserialize($rsp);

		$this->_debug("response content: \n{$rsp}");
		$this->_debug("response object: \n".print_r($rsp_obj, true));

		if (!$this->ok($rsp_obj)) {
			return $this->on_error($rsp_obj);
		} else {
			return $rsp_obj;
		}
	}
	
	function sign_args($args, $secret) {
		ksort($args);

		$a = '';

		foreach ($args as $k => $v){
			$a .= $k . $v;
		}

		$sig_string = $secret.$a;
		$sig = md5($sig_string);

		$this->_debug("signature string:\n{$sig_string}\nsignature:\n{$sig}");

		return $sig;
	}
	
	#
	# note: assumes desktop auth
	#
	
	function auth_shell($perms='read') {
		$rsp = $this->call_method('flickr.auth.getFrob', array(), 1);
		if ($this->ok($rsp)) {
			$frob = $rsp['frob']['_content'];
		
			$url = $this->auth_url($frob, $perms);
			echo "Open this URL: $url\n";
			echo "Hit return when done.\n";
			fgets(STDIN);
		
			$rsp = $this->call_method('flickr.auth.getToken', array('frob' => $frob), 'sign');
			if ($this->ok($rsp)) {
				echo "Token: " . $rsp['auth']['token']['_content'] . "\n";
				return;
			}
		}
		
		echo "Something went wrong :(\n";
		exit;
	}
	
	function auth_url($frob, $perms='read') {
		$args = array(
			'api_key' => $this->api_key,
			'frob' => $frob,
			'perms' => $perms,
		);
		$base_url = "http://" . $this->auth_host . "/services/auth/?";
		$url = $this->_request_url($base_url, $args, 'sign');
		return $url;
	}
	
	function on_error($rsp) {
		return $rsp;
	}
	
	function ok($rsp) {
		return ($rsp['stat'] == 'ok') ? true : false;
	}
	
	function _request_url($base_url, $args=array(), $sign_call=0) {
		
		if (isset($args['auth_token']) and $args['auth_token'] or $sign_call) {
			$args['api_sig'] = $this->sign_args($args, $this->api_secret);
		}

		$encoded_params = array();

		foreach ($args as $k => $v){
			$encoded_params[] = urlencode($k).'='.urlencode($v);
		}

		return $base_url.implode('&', $encoded_params);
	}

	private function _debug($str) {
		if ($this->debug) {
			echo "[debug] $str\n";
		}
	}
}

?>