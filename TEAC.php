<?php

class TEAC
{
	function __construct()
	{
		$this -> version = "1.0";
		$this -> server = 'http://api.eve-online.com';
	}

	function get_xml($type, $post = NULL)
	{
		if($type == 'standings')
			$url = "/corp/Standings.xml.aspx";
		elseif($type == 'alliances')
			$url = "/eve/AllianceList.xml.aspx";
		elseif($type == 'corp')
			$url = "/corp/CorporationSheet.xml.aspx";
		elseif($type == 'charsheet')
			$url = "/char/CharacterSheet.xml.aspx";
		elseif($type == 'facwar')
			$url = "/char/FacWarStats.xml.aspx";
		elseif($type == 'find')
			$url = "/eve/CharacterID.xml.aspx";
		elseif($type == 'name')
			$url = "/eve/CharacterName.xml.aspx ";
		else
			$url = "/account/Characters.xml.aspx";

		if(!empty($post))
		{
			foreach($post as $i => $v)
			{
				$post[$i] = $i.'='.$v;
			}
			$post = implode('&', $post);
		}

		$cache = FALSE;
		if(function_exists($this -> get_cache))
		{
			$cache = $this -> get_cache($url, $post);
		}
		if($cache)
			return $cache;

		$xml = $this -> get_site($this -> server.$url, $post);

		if(function_exists($this -> set_cache))
		{
			$cache = $this -> set_cache($url, $post, $xml);
		}

		return $xml;
	}

	function get_site($url, $post=FALSE)
	{
		if(!function_exists('curl_init'))
		{
			$return = $this->get_site_sock($url, $post);
			if ($return['error'])
			{
				echo $return['errordesc'] . " Reason (" . $return['content'] . ")";
			}

			return $return["content"];
		}
		else
		{
			Return $this->get_site_curl($url, $post);
		}
	}

	function get_site_sock($url, $post)
	{
		$get_url = parse_url($url);

		$address = gethostbyname($get_url['host']);

		/* Get the port for the WWW service. */
		$service_port = getservbyname('www', 'tcp');

		/* Create a TCP/IP socket. */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		// Check to see if the socket failed to create.
		if ($socket === false) {
			$return["error"] = true;
			$return["errordesc"] = "Failed to create a socket";
			$return["content"] = socket_strerror(socket_last_error());

			return $return;
		}
		
		// Set some sane read timeouts to prevent the bot from hanging forever.
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 30, "usec" => 0));
		
		$connect_result = @socket_connect($socket, $address, $service_port);

		// Make sure we have a connection
		if ($connect_result === false)
		{
			echo "Failed to connect to server: $url\n";
			$return["error"] = true;
			$return["errordesc"] = "Coult not connect to server " . $address . ":" . $service_port . " (" . $url . ")";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		// Rebuild the full query after parse_url
		$url = $get_url["path"];
		// if (!empty($get_url["query"]))
		// {
			// $url .= '?' . $get_url["query"];
		// }
		if(!empty($post))
		{
			$url .= '?'.$post;
		}

		$in = "GET $url HTTP/1.0\r\n";
		$in .= "Host: " . $get_url['host'] . "\r\n";
		$in .= "Connection: Close\r\n";
		$in .= "User-Agent:TEA 1.1.1\r\n\r\n";

		$write_result = @socket_write($socket, $in, strlen($in));

		// Make sure we wrote to the server okay.
		if ($write_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Coult not write to server";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		$return["content"] = "";
		$read_result = @socket_read($socket, 2048);
		while ($read_result != "" && $read_result !== false)
		{
			$return["content"] .= $read_result;
			$read_result = @socket_read($socket, 2048);
		}

		// Make sure we got a response back from the server.
		if ($read_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Server returned no data";
			$return["content"] = socket_strerror(socket_last_error());
			
			return $return;
		}

		$close_result = @socket_close($socket);

		// Make sure we closed our socket properly.  Open sockets are bad!
		if ($close_result === false)
		{
			$return["error"] = true;
			$return["errordesc"] = "Failed to close socket";
			$return["content"] = socket_strerror(socket_last_error());
	
			return $return;
		}

		// Did the calling function want http headers stripped?
	//	if ($strip_headers)
	//	{
			$split = explode("\r\n\r\n", $return['content'], 2);
			$return["content"] = $split[1];
	//	}

		return $return;
	}

	function get_site_curl($url, $post)
	{
		$ch = curl_init();

		if(!empty($post))
		{
			curl_setopt($ch, CURLOPT_POST      ,1);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec($ch);
		curl_close($ch);

		//echo "<pre>"; var_dump($data); echo "</pre>";
		Return $data;
	}

	function corp_info($corp)
	{
		$post = array('corporationID' => $corp);
		$xml2 = $this -> get_xml('corp', $post);
		$xml = new SimpleXMLElement($xml2);
		if(isset($xml -> result -> corporationName))
		{
			$info['corpname'] = (string)$xml -> result -> corporationName;
			$info['ticker'] = (string)$xml -> result -> ticker;
			$info['allianceid'] = (string)$xml -> result -> allianceID;
			if(empty($info['allianceid']) || $info['allianceid'] == '')
				$info['allianceid'] = 0;
			$info['alliance'] = (string)$xml -> result -> allianceName;
		}
		Return ($info);
	}
}

?>