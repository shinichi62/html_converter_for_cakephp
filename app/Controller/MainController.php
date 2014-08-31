<?php
/**
 * コンバーターの全体を制御するコントローラー
 *
 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');
class MainController extends AppController {

    public $components = array('Session', 'RequestManager', 'Cookie');
    public $uses = array('UrlManager', 'Parse');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Cookie->name = 'cookie name';
    }

    /**
     * 全リクエストが通るメソッド
     *
     * @return void
     */
	public function display() {
        // パラメータからリクエスト URL を取得する
        $cUrl = $this->__getRequestUrl();

        // PC へリクエストを送る
        $s = $this->RequestManager->send($cUrl, array('proxy' => Configure::read('proxy.url')));

        // SSL チェック　
        $url = $this->Session->read('curl_getinfo_url');
        if ((!$this->request->is('ssl') && env('HTTP_PROTOCOL') !== 'HTTPS') && $this->__isSecure($url)) {
            $this->__forceSecure();
        }

        // リクエスト先を判定する
        $info = $this->UrlManager->getInfo($url);

        // HTML を解析する
        $data = $this->Parse->get($s, $info, $this->request->query);

        // URL 毎の処理
        $func = '__'.Inflector::variable($info['name']);
        if (method_exists($this, $func)) {
            $this->$func($data);
        }

        // 結果を表示する
        $this->view = $info['name'];
        $this->set(compact('url', 'data'));
        $this->set('pcUrl', $cUrl); // PC へリクエストを送った URL。$url は PC から戻った URL
    }

    /**
     * パラメータからリクエスト URL を取得する
     *
     * @return string url
     */
	private function __getRequestUrl() {
        // HTTP/SSL の判定
        $curlGetinfoUrl = $this->Session->read('curl_getinfo_url');
        $protocol = 'http://';
        if (strpos($curlGetinfoUrl, 'https://') !== false) {
            $protocol = 'https://';
        }

        // URL セット
        $pass = implode('/', $this->request->params['pass']);
        $url = $protocol.$this->url.$pass;
        $query = '';
        foreach ($this->request->query as $key => $value) {
            if (empty($query)) {
                $query .= '?';
            } else {
                $query .= '&';
            }
            $query .= $key.'='.rawurlencode($value);
        }
        $url .= $query;
        return $url;
    }

    /**
     * SSL の判定
     *
     * @param string $url
     * @return boolean
     */
	private function __isSecure($url) {
        if (preg_match('/^https:\/\//', $url, $matches)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * SSL を強制する
     *
     * @return void
     */
	private function __forceSecure() {
        if (Configure::read('sp.requireSecure')) {
            $this->redirect('https://'.env('SERVER_NAME').$this->here);
        }
    }

}
