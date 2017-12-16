<?php

/**
 * User: teomantuncer
 * Date: 16.12.2017
 * Time: 22:41
 */


/**
 * @method static HavaDurumu il(string $isim) İl belirlemek için parametre olarak plaka gönderin
 * @method static HavaDurumu ilce(string $isim) İlçe belirlemek için parametre olarak isim gönderin
 */
class HavaDurumu
{
	private $url = 'https://servis.mgm.gov.tr/api/';
	private $servis = [
		'il' => 'merkezler/iller',
		'ilce' => 'merkezler/ililcesi?il=',
		'son' => 'sondurumlar?istno=',
		'saatlik' => 'tahminler/saatlik?istno=',
		'gunluk' => 'tahminler/gunluk?istno='
	];
	private $method = 'file', $iller, $il, $ilceler, $ilce, $ilFirst;

	/**
	 * HavaDurumu constructor.
	 */
	function __construct()
	{
		array_walk($this->servis, function(&$i) {
			$i = $this->url.$i;
		});
		if(func_get_args()){
			$pars = (is_array(func_get_args()[0]) ? func_get_args()[0] : func_get_args()[0]);
			if(!is_array($pars)){
				$this->ilFirst = $pars;
				$this->ilGetir();
			}else{
				if(isset($pars['method']))
					$this->method = $pars['method'];
				if(isset($pars['il']))
					$this->ilFirst = $pars['il'];
			}
		}
	}

	/**
	 * @param $name
	 * @param $pars
	 * @return $this
	 */
	public function __call($name, $pars){
		if(method_exists($this,$name.'Getir'))
			call_user_func_array([$this,$name.'Getir'],$pars);
		return $this;
	}

	/**
	 * @param $url
	 * @return mixed
	 */
	protected function api($url){
		switch ($this->method){
			case 'curl':
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				$sonuc = curl_exec($ch);
				curl_close($ch);
				break;
			case 'file':
				$sonuc = file_get_contents($url);
				break;
			default:
				$sonuc = json_encode(['error' => 'true']);
		}
		return json_decode($sonuc);
	}

	/**
	 * @return $this
	 */
	public function iller(){
		$liste = $this->api($this->servis['il']);
		$sonuc = [];
		foreach ($liste as $il) {
			$sonuc[$il->ilPlaka] = $il;
		}
		ksort($sonuc);
		$this->iller = $sonuc;
		return $this;
	}

	/**
	 * @param string $il
	 * @return mixed
	 */
	function ilGetir($il=''){
		if(empty($this->iller))
			$this->iller();
		if(empty($il))
			$il = $this->ilFirst;

		foreach($this->iller as $val)
			if($val->il == $il){
				$this->il = $val;
			}
		return $this;
	}

	/**
	 * @param string $il
	 * @return $this
	 */
	function ilceler($il=''){
		if(empty($this->iller))
			$this->iller();
		if(empty($il)){
			if(empty($this->il))
				$this->ilGetir();
			$il = $this->il;
		}
		$this->ilceler = $this->api($this->servis['ilce'].mb_strtolower($il->il));
		return $this;
	}

	/**
	 * @param string $ilce
	 * @return mixed
	 */
	function ilceGetir($ilce=''){
		if(empty($this->ilceler))
			$this->ilceler();
		if(empty($ilce)){
			if(empty($this->il))
				$this->ilGetir();
			$ilce = $this->il->ilce;
		}
		foreach($this->ilceler as $val)
			if($val->ilce == $ilce){
				$this->ilce = $val;
			}
		return $this;
	}

	/**
	 * @param string $_id
	 * @return string
	 */
	function _id($_id = '', $field){
		$id = '';
		if(!empty($this->il))
			$id = $this->il->{$field};
		if(!empty($this->ilce))
			$id = $this->ilce->{$field};
		if(!empty($_id))
			$id = $_id;
		return $id;
	}

	/**
	 * @param string $id
	 * @return mixed
	 */
	function sonDurum($id=''){
		return $this->api($this->servis['son'].$this->_id($id, 'sondurumIstNo'));
	}

	/**
	 * @param string $id
	 * @return mixed
	 */
	function saatlik($id=''){
		return $this->api($this->servis['saatlik'].$this->_id($id, 'saatlikTahminIstNo'));
	}

	/**
	 * @param string $id
	 * @return mixed
	 */
	function gunluk($id=''){
		print_r($this->_id($id, 'gunlukTahminIstNo'));
		return $this->api($this->servis['gunluk'].$this->_id($id, 'gunlukTahminIstNo'));
	}
}
$durum = new HavaDurumu('İstanbul');
print_r($durum->gunluk());
print_r($durum->ilce('Adalar')->gunluk());
print_r($durum->ilce('Üsküdar')->saatlik());
print_r($durum->ilce('Pendik')->sonDurum());