<?php
/**
 * HTTP(S) リクエストを管理するクラス
 *
 */
App::uses('Component', 'Controller');
class RequestManagerComponent extends Component {

	public $components = array('Session', 'RequestHandler');

	public $url = '';

    // cURL リソース
	private $ch = null;

	public function initialize(Controller $controller) {
		$this->request = $controller->request;
		$this->response = $controller->response;
		$this->_methods = $controller->methods;
	}

	public function send($url, $options = array()) {
        // クッキーのデータを保持するファイル
        $file = '/tmp/CURLCOOKIE'.$this->Session->id();
        $fp = fopen($file, 'a+');

        $this->ch = curl_init();

        curl_setopt($this->ch, CURLOPT_URL, $url);

        // リロード対応
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);

        // セッション対応
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $file);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $file);
        curl_setopt($this->ch, CURLOPT_WRITEHEADER, $fp);

        if (!empty($this->request->data)) {
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
        }

        // 返り値を文字列で
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

        // プロキシの設定
        if (!empty($options['proxy'])) {
            curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
            curl_setopt($this->ch, CURLOPT_PROXY, $options['proxy']);
        }

        // リクエスト
        $s = curl_exec($this->ch);

        // リクエスト結果を保存
        $info = curl_getinfo($this->ch);
        $this->Session->write('curl_getinfo_url', $info['url']);

       return $s;
    }
}