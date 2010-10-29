<?php

class TEAC
{
	function __construct()
	{
		$this -> version = "1.0";
	}

	function get_xml($type, $post = NULL)
	{
		if($type == 'standings')
			$url = "http://api.eve-online.com/corp/Standings.xml.aspx";
		elseif($type == 'alliances')
			$url = "http://api.eve-online.com/eve/AllianceList.xml.aspx";
		elseif($type == 'corp')
			$url = "http://api.eve-online.com/corp/CorporationSheet.xml.aspx";
		elseif($type == 'charsheet')
			$url = "http://api.eve-online.com/char/CharacterSheet.xml.aspx";
		elseif($type == 'facwar')
			$url = "http://api.eve-online.com/char/FacWarStats.xml.aspx";
		elseif($type == 'find')
			$url = "http://api.eve-online.com/eve/CharacterID.xml.aspx";
		elseif($type == 'name')
			$url = "http://api.eve-online.com/eve/CharacterName.xml.aspx ";
		else
			$url = "http://api.eve-online.com/account/Characters.xml.aspx";

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

		$xml = $this -> get_site($url, $post);

		if(function_exists($this -> set_cache))
		{
			$cache = $this -> set_cache($url, $post, $xml);
		}

		return $xml;
	}

	function get_site($url, $post=FALSE)
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